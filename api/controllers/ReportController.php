<?php
/**
 * BBR Fragance - Report Controller
 * Genera reportes de ventas, ganancias, inventario, gastos y resumen general
 */

class ReportController {

    // =========================================================================
    // 1. Reporte de Ventas
    // =========================================================================

    /**
     * GET /reports/sales
     * Reporte de ventas agrupado por fecha, producto, categoria o metodo de pago
     * Query params: date_from, date_to, group_by (date|product|category|payment_method)
     */
    public static function sales() {
        $db = getDB();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo   = $_GET['date_to']   ?? date('Y-m-d');
        $groupBy  = $_GET['group_by']  ?? 'date';

        $validGroups = ['date', 'product', 'category', 'payment_method'];
        if (!in_array($groupBy, $validGroups)) {
            errorResponse('Agrupacion no valida. Opciones: date, product, category, payment_method.', 400);
        }

        // --- Resumen general (solo ventas completadas) ---
        $stmtSummary = $db->prepare(
            "SELECT COALESCE(SUM(s.total), 0) AS total_revenue,
                    COUNT(s.id) AS total_sales_count,
                    COALESCE(SUM(s.discount_amount), 0) AS total_discount,
                    COALESCE(SUM(s.tax_amount), 0) AS total_tax,
                    COALESCE(AVG(s.total), 0) AS average_sale
             FROM sales s
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to"
        );
        $stmtSummary->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $summary = $stmtSummary->fetch();

        // Total de items vendidos
        $stmtItems = $db->prepare(
            "SELECT COALESCE(SUM(si.quantity), 0) AS total_items_sold
             FROM sale_items si
             INNER JOIN sales s ON si.sale_id = s.id
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to"
        );
        $stmtItems->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $itemsRow = $stmtItems->fetch();

        $overallSummary = [
            'total_revenue'     => (float)$summary['total_revenue'],
            'total_sales_count' => (int)$summary['total_sales_count'],
            'total_items_sold'  => (int)$itemsRow['total_items_sold'],
            'total_discount'    => (float)$summary['total_discount'],
            'total_tax'         => (float)$summary['total_tax'],
            'average_sale'      => round((float)$summary['average_sale'], 2),
        ];

        // --- Datos agrupados ---
        $grouped = [];

        switch ($groupBy) {
            case 'date':
                $stmtGroup = $db->prepare(
                    "SELECT DATE(s.created_at) AS date,
                            COALESCE(SUM(s.total), 0) AS total_sales,
                            COUNT(s.id) AS total_count,
                            COALESCE(SUM(s.discount_amount), 0) AS total_discount,
                            COALESCE(SUM(s.tax_amount), 0) AS total_tax
                     FROM sales s
                     WHERE s.status = 'completed'
                       AND DATE(s.created_at) >= :date_from
                       AND DATE(s.created_at) <= :date_to
                     GROUP BY DATE(s.created_at)
                     ORDER BY DATE(s.created_at) ASC"
                );
                $stmtGroup->execute([
                    ':date_from' => $dateFrom,
                    ':date_to'   => $dateTo,
                ]);
                $rows = $stmtGroup->fetchAll();
                foreach ($rows as &$row) {
                    $row['total_sales']    = (float)$row['total_sales'];
                    $row['total_count']    = (int)$row['total_count'];
                    $row['total_discount'] = (float)$row['total_discount'];
                    $row['total_tax']      = (float)$row['total_tax'];
                }
                unset($row);
                $grouped = $rows;
                break;

            case 'product':
                $stmtGroup = $db->prepare(
                    "SELECT si.product_name,
                            si.product_brand AS brand,
                            COALESCE(SUM(si.quantity), 0) AS total_quantity,
                            COALESCE(SUM(si.subtotal), 0) AS total_revenue
                     FROM sale_items si
                     INNER JOIN sales s ON si.sale_id = s.id
                     WHERE s.status = 'completed'
                       AND DATE(s.created_at) >= :date_from
                       AND DATE(s.created_at) <= :date_to
                     GROUP BY si.product_name, si.product_brand
                     ORDER BY total_revenue DESC"
                );
                $stmtGroup->execute([
                    ':date_from' => $dateFrom,
                    ':date_to'   => $dateTo,
                ]);
                $rows = $stmtGroup->fetchAll();
                foreach ($rows as &$row) {
                    $row['total_quantity'] = (int)$row['total_quantity'];
                    $row['total_revenue']  = (float)$row['total_revenue'];
                }
                unset($row);
                $grouped = $rows;
                break;

            case 'category':
                $stmtGroup = $db->prepare(
                    "SELECT COALESCE(cat.name, 'Sin categoria') AS category_name,
                            COALESCE(SUM(si.quantity), 0) AS total_quantity,
                            COALESCE(SUM(si.subtotal), 0) AS total_revenue
                     FROM sale_items si
                     INNER JOIN sales s ON si.sale_id = s.id
                     LEFT JOIN products p ON si.product_id = p.id
                     LEFT JOIN categories cat ON p.category_id = cat.id
                     WHERE s.status = 'completed'
                       AND DATE(s.created_at) >= :date_from
                       AND DATE(s.created_at) <= :date_to
                     GROUP BY cat.id, cat.name
                     ORDER BY total_revenue DESC"
                );
                $stmtGroup->execute([
                    ':date_from' => $dateFrom,
                    ':date_to'   => $dateTo,
                ]);
                $rows = $stmtGroup->fetchAll();
                foreach ($rows as &$row) {
                    $row['total_quantity'] = (int)$row['total_quantity'];
                    $row['total_revenue']  = (float)$row['total_revenue'];
                }
                unset($row);
                $grouped = $rows;
                break;

            case 'payment_method':
                $stmtGroup = $db->prepare(
                    "SELECT s.payment_method AS method,
                            COALESCE(SUM(s.total), 0) AS total_amount,
                            COUNT(s.id) AS count
                     FROM sales s
                     WHERE s.status = 'completed'
                       AND DATE(s.created_at) >= :date_from
                       AND DATE(s.created_at) <= :date_to
                     GROUP BY s.payment_method
                     ORDER BY total_amount DESC"
                );
                $stmtGroup->execute([
                    ':date_from' => $dateFrom,
                    ':date_to'   => $dateTo,
                ]);
                $rows = $stmtGroup->fetchAll();
                foreach ($rows as &$row) {
                    $row['total_amount'] = (float)$row['total_amount'];
                    $row['count']        = (int)$row['count'];
                }
                unset($row);
                $grouped = $rows;
                break;
        }

        $report = [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'group_by'  => $groupBy,
            'summary'   => $overallSummary,
            'data'      => $grouped,
        ];

        successResponse($report, 'Reporte de ventas generado exitosamente.');
    }

