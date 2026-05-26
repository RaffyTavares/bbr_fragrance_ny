
<?php
/**
 * BBR Fragrance - Order Controller
 * Gestiona pedidos desde la tienda online (storefront)
 */

require_once __DIR__ . '/../services/MailService.php';

class OrderController {

    /**
     * POST /orders
     * Crear un nuevo pedido desde la tienda online
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campos requeridos
        $errors = validateRequired($data, ['customer_name', 'customer_phone', 'items']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar que items sea un arreglo no vacio
        if (!is_array($data['items']) || empty($data['items'])) {
            errorResponse('Debe incluir al menos un producto en el pedido.', 400);
        }

        // Validar metodo de pago si viene
        $paymentMethod = $data['payment_method'] ?? 'pending';
        $validPayments = ['cash', 'card', 'transfer', 'card_online', 'pending'];
        if (!in_array($paymentMethod, $validPayments)) {
            errorResponse('Metodo de pago no valido. Opciones: cash, card, transfer, card_online.', 400);
        }

        // Validar email si viene
        if (!empty($data['customer_email']) && validateEmail($data['customer_email'])) {
            errorResponse('El correo electronico no es valido.', 400);
        }

        // =====================================================================
        // RATE LIMITING: max 3 pedidos por IP o telefono en la ultima hora
        // Se puede desactivar temporalmente con: UPDATE settings SET setting_value='0'
        //   WHERE setting_key='order_rate_limit_enabled';
        // =====================================================================
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $customerPhone = sanitizeString($data['customer_phone']);
        $rateLimitWindow = 1; // horas
        $rateLimitMax = 3;

        // Leer toggle (por defecto activo si no existe el setting)
        $stmtRl = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'order_rate_limit_enabled' LIMIT 1");
        $stmtRl->execute();
        $rateLimitEnabled = $stmtRl->fetchColumn();
        $rateLimitEnabled = ($rateLimitEnabled === false || $rateLimitEnabled === null) ? '1' : (string)$rateLimitEnabled;

        if ($rateLimitEnabled === '1') {
            // Por telefono
            $stmtRatePhone = $db->prepare(
                "SELECT COUNT(*) FROM orders
                 WHERE customer_phone = :phone
                 AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)"
            );
            $stmtRatePhone->bindValue(':phone', $customerPhone);
            $stmtRatePhone->bindValue(':hours', $rateLimitWindow, PDO::PARAM_INT);
            $stmtRatePhone->execute();
            $phoneOrderCount = (int)$stmtRatePhone->fetchColumn();

            if ($phoneOrderCount >= $rateLimitMax) {
                errorResponse(
                    "Has alcanzado el limite de {$rateLimitMax} pedidos por hora con este telefono. Intenta mas tarde.",
                    429
                );
            }

            // Por IP
            $stmtRateIp = $db->prepare(
                "SELECT COUNT(*) FROM orders o
                 INNER JOIN activity_log al ON al.entity_type = 'order' AND al.entity_id = o.id
                    AND al.action = 'create' AND al.ip_address = :ip
                 WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)"
            );
            $stmtRateIp->bindValue(':ip', $clientIp);
            $stmtRateIp->bindValue(':hours', $rateLimitWindow, PDO::PARAM_INT);
            $stmtRateIp->execute();
            $ipOrderCount = (int)$stmtRateIp->fetchColumn();

            if ($ipOrderCount >= $rateLimitMax) {
                errorResponse(
                    "Has alcanzado el limite de {$rateLimitMax} pedidos por hora. Intenta mas tarde.",
                    429
                );
            }
        }

        // =====================================================================
        // MONTO MINIMO
        // =====================================================================
        $stmtMinOrder = $db->prepare(
            "SELECT setting_value FROM settings WHERE setting_key = 'min_order_amount'"
        );
        $stmtMinOrder->execute();
        $minOrderAmount = (float)($stmtMinOrder->fetchColumn() ?: 0);

        // Pre-calcular subtotal rapido para validar monto minimo antes de procesar items
        // (se recalcula despues con precios reales de la BD)

        // --- Generar numero de pedido: PED-YYYYMMDD-NNN ---
        $today = date('Ymd');
        $stmtSeq = $db->prepare(
            "SELECT COUNT(*) FROM orders WHERE order_number LIKE :pattern"
        );
        $stmtSeq->execute([':pattern' => "PED-{$today}-%"]);
        $seq = (int)$stmtSeq->fetchColumn() + 1;
        $orderNumber = sprintf("PED-%s-%03d", $today, $seq);

        // --- Cargar configuracion de impuestos y envio ---
        $stmtSettings = $db->prepare(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('tax_percent', 'tax_enabled', 'min_free_shipping')"
        );
        $stmtSettings->execute();
        $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

        $taxEnabled = isset($settings['tax_enabled']) && $settings['tax_enabled'] == '1';
        $taxPercent = $taxEnabled && isset($settings['tax_percent']) ? (float)$settings['tax_percent'] : 0;
        $minFreeShipping = isset($settings['min_free_shipping']) ? (float)$settings['min_free_shipping'] : 0;

        // --- Validar formato de cada item (solo IDs y cantidades, sin DB) ---
        $itemRequests = [];
        foreach ($data['items'] as $index => $item) {
            if (empty($item['product_id'])) {
                errorResponse("El item #" . ($index + 1) . " no tiene product_id.", 400);
            }
            if (empty($item['quantity']) || (int)$item['quantity'] < 1) {
                errorResponse("El item #" . ($index + 1) . " debe tener una cantidad mayor a 0.", 400);
            }
            $itemRequests[] = [
                'product_id' => (int)$item['product_id'],
                'quantity'   => (int)$item['quantity'],
            ];
        }

        $customerName    = sanitizeString($data['customer_name']);
        $customerEmail   = !empty($data['customer_email'])   ? sanitizeString($data['customer_email'])   : null;
        $customerAddress = !empty($data['customer_address']) ? sanitizeString($data['customer_address']) : null;
        $notes           = !empty($data['notes'])            ? sanitizeString($data['notes'])            : null;
        $customerId      = !empty($data['customer_id'])      ? (int)$data['customer_id']                 : null;

        // =====================================================================
        // TRANSACCION — los productos se leen con FOR UPDATE dentro de la
        // transaccion para prevenir race conditions de stock concurrente.
        // =====================================================================
        $db->beginTransaction();

        try {
            // 1. Bloquear y validar cada producto (FOR UPDATE previene race conditions)
            $itemsData = [];
            $subtotal  = 0;

            foreach ($itemRequests as $index => $req) {
                $stmtProd = $db->prepare(
                    "SELECT p.id, p.name, p.price, p.stock, p.status,
                            b.name AS brand_name
                     FROM products p
                     LEFT JOIN brands b ON p.brand_id = b.id
                     WHERE p.id = :id
                     FOR UPDATE"
                );
                $stmtProd->execute([':id' => $req['product_id']]);
                $product = $stmtProd->fetch();

                if (!$product) {
                    $db->rollBack();
                    errorResponse("El producto #" . ($index + 1) . " (ID: {$req['product_id']}) no fue encontrado.", 404);
                }
                if ($product['status'] !== 'active') {
                    $db->rollBack();
                    errorResponse("El producto '{$product['name']}' no esta disponible.", 400);
                }
                if ($product['stock'] < $req['quantity']) {
                    $db->rollBack();
                    errorResponse(
                        "Stock insuficiente para '{$product['name']}'. Disponible: {$product['stock']}, solicitado: {$req['quantity']}.",
                        400
                    );
                }

                $lineSubtotal = $product['price'] * $req['quantity'];
                $itemsData[]  = [
                    'product_id'    => (int)$product['id'],
                    'product_name'  => $product['name'],
                    'product_brand' => $product['brand_name'] ?? '',
                    'quantity'      => $req['quantity'],
                    'unit_price'    => (float)$product['price'],
                    'subtotal'      => $lineSubtotal,
                ];
                $subtotal += $lineSubtotal;
            }

            // 2. Validar monto minimo (dentro de la transaccion con precios reales)
            if ($minOrderAmount > 0 && $subtotal < $minOrderAmount) {
                $db->rollBack();
                errorResponse(
                    'El monto minimo de pedido es RD$ ' . number_format($minOrderAmount, 2) .
                    '. Tu subtotal es RD$ ' . number_format($subtotal, 2) . '.',
                    400
                );
            }

            // 3. Calcular totales del pedido
            $discountAmount = 0;
            $shippingCost   = ($minFreeShipping > 0 && $subtotal >= $minFreeShipping)
                ? 0
                : ($minFreeShipping > 0 ? (float)($settings['shipping_cost'] ?? 0) : 0);

            // Pedidos online: ITBIS ya esta incluido en el precio (se extrae, no se suma)
            if ($taxEnabled && $taxPercent > 0) {
                $taxAmount = round($subtotal - ($subtotal / (1 + $taxPercent / 100)), 2);
            } else {
                $taxAmount = 0;
            }
            $total = round($subtotal - $discountAmount + $shippingCost, 2);

            // 4. Insertar pedido
            $stmtOrder = $db->prepare(
                "INSERT INTO orders (order_number, customer_id, customer_name, customer_phone,
                                     customer_email, customer_address, subtotal, discount_amount,
                                     shipping_cost, tax_amount, total, payment_method, status,
                                     notes, created_at, updated_at)
                 VALUES (:order_number, :customer_id, :customer_name, :customer_phone,
                         :customer_email, :customer_address, :subtotal, :discount_amount,
                         :shipping_cost, :tax_amount, :total, :payment_method, 'pending',
                         :notes, NOW(), NOW())"
            );
            $stmtOrder->execute([
                ':order_number'     => $orderNumber,
                ':customer_id'      => $customerId,
                ':customer_name'    => $customerName,
                ':customer_phone'   => $customerPhone,
                ':customer_email'   => $customerEmail,
                ':customer_address' => $customerAddress,
                ':subtotal'         => $subtotal,
                ':discount_amount'  => $discountAmount,
                ':shipping_cost'    => $shippingCost,
                ':tax_amount'       => $taxAmount,
                ':total'            => $total,
                ':payment_method'   => $paymentMethod,
                ':notes'            => $notes,
            ]);

            $orderId = (int)$db->lastInsertId();

            // 5. Insertar items del pedido
            $stmtItem = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, product_brand,
                                          quantity, unit_price, subtotal)
                 VALUES (:order_id, :product_id, :product_name, :product_brand,
                         :quantity, :unit_price, :subtotal)"
            );
            foreach ($itemsData as $orderItem) {
                $stmtItem->execute([
                    ':order_id'      => $orderId,
                    ':product_id'    => $orderItem['product_id'],
                    ':product_name'  => $orderItem['product_name'],
                    ':product_brand' => $orderItem['product_brand'],
                    ':quantity'      => $orderItem['quantity'],
                    ':unit_price'    => $orderItem['unit_price'],
                    ':subtotal'      => $orderItem['subtotal'],
                ]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al procesar el pedido: ' . $e->getMessage(), 500);
        }

        // --- Registrar actividad ---
        logActivity('create', 'order', $orderId, "Pedido creado: {$orderNumber} por RD$ " . number_format($total, 2));

        // --- Enviar email de confirmacion ---
        try {
            $emailOrder = [
                'order_number'   => $orderNumber,
                'customer_name'  => $customerName,
                'customer_email' => $customerEmail,
                'payment_method' => $paymentMethod,
                'items'          => $itemsData,
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'shipping_cost'  => $shippingCost,
                'total'          => $total,
            ];
            MailService::sendOrderConfirmation($emailOrder);
        } catch (Exception $e) {
            error_log('OrderController: email failed: ' . $e->getMessage());
        }

        // --- Retornar pedido completo ---
        self::_getOrderById($db, $orderId, 'Pedido registrado exitosamente.', 201);
    }

    /**
     * GET /orders
     * Listar pedidos con filtros y paginacion
     */
    public static function index() {
        $db = getDB();

        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por estado
        if (!empty($_GET['status'])) {
            $where[] = "o.status = :status";
            $params[':status'] = $_GET['status'];
        }

        // Filtro por estado de pago
        if (!empty($_GET['payment_status'])) {
            $where[] = "o.payment_status = :payment_status";
            $params[':payment_status'] = $_GET['payment_status'];
        }

        // Filtro por fecha desde
        if (!empty($_GET['date_from'])) {
            $where[] = "o.created_at >= :date_from";
            $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
        }

        // Filtro por fecha hasta
        if (!empty($_GET['date_to'])) {
            $where[] = "o.created_at <= :date_to";
            $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
        }

        // Busqueda por order_number o customer_name
        if (!empty($_GET['search'])) {
            $where[] = "(o.order_number LIKE :search1 OR o.customer_name LIKE :search2)";
            $params[':search1'] = '%' . $_GET['search'] . '%';
            $params[':search2'] = '%' . $_GET['search'] . '%';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM orders o {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener pedidos
        $sql = "SELECT o.id, o.order_number, o.customer_id, o.customer_name,
                       o.customer_phone, o.customer_email,
                       o.subtotal, o.discount_amount, o.shipping_cost,
                       o.tax_amount, o.total, o.payment_method,
                       o.status, o.payment_status, o.payment_gateway,
                       o.payment_transaction_id, o.payment_authorization,
                       o.payment_paid_at, o.created_at, o.updated_at
                FROM orders o
                {$whereSQL}
                ORDER BY o.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        paginatedResponse($orders, $total, $page, $limit);
    }

    /**
     * GET /orders/{id}
     * Detalle completo de un pedido con sus items
     */
    public static function show($id) {
        $db = getDB();
        self::_getOrderById($db, $id);
    }

    /**
     * PUT /orders/{id}/status
     * Actualizar el estado de un pedido
     */
    public static function updateStatus($id) {
        $db = getDB();
        $data = getJsonInput();

        // Validar campo status
        $errors = validateRequired($data, ['status']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar que sea un estado valido
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        $statusError = validateEnum($data['status'], $validStatuses, 'status');
        if ($statusError) {
            errorResponse($statusError, 400);
        }

        $newStatus = $data['status'];

        // Obtener pedido actual
        $stmtOrder = $db->prepare(
            "SELECT id, order_number, status FROM orders WHERE id = :id"
        );
        $stmtOrder->execute([':id' => $id]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }

        $oldStatus = $order['status'];

        if ($oldStatus === $newStatus) {
            errorResponse("El pedido ya tiene el estado '{$newStatus}'.", 400);
        }

        // Determinar si el stock ya fue descontado con el estado anterior
        $stockDeductedStatuses = ['confirmed', 'processing', 'shipped'];
        $wasStockDeducted = in_array($oldStatus, $stockDeductedStatuses);
        $needsStockDeduction = in_array($newStatus, ['confirmed', 'processing']) && !$wasStockDeducted;
        $needsStockRestore = ($newStatus === 'cancelled') && $wasStockDeducted;

        // Obtener items del pedido
        $stmtItems = $db->prepare(
            "SELECT oi.product_id, oi.quantity, p.stock, p.name AS product_name
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :order_id"
        );
        $stmtItems->execute([':order_id' => $id]);
        $items = $stmtItems->fetchAll();

        // Si hay que descontar stock, verificar disponibilidad primero
        if ($needsStockDeduction) {
            foreach ($items as $item) {
                if ($item['stock'] < $item['quantity']) {
                    errorResponse(
                        "Stock insuficiente para '{$item['product_name']}'. Disponible: {$item['stock']}, requerido: {$item['quantity']}.",
                        400
                    );
                }
            }
        }

        // =====================================================================
        // TRANSACCION
        // =====================================================================
        $db->beginTransaction();

        try {
            // 1. Actualizar estado del pedido
            $stmtUpdate = $db->prepare(
                "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id"
            );
            $stmtUpdate->execute([
                ':status' => $newStatus,
                ':id'     => $id,
            ]);

            // 2. Descontar stock si se confirma/procesa por primera vez
            if ($needsStockDeduction) {
                $stmtDeduct = $db->prepare(
                    "UPDATE products SET stock = stock - :qty WHERE id = :id"
                );
                foreach ($items as $item) {
                    $stmtDeduct->execute([
                        ':qty' => $item['quantity'],
                        ':id'  => $item['product_id'],
                    ]);
                }
            }

            // 3. Restaurar stock si se cancela un pedido que ya tenia stock descontado
            if ($needsStockRestore) {
                $stmtRestore = $db->prepare(
                    "UPDATE products SET stock = stock + :qty WHERE id = :id"
                );
                foreach ($items as $item) {
                    $stmtRestore->execute([
                        ':qty' => $item['quantity'],
                        ':id'  => $item['product_id'],
                    ]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al actualizar el estado del pedido: ' . $e->getMessage(), 500);
        }

        // --- Registrar actividad ---
        logActivity(
            'update_status',
            'order',
            $id,
            "Pedido {$order['order_number']} cambiado de '{$oldStatus}' a '{$newStatus}'"
        );

        // --- Enviar email de actualizacion de estado ---
        try {
            $stmtFull = $db->prepare(
                "SELECT id, order_number, customer_name, customer_email, total FROM orders WHERE id = :id"
            );
            $stmtFull->execute([':id' => $id]);
            $emailOrder = $stmtFull->fetch();
            if ($emailOrder) {
                MailService::sendOrderStatusUpdate($emailOrder, $newStatus);
            }
        } catch (Exception $e) {
            error_log('OrderController: status email failed: ' . $e->getMessage());
        }

        self::_getOrderById($db, $id, 'Estado del pedido actualizado exitosamente.');
    }

    /**
     * DELETE /orders/{id}
     * Eliminar un pedido (solo si esta pendiente o cancelado)
     */
    public static function destroy($id) {
        $db = getDB();

        // Verificar que el pedido existe
        $stmtCheck = $db->prepare(
            "SELECT id, order_number, status FROM orders WHERE id = :id"
        );
        $stmtCheck->execute([':id' => $id]);
        $order = $stmtCheck->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }

        // Solo se puede eliminar si esta pendiente o cancelado
        $deletableStatuses = ['pending', 'cancelled'];
        if (!in_array($order['status'], $deletableStatuses)) {
            errorResponse(
                "Solo se pueden eliminar pedidos con estado 'pending' o 'cancelled'. Estado actual: '{$order['status']}'.",
                400
            );
        }

        // Eliminar items del pedido
        $db->prepare("DELETE FROM order_items WHERE order_id = :order_id")
           ->execute([':order_id' => $id]);

        // Eliminar pedido
        $db->prepare("DELETE FROM orders WHERE id = :id")
           ->execute([':id' => $id]);

        logActivity('delete', 'order', $id, "Pedido eliminado: {$order['order_number']}");

        successResponse(null, 'Pedido eliminado exitosamente.');
    }

    // =========================================================================
    // POST /orders/{id}/mark-paid
    // Marcar un pedido de efectivo / transferencia como pagado manualmente
    // =========================================================================
    public static function markPaid($id) {
        $db   = getDB();
        $data = getJsonInput();

        $stmt = $db->prepare(
            "SELECT id, order_number, status, payment_method, payment_status,
                    customer_id, subtotal, discount_amount, shipping_cost,
                    tax_amount, total, notes
             FROM orders WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }

        $allowedMethods = ['cash', 'card', 'transfer', 'pending'];
        if (!in_array($order['payment_method'], $allowedMethods)) {
            errorResponse('Solo se pueden marcar manualmente pedidos de efectivo, tarjeta (POS) o transferencia.', 400);
        }

        if (($order['payment_status'] ?? '') === 'paid') {
            errorResponse('Este pedido ya esta marcado como pagado.', 400);
        }

        $notes = !empty($data['notes']) ? sanitizeString($data['notes']) : null;

        // Determinar si necesitamos descontar stock (pending → confirmed)
        $needsStockDeduction = ($order['status'] === 'pending');

        $db->beginTransaction();
        try {
            $newStatus = $needsStockDeduction ? 'confirmed' : $order['status'];

            $stmtUpd = $db->prepare(
                "UPDATE orders SET
                    payment_status  = 'paid',
                    payment_paid_at = NOW(),
                    status          = :status,
                    updated_at      = NOW()
                 WHERE id = :id"
            );
            $stmtUpd->execute([':status' => $newStatus, ':id' => $id]);

            if ($notes) {
                $db->prepare("UPDATE orders SET notes = CONCAT(COALESCE(notes,''), :note) WHERE id = :id")
                   ->execute([':note' => "\n[Pago confirmado] {$notes}", ':id' => $id]);
            }

            // Descontar stock al confirmar (el pedido web no lo desconto al crearse)
            if ($needsStockDeduction) {
                $stmtItems = $db->prepare(
                    "SELECT product_id, quantity FROM order_items WHERE order_id = :oid"
                );
                $stmtItems->execute([':oid' => $id]);
                $orderItems = $stmtItems->fetchAll();

                $stmtDeduct = $db->prepare(
                    "UPDATE products SET stock = stock - :qty WHERE id = :pid"
                );
                foreach ($orderItems as $oi) {
                    $stmtDeduct->execute([':qty' => $oi['quantity'], ':pid' => $oi['product_id']]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al marcar como pagado: ' . $e->getMessage(), 500);
        }

        logActivity('payment_confirmed', 'order', $id,
            "Pago manual confirmado para pedido {$order['order_number']}");

        // Registrar en el sistema de ventas (fuera de la transaccion anterior)
        $order['status']         = ($needsStockDeduction ? 'confirmed' : $order['status']);
        $order['payment_status'] = 'paid';
        self::createSaleFromOrder($db, $order);

        self::_getOrderById($db, $id, 'Pedido marcado como pagado.');
    }

    // =========================================================================
    // GET /orders/reconciliation
    // Panel de conciliacion de pagos en linea
    // =========================================================================
    public static function reconciliation() {
        $db = getDB();

        // Resumen por payment_status para pedidos con pago en linea
        $stmtSummary = $db->query(
            "SELECT
                COALESCE(payment_status, 'pending') AS pst,
                COUNT(*) AS total_orders,
                COALESCE(SUM(total), 0) AS total_amount
             FROM orders
             WHERE payment_method = 'card_online'
                OR payment_gateway IS NOT NULL
             GROUP BY pst"
        );
        $rows = $stmtSummary->fetchAll();
        $summary = ['pending' => ['count' => 0, 'amount' => 0],
                    'paid'    => ['count' => 0, 'amount' => 0],
                    'failed'  => ['count' => 0, 'amount' => 0],
                    'refunded'=> ['count' => 0, 'amount' => 0]];
        foreach ($rows as $row) {
            $k = $row['pst'];
            if (isset($summary[$k])) {
                $summary[$k]['count']  = (int)$row['total_orders'];
                $summary[$k]['amount'] = (float)$row['total_amount'];
            }
        }

        // Pedidos pagados pero aun en estado 'pending' de pedido (requieren confirmacion manual)
        $stmtPaid = $db->query(
            "SELECT id, order_number, customer_name, customer_phone,
                    total, payment_gateway, payment_transaction_id,
                    payment_authorization, payment_paid_at, status, payment_status,
                    created_at
             FROM orders
             WHERE payment_status = 'paid' AND status = 'pending'
             ORDER BY payment_paid_at DESC
             LIMIT 50"
        );
        $paidUnconfirmed = $stmtPaid->fetchAll();

        // Pedidos con pago fallido (recientes, ultimas 72h)
        $stmtFailed = $db->query(
            "SELECT id, order_number, customer_name, customer_phone,
                    total, payment_gateway, payment_response_code,
                    status, payment_status, created_at, updated_at
             FROM orders
             WHERE payment_status = 'failed'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 72 HOUR)
             ORDER BY updated_at DESC
             LIMIT 50"
        );
        $recentFailed = $stmtFailed->fetchAll();

        successResponse([
            'summary'          => $summary,
            'paid_unconfirmed' => $paidUnconfirmed,
            'recent_failed'    => $recentFailed,
        ]);
    }

    // =========================================================================
    // GET /orders/{id}/invoice
    // Genera HTML de factura/recibo imprimible para un pedido
    // =========================================================================
    public static function invoice($id) {
        $db = getDB();

        $stmtOrder = $db->prepare("SELECT o.* FROM orders o WHERE o.id = :id");
        $stmtOrder->execute([':id' => $id]);
        $order = $stmtOrder->fetch();
        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }

        $stmtItems = $db->prepare(
            "SELECT product_name, product_brand, quantity, unit_price, subtotal
             FROM order_items WHERE order_id = :id ORDER BY id ASC"
        );
        $stmtItems->execute([':id' => $id]);
        $items = $stmtItems->fetchAll();

        // Cargar nombre del negocio desde settings
        $stmtSettings = $db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('business_name','business_phone','business_email','business_address')"
        );
        $stmtSettings->execute();
        $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

        $businessName    = htmlspecialchars($settings['business_name']    ?? 'BBR Fragrance', ENT_QUOTES, 'UTF-8');
        $businessPhone   = htmlspecialchars($settings['business_phone']   ?? '', ENT_QUOTES, 'UTF-8');
        $businessEmail   = htmlspecialchars($settings['business_email']   ?? '', ENT_QUOTES, 'UTF-8');
        $businessAddress = htmlspecialchars($settings['business_address'] ?? '601 West 162 Street, New York, NY 10032', ENT_QUOTES, 'UTF-8');

        $orderNumber   = htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8');
        $customerName  = htmlspecialchars($order['customer_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $customerPhone = htmlspecialchars($order['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8');
        $customerEmail = htmlspecialchars($order['customer_email'] ?? '', ENT_QUOTES, 'UTF-8');
        $customerAddr  = htmlspecialchars($order['customer_address'] ?? '', ENT_QUOTES, 'UTF-8');

        $paymentLabels = ['cash' => 'Efectivo', 'card' => 'Tarjeta (POS)',
                          'transfer' => 'Transferencia', 'card_online' => 'Tarjeta en Linea',
                          'pending' => 'Pendiente'];
        $paymentLabel  = $paymentLabels[$order['payment_method'] ?? 'pending'] ?? $order['payment_method'];
        $payStatusLabels = ['pending' => 'Pendiente', 'paid' => 'Pagado', 'failed' => 'Fallido', 'refunded' => 'Reembolsado'];
        $payStatusLabel = $payStatusLabels[$order['payment_status'] ?? 'pending'] ?? ($order['payment_status'] ?? 'Pendiente');

        $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado',
                         'processing' => 'En Proceso', 'shipped' => 'Enviado',
                         'delivered' => 'Entregado', 'cancelled' => 'Cancelado'];
        $statusLabel = $statusLabels[$order['status'] ?? 'pending'] ?? $order['status'];

        $createdAt = date('d/m/Y H:i', strtotime($order['created_at']));
        $paidAt    = !empty($order['payment_paid_at']) ? date('d/m/Y H:i', strtotime($order['payment_paid_at'])) : '-';

        $itemsHtml = '';
        foreach ($items as $item) {
            $name     = htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8');
            $brand    = htmlspecialchars($item['product_brand'] ?? '', ENT_QUOTES, 'UTF-8');
            $qty      = (int)$item['quantity'];
            $price    = number_format((float)$item['unit_price'], 2);
            $subtotal = number_format((float)$item['subtotal'], 2);
            $itemsHtml .= "<tr>
                <td class=\"product-name\">{$name}" . ($brand ? " <small>({$brand})</small>" : '') . "</td>
                <td class=\"center\">{$qty}</td>
                <td class=\"right\">RD\$ {$price}</td>
                <td class=\"right\">RD\$ {$subtotal}</td>
            </tr>";
        }

        $subtotalFmt  = number_format((float)$order['subtotal'], 2);
        $shippingFmt  = number_format((float)$order['shipping_cost'], 2);
        $taxFmt       = number_format((float)$order['tax_amount'], 2);
        $discountFmt  = number_format((float)$order['discount_amount'], 2);
        $totalFmt     = number_format((float)$order['total'], 2);

        $authCode = !empty($order['payment_authorization'])  ? htmlspecialchars($order['payment_authorization'],  ENT_QUOTES, 'UTF-8') : '';
        $txId     = !empty($order['payment_transaction_id']) ? htmlspecialchars($order['payment_transaction_id'], ENT_QUOTES, 'UTF-8') : '';
        $payStatus = $order['payment_status'] ?? 'pending';

        // Pre-construir partes opcionales del HTML (no se pueden usar ternarios dentro de heredoc)
        $phoneHtml    = $businessPhone ? "<div class=\"brand-sub\">Tel: {$businessPhone}</div>" : '';
        $emailHtml    = $businessEmail ? "<div class=\"brand-sub\">{$businessEmail}</div>" : '';
        $cEmailHtml   = $customerEmail ? "<p><span class=\"label\">Email:</span> {$customerEmail}</p>" : '';
        $cAddrHtml    = $customerAddr  ? "<p><span class=\"label\">Direccion:</span> {$customerAddr}</p>" : '';
        $discountHtml = ((float)$order['discount_amount'] > 0)
            ? "<tr><td>Descuento:</td><td>- RD\$ {$discountFmt}</td></tr>"
            : '';
        $paymentInfoHtml = '';
        if ($authCode || $txId) {
            $txHtml   = $txId     ? "<div><span class=\"label\">ID Transaccion</span><span class=\"value\">{$txId}</span></div>" : '';
            $authHtml = $authCode ? "<div><span class=\"label\">Cod. Autorizacion</span><span class=\"value\">{$authCode}</span></div>" : '';
            $paidHtml = ($paidAt !== '-') ? "<div><span class=\"label\">Fecha de Pago</span><span class=\"value\">{$paidAt}</span></div>" : '';
            $paymentInfoHtml = "<div class=\"payment-section\">
    <div class=\"section-title\">Datos del Pago en Linea</div>
    <div class=\"payment-grid\">{$txHtml}{$authHtml}{$paidHtml}</div>
  </div>";
        }
        $generatedAt = date('d/m/Y H:i');

        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura {$orderNumber}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; padding: 20px; }
  .invoice-wrapper { max-width: 760px; margin: 0 auto; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #C9A96E; padding-bottom: 16px; margin-bottom: 20px; }
  .brand { font-size: 22px; font-weight: bold; color: #5C4A2A; }
  .brand-sub { font-size: 11px; color: #666; margin-top: 2px; }
  .invoice-meta { text-align: right; }
  .invoice-meta h2 { font-size: 18px; color: #5C4A2A; margin-bottom: 4px; }
  .invoice-meta p { font-size: 11px; color: #555; }
  .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #888; letter-spacing: .5px; margin-bottom: 6px; border-bottom: 1px solid #e5e5e5; padding-bottom: 3px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
  .info-block p { margin: 2px 0; font-size: 11.5px; }
  .info-block .label { color: #888; font-size: 10.5px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11.5px; }
  thead tr { background: #5C4A2A; color: #fff; }
  thead th { padding: 7px 10px; text-align: left; font-size: 11px; }
  thead th.right { text-align: right; }
  thead th.center { text-align: center; }
  tbody tr:nth-child(even) { background: #fafaf8; }
  tbody td { padding: 6px 10px; border-bottom: 1px solid #eee; }
  td.right { text-align: right; }
  td.center { text-align: center; }
  td.product-name small { color: #888; font-size: 10px; }
  .totals { width: 280px; margin-left: auto; font-size: 12px; }
  .totals tr td { padding: 4px 10px; }
  .totals tr td:last-child { text-align: right; font-weight: 500; }
  .totals .total-row td { font-size: 14px; font-weight: bold; color: #5C4A2A; border-top: 2px solid #C9A96E; padding-top: 8px; }
  .payment-section { background: #fafaf8; border: 1px solid #e5e5e5; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; }
  .payment-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 11px; }
  .payment-grid .label { color: #888; font-size: 10.5px; display: block; }
  .payment-grid .value { font-weight: 600; font-size: 11.5px; }
  .footer { text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 12px; margin-top: 20px; }
  .status-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; background: #f0f0f0; }
  .status-paid { background: #dcfce7; color: #166534; }
  .status-pending { background: #fef9c3; color: #854d0e; }
  .status-failed { background: #fee2e2; color: #991b1b; }
  .status-refunded { background: #ffedd5; color: #9a3412; }
  .no-print { margin-top: 24px; text-align: center; }
  .btn-print { background: #5C4A2A; color: white; border: none; padding: 10px 28px; border-radius: 6px; font-size: 13px; cursor: pointer; }
  @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
<div class="invoice-wrapper">
  <div class="header">
    <div>
      <div class="brand">{$businessName}</div>
      <div class="brand-sub">{$businessAddress}</div>
      {$phoneHtml}
      {$emailHtml}
    </div>
    <div class="invoice-meta">
      <h2>FACTURA / RECIBO</h2>
      <p><strong>{$orderNumber}</strong></p>
      <p>Fecha: {$createdAt}</p>
      <p>Estado: <span class="status-badge">{$statusLabel}</span></p>
    </div>
  </div>

  <div class="info-grid">
    <div class="info-block">
      <div class="section-title">Datos del Cliente</div>
      <p><span class="label">Nombre:</span> {$customerName}</p>
      <p><span class="label">Telefono:</span> {$customerPhone}</p>
      {$cEmailHtml}
      {$cAddrHtml}
    </div>
    <div class="info-block">
      <div class="section-title">Informacion del Pedido</div>
      <p><span class="label">No. Pedido:</span> {$orderNumber}</p>
      <p><span class="label">Fecha:</span> {$createdAt}</p>
      <p><span class="label">Metodo de pago:</span> {$paymentLabel}</p>
      <p><span class="label">Estado de pago:</span> <span class="status-badge status-{$payStatus}">{$payStatusLabel}</span></p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th class="center">Cant.</th>
        <th class="right">Precio Unit.</th>
        <th class="right">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      {$itemsHtml}
    </tbody>
  </table>

  <table class="totals">
    <tr><td>Subtotal:</td><td>RD$ {$subtotalFmt}</td></tr>
    {$discountHtml}
    <tr><td>Envio:</td><td>RD$ {$shippingFmt}</td></tr>
    <tr><td>ITBIS (incluido):</td><td>RD$ {$taxFmt}</td></tr>
    <tr class="total-row"><td>TOTAL:</td><td>RD$ {$totalFmt}</td></tr>
  </table>

  {$paymentInfoHtml}

  <div class="footer">
    Gracias por su compra &mdash; {$businessName} &mdash; Documento generado el {$generatedAt}
  </div>

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
  </div>
</div>
</body>
</html>
HTML;
        exit;
    }

    // =========================================================================
    // Registrar venta web en el sistema de ventas (tabla sales)
    // Debe llamarse FUERA de una transaccion activa.
    // Es idempotente: si ya existe una venta para este order_id no hace nada.
    // =========================================================================
    public static function createSaleFromOrder($db, $order) {
        // Idempotencia: no crear duplicados
        $stmtCheck = $db->prepare("SELECT id FROM sales WHERE order_id = :oid LIMIT 1");
        $stmtCheck->execute([':oid' => $order['id']]);
        if ($stmtCheck->fetchColumn()) {
            return;
        }

        // Items del pedido con costo de producto (para margen en reportes)
        $stmtItems = $db->prepare(
            "SELECT oi.product_id, oi.product_name, oi.product_brand,
                    oi.quantity, oi.unit_price, oi.subtotal,
                    p.cost AS unit_cost
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :oid
             ORDER BY oi.id ASC"
        );
        $stmtItems->execute([':oid' => $order['id']]);
        $items = $stmtItems->fetchAll();

        if (empty($items)) {
            return;
        }

        // Leer tax_percent desde settings (solo para informacion; el importe ya esta calculado)
        $stmtTax = $db->prepare(
            "SELECT setting_value FROM settings WHERE setting_key = 'tax_percent' LIMIT 1"
        );
        $stmtTax->execute();
        $taxPercent = (float)($stmtTax->fetchColumn() ?: 0);

        // Metodo de pago: mapear al ENUM de sales (incluye card_online y pending)
        $pm = $order['payment_method'] ?? 'pending';
        $validPm = ['cash', 'card', 'transfer', 'mixed', 'card_online', 'pending'];
        if (!in_array($pm, $validPm)) {
            $pm = 'pending';
        }

        // Pagos en efectivo cobrados por el mensajero entran a la caja del dia.
        // Transferencias y tarjeta online NO tocan la caja fisica.
        $registerSessionId = null;
        if ($pm === 'cash') {
            $stmtReg = $db->prepare(
                "SELECT id FROM cash_register_sessions
                 WHERE status = 'open'
                 ORDER BY opened_at DESC
                 LIMIT 1"
            );
            $stmtReg->execute();
            $registerSessionId = $stmtReg->fetchColumn() ?: null;
        }

        $total   = (float)$order['total'];
        $today   = date('Ymd');
        $pattern = "VTA-{$today}-%";

        $db->beginTransaction();
        try {
            // Numero de venta con bloqueo para evitar colisiones de secuencia
            $stmtSeq = $db->prepare(
                "SELECT sale_number FROM sales
                 WHERE sale_number LIKE :p
                 ORDER BY sale_number DESC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtSeq->execute([':p' => $pattern]);
            $last = $stmtSeq->fetchColumn();
            $seq  = $last ? ((int)substr($last, strrpos($last, '-') + 1) + 1) : 1;
            $saleNumber = sprintf("VTA-%s-%03d", $today, $seq);

            // Insertar venta web
            $stmtSale = $db->prepare(
                "INSERT INTO sales
                    (source, order_id, sale_number, customer_id,
                     user_id, register_session_id,
                     subtotal, discount_amount, discount_percent,
                     tax_percent, tax_amount, total,
                     payment_method, status, notes, created_at)
                 VALUES
                    ('web', :order_id, :sale_number, :customer_id,
                     NULL, :reg_session,
                     :subtotal, :discount, 0,
                     :tax_pct, :tax_amt, :total,
                     :pm, 'completed', :notes, NOW())"
            );
            $stmtSale->execute([
                ':order_id'   => (int)$order['id'],
                ':sale_number'=> $saleNumber,
                ':customer_id'=> !empty($order['customer_id']) ? (int)$order['customer_id'] : null,
                ':reg_session'=> $registerSessionId,
                ':subtotal'   => (float)$order['subtotal'],
                ':discount'   => (float)($order['discount_amount'] ?? 0),
                ':tax_pct'    => $taxPercent,
                ':tax_amt'    => (float)($order['tax_amount'] ?? 0),
                ':total'      => $total,
                ':pm'         => $pm,
                ':notes'      => !empty($order['notes']) ? $order['notes'] : null,
            ]);
            $saleId = (int)$db->lastInsertId();

            // Insertar items de la venta
            $stmtItem = $db->prepare(
                "INSERT INTO sale_items
                    (sale_id, product_id, product_name, product_brand,
                     quantity, unit_price, unit_cost, discount, subtotal)
                 VALUES
                    (:sale_id, :product_id, :product_name, :product_brand,
                     :qty, :price, :cost, 0, :sub)"
            );
            foreach ($items as $item) {
                $stmtItem->execute([
                    ':sale_id'       => $saleId,
                    ':product_id'    => $item['product_id'],
                    ':product_name'  => $item['product_name'],
                    ':product_brand' => $item['product_brand'],
                    ':qty'           => $item['quantity'],
                    ':price'         => (float)$item['unit_price'],
                    ':cost'          => $item['unit_cost'] !== null ? (float)$item['unit_cost'] : null,
                    ':sub'           => (float)$item['subtotal'],
                ]);
            }

            // Acreditar el efectivo en la caja del dia (solo pagos cash)
            if ($registerSessionId) {
                $db->prepare(
                    "UPDATE cash_register_sessions
                     SET total_sales_count = total_sales_count + 1,
                         total_cash_sales  = total_cash_sales + :total
                     WHERE id = :reg_id"
                )->execute([':total' => $total, ':reg_id' => $registerSessionId]);
            }

            $db->commit();

            logActivity('create', 'sale', $saleId,
                "Venta web {$saleNumber} registrada desde pedido {$order['order_number']}");

        } catch (Exception $e) {
            $db->rollBack();
            // No propagar — la confirmacion del pedido ya se completo exitosamente
            error_log("createSaleFromOrder failed for order {$order['id']}: " . $e->getMessage());
        }
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener un pedido completo por ID y devolver como successResponse
     */
    private static function _getOrderById($db, $id, $message = null, $code = 200) {
        // Obtener pedido
        $stmtOrder = $db->prepare(
            "SELECT o.*
             FROM orders o
             WHERE o.id = :id"
        );
        $stmtOrder->execute([':id' => $id]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }

        // Obtener items del pedido
        $stmtItems = $db->prepare(
            "SELECT id, product_id, product_name, product_brand,
                    quantity, unit_price, subtotal
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC"
        );
        $stmtItems->execute([':order_id' => $id]);
        $order['items'] = $stmtItems->fetchAll();

        successResponse($order, $message, $code);
    }
}
