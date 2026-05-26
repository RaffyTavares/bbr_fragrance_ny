<?php
/**
 * BBR Fragrance - Settings Controller
 * Gestiona la configuracion general del sistema
 */

class SettingsController {

    /**
     * GET /settings
     * Obtener todas las configuraciones como objeto clave-valor
     */
    public static function index() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Convertir a objeto clave-valor
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Ocultar campos sensibles para usuarios no autenticados
        $sensitiveKeys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email'];
        if (!isset($_SESSION['user_id'])) {
            foreach ($sensitiveKeys as $key) {
                unset($settings[$key]);
            }
        }

        // La clave secreta de Cardnet NUNCA se expone al cliente
        unset($settings['cardnet_secret_key']);

        successResponse($settings, 'Configuracion obtenida exitosamente.');
    }

    /**
     * PUT /settings
     * Actualizar configuraciones (upsert de pares clave-valor)
     */
    public static function update() {
        $db = getDB();
        $data = getJsonInput();

        if (empty($data)) {
            errorResponse('No se enviaron datos para actualizar.', 400);
        }

        $stmt = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (:setting_key, :setting_value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = :setting_value_update, updated_at = NOW()"
        );

        foreach ($data as $key => $value) {
            $safeKey = sanitizeString($key);
            $safeValue = is_string($value) ? trim($value) : (string)$value;
            $stmt->execute([
                ':setting_key'          => $safeKey,
                ':setting_value'        => $safeValue,
                ':setting_value_update' => $safeValue,
            ]);
        }

        logActivity('update', 'settings', null, 'Configuracion actualizada');

        // Retornar configuracion completa actualizada
        $stmtAll = $db->prepare(
            "SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC"
        );
        $stmtAll->execute();
        $rows = $stmtAll->fetchAll();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        unset($settings['cardnet_secret_key']);

        successResponse($settings, 'Configuracion actualizada exitosamente.');
    }

    /**
     * POST /settings/promo-image
     * Subir imagen o video para la seccion de promocion del mes
     * Acepta el campo de archivo bajo el nombre 'image' (compatibilidad) o 'media'.
     */
    public static function uploadPromoImage() {
        // Accept either 'image' (legacy) or 'media' field name
        $file = $_FILES['media'] ?? $_FILES['image'] ?? null;
        if (empty($file)) {
            errorResponse('No se envio ningun archivo.', 400);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            errorResponse('Error al subir el archivo.', 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Detect media type
        $isImage = in_array($mimeType, ALLOWED_IMAGE_TYPES) && in_array($extension, ALLOWED_IMAGE_EXTENSIONS);
        $isVideo = in_array($mimeType, ALLOWED_VIDEO_TYPES) && in_array($extension, ALLOWED_VIDEO_EXTENSIONS);

        if (!$isImage && !$isVideo) {
            errorResponse('Tipo de archivo no permitido. Imagenes (JPG, PNG, WEBP) o Videos (MP4, WEBM, MOV).', 400);
        }

        $maxSize = $isVideo ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;
        if ($file['size'] > $maxSize) {
            $limitMb = $isVideo ? '25MB' : '5MB';
            errorResponse("El archivo excede el tamano maximo de {$limitMb}.", 400);
        }

        $mediaType = $isVideo ? 'video' : 'image';

        $promoDir = UPLOADS_PATH . '/promo';
        if (!is_dir($promoDir)) {
            mkdir($promoDir, 0777, true);
        }

        // Delete old promo media if exists
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'promo_image'");
        $stmt->execute();
        $oldMedia = $stmt->fetchColumn();
        if ($oldMedia) {
            $oldPath = UPLOADS_PATH . '/promo/' . basename($oldMedia);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $promoDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            errorResponse('Error al guardar el archivo.', 500);
        }

        $mediaUrl = UPLOADS_URL . '/promo/' . $filename;

        // Save URL and media type in settings
        $upsert = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES ('promo_image', :url, NOW())
             ON DUPLICATE KEY UPDATE setting_value = :url_update, updated_at = NOW()"
        );
        $upsert->execute([':url' => $mediaUrl, ':url_update' => $mediaUrl]);

        $upsertType = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES ('promo_media_type', :type, NOW())
             ON DUPLICATE KEY UPDATE setting_value = :type_update, updated_at = NOW()"
        );
        $upsertType->execute([':type' => $mediaType, ':type_update' => $mediaType]);

        logActivity('update', 'settings', null, 'Medio de promocion actualizado (' . $mediaType . ')');

        successResponse([
            'url' => $mediaUrl,
            'type' => $mediaType
        ], 'Medio de promocion subido exitosamente.');
    }

    /**
     * POST /settings/test-email
     * Envia un email de prueba al admin para verificar la configuracion SMTP.
     */
    public static function testEmail() {
        require_once __DIR__ . '/../services/MailService.php';

        $data    = getJsonInput();
        $toEmail = trim($data['email'] ?? '');

        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            // Si no viene email en el body, usar el del usuario en sesion
            $db = getDB();
            $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            $toEmail = $user['email'] ?? '';
            $toName  = $user['full_name'] ?? 'Administrador';

            if (empty($toEmail)) {
                errorResponse('Incluye un email de destino en el body o configura tu email de usuario.', 400);
            }
        } else {
            $toName = $data['name'] ?? 'Administrador';
        }

        $result = MailService::sendTestEmail($toEmail, $toName);

        if ($result['success']) {
            logActivity('test_email', 'settings', null, "Email de prueba enviado a {$toEmail}");
            successResponse(['to' => $toEmail], $result['message']);
        } else {
            errorResponse($result['message'], 502);
        }
    }
}
