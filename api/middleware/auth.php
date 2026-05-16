<?php
/**
 * BBR Fragrance - Auth Middleware
 */

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        errorResponse('No autorizado. Inicie sesion.', 401);
    }
}

function requireAdmin() {
    requireAuth();
    if ($_SESSION['role'] !== 'admin') {
        errorResponse('Acceso denegado. Se requieren permisos de administrador.', 403);
    }
}

/**
 * Verificar si el rol del usuario actual tiene un permiso especifico.
 * Consulta la tabla role_permissions.
 */
function requirePermission($permissionKey) {
    requireAuth();
    $role = $_SESSION['role'] ?? '';
    // Admin siempre tiene todos los permisos
    if ($role === 'admin') return;

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM role_permissions WHERE role = :role AND permission_key = :perm"
    );
    $stmt->execute([':role' => $role, ':perm' => $permissionKey]);
    if ((int)$stmt->fetchColumn() === 0) {
        errorResponse('Acceso denegado. No tiene permiso: ' . $permissionKey, 403);
    }
}

/**
 * Verificar si el rol del usuario actual tiene al menos uno de los permisos dados.
 */
function requireAnyPermission(array $permissionKeys) {
    requireAuth();
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') return;

    $db = getDB();
    $placeholders = [];
    $params = [':role' => $role];
    foreach ($permissionKeys as $i => $key) {
        $placeholders[] = ":perm{$i}";
        $params[":perm{$i}"] = $key;
    }
    $in = implode(',', $placeholders);
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM role_permissions WHERE role = :role AND permission_key IN ({$in})"
    );
    $stmt->execute($params);
    if ((int)$stmt->fetchColumn() === 0) {
        errorResponse('Acceso denegado. Permisos insuficientes.', 403);
    }
}

/**
 * Obtener todos los permisos del rol del usuario actual.
 * Retorna array de permission_key strings.
 */
function getUserPermissions() {
    if (!isset($_SESSION['user_id'])) return [];
    $role = $_SESSION['role'] ?? '';

    // Admin tiene todos
    if ($role === 'admin') {
        $db = getDB();
        $stmt = $db->query("SELECT permission_key FROM permissions ORDER BY sort_order");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT permission_key FROM role_permissions WHERE role = :role");
    $stmt->execute([':role' => $role]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
}

function logActivity($action, $entityType = null, $entityId = null, $description = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip)"
        );
        $stmt->execute([
            ':user_id' => getCurrentUserId(),
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':description' => $description,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        // Don't fail the main request if logging fails
    }
}
