<?php
/**
 * BBR Fragance - Expense Controller
 * Gestiona gastos, categorias de gastos y reportes de resumen
 */

class ExpenseController {

    /**
     * GET /expenses
     * Listar gastos con filtros y paginacion
     */
    public static function index() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por categoria
        if (!empty($_GET['category_id'])) {
            $where[] = "e.expense_category_id = :category_id";
            $params[':category_id'] = (int)$_GET['category_id'];
        }

        // Filtro por fecha desde
        if (!empty($_GET['date_from'])) {
            $where[] = "e.expense_date >= :date_from";
            $params[':date_from'] = $_GET['date_from'];
        }

        // Filtro por fecha hasta
        if (!empty($_GET['date_to'])) {
            $where[] = "e.expense_date <= :date_to";
            $params[':date_to'] = $_GET['date_to'];
        }

        // Filtro por metodo de pago
        if (!empty($_GET['payment_method'])) {
            $where[] = "e.payment_method = :payment_method";
            $params[':payment_method'] = $_GET['payment_method'];
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM expenses e {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener gastos con categoria y usuario
        $sql = "SELECT e.id, e.expense_category_id, e.user_id,
                       e.description, e.amount, e.expense_date,
                       e.payment_method, e.receipt_number, e.notes, e.created_at,
                       ec.name AS category_name,
                       u.full_name AS user_name
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
                LEFT JOIN users u ON e.user_id = u.id
                {$whereSQL}
                ORDER BY e.expense_date DESC, e.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $expenses = $stmt->fetchAll();

        paginatedResponse($expenses, $total, $page, $limit);
    }

    /**
     * GET /expenses/{id}
     * Detalle de un gasto con categoria y usuario
     */
    public static function show($id) {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT e.id, e.expense_category_id, e.user_id,
                    e.description, e.amount, e.expense_date,
                    e.payment_method, e.receipt_number, e.notes, e.created_at,
                    ec.name AS category_name,
                    u.full_name AS user_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $expense = $stmt->fetch();

        if (!$expense) {
            errorResponse('Gasto no encontrado.', 404);
        }

        successResponse($expense);
    }

    /**
     * POST /expenses
     * Crear un nuevo gasto
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campos requeridos
        $errors = validateRequired($data, ['expense_category_id', 'description', 'amount', 'expense_date']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar monto
        $amountError = validateNumeric($data['amount'], 'amount', 0.01);
        if ($amountError) {
            errorResponse($amountError, 400);
        }

        // Validar fecha
        $dateError = validateDate($data['expense_date'], 'expense_date');
        if ($dateError) {
            errorResponse($dateError, 400);
        }

        // Validar metodo de pago si viene
        if (!empty($data['payment_method'])) {
            $paymentError = validateEnum($data['payment_method'], ['cash', 'card', 'transfer'], 'payment_method');
            if ($paymentError) {
                errorResponse($paymentError, 400);
            }
        }

        // Validar que la categoria existe
        $stmtCat = $db->prepare("SELECT id FROM expense_categories WHERE id = :id AND is_active = 1");
        $stmtCat->execute([':id' => (int)$data['expense_category_id']]);
        if (!$stmtCat->fetch()) {
            errorResponse('La categoria de gasto especificada no existe o no esta activa.', 404);
        }

        $userId = getCurrentUserId();

        // Insertar gasto
        $stmt = $db->prepare(
            "INSERT INTO expenses (expense_category_id, user_id, description, amount, expense_date,
                                   payment_method, receipt_number, notes, created_at)
             VALUES (:expense_category_id, :user_id, :description, :amount, :expense_date,
                     :payment_method, :receipt_number, :notes, NOW())"
        );

        $stmt->execute([
            ':expense_category_id' => (int)$data['expense_category_id'],
            ':user_id'             => $userId,
            ':description'         => sanitizeString($data['description']),
            ':amount'              => (float)$data['amount'],
            ':expense_date'        => $data['expense_date'],
            ':payment_method'      => $data['payment_method'] ?? 'cash',
            ':receipt_number'      => !empty($data['receipt_number']) ? sanitizeString($data['receipt_number']) : null,
            ':notes'               => !empty($data['notes']) ? sanitizeString($data['notes']) : null,
        ]);

        $expenseId = (int)$db->lastInsertId();

        logActivity('create', 'expense', $expenseId, "Gasto creado: " . sanitizeString($data['description']) . " por RD$ " . number_format((float)$data['amount'], 2));

        // Retornar gasto creado
        self::_getExpenseById($db, $expenseId, 'Gasto registrado exitosamente.', 201);
    }

    /**
     * PUT /expenses/{id}
     * Actualizar un gasto existente
     */
    public static function update($id) {
        $db = getDB();

        // Verificar que el gasto existe
        $stmtCheck = $db->prepare("SELECT id, description FROM expenses WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            errorResponse('Gasto no encontrado.', 404);
        }

        $data = getJsonInput();

        if (empty($data)) {
            errorResponse('No se enviaron datos para actualizar.', 400);
        }

        // Validar monto si viene
        if (isset($data['amount'])) {
            $amountError = validateNumeric($data['amount'], 'amount', 0.01);
            if ($amountError) {
                errorResponse($amountError, 400);
            }
        }

        // Validar fecha si viene
        if (isset($data['expense_date'])) {
            $dateError = validateDate($data['expense_date'], 'expense_date');
            if ($dateError) {
                errorResponse($dateError, 400);
            }
        }

        // Validar metodo de pago si viene
        if (!empty($data['payment_method'])) {
            $paymentError = validateEnum($data['payment_method'], ['cash', 'card', 'transfer'], 'payment_method');
            if ($paymentError) {
                errorResponse($paymentError, 400);
            }
        }

        // Validar que la categoria existe si viene
        if (isset($data['expense_category_id'])) {
            $stmtCat = $db->prepare("SELECT id FROM expense_categories WHERE id = :id AND is_active = 1");
            $stmtCat->execute([':id' => (int)$data['expense_category_id']]);
            if (!$stmtCat->fetch()) {
                errorResponse('La categoria de gasto especificada no existe o no esta activa.', 404);
            }
        }

        // Construir SET dinamico solo con los campos enviados
        $fields = [];
        $params = [':id' => $id];

        $updatable = [
            'expense_category_id' => 'int',
            'description'         => 'string',
            'amount'              => 'float',
            'expense_date'        => 'raw',
            'payment_method'      => 'raw',
            'receipt_number'      => 'string_nullable',
            'notes'               => 'string_nullable',
        ];

        foreach ($updatable as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $value = $data[$field];

                switch ($type) {
                    case 'string':
                        $params[":{$field}"] = sanitizeString($value);
                        break;
                    case 'string_nullable':
                        $params[":{$field}"] = ($value !== '' && $value !== null) ? sanitizeString($value) : null;
                        break;
                    case 'int':
                        $params[":{$field}"] = (int)$value;
                        break;
                    case 'float':
                        $params[":{$field}"] = (float)$value;
                        break;
                    case 'raw':
                        $params[":{$field}"] = $value;
                        break;
                }
            }
        }

        if (!empty($fields)) {
            $sql = "UPDATE expenses SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        logActivity('update', 'expense', $id, "Gasto actualizado: " . ($data['description'] ?? $existing['description']));

        // Retornar gasto actualizado
        self::_getExpenseById($db, $id, 'Gasto actualizado exitosamente.');
    }

    /**
     * DELETE /expenses/{id}
     * Eliminar un gasto
     */
    public static function destroy($id) {
        $db = getDB();

        // Verificar que el gasto existe
        $stmtCheck = $db->prepare("SELECT id, description, amount FROM expenses WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $expense = $stmtCheck->fetch();

        if (!$expense) {
            errorResponse('Gasto no encontrado.', 404);
        }

        // Eliminar gasto
        $db->prepare("DELETE FROM expenses WHERE id = :id")
           ->execute([':id' => $id]);

        logActivity('delete', 'expense', $id, "Gasto eliminado: {$expense['description']} por RD$ " . number_format((float)$expense['amount'], 2));

        successResponse(null, 'Gasto eliminado exitosamente.');
    }

    /**
     * GET /expense-categories
     * Listar todas las categorias de gastos activas con total gastado
     */
    public static function categories() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT ec.id, ec.name, ec.slug, ec.icon, ec.color, ec.is_active,
                    COALESCE(SUM(e.amount), 0) AS total_spent
             FROM expense_categories ec
             LEFT JOIN expenses e ON ec.id = e.expense_category_id
             WHERE ec.is_active = 1
             GROUP BY ec.id, ec.name, ec.slug, ec.icon, ec.color, ec.is_active
             ORDER BY ec.name ASC"
        );
        $stmt->execute();
        $categories = $stmt->fetchAll();

        // Convertir total_spent a float
        foreach ($categories as &$cat) {
            $cat['total_spent'] = (float)$cat['total_spent'];
        }
        unset($cat);

        successResponse($categories, 'Categorias de gastos obtenidas exitosamente.');
    }

