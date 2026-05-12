<?php
/**
 * BBR Fragance - Cash Register Controller
 * Gestiona apertura, cierre y consulta de sesiones de caja
 */

class CashRegisterController {

    /**
     * POST /cash-register/open
     * Abrir una nueva sesion de caja
     */
    public static function open() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campos requeridos
        $errors = validateRequired($data, ['opening_amount']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar que opening_amount sea numerico y positivo
        $amountError = validateNumeric($data['opening_amount'], 'opening_amount', 0);
        if ($amountError) {
            errorResponse($amountError, 400);
        }

        // Verificar que no exista otra sesion abierta para NINGUN usuario
        $stmtCheck = $db->prepare(
            "SELECT id, user_id FROM cash_register_sessions WHERE status = 'open' LIMIT 1"
        );
        $stmtCheck->execute();
        $openSession = $stmtCheck->fetch();

        if ($openSession) {
            errorResponse('Ya existe una sesion de caja abierta. Debe cerrarla antes de abrir una nueva.', 400);
        }

        $userId = getCurrentUserId();
        $openingAmount = (float)$data['opening_amount'];

        // Crear sesion de caja
        $stmt = $db->prepare(
            "INSERT INTO cash_register_sessions (user_id, opening_amount, status, opened_at)
             VALUES (:user_id, :opening_amount, 'open', NOW())"
        );
        $stmt->execute([
            ':user_id'        => $userId,
            ':opening_amount' => $openingAmount,
        ]);

        $sessionId = (int)$db->lastInsertId();

        // Registrar actividad
        logActivity('create', 'cash_register', $sessionId, "Caja abierta con monto inicial de RD$ " . number_format($openingAmount, 2));

        // Retornar sesion creada
        self::_getSessionById($db, $sessionId, 'Sesion de caja abierta exitosamente.', 201);
    }

    /**
     * PUT /cash-register/close
     * Cerrar la sesion de caja abierta del usuario actual
     */
    public static function close() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campos requeridos
        $errors = validateRequired($data, ['closing_amount']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar que closing_amount sea numerico y no negativo
        $amountError = validateNumeric($data['closing_amount'], 'closing_amount', 0);
        if ($amountError) {
            errorResponse($amountError, 400);
        }

        $userId = getCurrentUserId();

        // Buscar sesion abierta del usuario actual
        $stmtSession = $db->prepare(
            "SELECT id, user_id, opening_amount, total_cash_sales, total_card_sales,
                    total_transfer_sales, total_sales_count, total_expenses
             FROM cash_register_sessions
             WHERE status = 'open' AND user_id = :user_id
             LIMIT 1"
        );
        $stmtSession->execute([':user_id' => $userId]);
        $session = $stmtSession->fetch();

        if (!$session) {
            errorResponse('No tiene una sesion de caja abierta.', 404);
        }

        $closingAmount = (float)$data['closing_amount'];
        $closingNotes = !empty($data['notes']) ? sanitizeString($data['notes']) : null;

        // Calcular totales en tiempo real desde la tabla de ventas
        $stmtSales = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total
                                     WHEN payment_method = 'mixed' THEN COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS total_cash,
                    COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total
                                     WHEN payment_method = 'mixed' AND (card_reference IS NULL OR card_reference = 'card') THEN total - COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS total_card,
                    COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN total
                                     WHEN payment_method = 'mixed' AND card_reference = 'transfer' THEN total - COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS total_transfer,
                    COUNT(*) AS sales_count
             FROM sales
             WHERE register_session_id = :session_id AND status = 'completed'"
        );
        $stmtSales->execute([':session_id' => $session['id']]);
        $salesTotals = $stmtSales->fetch();

        $totalCashSales = (float)$salesTotals['total_cash'];
        $totalCardSales = (float)$salesTotals['total_card'];
        $totalTransferSales = (float)$salesTotals['total_transfer'];
        $totalSalesCount = (int)$salesTotals['sales_count'];

