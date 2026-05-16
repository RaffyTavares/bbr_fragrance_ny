<?php
/**
 * BBR Fragrance - Sale Controller (POS)
 * Gestiona ventas, recibos y operaciones del punto de venta
 */

class SaleController {

    /**
     * POST /sales
     * Crear una nueva venta desde el punto de venta
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campos requeridos
        $errors = validateRequired($data, ['items', 'payment_method']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar que items sea un arreglo no vacio
        if (!is_array($data['items']) || empty($data['items'])) {
            errorResponse('Debe incluir al menos un producto en la venta.', 400);
        }

        // Validar metodo de pago
        $validPayments = ['cash', 'card', 'transfer', 'mixed'];
        if (!in_array($data['payment_method'], $validPayments)) {
            errorResponse('Metodo de pago no valido. Opciones: cash, card, transfer, mixed.', 400);
        }

        // Validar discount_percent si viene
        $discountPercent = 0;
        if (isset($data['discount_percent']) && $data['discount_percent'] !== '' && $data['discount_percent'] !== null) {
            $dpError = validateNumeric($data['discount_percent'], 'discount_percent', 0, 100);
            if ($dpError) {
                errorResponse($dpError, 400);
            }
            $discountPercent = (float)$data['discount_percent'];
        }

        // --- Generar numero de venta se hara dentro de la transaccion ---
        $today = date('Ymd');
        $saleNumberPattern = "VTA-{$today}-";

        // --- Cargar configuracion de impuestos ---
        $stmtTax = $db->prepare(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('tax_percent', 'tax_enabled')"
        );
        $stmtTax->execute();
        $taxSettings = $stmtTax->fetchAll(PDO::FETCH_KEY_PAIR);

        $taxEnabled = isset($taxSettings['tax_enabled']) && $taxSettings['tax_enabled'] == '1';
        $taxPercent = $taxEnabled && isset($taxSettings['tax_percent']) ? (float)$taxSettings['tax_percent'] : 0;

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
            $itemDiscount = isset($item['discount']) ? (float)$item['discount'] : 0;

            if ($itemDiscount < 0) {
                errorResponse("El descuento del item #" . ($index + 1) . " no puede ser negativo.", 400);
            }

            // Verificar que el producto existe y tiene stock
            $stmtProd = $db->prepare(
                "SELECT p.id, p.name, p.price, p.cost, p.stock, p.status,
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
                errorResponse("El producto '{$product['name']}' no esta disponible para la venta.", 400);
            }

            if ($product['stock'] < $quantity) {
                errorResponse(
                    "Stock insuficiente para '{$product['name']}'. Disponible: {$product['stock']}, solicitado: {$quantity}.",
                    400
                );
            }

            $lineSubtotal = ($product['price'] * $quantity) - $itemDiscount;

            $itemsData[] = [
                'product_id'    => (int)$product['id'],
                'product_name'  => $product['name'],
                'product_brand' => $product['brand_name'] ?? '',
                'quantity'      => $quantity,
                'unit_price'    => (float)$product['price'],
                'unit_cost'     => $product['cost'] !== null ? (float)$product['cost'] : null,
                'discount'      => $itemDiscount,
                'subtotal'      => $lineSubtotal,
            ];

            $subtotal += $lineSubtotal;
        }

        // --- Calcular totales de la venta ---
        $discountAmount = ($discountPercent > 0) ? round($subtotal * ($discountPercent / 100), 2) : 0;
        $subtotalAfterDiscount = $subtotal - $discountAmount;

        // ITBIS: con NCF se suma al precio, sin NCF ya esta incluido en el precio
        $isNcfSale = !empty($data['ncf_requested']);
        if ($taxEnabled && $taxPercent > 0) {
            if ($isNcfSale) {
                // Con NCF: ITBIS se agrega encima del precio
                $taxAmount = round($subtotalAfterDiscount * ($taxPercent / 100), 2);
                $total = round($subtotalAfterDiscount + $taxAmount, 2);
            } else {
                // Sin NCF: ITBIS ya esta incluido en el precio, se extrae
                $taxAmount = round($subtotalAfterDiscount - ($subtotalAfterDiscount / (1 + $taxPercent / 100)), 2);
                $total = round($subtotalAfterDiscount, 2);
            }
        } else {
            $taxAmount = 0;
            $total = round($subtotalAfterDiscount, 2);
        }

        // Validar efectivo recibido si el pago es en efectivo
        $cashReceived = null;
        $cashChange = null;
        if ($data['payment_method'] === 'cash' || $data['payment_method'] === 'mixed') {
            if (isset($data['cash_received']) && $data['cash_received'] !== null) {
                $cashReceived = (float)$data['cash_received'];
                if ($data['payment_method'] === 'cash' && $cashReceived < $total) {
                    errorResponse(
                        "El efectivo recibido (RD$ " . number_format($cashReceived, 2) . ") es menor al total (RD$ " . number_format($total, 2) . ").",
                        400
                    );
                }
                $cashChange = round($cashReceived - $total, 2);
                if ($data['payment_method'] === 'mixed') {
                    $cashChange = max(0, $cashChange);
                }
            }
        }

        $cardReference = isset($data['card_reference']) ? sanitizeString($data['card_reference']) : null;
        // Para pagos mixtos, almacenar el segundo metodo en card_reference
        if ($data['payment_method'] === 'mixed' && isset($data['mixed_other_method'])) {
            $cardReference = $data['mixed_other_method'] === 'transfer' ? 'transfer' : 'card';
        }
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
        $registerSessionId = !empty($data['register_session_id']) ? (int)$data['register_session_id'] : null;
        $notes = !empty($data['notes']) ? sanitizeString($data['notes']) : null;
        $userId = getCurrentUserId();

        // --- Validar sesion de caja (OBLIGATORIA) ---
        if (!$registerSessionId) {
            errorResponse('Debe abrir una caja antes de realizar ventas.', 400);
        }
        $stmtReg = $db->prepare(
            "SELECT id, status FROM cash_register_sessions WHERE id = :id"
        );
        $stmtReg->execute([':id' => $registerSessionId]);
        $regSession = $stmtReg->fetch();
        if (!$regSession) {
            errorResponse('La sesion de caja especificada no fue encontrada.', 404);
        }
        if ($regSession['status'] !== 'open') {
            errorResponse('La sesion de caja no esta abierta.', 400);
        }

        // --- Validar cliente si se proporciona ---
        if ($customerId) {
            $stmtCust = $db->prepare("SELECT id FROM customers WHERE id = :id");
            $stmtCust->execute([':id' => $customerId]);
            if (!$stmtCust->fetch()) {
                errorResponse('El cliente especificado no fue encontrado.', 404);
            }
        }

        // --- NCF (Comprobante Fiscal) ---
        $ncfNumber = null;
        $ncfType = null;
        $customerRnc = null;
        $ncfSequenceId = null;
        $ncfExpectedNumber = null;

        if (!empty($data['ncf_requested'])) {
            // Verificar que NCF esta habilitado
            $stmtNcfSetting = $db->prepare(
                "SELECT setting_value FROM settings WHERE setting_key = 'ncf_enabled'"
            );
            $stmtNcfSetting->execute();
            $ncfEnabledVal = $stmtNcfSetting->fetchColumn();
            if ($ncfEnabledVal !== '1') {
                errorResponse('Los comprobantes fiscales no estan habilitados en la configuracion.', 400);
            }

            $ncfType = !empty($data['ncf_type']) ? strtoupper(trim($data['ncf_type'])) : 'B02';
            $validNcfTypes = ['B01', 'B02', 'B14', 'B15'];
            if (!in_array($ncfType, $validNcfTypes)) {
                errorResponse('Tipo de NCF no valido. Opciones: B01, B02, B14, B15.', 400);
            }

            // B01 (Credito Fiscal) requiere cliente con RNC
            if ($ncfType === 'B01') {
                if (!$customerId) {
                    errorResponse('NCF tipo B01 (Credito Fiscal) requiere un cliente seleccionado.', 400);
                }
                $stmtCustRnc = $db->prepare("SELECT rnc FROM customers WHERE id = :id");
                $stmtCustRnc->execute([':id' => $customerId]);
                $customerRnc = $stmtCustRnc->fetchColumn();
                if (empty($customerRnc)) {
                    errorResponse('NCF tipo B01 (Credito Fiscal) requiere que el cliente tenga un RNC registrado.', 400);
                }
            }

            // Buscar secuencia activa y disponible
            $today = date('Y-m-d');
            $stmtSeqNcf = $db->prepare(
                "SELECT id, current_number, end_number, prefix
                 FROM ncf_sequences
                 WHERE ncf_type = :ncf_type
                   AND is_active = 1
                   AND expiration_date >= :today
                   AND current_number < end_number
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $stmtSeqNcf->execute([':ncf_type' => $ncfType, ':today' => $today]);
            $ncfSeq = $stmtSeqNcf->fetch();

            if (!$ncfSeq) {
                errorResponse("No hay secuencia NCF disponible para el tipo {$ncfType}. Verifique la configuracion de comprobantes.", 400);
            }

            $ncfSequenceId = (int)$ncfSeq['id'];
            $ncfExpectedNumber = (int)$ncfSeq['current_number'];
            $nextNumber = $ncfExpectedNumber + 1;
            $ncfNumber = $ncfSeq['prefix'] . str_pad($nextNumber, 8, '0', STR_PAD_LEFT);
        }

        // =====================================================================
        // TRANSACCION
        // =====================================================================
        $db->beginTransaction();

        try {
            // 0a. Generar numero de venta con bloqueo (dentro de transaccion)
            $stmtSeq = $db->prepare(
                "SELECT sale_number FROM sales WHERE sale_number LIKE :pattern ORDER BY sale_number DESC LIMIT 1 FOR UPDATE"
            );
            $stmtSeq->execute([':pattern' => $saleNumberPattern . '%']);
            $lastSaleNumber = $stmtSeq->fetchColumn();
            if ($lastSaleNumber) {
                $lastSeq = (int)substr($lastSaleNumber, strrpos($lastSaleNumber, '-') + 1);
                $seq = $lastSeq + 1;
            } else {
                $seq = 1;
            }
            $saleNumber = sprintf("VTA-%s-%03d", $today, $seq);

            // 0b. Asignar NCF con bloqueo optimista si fue solicitado
            if ($ncfSequenceId !== null) {
                $stmtNcfUpdate = $db->prepare(
                    "UPDATE ncf_sequences
                     SET current_number = current_number + 1, updated_at = NOW()
                     WHERE id = :id AND current_number = :expected"
                );
                $stmtNcfUpdate->execute([
                    ':id'       => $ncfSequenceId,
                    ':expected' => $ncfExpectedNumber,
                ]);
                if ($stmtNcfUpdate->rowCount() === 0) {
                    $db->rollBack();
                    errorResponse('Error de concurrencia al asignar NCF. Intente nuevamente.', 409);
                }
            }

            // 1. Insertar venta
            $stmtSale = $db->prepare(
                "INSERT INTO sales (sale_number, ncf_number, ncf_type, customer_rnc,
                                    customer_id, user_id, register_session_id,
                                    subtotal, discount_amount, discount_percent,
                                    tax_percent, tax_amount, total,
                                    payment_method, cash_received, cash_change,
                                    card_reference, status, notes, created_at)
                 VALUES (:sale_number, :ncf_number, :ncf_type, :customer_rnc,
                         :customer_id, :user_id, :register_session_id,
                         :subtotal, :discount_amount, :discount_percent,
                         :tax_percent, :tax_amount, :total,
                         :payment_method, :cash_received, :cash_change,
                         :card_reference, 'completed', :notes, NOW())"
            );
            $stmtSale->execute([
                ':sale_number'         => $saleNumber,
                ':ncf_number'          => $ncfNumber,
                ':ncf_type'            => $ncfType,
                ':customer_rnc'        => $customerRnc,
                ':customer_id'         => $customerId,
                ':user_id'             => $userId,
                ':register_session_id' => $registerSessionId,
                ':subtotal'            => $subtotal,
                ':discount_amount'     => $discountAmount,
                ':discount_percent'    => $discountPercent,
                ':tax_percent'         => $taxPercent,
                ':tax_amount'          => $taxAmount,
                ':total'               => $total,
                ':payment_method'      => $data['payment_method'],
                ':cash_received'       => $cashReceived,
                ':cash_change'         => $cashChange,
                ':card_reference'      => $cardReference,
                ':notes'               => $notes,
            ]);

            $saleId = (int)$db->lastInsertId();

            // 2. Insertar items de la venta
            $stmtItem = $db->prepare(
                "INSERT INTO sale_items (sale_id, product_id, product_name, product_brand,
                                         quantity, unit_price, unit_cost, discount, subtotal)
                 VALUES (:sale_id, :product_id, :product_name, :product_brand,
                         :quantity, :unit_price, :unit_cost, :discount, :subtotal)"
            );

            $stmtStock = $db->prepare(
                "UPDATE products SET stock = stock - :qty WHERE id = :id"
            );

            foreach ($itemsData as $saleItem) {
                $stmtItem->execute([
                    ':sale_id'       => $saleId,
                    ':product_id'    => $saleItem['product_id'],
                    ':product_name'  => $saleItem['product_name'],
                    ':product_brand' => $saleItem['product_brand'],
                    ':quantity'      => $saleItem['quantity'],
                    ':unit_price'    => $saleItem['unit_price'],
                    ':unit_cost'     => $saleItem['unit_cost'],
                    ':discount'      => $saleItem['discount'],
                    ':subtotal'      => $saleItem['subtotal'],
                ]);

                // 3. Descontar stock
                $stmtStock->execute([
                    ':qty' => $saleItem['quantity'],
                    ':id'  => $saleItem['product_id'],
                ]);
            }

            // 4. Actualizar cliente si aplica
            if ($customerId) {
                $stmtCustUpdate = $db->prepare(
                    "UPDATE customers
                     SET total_purchases = total_purchases + :total,
                         visit_count = visit_count + 1
                     WHERE id = :id"
                );
                $stmtCustUpdate->execute([
                    ':total' => $total,
                    ':id'    => $customerId,
                ]);
            }

            // 5. Actualizar sesion de caja si aplica
            if ($registerSessionId) {
                $regUpdateFields = ["total_sales_count = total_sales_count + 1"];
                $regParams = [':reg_id' => $registerSessionId];

                switch ($data['payment_method']) {
                    case 'cash':
                        $regUpdateFields[] = "total_cash_sales = total_cash_sales + :sale_total";
                        $regParams[':sale_total'] = $total;
                        break;
                    case 'card':
                        $regUpdateFields[] = "total_card_sales = total_card_sales + :sale_total";
                        $regParams[':sale_total'] = $total;
                        break;
                    case 'transfer':
                        $regUpdateFields[] = "total_transfer_sales = total_transfer_sales + :sale_total";
                        $regParams[':sale_total'] = $total;
                        break;
                    case 'mixed':
                        $mixedCashPortion = $cashReceived !== null ? min($cashReceived, $total) : $total;
                        $mixedOtherPortion = $total - $mixedCashPortion;
                        $mixedOtherMethod = isset($data['mixed_other_method']) ? $data['mixed_other_method'] : 'card';

                        $regUpdateFields[] = "total_cash_sales = total_cash_sales + :cash_portion";
                        $regParams[':cash_portion'] = $mixedCashPortion;

                        if ($mixedOtherMethod === 'transfer') {
                            $regUpdateFields[] = "total_transfer_sales = total_transfer_sales + :other_portion";
                        } else {
                            $regUpdateFields[] = "total_card_sales = total_card_sales + :other_portion";
                        }
                        $regParams[':other_portion'] = $mixedOtherPortion;
                        break;
                }

                $regSQL = "UPDATE cash_register_sessions SET " . implode(', ', $regUpdateFields) . " WHERE id = :reg_id";
                $stmtReg = $db->prepare($regSQL);
                $stmtReg->execute($regParams);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al procesar la venta: ' . $e->getMessage(), 500);
        }

        // --- Registrar actividad ---
        logActivity('create', 'sale', $saleId, "Venta creada: {$saleNumber} por RD$ " . number_format($total, 2));

        // --- Retornar venta completa ---
        self::_getSaleById($db, $saleId, 'Venta registrada exitosamente.', 201);
    }

    /**
     * GET /sales
     * Listar ventas con filtros y paginacion
     */
    public static function index() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por fecha desde
        if (!empty($_GET['date_from'])) {
            $where[] = "s.created_at >= :date_from";
            $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
        }