    // =========================================================================
    // 2. Reporte de Ganancias
    // =========================================================================

    /**
     * GET /reports/profit
     * Reporte de ganancias con ingresos, costos, gastos y utilidad neta
     * Query params: date_from, date_to
     */
    public static function profit() {
        $db = getDB();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo   = $_GET['date_to']   ?? date('Y-m-d');

        // --- Ingresos totales (ventas completadas) ---
        $stmtRevenue = $db->prepare(
            "SELECT COALESCE(SUM(s.total), 0) AS total_revenue
             FROM sales s
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to"
        );
        $stmtRevenue->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $totalRevenue = (float)$stmtRevenue->fetchColumn();

        // --- Costo total de productos vendidos ---
        $stmtCost = $db->prepare(
            "SELECT COALESCE(SUM(si.unit_cost * si.quantity), 0) AS total_cost
             FROM sale_items si
             INNER JOIN sales s ON si.sale_id = s.id
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to"
        );
        $stmtCost->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $totalCost = (float)$stmtCost->fetchColumn();

        $grossProfit = $totalRevenue - $totalCost;

        // --- Gastos totales en el rango ---
        $stmtExpenses = $db->prepare(
            "SELECT COALESCE(SUM(e.amount), 0) AS total_expenses
             FROM expenses e
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to"
        );
        $stmtExpenses->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $totalExpenses = (float)$stmtExpenses->fetchColumn();

        $netProfit    = $grossProfit - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        // --- Desglose diario para grafico ---
        $stmtDaily = $db->prepare(
            "SELECT dates.date,
                    COALESCE(rev.revenue, 0) AS revenue,
                    COALESCE(rev.cost, 0) AS cost,
                    COALESCE(exp.expenses, 0) AS expenses,
                    (COALESCE(rev.revenue, 0) - COALESCE(rev.cost, 0) - COALESCE(exp.expenses, 0)) AS profit
             FROM (
                 SELECT DATE(s.created_at) AS date FROM sales s
                 WHERE s.status = 'completed'
                   AND DATE(s.created_at) >= :df1
                   AND DATE(s.created_at) <= :dt1
                 UNION
                 SELECT e.expense_date AS date FROM expenses e
                 WHERE e.expense_date >= :df2
                   AND e.expense_date <= :dt2
             ) dates
             LEFT JOIN (
                 SELECT DATE(s.created_at) AS date,
                        SUM(s.total) AS revenue,
                        SUM(si_agg.total_cost) AS cost
                 FROM sales s
                 LEFT JOIN (
                     SELECT sale_id, SUM(unit_cost * quantity) AS total_cost
                     FROM sale_items
                     GROUP BY sale_id
                 ) si_agg ON s.id = si_agg.sale_id
                 WHERE s.status = 'completed'
                   AND DATE(s.created_at) >= :df3
                   AND DATE(s.created_at) <= :dt3
                 GROUP BY DATE(s.created_at)
             ) rev ON dates.date = rev.date
             LEFT JOIN (
                 SELECT expense_date AS date, SUM(amount) AS expenses
                 FROM expenses
                 WHERE expense_date >= :df4
                   AND expense_date <= :dt4
                 GROUP BY expense_date
             ) exp ON dates.date = exp.date
             ORDER BY dates.date ASC"
        );
        $stmtDaily->execute([
            ':df1' => $dateFrom, ':dt1' => $dateTo,
            ':df2' => $dateFrom, ':dt2' => $dateTo,
            ':df3' => $dateFrom, ':dt3' => $dateTo,
            ':df4' => $dateFrom, ':dt4' => $dateTo,
        ]);
        $dailyBreakdown = $stmtDaily->fetchAll();

        foreach ($dailyBreakdown as &$day) {
            $day['revenue']  = (float)$day['revenue'];
            $day['cost']     = (float)$day['cost'];
            $day['expenses'] = (float)$day['expenses'];
            $day['profit']   = (float)$day['profit'];
        }
        unset($day);

        $report = [
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'total_revenue'  => $totalRevenue,
            'total_cost'     => $totalCost,
            'gross_profit'   => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $netProfit,
            'profit_margin'  => $profitMargin,
            'daily_breakdown' => $dailyBreakdown,
        ];

        successResponse($report, 'Reporte de ganancias generado exitosamente.');
    }

