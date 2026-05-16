<?php
/**
 * BBR Fragrance - Auth Controller
 */

class AuthController {

    public static function login() {
        $data = getJsonInput();
        $errors = validateRequired($data, ['username', 'password']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
        $stmt->execute([':username' => $data['username']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            errorResponse('Usuario o contrasena incorrectos.', 401);
        }

        // Update last login
        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
           ->execute([':id' => $user['id']]);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        logActivity('login', 'user', $user['id'], "Inicio de sesion: {$user['username']}");

        // Get user permissions
        $permissions = getUserPermissions();

        successResponse([
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'permissions' => $permissions
        ], 'Inicio de sesion exitoso.');
    }

    public static function logout() {
        logActivity('logout', 'user', getCurrentUserId(), 'Cierre de sesion');
        // Limpiar datos de sesion
        $_SESSION = [];
        // Eliminar la cookie de sesion del browser
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
        // Evitar que el browser cachee esta respuesta
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        successResponse(null, 'Sesion cerrada exitosamente.');
    }

    public static function check() {
        if (!isset($_SESSION['user_id'])) {
            errorResponse('No autenticado.', 401);
        }

        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, full_name, role, email FROM users WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            errorResponse('Sesion invalida.', 401);
        }

        // Include permissions in check response
        $permissions = getUserPermissions();

        successResponse([
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'email' => $user['email'],
            'permissions' => $permissions
        ]);
    }
}