        // Filtro por fecha hasta
        if (!empty($_GET['date_to'])) {
            $where[] = "s.created_at <= :date_to";
            $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
        }

        // Filtro por metodo de pago
        if (!empty($_GET['payment_method'])) {
            $where[] = "s.payment_method = :payment_method";
            $params[':payment_method'] = $_GET['payment_method'];
        }

        // Filtro por estado
        if (!empty($_GET['status'])) {
            $where[] = "s.status = :status";
            $params[':status'] = $_GET['status'];
        }

        // Filtro por usuario
        if (!empty($_GET['user_id'])) {
            $where[] = "s.user_id = :user_id";
            $params[':user_id'] = (int)$_GET['user_id'];
        }

        // Filtro por busqueda (numero venta, NCF, cliente, vendedor)
        if (!empty($_GET['search'])) {
            $where[] = "(s.sale_number LIKE :search1 OR c.name LIKE :search2 OR u.full_name LIKE :search3 OR s.ncf_number LIKE :search4)";
            $params[':search1'] = '%' . $_GET['search'] . '%';
            $params[':search2'] = '%' . $_GET['search'] . '%';
            $params[':search3'] = '%' . $_GET['search'] . '%';
            $params[':search4'] = '%' . $_GET['search'] . '%';
        }

        // Filtro por monto minimo
        if (!empty($_GET['min_amount'])) {
            $where[] = "s.total >= :min_amount";
            $params[':min_amount'] = (float)$_GET['min_amount'];
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM sales s
                     LEFT JOIN users u ON s.user_id = u.id
                     LEFT JOIN customers c ON s.customer_id = c.id
                     {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener ventas
        $sql = "SELECT s.id, s.sale_number, s.ncf_number, s.ncf_type, s.customer_id, s.user_id,
                       s.subtotal, s.discount_amount, s.discount_percent,
                       s.tax_percent, s.tax_amount, s.total,
                       s.payment_method, s.status, s.created_at,
                       u.full_name AS user_name,
                       c.name AS customer_name
                FROM sales s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN customers c ON s.customer_id = c.id
                {$whereSQL}
                ORDER BY s.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sales = $stmt->fetchAll();

        paginatedResponse($sales, $total, $page, $limit);
    }

    /**
     * GET /sales/{id}
     * Detalle completo de una venta con items, usuario y cliente
     */
    public static function show($id) {
        $db = getDB();
        self::_getSaleById($db, $id);
    }

    /**
     * POST /sales/{id}/cancel
     * Cancelar una venta completada y revertir stock/totales
     */
    public static function cancel($id) {
        $db = getDB();

        // Verificar que la venta existe y esta completada
        $stmtSale = $db->prepare(
            "SELECT id, sale_number, customer_id, register_session_id,
                    total, payment_method, cash_received, card_reference, status
             FROM sales WHERE id = :id"
        );
        $stmtSale->execute([':id' => $id]);
        $sale = $stmtSale->fetch();

        if (!$sale) {
            errorResponse('Venta no encontrada.', 404);
        }

        if ($sale['status'] !== 'completed') {
            errorResponse('Solo se pueden cancelar ventas con estado "completed". Estado actual: ' . $sale['status'] . '.', 400);
        }

        // Obtener items de la venta
        $stmtItems = $db->prepare(
            "SELECT product_id, quantity FROM sale_items WHERE sale_id = :sale_id"
        );
        $stmtItems->execute([':sale_id' => $id]);
        $items = $stmtItems->fetchAll();

        // =====================================================================
        // TRANSACCION
        // =====================================================================
        $db->beginTransaction();

        try {
            // 1. Cambiar estado de la venta
            $stmtUpdate = $db->prepare(
                "UPDATE sales SET status = 'cancelled' WHERE id = :id"
            );
            $stmtUpdate->execute([':id' => $id]);

            // 2. Restaurar stock de cada producto
            $stmtRestore = $db->prepare(
                "UPDATE products SET stock = stock + :qty WHERE id = :id"
            );
            foreach ($items as $item) {
                $stmtRestore->execute([
                    ':qty' => $item['quantity'],
                    ':id'  => $item['product_id'],
                ]);
            }

            // 3. Revertir totales del cliente si aplica
            if ($sale['customer_id']) {
                $stmtCustRevert = $db->prepare(
                    "UPDATE customers
                     SET total_purchases = total_purchases - :total,
                         visit_count = GREATEST(visit_count - 1, 0)
                     WHERE id = :id"
                );
                $stmtCustRevert->execute([
                    ':total' => $sale['total'],
                    ':id'    => $sale['customer_id'],
                ]);
            }

            // 4. Revertir totales de la sesion de caja si aplica
            if ($sale['register_session_id']) {
                $regRevertFields = ["total_sales_count = GREATEST(total_sales_count - 1, 0)"];
                $regParams = [':reg_id' => $sale['register_session_id']];

                switch ($sale['payment_method']) {
                    case 'cash':
                        $regRevertFields[] = "total_cash_sales = total_cash_sales - :sale_total";
                        $regParams[':sale_total'] = $sale['total'];
                        break;
                    case 'card':
                        $regRevertFields[] = "total_card_sales = total_card_sales - :sale_total";
                        $regParams[':sale_total'] = $sale['total'];
                        break;
                    case 'transfer':
                        $regRevertFields[] = "total_transfer_sales = total_transfer_sales - :sale_total";
                        $regParams[':sale_total'] = $sale['total'];
                        break;
                    case 'mixed':
                        $mixedCash = $sale['cash_received'] !== null ? min((float)$sale['cash_received'], (float)$sale['total']) : (float)$sale['total'];
                        $mixedOther = (float)$sale['total'] - $mixedCash;

                        $regRevertFields[] = "total_cash_sales = total_cash_sales - :cash_portion";
                        $regParams[':cash_portion'] = $mixedCash;

                        if ($sale['card_reference'] === 'transfer') {
                            $regRevertFields[] = "total_transfer_sales = total_transfer_sales - :other_portion";
                        } else {
                            $regRevertFields[] = "total_card_sales = total_card_sales - :other_portion";
                        }
                        $regParams[':other_portion'] = $mixedOther;
                        break;
                }

                $regSQL = "UPDATE cash_register_sessions SET " . implode(', ', $regRevertFields) . " WHERE id = :reg_id";
                $stmtReg = $db->prepare($regSQL);
                $stmtReg->execute($regParams);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al cancelar la venta: ' . $e->getMessage(), 500);
        }

        // --- Registrar actividad ---
        logActivity('cancel', 'sale', $id, "Venta cancelada: {$sale['sale_number']}");

        self::_getSaleById($db, $id, 'Venta cancelada exitosamente.');
    }

    /**
     * GET /sales/{id}/receipt
     * Datos formateados para recibo/ticket de venta
     */
    public static function receipt($id) {
        $db = getDB();

        // Obtener venta
        $stmtSale = $db->prepare(
            "SELECT s.*,
                    u.full_name AS user_name,
                    c.name AS customer_name
             FROM sales s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = :id"
        );
        $stmtSale->execute([':id' => $id]);
        $sale = $stmtSale->fetch();

        if (!$sale) {
            errorResponse('Venta no encontrada.', 404);
        }

        // Obtener items
        $stmtItems = $db->prepare(
            "SELECT product_name, product_brand, quantity, unit_price, discount, subtotal
             FROM sale_items
             WHERE sale_id = :sale_id
             ORDER BY id ASC"
        );
        $stmtItems->execute([':sale_id' => $id]);
        $items = $stmtItems->fetchAll();

        // Obtener datos de la tienda desde settings
        $stmtStore = $db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('store_name', 'address', 'contact_phone', 'contact_email', 'whatsapp_number', 'store_rnc')"
        );
        $stmtStore->execute();
        $storeSettings = $stmtStore->fetchAll(PDO::FETCH_KEY_PAIR);

        // Mapear metodo de pago a texto legible
        $paymentLabels = [
            'cash'     => 'Efectivo',
            'card'     => 'Tarjeta',
            'transfer' => 'Transferencia',
            'mixed'    => 'Mixto',
        ];

        // NCF type labels
        $ncfLabels = [
            'B01' => 'Credito Fiscal',
            'B02' => 'Consumo',
            'B14' => 'Regimenes Especiales',
            'B15' => 'Gubernamental',
        ];

        $receipt = [
            'store' => [
                'store_name'  => $storeSettings['store_name'] ?? 'BBR Fragrance',
                'address'     => $storeSettings['address'] ?? '',
                'store_phone' => $storeSettings['contact_phone'] ?? '',
                'store_email' => $storeSettings['contact_email'] ?? '',
                'store_rnc'   => $storeSettings['store_rnc'] ?? '',
                'store_footer' => 'Gracias por su compra! - BBR Fragrance',
            ],
            'sale' => [
                'sale_number'      => $sale['sale_number'],
                'date'             => $sale['created_at'],
                'cashier'          => $sale['user_name'],
                'customer'         => $sale['customer_name'],
                'payment_method'   => $paymentLabels[$sale['payment_method']] ?? $sale['payment_method'],
                'status'           => $sale['status'],
            ],
            'ncf' => [
                'ncf_number'   => $sale['ncf_number'] ?? null,
                'ncf_type'     => $sale['ncf_type'] ?? null,
                'ncf_label'    => !empty($sale['ncf_type']) ? ($ncfLabels[$sale['ncf_type']] ?? $sale['ncf_type']) : null,
                'customer_rnc' => $sale['customer_rnc'] ?? null,
            ],
            'items' => $items,
            'totals' => [
                'subtotal'         => (float)$sale['subtotal'],
                'discount_percent' => (float)$sale['discount_percent'],
                'discount_amount'  => (float)$sale['discount_amount'],
                'tax_percent'      => (float)$sale['tax_percent'],
                'tax_amount'       => (float)$sale['tax_amount'],
                'total'            => (float)$sale['total'],
            ],
            'payment' => [
                'cash_received'    => $sale['cash_received'] !== null ? (float)$sale['cash_received'] : null,
                'cash_change'      => $sale['cash_change'] !== null ? (float)$sale['cash_change'] : null,
                'card_reference'   => $sale['card_reference'],
            ],
            'notes' => $sale['notes'],
        ];

        successResponse($receipt, 'Recibo generado exitosamente.');
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener una venta completa por ID y devolver como successResponse
     */
    private static function _getSaleById($db, $id, $message = null, $code = 200) {
        // Obtener venta
        $stmtSale = $db->prepare(
            "SELECT s.*,
                    u.full_name AS user_name,
                    c.name AS customer_name
             FROM sales s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = :id"
        );
        $stmtSale->execute([':id' => $id]);
        $sale = $stmtSale->fetch();

        if (!$sale) {
            errorResponse('Venta no encontrada.', 404);
        }

        // Obtener items de la venta
        $stmtItems = $db->prepare(
            "SELECT id, product_id, product_name, product_brand,
                    quantity, unit_price, unit_cost, discount, subtotal
             FROM sale_items
             WHERE sale_id = :sale_id
             ORDER BY id ASC"
        );
        $stmtItems->execute([':sale_id' => $id]);
        $sale['items'] = $stmtItems->fetchAll();

        successResponse($sale, $message, $code);
    }
}