    // =========================================================================
    // 3. Reporte de Inventario
    // =========================================================================

    /**
     * GET /reports/inventory
     * Reporte de inventario actual con valoracion y productos con bajo stock
     */
    public static function inventory() {
        $db = getDB();

        // --- Totales generales (productos activos) ---
        $stmtTotals = $db->prepare(
            "SELECT COUNT(p.id) AS total_products,
                    COALESCE(SUM(p.stock), 0) AS total_units,
                    COALESCE(SUM(p.stock * p.price), 0) AS retail_value,
                    COALESCE(SUM(CASE WHEN p.cost IS NOT NULL AND p.cost > 0 THEN p.stock * p.cost ELSE 0 END), 0) AS cost_value
             FROM products p
             WHERE p.status = 'active'"
        );
        $stmtTotals->execute();
        $totals = $stmtTotals->fetch();

        $retailValue     = (float)$totals['retail_value'];
        $costValue       = (float)$totals['cost_value'];
        $potentialProfit  = $retailValue - $costValue;

        // --- Agrupado por categoria ---
        $stmtByCategory = $db->prepare(
            "SELECT COALESCE(cat.name, 'Sin categoria') AS category_name,
                    COUNT(p.id) AS total_products,
                    COALESCE(SUM(p.stock), 0) AS total_units,
                    COALESCE(SUM(p.stock * p.price), 0) AS retail_value
             FROM products p
             LEFT JOIN categories cat ON p.category_id = cat.id
             WHERE p.status = 'active'
             GROUP BY cat.id, cat.name
             ORDER BY retail_value DESC"
        );
        $stmtByCategory->execute();
        $byCategory = $stmtByCategory->fetchAll();

        foreach ($byCategory as &$cat) {
            $cat['total_products'] = (int)$cat['total_products'];
            $cat['total_units']    = (int)$cat['total_units'];
            $cat['retail_value']   = (float)$cat['retail_value'];
        }
        unset($cat);

        // --- Productos con bajo stock (stock <= min_stock) ---
        $stmtLowStock = $db->prepare(
            "SELECT p.id, p.name, p.stock, p.min_stock, p.price, p.sku,
                    b.name AS brand_name,
                    cat.name AS category_name
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             WHERE p.status = 'active'
               AND p.stock <= p.min_stock
             ORDER BY (p.stock - p.min_stock) ASC, p.name ASC"
        );
        $stmtLowStock->execute();
        $lowStock = $stmtLowStock->fetchAll();

        foreach ($lowStock as &$item) {
            $item['stock']     = (int)$item['stock'];
            $item['min_stock'] = (int)$item['min_stock'];
            $item['price']     = (float)$item['price'];
        }
        unset($item);

        $report = [
            'total_products'   => (int)$totals['total_products'],
            'total_units'      => (int)$totals['total_units'],
            'retail_value'     => $retailValue,
            'cost_value'       => $costValue,
            'potential_profit'  => $potentialProfit,
            'by_category'      => $byCategory,
            'low_stock'        => $lowStock,
        ];

        successResponse($report, 'Reporte de inventario generado exitosamente.');
    }

