<?php
/**
 * BBR Fragance - API Router
 * Entry point for all API requests
 */

// Session config
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load dependencies
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/validation.php';
require_once __DIR__ . '/helpers/upload.php';
require_once __DIR__ . '/middleware/auth.php';

// Parse request
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/web-BBR_Fragance/api';
$path = rtrim(str_replace($basePath, '', $uri), '/');
$method = $_SERVER['REQUEST_METHOD'];

// Split path into segments
$segments = array_values(array_filter(explode('/', $path)));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;
$subId = $segments[3] ?? null;

// If $id is not numeric, treat it as action
if ($id !== null && !is_numeric($id)) {
    $action = $id;
    $id = null;
}

// If there's a 3rd segment and id is numeric
if (isset($segments[2])) {
    $action = $segments[2];
}
// 4th segment (e.g. /roles/vendedor/permissions)
if (isset($segments[3])) {
    $subId = $segments[3];
}

// Route to controller
try {
    switch ($resource) {
        // ===================== AUTH =====================
        case 'auth':
            require_once __DIR__ . '/controllers/AuthController.php';
            switch ($action) {
                case 'login':
                    if ($method === 'POST') AuthController::login();
                    break;
                case 'logout':
                    if ($method === 'POST') AuthController::logout();
                    break;
                case 'check':
                    if ($method === 'GET') AuthController::check();
                    break;
            }
            break;

        // ===================== DASHBOARD =====================
        case 'dashboard':
            require_once __DIR__ . '/controllers/DashboardController.php';
            requirePermission('dashboard.view');
            switch ($action) {
                case 'stats':
                    if ($method === 'GET') DashboardController::stats();
                    break;
                case 'recent-activity':
                    if ($method === 'GET') DashboardController::recentActivity();
                    break;
                case 'top-products':
                    if ($method === 'GET') DashboardController::topProducts();
                    break;
                case 'sales-chart':
                    if ($method === 'GET') DashboardController::salesChart();
                    break;
                default:
                    if ($method === 'GET' && !$action) DashboardController::stats();
                    break;
            }
            break;

        // ===================== PRODUCTS =====================
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            if ($id && $action === 'images') {
                requirePermission('products.edit');
                if ($method === 'POST') ProductController::uploadImage($id);
            } elseif ($id && $action === 'stock') {
                requirePermission('products.edit');
                if ($method === 'PUT') ProductController::updateStock($id);
            } elseif ($action === 'search') {
                if ($method === 'GET') ProductController::search();
            } elseif ($action === 'low-stock') {
                requirePermission('products.view');
                if ($method === 'GET') ProductController::lowStock();
            } elseif ($id) {
                switch ($method) {
                    case 'GET': ProductController::show($id); break;
                    case 'PUT': requirePermission('products.edit'); ProductController::update($id); break;
                    case 'POST': requirePermission('products.edit'); ProductController::update($id); break;
                    case 'DELETE': requirePermission('products.delete'); ProductController::destroy($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': ProductController::index(); break;
                    case 'POST': requirePermission('products.create'); ProductController::store(); break;
                }
            }
            break;

        // ===================== PRODUCT IMAGES =====================
        case 'product-images':
            require_once __DIR__ . '/controllers/ProductController.php';
            requirePermission('products.edit');
            if ($id && $method === 'DELETE') {
                ProductController::deleteImage($id);
            }
            break;

        // ===================== CATEGORIES =====================
        case 'categories':
            require_once __DIR__ . '/controllers/CategoryController.php';
            if ($method === 'GET') CategoryController::index();
            break;

        // ===================== FAMILIES =====================
        case 'families':
            require_once __DIR__ . '/controllers/CategoryController.php';
            if ($method === 'GET') CategoryController::families();
            break;

        // ===================== BRANDS =====================
        case 'brands':
            require_once __DIR__ . '/controllers/CategoryController.php';
            if ($id && $action === 'toggle') {
                requirePermission('products.edit');
                if ($method === 'PUT' || $method === 'POST') CategoryController::toggleBrand($id);
            } elseif ($action === 'all') {
                if ($method === 'GET') CategoryController::brandsAll();
            } elseif ($id) {
                switch ($method) {
                    case 'PUT':
                    case 'POST': requirePermission('products.edit'); CategoryController::updateBrand($id); break;
                    case 'DELETE': requirePermission('products.delete'); CategoryController::destroyBrand($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': CategoryController::brands(); break;
                    case 'POST': requirePermission('products.create'); CategoryController::storeBrand(); break;
                }
            }
            break;

        // ===================== SALES (POS) =====================
        case 'sales':
            require_once __DIR__ . '/controllers/SaleController.php';
            requirePermission('pos.access');
            if ($id && $action === 'cancel') {
                if ($method === 'POST') SaleController::cancel($id);
            } elseif ($id && $action === 'receipt') {
                if ($method === 'GET') SaleController::receipt($id);
            } elseif ($id) {
                if ($method === 'GET') SaleController::show($id);
            } else {
                switch ($method) {
                    case 'GET': SaleController::index(); break;
                    case 'POST': SaleController::store(); break;
                }
            }
            break;

        // ===================== ORDERS =====================
        case 'orders':
            require_once __DIR__ . '/controllers/OrderController.php';
            if ($id && $action === 'status') {
                requirePermission('orders.manage');
                if ($method === 'PUT' || $method === 'POST') OrderController::updateStatus($id);
            } elseif ($id) {
                switch ($method) {
                    case 'GET': requirePermission('orders.view'); OrderController::show($id); break;
                    case 'DELETE': requirePermission('orders.manage'); OrderController::destroy($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': requirePermission('orders.view'); OrderController::index(); break;
                    case 'POST': OrderController::store(); break; // Public - create order from front
                }
            }
            break;

        // ===================== EXPENSES =====================
        case 'expenses':
            require_once __DIR__ . '/controllers/ExpenseController.php';
            if ($action === 'summary') {
                requirePermission('expenses.view');
                if ($method === 'GET') ExpenseController::summary();
            } elseif ($id) {
                switch ($method) {
                    case 'GET': requirePermission('expenses.view'); ExpenseController::show($id); break;
                    case 'PUT': requirePermission('expenses.manage'); ExpenseController::update($id); break;
                    case 'POST': requirePermission('expenses.manage'); ExpenseController::update($id); break;
                    case 'DELETE': requirePermission('expenses.manage'); ExpenseController::destroy($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': requirePermission('expenses.view'); ExpenseController::index(); break;
                    case 'POST': requirePermission('expenses.manage'); ExpenseController::store(); break;
                }
            }
            break;

        // ===================== EXPENSE CATEGORIES =====================
        case 'expense-categories':
            require_once __DIR__ . '/controllers/ExpenseController.php';
            requirePermission('expenses.view');
            if ($method === 'GET') ExpenseController::categories();
            break;

        // ===================== CASH REGISTER =====================
        case 'cash-register':
            require_once __DIR__ . '/controllers/CashRegisterController.php';
            requirePermission('cash_register.access');
            switch ($action) {
                case 'open':
                    if ($method === 'POST') CashRegisterController::open();
                    break;
                case 'close':
                    requirePermission('cash_register.close');
                    if ($method === 'PUT' || $method === 'POST') CashRegisterController::close();
                    break;
                case 'current':
                    if ($method === 'GET') CashRegisterController::current();
                    break;
                case 'history':
                    if ($method === 'GET') CashRegisterController::history();
                    break;
                default:
                    if ($id && $method === 'GET') CashRegisterController::show($id);
                    elseif (!$action && $method === 'GET') CashRegisterController::current();
                    break;
            }
            break;

        // ===================== REPORTS =====================
        case 'reports':
            require_once __DIR__ . '/controllers/ReportController.php';
            requirePermission('reports.view');
            switch ($action) {
                case 'sales':
                    if ($method === 'GET') ReportController::sales();
                    break;
                case 'profit':
                    if ($method === 'GET') ReportController::profit();
                    break;
                case 'inventory':
                    if ($method === 'GET') ReportController::inventory();
                    break;
                case 'expenses':
                    if ($method === 'GET') ReportController::expenses();
                    break;
                case 'top-products':
                    if ($method === 'GET') ReportController::topProducts();
                    break;
                case 'summary':
                    if ($method === 'GET') ReportController::summary();
                    break;
            }
            break;

        // ===================== CUSTOMERS =====================
        case 'customers':
            require_once __DIR__ . '/controllers/CustomerController.php';
            if ($id) {
                switch ($method) {
                    case 'GET': requirePermission('customers.view'); CustomerController::show($id); break;
                    case 'PUT': requirePermission('customers.manage'); CustomerController::update($id); break;
                    case 'POST': requirePermission('customers.manage'); CustomerController::update($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': requirePermission('customers.view'); CustomerController::index(); break;
                    case 'POST': requirePermission('customers.manage'); CustomerController::store(); break;
                }
            }
            break;

        // ===================== USERS =====================
        case 'users':
            require_once __DIR__ . '/controllers/UserController.php';
            if ($id && $action === 'toggle-active') {
                requirePermission('users.manage');
                if ($method === 'PUT' || $method === 'POST') UserController::toggleActive($id);
            } elseif ($id && $action === 'reset-password') {
                requirePermission('users.manage');
                if ($method === 'PUT' || $method === 'POST') UserController::resetPassword($id);
            } elseif ($id) {
                switch ($method) {
                    case 'GET': requirePermission('users.view'); UserController::show($id); break;
                    case 'PUT': requirePermission('users.manage'); UserController::update($id); break;
                    case 'POST': requirePermission('users.manage'); UserController::update($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET': requirePermission('users.view'); UserController::index(); break;
                    case 'POST': requirePermission('users.manage'); UserController::store(); break;
                }
            }
            break;

        // ===================== ROLES =====================
        case 'roles':
            require_once __DIR__ . '/controllers/RoleController.php';
            requirePermission('roles.manage');
            // Special routing: /roles/permissions OR /roles/{roleName}/permissions
            // segments[1] can be 'permissions' or a role name like 'vendedor'
            $seg1 = $segments[1] ?? null;
            $seg2 = $segments[2] ?? null;
            if ($seg1 === 'permissions') {
                // GET /roles/permissions - all available permissions
                if ($method === 'GET') RoleController::getPermissions();
            } elseif ($seg1 && $seg2 === 'permissions') {
                // GET/PUT /roles/vendedor/permissions
                switch ($method) {
                    case 'GET': RoleController::getRolePermissions($seg1); break;
                    case 'PUT':
                    case 'POST': RoleController::updateRolePermissions($seg1); break;
                }
            } else {
                // GET /roles - list all roles
                if ($method === 'GET' && !$seg1) RoleController::index();
            }
            break;

        // ===================== SETTINGS =====================
        case 'settings':
            require_once __DIR__ . '/controllers/SettingsController.php';
            if ($action === 'promo-image') {
                requirePermission('settings.manage');
                if ($method === 'POST') SettingsController::uploadPromoImage();
            } else {
                switch ($method) {
                    case 'GET': SettingsController::index(); break;
                    case 'PUT':
                    case 'POST':
                        requirePermission('settings.manage');
                        SettingsController::update();
                        break;
                }
            }
            break;

        // ===================== NCF SEQUENCES =====================
        case 'ncf-sequences':
            require_once __DIR__ . '/controllers/NcfController.php';
            if ($action === 'status') {
                requirePermission('pos.access');
                if ($method === 'GET') NcfController::status();
            } elseif ($id) {
                requirePermission('ncf.manage');
                switch ($method) {
                    case 'GET': break;
                    case 'PUT':
                    case 'POST': NcfController::update($id); break;
                    case 'DELETE': NcfController::destroy($id); break;
                }
            } else {
                switch ($method) {
                    case 'GET':
                        requirePermission('ncf.manage');
                        NcfController::index();
                        break;
                    case 'POST':
                        requirePermission('ncf.manage');
                        NcfController::store();
                        break;
                }
            }
            break;

        // ===================== DEFAULT =====================
        default:
            if ($path === '' || $path === '/') {
                jsonResponse([
                    'success' => true,
                    'message' => 'BBR Fragance API v' . APP_VERSION,
                    'endpoints' => [
                        'auth' => API_URL . '/auth/login',
                        'products' => API_URL . '/products',
                        'categories' => API_URL . '/categories',
                        'sales' => API_URL . '/sales',
                        'dashboard' => API_URL . '/dashboard/stats',
                        'users' => API_URL . '/users',
                        'roles' => API_URL . '/roles',
                    ]
                ]);
            }
            break;
    }

    // If we get here, the route wasn't handled
    if ($resource === '') {
        errorResponse('Endpoint no encontrado.', 404);
    } else {
        errorResponse('Metodo no permitido o endpoint no encontrado.', 405);
    }

} catch (PDOException $e) {
    errorResponse('Error de base de datos.', 500);
} catch (Exception $e) {
    errorResponse('Error interno del servidor.', 500);
}