    /**
     * GET /expenses/summary
     * Resumen de gastos por categoria para un rango de fechas
     * Query params: date_from, date_to, period (month|week)
     */
    public static function summary() {
        $db = getDB();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $period = $_GET['period'] ?? 'month';

        // Validar fechas
        $dateFromError = validateDate($dateFrom, 'date_from');
        if ($dateFromError) {
            errorResponse($dateFromError, 400);
        }

        $dateToError = validateDate($dateTo, 'date_to');
        if ($dateToError) {
            errorResponse($dateToError, 400);
        }

        // Validar periodo
        $periodError = validateEnum($period, ['month', 'week'], 'period');
        if ($periodError) {
            errorResponse($periodError, 400);
        }

        // --- Resumen por categoria ---
        $stmtCategories = $db->prepare(
            "SELECT ec.id AS category_id, ec.name AS category_name, ec.icon, ec.color,
                    COALESCE(SUM(e.amount), 0) AS total_amount,
                    COUNT(e.id) AS total_count
             FROM expense_categories ec
             LEFT JOIN expenses e ON ec.id = e.expense_category_id
                 AND e.expense_date >= :date_from
                 AND e.expense_date <= :date_to
             WHERE ec.is_active = 1
             GROUP BY ec.id, ec.name, ec.icon, ec.color
             ORDER BY total_amount DESC"
        );
        $stmtCategories->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $categorySummary = $stmtCategories->fetchAll();

        // Convertir tipos numericos
        $overallTotal = 0;
        $overallCount = 0;
        foreach ($categorySummary as &$cat) {
            $cat['total_amount'] = (float)$cat['total_amount'];
            $cat['total_count'] = (int)$cat['total_count'];
            $overallTotal += $cat['total_amount'];
            $overallCount += $cat['total_count'];
        }
        unset($cat);

        // --- Datos agregados para grafico ---
        if ($period === 'week') {
            // Agrupado por dia
            $stmtChart = $db->prepare(
                "SELECT e.expense_date AS label,
                        SUM(e.amount) AS total
                 FROM expenses e
                 WHERE e.expense_date >= :date_from
                   AND e.expense_date <= :date_to
                 GROUP BY e.expense_date
                 ORDER BY e.expense_date ASC"
            );
        } else {
            // Agrupado por mes
            $stmtChart = $db->prepare(
                "SELECT DATE_FORMAT(e.expense_date, '%Y-%m') AS label,
                        SUM(e.amount) AS total
                 FROM expenses e
                 WHERE e.expense_date >= :date_from
                   AND e.expense_date <= :date_to
                 GROUP BY DATE_FORMAT(e.expense_date, '%Y-%m')
                 ORDER BY label ASC"
            );
        }
        $stmtChart->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $chartData = $stmtChart->fetchAll();

        // Convertir totales del grafico a float
        foreach ($chartData as &$point) {
            $point['total'] = (float)$point['total'];
        }
        unset($point);

        $summary = [
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'period'           => $period,
            'overall_total'    => $overallTotal,
            'overall_count'    => $overallCount,
            'by_category'      => $categorySummary,
            'chart_data'       => $chartData,
        ];

        successResponse($summary, 'Resumen de gastos obtenido exitosamente.');
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener un gasto por ID y devolver como successResponse
     */
    private static function _getExpenseById($db, $id, $message = null, $code = 200) {
        $stmt = $db->prepare(
            "SELECT e.id, e.expense_category_id, e.user_id,
                    e.description, e.amount, e.expense_date,
                    e.payment_method, e.receipt_number, e.notes, e.created_at,
                    ec.name AS category_name,
                    u.full_name AS user_name
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             LEFT JOIN users u ON e.user_id = u.id
             WHERE e.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $expense = $stmt->fetch();

        if (!$expense) {
            errorResponse('Gasto no encontrado.', 404);
        }

        successResponse($expense, $message, $code);
    }
}