    // =========================================================================
    // 4. Reporte de Gastos
    // =========================================================================

    /**
     * GET /reports/expenses
     * Reporte de gastos agrupado por categoria, metodo de pago y fecha
     * Query params: date_from, date_to
     */
    public static function expenses() {
        $db = getDB();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo   = $_GET['date_to']   ?? date('Y-m-d');

        // --- Total general ---
        $stmtTotal = $db->prepare(
            "SELECT COALESCE(SUM(e.amount), 0) AS total_amount,
                    COUNT(e.id) AS total_count
             FROM expenses e
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to"
        );
        $stmtTotal->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $overall = $stmtTotal->fetch();

        $totalAmount = (float)$overall['total_amount'];
        $totalCount  = (int)$overall['total_count'];

        // Calcular promedio diario
        $daysDiff = max(1, (int)((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
        $dailyAverage = round($totalAmount / $daysDiff, 2);

        // --- Por categoria ---
        $stmtByCategory = $db->prepare(
            "SELECT COALESCE(ec.name, 'Sin categoria') AS category_name,
                    COALESCE(SUM(e.amount), 0) AS total_amount,
                    COUNT(e.id) AS count
             FROM expenses e
             LEFT JOIN expense_categories ec ON e.expense_category_id = ec.id
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to
             GROUP BY ec.id, ec.name
             ORDER BY total_amount DESC"
        );
        $stmtByCategory->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $byCategory = $stmtByCategory->fetchAll();

        foreach ($byCategory as &$cat) {
            $cat['total_amount'] = (float)$cat['total_amount'];
            $cat['count']        = (int)$cat['count'];
            $cat['percentage']   = $totalAmount > 0 ? round(($cat['total_amount'] / $totalAmount) * 100, 2) : 0;
        }
        unset($cat);

        // --- Por metodo de pago ---
        $stmtByPayment = $db->prepare(
            "SELECT e.payment_method,
                    COALESCE(SUM(e.amount), 0) AS total_amount,
                    COUNT(e.id) AS count
             FROM expenses e
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to
             GROUP BY e.payment_method
             ORDER BY total_amount DESC"
        );
        $stmtByPayment->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $byPaymentMethod = $stmtByPayment->fetchAll();

        foreach ($byPaymentMethod as &$pm) {
            $pm['total_amount'] = (float)$pm['total_amount'];
            $pm['count']        = (int)$pm['count'];
        }
        unset($pm);

        // --- Por fecha (diario) ---
        $stmtByDate = $db->prepare(
            "SELECT e.expense_date AS date,
                    COALESCE(SUM(e.amount), 0) AS total_amount,
                    COUNT(e.id) AS count
             FROM expenses e
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to
             GROUP BY e.expense_date
             ORDER BY e.expense_date ASC"
        );
        $stmtByDate->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $byDate = $stmtByDate->fetchAll();

        foreach ($byDate as &$d) {
            $d['total_amount'] = (float)$d['total_amount'];
            $d['count']        = (int)$d['count'];
        }
        unset($d);

        $report = [
            'date_from'         => $dateFrom,
            'date_to'           => $dateTo,
            'overall'           => [
                'total_amount'  => $totalAmount,
                'total_count'   => $totalCount,
                'daily_average' => $dailyAverage,
            ],
            'by_category'       => $byCategory,
            'by_payment_method' => $byPaymentMethod,
            'by_date'           => $byDate,
        ];

        successResponse($report, 'Reporte de gastos generado exitosamente.');
    }

    // =========================================================================
    // 5. Productos mas vendidos
    // =========================================================================

    /**
     * GET /reports/top-products
     * Productos mas vendidos por cantidad y por ingresos
     * Query params: date_from, date_to, limit
     */
    public static function topProducts() {
        $db = getDB();

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo   = $_GET['date_to']   ?? date('Y-m-d');
        $limit    = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        // --- Top por cantidad vendida ---
        $stmtByQuantity = $db->prepare(
            "SELECT si.product_id,
                    si.product_name,
                    si.product_brand AS brand,
                    COALESCE(SUM(si.quantity), 0) AS total_quantity,
                    COALESCE(SUM(si.subtotal), 0) AS total_revenue
             FROM sale_items si
             INNER JOIN sales s ON si.sale_id = s.id
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to
             GROUP BY si.product_id, si.product_name, si.product_brand
             ORDER BY total_quantity DESC
             LIMIT :lim"
        );
        $stmtByQuantity->bindValue(':date_from', $dateFrom);
        $stmtByQuantity->bindValue(':date_to', $dateTo);
        $stmtByQuantity->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtByQuantity->execute();
        $byQuantity = $stmtByQuantity->fetchAll();

        foreach ($byQuantity as &$row) {
            $row['product_id']     = (int)$row['product_id'];
            $row['total_quantity'] = (int)$row['total_quantity'];
            $row['total_revenue']  = (float)$row['total_revenue'];
        }
        unset($row);

        // --- Top por ingresos ---
        $stmtByRevenue = $db->prepare(
            "SELECT si.product_id,
                    si.product_name,
                    si.product_brand AS brand,
                    COALESCE(SUM(si.quantity), 0) AS total_quantity,
                    COALESCE(SUM(si.subtotal), 0) AS total_revenue
             FROM sale_items si
             INNER JOIN sales s ON si.sale_id = s.id
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to
             GROUP BY si.product_id, si.product_name, si.product_brand
             ORDER BY total_revenue DESC
             LIMIT :lim"
        );
        $stmtByRevenue->bindValue(':date_from', $dateFrom);
        $stmtByRevenue->bindValue(':date_to', $dateTo);
        $stmtByRevenue->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtByRevenue->execute();
        $byRevenue = $stmtByRevenue->fetchAll();

        foreach ($byRevenue as &$row) {
            $row['product_id']     = (int)$row['product_id'];
            $row['total_quantity'] = (int)$row['total_quantity'];
            $row['total_revenue']  = (float)$row['total_revenue'];
        }
        unset($row);

        $report = [
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'limit'       => $limit,
            'by_quantity'  => $byQuantity,
            'by_revenue'   => $byRevenue,
        ];

        successResponse($report, 'Reporte de productos mas vendidos generado exitosamente.');
    }

    // =========================================================================
    // 6. Resumen rapido (Dashboard cards)
    // =========================================================================

    /**
     * GET /reports/summary
     * Tarjetas de resumen: hoy, esta semana, este mes, con comparacion vs periodo anterior
     */
    public static function summary() {
        $db = getDB();

        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Semana actual (lunes a domingo)
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));

        // Semana anterior
        $prevWeekStart = date('Y-m-d', strtotime('monday last week'));
        $prevWeekEnd   = date('Y-m-d', strtotime('sunday last week'));

        // Mes actual
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        // Mes anterior
        $prevMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $prevMonthEnd   = date('Y-m-t', strtotime('last day of last month'));

        // --- Obtener datos de cada periodo ---
        $todayData     = self::_getPeriodSummary($db, $today, $today);
        $yesterdayData = self::_getPeriodSummary($db, $yesterday, $yesterday);

        $weekData     = self::_getPeriodSummary($db, $weekStart, $weekEnd);
        $prevWeekData = self::_getPeriodSummary($db, $prevWeekStart, $prevWeekEnd);

        $monthData     = self::_getPeriodSummary($db, $monthStart, $monthEnd);
        $prevMonthData = self::_getPeriodSummary($db, $prevMonthStart, $prevMonthEnd);

        $report = [
            'today' => [
                'sales_total'    => $todayData['sales_total'],
                'sales_count'    => $todayData['sales_count'],
                'expenses_total' => $todayData['expenses_total'],
                'comparison'     => [
                    'sales_change'    => self::_percentChange($todayData['sales_total'], $yesterdayData['sales_total']),
                    'expenses_change' => self::_percentChange($todayData['expenses_total'], $yesterdayData['expenses_total']),
                    'compared_to'     => 'ayer',
                ],
            ],
            'this_week' => [
                'sales_total'    => $weekData['sales_total'],
                'sales_count'    => $weekData['sales_count'],
                'expenses_total' => $weekData['expenses_total'],
                'comparison'     => [
                    'sales_change'    => self::_percentChange($weekData['sales_total'], $prevWeekData['sales_total']),
                    'expenses_change' => self::_percentChange($weekData['expenses_total'], $prevWeekData['expenses_total']),
                    'compared_to'     => 'semana anterior',
                ],
            ],
            'this_month' => [
                'sales_total'    => $monthData['sales_total'],
                'sales_count'    => $monthData['sales_count'],
                'expenses_total' => $monthData['expenses_total'],
                'comparison'     => [
                    'sales_change'    => self::_percentChange($monthData['sales_total'], $prevMonthData['sales_total']),
                    'expenses_change' => self::_percentChange($monthData['expenses_total'], $prevMonthData['expenses_total']),
                    'compared_to'     => 'mes anterior',
                ],
            ],
        ];

        successResponse($report, 'Resumen generado exitosamente.');
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener totales de ventas y gastos para un rango de fechas
     */
    private static function _getPeriodSummary($db, $dateFrom, $dateTo) {
        // Ventas completadas
        $stmtSales = $db->prepare(
            "SELECT COALESCE(SUM(s.total), 0) AS sales_total,
                    COUNT(s.id) AS sales_count
             FROM sales s
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
               AND DATE(s.created_at) <= :date_to"
        );
        $stmtSales->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $salesRow = $stmtSales->fetch();

        // Gastos
        $stmtExpenses = $db->prepare(
            "SELECT COALESCE(SUM(e.amount), 0) AS expenses_total
             FROM expenses e
             WHERE e.expense_date >= :date_from
               AND e.expense_date <= :date_to"
        );
        $stmtExpenses->execute([
            ':date_from' => $dateFrom,
            ':date_to'   => $dateTo,
        ]);
        $expensesRow = $stmtExpenses->fetch();

        return [
            'sales_total'    => (float)$salesRow['sales_total'],
            'sales_count'    => (int)$salesRow['sales_count'],
            'expenses_total' => (float)$expensesRow['expenses_total'],
        ];
    }

    /**
     * Calcular el cambio porcentual entre el valor actual y el anterior
     */
    private static function _percentChange($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }
}
