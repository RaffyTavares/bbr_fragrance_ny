<?php
/**
 * BBR Fragrance - Role Controller
 * Gestion de permisos por rol
 */

class RoleController {

    /**
     * GET /roles
     * Listar todos los roles con sus permisos
     */
    public static function index() {
        $db = getDB();

        $roles = ['admin', 'vendedor', 'cajero', 'tecnico'];
        $roleLabels = [
            'admin' => 'Administrador',
            'vendedor' => 'Vendedor',
            'cajero' => 'Cajero',
            'tecnico' => 'Tecnico'
        ];

        $result = [];
        foreach ($roles as $role) {
            $stmt = $db->prepare(
                "SELECT rp.permission_key
                 FROM role_permissions rp
                 WHERE rp.role = :role"
            );
            $stmt->execute([':role' => $role]);
            $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Count users with this role
            $countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = :role");
            $countStmt->execute([':role' => $role]);

            $result[] = [
                'role' => $role,
                'label' => $roleLabels[$role] ?? $role,
                'permissions' => $perms,
                'user_count' => (int)$countStmt->fetchColumn(),
                'is_system' => ($role === 'admin') // admin role cannot be modified
            ];
        }

        successResponse($result);
    }

    /**
     * GET /roles/permissions
     * Listar todos los permisos disponibles agrupados por modulo
     */
    public static function getPermissions() {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM permissions ORDER BY sort_order, module");
        $all = $stmt->fetchAll();

        // Agrupar por modulo
        $grouped = [];
        foreach ($all as $perm) {
            $module = $perm['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = [
                'key' => $perm['permission_key'],
                'name' => $perm['name'],
                'description' => $perm['description']
            ];
        }

        successResponse([
            'permissions' => $all,
            'grouped' => $grouped
        ]);
    }

    /**
     * GET /roles/{role}/permissions
     * Obtener permisos de un rol especifico
     */
    public static function getRolePermissions($role) {
        $validRoles = ['admin', 'vendedor', 'cajero', 'tecnico'];
        if (!in_array($role, $validRoles)) {
            errorResponse('Rol no valido.', 400);
        }

        $db = getDB();

        // All permissions
        $allStmt = $db->query("SELECT * FROM permissions ORDER BY sort_order");
        $allPerms = $allStmt->fetchAll();

        // Role's permissions
        $roleStmt = $db->prepare("SELECT permission_key FROM role_permissions WHERE role = :role");
        $roleStmt->execute([':role' => $role]);
        $rolePerms = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

        successResponse([
            'role' => $role,
            'all_permissions' => $allPerms,
            'granted' => $rolePerms
        ]);
    }

    /**
     * PUT /roles/{role}/permissions
     * Actualizar permisos de un rol
     * Body: { permissions: ['perm1', 'perm2', ...] }
     */
    public static function updateRolePermissions($role) {
        // No permitir editar permisos de admin
        if ($role === 'admin') {
            errorResponse('No se pueden modificar los permisos del administrador.', 400);
        }

        $validRoles = ['vendedor', 'cajero', 'tecnico'];
        if (!in_array($role, $validRoles)) {
            errorResponse('Rol no valido.', 400);
        }

        $data = getJsonInput();
        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            errorResponse('Se requiere un array de permisos.', 400);
        }

        $db = getDB();

        // Validar que todos los permisos existen
        $newPerms = $data['permissions'];
        if (!empty($newPerms)) {
            $placeholders = [];
            $params = [];
            foreach ($newPerms as $i => $key) {
                $placeholders[] = ":p{$i}";
                $params[":p{$i}"] = $key;
            }
            $in = implode(',', $placeholders);
            $validStmt = $db->prepare("SELECT COUNT(*) FROM permissions WHERE permission_key IN ({$in})");
            $validStmt->execute($params);
            $validCount = (int)$validStmt->fetchColumn();

            if ($validCount !== count($newPerms)) {
                errorResponse('Uno o mas permisos no son validos.', 400);
            }
        }

        // Transaction: delete old + insert new
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM role_permissions WHERE role = :role")
               ->execute([':role' => $role]);

            if (!empty($newPerms)) {
                $insertStmt = $db->prepare(
                    "INSERT INTO role_permissions (role, permission_key) VALUES (:role, :perm)"
                );
                foreach ($newPerms as $perm) {
                    $insertStmt->execute([':role' => $role, ':perm' => $perm]);
                }
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            errorResponse('Error al actualizar permisos: ' . $e->getMessage(), 500);
        }

        logActivity('update', 'role', null, "Permisos actualizados para rol: {$role} (" . count($newPerms) . " permisos)");

        successResponse([
            'role' => $role,
            'permissions' => $newPerms,
            'count' => count($newPerms)
        ], 'Permisos actualizados exitosamente.');
    }
}
