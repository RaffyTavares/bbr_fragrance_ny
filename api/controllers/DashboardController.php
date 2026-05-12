<?php
/**
 * BBR Fragance - Dashboard Controller
 * Proporciona estadisticas, KPIs, actividad reciente y datos para graficos del panel
 */

class DashboardController {

    /**
     * GET /dashboard/stats
     * Indicadores clave de rendimiento (KPIs) del negocio
     */
    public static function stats() {
        $db = getDB();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        // --- Productos ---
        $stmtProducts = $db->prepare(
            "SELECT COUNT(*) FROM products WHERE status = 'active'"
        );
        $stmtProducts->execute();
        $totalProducts = (int)$stmtProducts->fetchColumn();

        $stmtLowStock = $db->prepare(
            "SELECT COUNT(*) FROM products WHERE stock <= min_stock AND status = 'active'"
        );
        $stmtLowStock->execute();
        $lowStockCount = (int)$stmtLowStock->fetchColumn();

        // Productos con stock bajo (top 5 para notificaciones)
        $stmtLowStockList = $db->prepare(
            "SELECT name, stock FROM products WHERE stock <= min_stock AND status = 'active' ORDER BY stock ASC LIMIT 5"
        );
        $stmtLowStockList->execute();
        $lowStockProducts = $stmtLowStockList->fetchAll();

        // --- Ventas de hoy ---
        $stmtTodaySales = $db->prepare(
            "SELECT COALESCE(SUM(total), 0) AS total_sum, COUNT(*) AS total_count
             FROM sales
             WHERE status = 'completed'
               AND DATE(created_at) = :today"
        );
        $stmtTodaySales->execute([':today' => $today]);
        $todaySalesRow = $stmtTodaySales->fetch();

        // --- Ventas del mes ---
        $stmtMonthSales = $db->prepare(
            "SELECT COALESCE(SUM(total), 0) AS total_sum, COUNT(*) AS total_count
             FROM sales
             WHERE status = 'completed'
               AND DATE(created_at) >= :month_start
               AND DATE(created_at) <= :today"
        );
        $stmtMonthSales->execute([
            ':month_start' => $monthStart,
            ':today'       => $today,
        ]);
        $monthSalesRow = $stmtMonthSales->fetch();

        // --- Pedidos pendientes ---
        $stmtPendingOrders = $db->prepare(
            "SELECT COUNT(*) FROM orders WHERE status = 'pending'"
        );
        $stmtPendingOrders->execute();
        $pendingOrders = (int)$stmtPendingOrders->fetchColumn();

        // --- Total de clientes ---
        $stmtCustomers = $db->prepare(
            "SELECT COUNT(*) FROM customers"
        );
        $stmtCustomers->execute();
        $totalCustomers = (int)$stmtCustomers->fetchColumn();

        // --- Gastos del mes ---
        $stmtMonthExpenses = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses
             WHERE expense_date >= :month_start
               AND expense_date <= :today"
        );
        $stmtMonthExpenses->execute([
            ':month_start' => $monthStart,
            ':today'       => $today,
        ]);
        $monthExpenses = (float)$stmtMonthExpenses->fetchColumn();

