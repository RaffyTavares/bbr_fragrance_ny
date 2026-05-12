<?php
/**
 * BBR Fragance - Order Controller
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
        $validPayments = ['cash', 'card', 'transfer', 'pending'];
        if (!in_array($paymentMethod, $validPayments)) {
            errorResponse('Metodo de pago no valido. Opciones: cash, card, transfer.', 400);
        }

        // Validar email si viene
        if (!empty($data['customer_email']) && validateEmail($data['customer_email'])) {
            errorResponse('El correo electronico no es valido.', 400);
        }

        // =====================================================================
        // RATE LIMITING: max 3 pedidos por IP o telefono en la ultima hora
        // =====================================================================
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $customerPhone = sanitizeString($data['customer_phone']);
        $rateLimitWindow = 1; // horas
        $rateLimitMax = 3;

        // Por IP
        $stmtRateIp = $db->prepare(
            "SELECT COUNT(*) FROM orders
             WHERE SUBSTRING_INDEX(notes, '[IP:', -1) LIKE :ip_pattern
             AND created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)"
        );
        // Mejor usar un campo dedicado: buscar por IP en notes no es ideal,
        // asi que contamos por telefono que es mas confiable para este caso
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

        // --- Validar cada item y calcular totales ---
        $itemsData = [];
        $subtotal = 0;

        foreach ($data['items'] as $index => $item) {
            if (empty($item['product_id'])) {
                errorResponse("El item #" . ($index + 1) . " no tiene product_id.", 400);
            }
            if (empty($item['quantity']) || (int)$item['quantity'] < 1) {
                errorResponse("El item #" . ($index + 1) . " debe tener una cantidad mayor a 0.", 400);
            }

            $quantity = (int)$item['quantity'];

            // Verificar que el producto existe y esta activo
            $stmtProd = $db->prepare(
                "SELECT p.id, p.name, p.price, p.stock, p.status,
                        b.name AS brand_name
                 FROM products p
                 LEFT JOIN brands b ON p.brand_id = b.id
                 WHERE p.id = :id"
            );
            $stmtProd->execute([':id' => $item['product_id']]);
            $product = $stmtProd->fetch();

            if (!$product) {
                errorResponse("El producto #" . ($index + 1) . " (ID: {$item['product_id']}) no fue encontrado.", 404);
            }

            if ($product['status'] !== 'active') {
                errorResponse("El producto '{$product['name']}' no esta disponible.", 400);
            }

            // Verificar stock disponible (sin descontar aun)
            if ($product['stock'] < $quantity) {
                errorResponse(
                    "Stock insuficiente para '{$product['name']}'. Disponible: {$product['stock']}, solicitado: {$quantity}.",
                    400
                );
            }

            $lineSubtotal = $product['price'] * $quantity;

            $itemsData[] = [
                'product_id'    => (int)$product['id'],
                'product_name'  => $product['name'],
                'product_brand' => $product['brand_name'] ?? '',
                'quantity'      => $quantity,
                'unit_price'    => (float)$product['price'],
                'subtotal'      => $lineSubtotal,
            ];

            $subtotal += $lineSubtotal;
        }

        // --- Validar monto minimo ---
        if ($minOrderAmount > 0 && $subtotal < $minOrderAmount) {
            errorResponse(
                'El monto minimo de pedido es RD$ ' . number_format($minOrderAmount, 2) . '. Tu subtotal es RD$ ' . number_format($subtotal, 2) . '.',
                400
            );
        }

        // --- Calcular totales del pedido ---
        $discountAmount = 0;
        $shippingCost = ($minFreeShipping > 0 && $subtotal >= $minFreeShipping) ? 0 : ($minFreeShipping > 0 ? (float)($settings['shipping_cost'] ?? 0) : 0);

        // Pedidos online: ITBIS ya esta incluido en el precio (se extrae, no se suma)
        if ($taxEnabled && $taxPercent > 0) {
            $taxAmount = round($subtotal - ($subtotal / (1 + $taxPercent / 100)), 2);
        } else {
            $taxAmount = 0;
        }
        $total = round($subtotal - $discountAmount + $shippingCost, 2);

        $customerName = sanitizeString($data['customer_name']);
        // $customerPhone ya fue sanitizado arriba (rate limiting)
        $customerEmail = !empty($data['customer_email']) ? sanitizeString($data['customer_email']) : null;
        $customerAddress = !empty($data['customer_address']) ? sanitizeString($data['customer_address']) : null;
        $notes = !empty($data['notes']) ? sanitizeString($data['notes']) : null;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;

        // =====================================================================
        // TRANSACCION
        // =====================================================================
        $db->beginTransaction();

        try {
            // 1. Insertar pedido
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
                ':order_number'    => $orderNumber,
                ':customer_id'     => $customerId,
                ':customer_name'   => $customerName,
                ':customer_phone'  => $customerPhone,
                ':customer_email'  => $customerEmail,
                ':customer_address'=> $customerAddress,
                ':subtotal'        => $subtotal,
                ':discount_amount' => $discountAmount,
                ':shipping_cost'   => $shippingCost,
                ':tax_amount'      => $taxAmount,
                ':total'           => $total,
                ':payment_method'  => $paymentMethod,
                ':notes'           => $notes,
            ]);

            $orderId = (int)$db->lastInsertId();

            // 2. Insertar items del pedido
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

        // =====================================================================
        // AUTO-CANCELACION: pedidos pendientes con mas de 48 horas
        // =====================================================================
        try {
            $stmtExpired = $db->prepare(
                "UPDATE orders SET status = 'cancelled', updated_at = NOW()
                 WHERE status = 'pending'
                 AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)"
            );
            $stmtExpired->execute();
            $cancelledCount = $stmtExpired->rowCount();
            if ($cancelledCount > 0) {
                error_log("OrderController: auto-cancelled {$cancelledCount} expired pending orders.");
            }
        } catch (Exception $e) {
            error_log('OrderController: auto-cancel failed: ' . $e->getMessage());
        }

        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por estado
        if (!empty($_GET['status'])) {
            $where[] = "o.status = :status";
            $params[':status'] = $_GET['status'];
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
                       o.status, o.created_at, o.updated_at
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
