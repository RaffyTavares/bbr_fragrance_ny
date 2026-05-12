<?php
/**
 * BBR Fragance - User Controller
 * CRUD de usuarios del sistema
 */

class UserController {

    /**
     * GET /users
     * Listar usuarios con busqueda y filtros
     */
    public static function index() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(username LIKE :search1 OR full_name LIKE :search2 OR email LIKE :search3)";
            $params[':search1'] = '%' . $_GET['search'] . '%';
            $params[':search2'] = '%' . $_GET['search'] . '%';
            $params[':search3'] = '%' . $_GET['search'] . '%';
        }

        if (!empty($_GET['role'])) {
            $where[] = "role = :role";
            $params[':role'] = $_GET['role'];
        }

        if (isset($_GET['is_active'])) {
            $where[] = "is_active = :is_active";
            $params[':is_active'] = (int)$_GET['is_active'];
        }

        $whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM users {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch users (never expose password_hash)
        $stmt = $db->prepare(
            "SELECT id, username, full_name, email, phone, role, is_active, last_login, created_at, updated_at
             FROM users
             {$whereSQL}
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        paginatedResponse($stmt->fetchAll(), $total, $page, $limit);
    }

    /**
     * POST /users
     * Crear un nuevo usuario
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        $errors = validateRequired($data, ['username', 'password', 'full_name', 'role']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar rol
        $validRoles = ['admin', 'vendedor', 'cajero', 'tecnico'];
        $roleError = validateEnum($data['role'], $validRoles, 'role');
        if ($roleError) {
            errorResponse($roleError, 400);
        }

        // Validar username unico
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $checkStmt->execute([':username' => $data['username']]);
        if ($checkStmt->fetch()) {
            errorResponse('El nombre de usuario ya existe.', 409);
        }

        // Validar password minima
        if (strlen($data['password']) < 6) {
            errorResponse('La contrasena debe tener al menos 6 caracteres.', 400);
        }

        // Validar email si viene
        if (!empty($data['email'])) {
            $emailError = validateEmail($data['email']);
            if ($emailError) {
                errorResponse($emailError, 400);
            }
        }

        $stmt = $db->prepare(
            "INSERT INTO users (username, password_hash, full_name, email, phone, role, is_active)
             VALUES (:username, :password_hash, :full_name, :email, :phone, :role, :is_active)"
        );
        $stmt->execute([
            ':username' => sanitizeString($data['username']),
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':full_name' => sanitizeString($data['full_name']),
            ':email' => !empty($data['email']) ? sanitizeString($data['email']) : null,
            ':phone' => !empty($data['phone']) ? sanitizeString($data['phone']) : null,
            ':role' => $data['role'],
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        $userId = (int)$db->lastInsertId();
        logActivity('create', 'user', $userId, "Usuario creado: " . sanitizeString($data['username']));

        // Retornar usuario creado
        $stmt2 = $db->prepare(
            "SELECT id, username, full_name, email, phone, role, is_active, created_at
             FROM users WHERE id = :id"
        );
        $stmt2->execute([':id' => $userId]);
        successResponse($stmt2->fetch(), 'Usuario creado exitosamente.', 201);
    }

    /**
     * GET /users/{id}
     */
    public static function show($id) {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT id, username, full_name, email, phone, role, is_active, last_login, created_at, updated_at
             FROM users WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            errorResponse('Usuario no encontrado.', 404);
        }

        successResponse($user);
    }

    /**
     * PUT /users/{id}
     * Actualizar usuario (sin cambiar password aqui)
     */
    public static function update($id) {
        $db = getDB();
        $data = getJsonInput();

        // Verificar que el usuario existe
        $checkStmt = $db->prepare("SELECT id, username FROM users WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        $existing = $checkStmt->fetch();
        if (!$existing) {
            errorResponse('Usuario no encontrado.', 404);
        }

        // No permitir que se modifique el propio rol (solo otro admin)
        $currentUserId = getCurrentUserId();
        if ((int)$id === $currentUserId && !empty($data['role']) && $data['role'] !== $_SESSION['role']) {
            errorResponse('No puede cambiar su propio rol.', 400);
        }

        // Validar rol si viene
        if (!empty($data['role'])) {
            $validRoles = ['admin', 'vendedor', 'cajero', 'tecnico'];
            $roleError = validateEnum($data['role'], $validRoles, 'role');
            if ($roleError) {
                errorResponse($roleError, 400);
            }
        }

        // Validar username unico si cambia
        if (!empty($data['username']) && $data['username'] !== $existing['username']) {
            $dupStmt = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $dupStmt->execute([':username' => $data['username'], ':id' => $id]);
            if ($dupStmt->fetch()) {
                errorResponse('El nombre de usuario ya existe.', 409);
            }
        }

        // Email validation
        if (!empty($data['email'])) {
            $emailError = validateEmail($data['email']);
            if ($emailError) {
                errorResponse($emailError, 400);
            }
        }

        // Build dynamic SET
        $fields = [];
        $params = [':id' => $id];
        $allowed = ['username', 'full_name', 'email', 'phone', 'role', 'is_active'];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "{$f} = :{$f}";
                if ($f === 'is_active') {
                    $params[":{$f}"] = (int)$data[$f];
                } else {
                    $params[":{$f}"] = $data[$f] !== '' ? sanitizeString($data[$f]) : null;
                }
            }
        }

        if (empty($fields)) {
            errorResponse('No se enviaron datos para actualizar.', 400);
        }

        $setSQL = implode(', ', $fields);
        $stmt = $db->prepare("UPDATE users SET {$setSQL} WHERE id = :id");
        $stmt->execute($params);

        logActivity('update', 'user', (int)$id, "Usuario actualizado: " . ($data['username'] ?? $existing['username']));

        // Return updated user
        $stmt2 = $db->prepare(
            "SELECT id, username, full_name, email, phone, role, is_active, last_login, created_at, updated_at
             FROM users WHERE id = :id"
        );
        $stmt2->execute([':id' => $id]);
        successResponse($stmt2->fetch(), 'Usuario actualizado exitosamente.');
    }

    /**
     * PUT /users/{id}/toggle-active
     * Activar/desactivar usuario
     */
    public static function toggleActive($id) {
        $db = getDB();

        // No permitir desactivarse a si mismo
        if ((int)$id === getCurrentUserId()) {
            errorResponse('No puede desactivar su propia cuenta.', 400);
        }

        $stmt = $db->prepare("SELECT id, username, is_active FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            errorResponse('Usuario no encontrado.', 404);
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $db->prepare("UPDATE users SET is_active = :status WHERE id = :id")
           ->execute([':status' => $newStatus, ':id' => $id]);

        $action = $newStatus ? 'activado' : 'desactivado';
        logActivity('update', 'user', (int)$id, "Usuario {$action}: " . $user['username']);

        successResponse(
            ['id' => (int)$id, 'is_active' => $newStatus],
            "Usuario {$action} exitosamente."
        );
    }

    /**
     * PUT /users/{id}/reset-password
     * Restablecer contrasena de un usuario
     */
    public static function resetPassword($id) {
        $db = getDB();
        $data = getJsonInput();

        if (empty($data['password']) || strlen($data['password']) < 6) {
            errorResponse('La nueva contrasena debe tener al menos 6 caracteres.', 400);
        }

        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        if (!$user) {
            errorResponse('Usuario no encontrado.', 404);
        }

        $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
           ->execute([':hash' => password_hash($data['password'], PASSWORD_DEFAULT), ':id' => $id]);

        logActivity('update', 'user', (int)$id, "Contrasena restablecida: " . $user['username']);

        successResponse(null, 'Contrasena restablecida exitosamente.');
    }
}