        // --- Gastos de hoy ---
        $stmtTodayExpenses = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM expenses
             WHERE expense_date = :today"
        );
        $stmtTodayExpenses->execute([':today' => $today]);
        $todayExpenses = (float)$stmtTodayExpenses->fetchColumn();

        $data = [
            'total_products'    => $totalProducts,
            'low_stock_count'   => $lowStockCount,
            'low_stock_products' => $lowStockProducts,
            'today_sales'       => (float)$todaySalesRow['total_sum'],
            'today_sales_count' => (int)$todaySalesRow['total_count'],
            'month_sales'       => (float)$monthSalesRow['total_sum'],
            'month_sales_count' => (int)$monthSalesRow['total_count'],
            'pending_orders'    => $pendingOrders,
            'total_customers'   => $totalCustomers,
            'month_expenses'    => $monthExpenses,
            'today_expenses'    => $todayExpenses,
        ];

        successResponse($data, 'Estadisticas del dashboard obtenidas exitosamente.');
    }

    /**
     * GET /dashboard/recent-activity
     * Ultimas 15 entradas del registro de actividad
     */
    public static function recentActivity() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT al.id, al.action, al.entity_type, al.entity_id,
                    al.description, al.created_at,
                    u.full_name AS user_name
             FROM activity_log al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT 15"
        );
        $stmt->execute();
        $activities = $stmt->fetchAll();

        // Mapear acciones a etiquetas legibles en espanol
        $actionLabels = [
            'create'  => 'Creacion',
            'update'  => 'Actualizacion',
            'delete'  => 'Eliminacion',
            'cancel'  => 'Cancelacion',
            'login'   => 'Inicio de sesion',
            'logout'  => 'Cierre de sesion',
            'export'  => 'Exportacion',
            'import'  => 'Importacion',
            'approve' => 'Aprobacion',
            'reject'  => 'Rechazo',
            'restore' => 'Restauracion',
            'refund'  => 'Reembolso',
        ];

        foreach ($activities as &$activity) {
            $activity['action_label'] = $actionLabels[$activity['action']] ?? ucfirst($activity['action']);
        }
        unset($activity);

        successResponse($activities, 'Actividad reciente obtenida exitosamente.');
    }

    /**
     * GET /dashboard/top-products
     * Top 5 productos mas vendidos en los ultimos 30 dias
     */
    public static function topProducts() {
        $db = getDB();

        $dateFrom = date('Y-m-d', strtotime('-30 days'));

        $stmt = $db->prepare(
            "SELECT si.product_name,
                    si.product_brand AS brand,
                    SUM(si.quantity) AS total_quantity,
                    SUM(si.subtotal) AS total_revenue
             FROM sale_items si
             INNER JOIN sales s ON si.sale_id = s.id
             WHERE s.status = 'completed'
               AND DATE(s.created_at) >= :date_from
             GROUP BY si.product_name, si.product_brand
             ORDER BY total_quantity DESC
             LIMIT 5"
        );
        $stmt->execute([':date_from' => $dateFrom]);
        $products = $stmt->fetchAll();

        // Convertir tipos numericos
        foreach ($products as &$product) {
            $product['total_quantity'] = (int)$product['total_quantity'];
            $product['total_revenue']  = (float)$product['total_revenue'];
        }
        unset($product);

        successResponse($products, 'Productos mas vendidos obtenidos exitosamente.');
    }

    /**
     * GET /dashboard/sales-chart
     * Totales de ventas diarias para los ultimos N dias (por defecto 7)
     * Query param: days (int, default 7)
     */
    public static function salesChart() {
        $db = getDB();

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
        if ($days < 1 || $days > 365) {
            errorResponse('El parametro "days" debe estar entre 1 y 365.', 400);
        }

        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $today = date('Y-m-d');

        // Obtener ventas agrupadas por dia
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS sale_date,
                    COALESCE(SUM(total), 0) AS total,
                    COUNT(*) AS count
             FROM sales
             WHERE status = 'completed'
               AND DATE(created_at) >= :date_from
               AND DATE(created_at) <= :today
             GROUP BY DATE(created_at)
             ORDER BY sale_date ASC"
        );
        $stmt->execute([
            ':date_from' => $dateFrom,
            ':today'     => $today,
        ]);
        $salesByDay = $stmt->fetchAll();

        // Indexar resultados por fecha para acceso rapido
        $salesMap = [];
        foreach ($salesByDay as $row) {
            $salesMap[$row['sale_date']] = [
                'total' => (float)$row['total'],
                'count' => (int)$row['count'],
            ];
        }

        // Generar arreglo completo con ceros para dias sin ventas
        $chartData = [];
        $currentDate = new DateTime($dateFrom);
        $endDate = new DateTime($today);

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $chartData[] = [
                'date'  => $dateStr,
                'total' => isset($salesMap[$dateStr]) ? $salesMap[$dateStr]['total'] : 0.0,
                'count' => isset($salesMap[$dateStr]) ? $salesMap[$dateStr]['count'] : 0,
            ];
            $currentDate->modify('+1 day');
        }

        successResponse($chartData, 'Datos del grafico de ventas obtenidos exitosamente.');
    }
}