        // Calcular gastos en efectivo del periodo de la sesion
        $stmtExpenses = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total_expenses
             FROM expenses
             WHERE payment_method = 'cash'
               AND expense_date = CURDATE()"
        );
        $stmtExpenses->execute();
        $totalExpenses = (float)$stmtExpenses->fetchColumn();

        // Calcular monto esperado: apertura + ventas en efectivo - gastos en efectivo
        $openingAmount = (float)$session['opening_amount'];
        $expectedAmount = round($openingAmount + $totalCashSales - $totalExpenses, 2);

        // Calcular diferencia: cierre - esperado
        $difference = round($closingAmount - $expectedAmount, 2);

        // Actualizar sesion de caja
        $stmtUpdate = $db->prepare(
            "UPDATE cash_register_sessions
             SET closing_amount = :closing_amount,
                 expected_amount = :expected_amount,
                 difference = :difference,
                 total_cash_sales = :total_cash_sales,
                 total_card_sales = :total_card_sales,
                 total_transfer_sales = :total_transfer_sales,
                 total_sales_count = :total_sales_count,
                 total_expenses = :total_expenses,
                 status = 'closed',
                 closed_at = NOW(),
                 closing_notes = :closing_notes
             WHERE id = :id"
        );
        $stmtUpdate->execute([
            ':closing_amount'       => $closingAmount,
            ':expected_amount'      => $expectedAmount,
            ':difference'           => $difference,
            ':total_cash_sales'     => $totalCashSales,
            ':total_card_sales'     => $totalCardSales,
            ':total_transfer_sales' => $totalTransferSales,
            ':total_sales_count'    => $totalSalesCount,
            ':total_expenses'       => $totalExpenses,
            ':closing_notes'        => $closingNotes,
            ':id'                   => $session['id'],
        ]);

        // Registrar actividad
        logActivity('update', 'cash_register', $session['id'],
            "Caja cerrada. Esperado: RD$ " . number_format($expectedAmount, 2) .
            ", Contado: RD$ " . number_format($closingAmount, 2) .
            ", Diferencia: RD$ " . number_format($difference, 2)
        );

        // Retornar sesion cerrada
        self::_getSessionById($db, $session['id'], 'Sesion de caja cerrada exitosamente.');
    }

    /**
     * GET /cash-register/current
     * Obtener la sesion de caja abierta actual con totales en tiempo real
     */
    public static function current() {
        $db = getDB();

        // Buscar sesion abierta (cualquier usuario)
        $stmtSession = $db->prepare(
            "SELECT crs.*, u.full_name AS user_name
             FROM cash_register_sessions crs
             LEFT JOIN users u ON crs.user_id = u.id
             WHERE crs.status = 'open'
             LIMIT 1"
        );
        $stmtSession->execute();
        $session = $stmtSession->fetch();

        if (!$session) {
            errorResponse('No hay una sesion de caja abierta actualmente.', 404);
        }

        // Calcular totales en tiempo real desde la tabla de ventas
        $stmtSales = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total
                                     WHEN payment_method = 'mixed' THEN COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS live_cash_sales,
                    COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total
                                     WHEN payment_method = 'mixed' AND (card_reference IS NULL OR card_reference = 'card') THEN total - COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS live_card_sales,
                    COALESCE(SUM(CASE WHEN payment_method = 'transfer' THEN total
                                     WHEN payment_method = 'mixed' AND card_reference = 'transfer' THEN total - COALESCE(cash_received, 0)
                                     ELSE 0 END), 0) AS live_transfer_sales,
                    COALESCE(SUM(total), 0) AS live_total_sales,
                    COUNT(*) AS live_sales_count
             FROM sales
             WHERE register_session_id = :session_id AND status = 'completed'"
        );
        $stmtSales->execute([':session_id' => $session['id']]);
        $liveSales = $stmtSales->fetch();

        // Calcular gastos en efectivo de hoy
        $stmtExpenses = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS live_total_expenses
             FROM expenses
             WHERE payment_method = 'cash'
               AND expense_date = CURDATE()"
        );
        $stmtExpenses->execute();
        $liveExpenses = (float)$stmtExpenses->fetchColumn();

        // Agregar totales en tiempo real a la sesion
        $session['live_totals'] = [
            'cash_sales'     => (float)$liveSales['live_cash_sales'],
            'card_sales'     => (float)$liveSales['live_card_sales'],
            'transfer_sales' => (float)$liveSales['live_transfer_sales'],
            'total_sales'    => (float)$liveSales['live_total_sales'],
            'sales_count'    => (int)$liveSales['live_sales_count'],
            'total_expenses' => $liveExpenses,
            'expected_cash'  => round((float)$session['opening_amount'] + (float)$liveSales['live_cash_sales'] - $liveExpenses, 2),
        ];

        successResponse($session, 'Sesion de caja actual obtenida exitosamente.');
    }

    /**
     * GET /cash-register/{id}
     * Obtener detalle de una sesion de caja especifica con ventas y gastos
     */
    public static function show($id) {
        $db = getDB();

        // Obtener sesion con datos del usuario
        $stmtSession = $db->prepare(
            "SELECT crs.*, u.full_name AS user_name
             FROM cash_register_sessions crs
             LEFT JOIN users u ON crs.user_id = u.id
             WHERE crs.id = :id"
        );
        $stmtSession->execute([':id' => $id]);
        $session = $stmtSession->fetch();

        if (!$session) {
            errorResponse('Sesion de caja no encontrada.', 404);
        }

        // Obtener ventas realizadas durante esta sesion
        $stmtSales = $db->prepare(
            "SELECT s.id, s.sale_number, s.total, s.payment_method, s.status, s.created_at,
                    u.full_name AS user_name
             FROM sales s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.register_session_id = :session_id
             ORDER BY s.created_at ASC"
        );
        $stmtSales->execute([':session_id' => $id]);
        $session['sales'] = $stmtSales->fetchAll();

        // Obtener gastos realizados durante el periodo de la sesion
        $stmtExpenses = $db->prepare(
            "SELECT e.id, e.description, e.amount, e.payment_method, e.expense_date,
                    ec.name AS category_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             WHERE e.expense_date >= DATE(:opened_at)
               AND (:closed_at IS NULL OR e.expense_date <= DATE(:closed_at_end))
             ORDER BY e.expense_date ASC, e.created_at ASC"
        );
        $stmtExpenses->execute([
            ':opened_at'      => $session['opened_at'],
            ':closed_at'      => $session['closed_at'],
            ':closed_at_end'  => $session['closed_at'] ?? date('Y-m-d'),
        ]);
        $session['expenses'] = $stmtExpenses->fetchAll();

        successResponse($session, 'Detalle de sesion de caja obtenido exitosamente.');
    }

    /**
     * GET /cash-register/history
     * Listar sesiones de caja pasadas con paginacion y filtros
     */
    public static function history() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por fecha desde
        if (!empty($_GET['date_from'])) {
            $where[] = "crs.opened_at >= :date_from";
            $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
        }

        // Filtro por fecha hasta
        if (!empty($_GET['date_to'])) {
            $where[] = "crs.opened_at <= :date_to";
            $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM cash_register_sessions crs {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener sesiones con nombre de usuario
        $sql = "SELECT crs.id, crs.user_id, crs.opening_amount, crs.closing_amount,
                       crs.expected_amount, crs.difference,
                       crs.total_cash_sales, crs.total_card_sales, crs.total_transfer_sales,
                       crs.total_sales_count, crs.total_expenses,
                       crs.status, crs.opened_at, crs.closed_at, crs.closing_notes,
                       u.full_name AS user_name
                FROM cash_register_sessions crs
                LEFT JOIN users u ON crs.user_id = u.id
                {$whereSQL}
                ORDER BY crs.opened_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $sessions = $stmt->fetchAll();

        paginatedResponse($sessions, $total, $page, $limit);
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener una sesion de caja por ID y devolver como successResponse
     */
    private static function _getSessionById($db, $id, $message = null, $code = 200) {
        $stmt = $db->prepare(
            "SELECT crs.*, u.full_name AS user_name
             FROM cash_register_sessions crs
             LEFT JOIN users u ON crs.user_id = u.id
             WHERE crs.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $session = $stmt->fetch();

        if (!$session) {
            errorResponse('Sesion de caja no encontrada.', 404);
        }

        successResponse($session, $message, $code);
    }
}
