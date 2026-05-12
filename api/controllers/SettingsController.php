<?php
/**
 * BBR Fragance - Settings Controller
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

        successResponse($settings, 'Configuracion actualizada exitosamente.');
    }

    /**
     * POST /settings/promo-image
     * Subir imagen para la seccion de promocion del mes
     */
    public static function uploadPromoImage() {
        if (empty($_FILES['image'])) {
            errorResponse('No se envio ninguna imagen.', 400);
        }

        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            errorResponse('Error al subir el archivo.', 400);
        }

        if ($file['size'] > MAX_IMAGE_SIZE) {
            errorResponse('El archivo excede el tamano maximo de 5MB.', 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            errorResponse('Tipo de archivo no permitido. Solo JPG, PNG y WEBP.', 400);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_IMAGE_EXTENSIONS)) {
            errorResponse('Extension de archivo no permitida.', 400);
        }

        $promoDir = UPLOADS_PATH . '/promo';
        if (!is_dir($promoDir)) {
            mkdir($promoDir, 0777, true);
        }

        // Delete old promo image if exists
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'promo_image'");
        $stmt->execute();
        $oldImage = $stmt->fetchColumn();
        if ($oldImage) {
            $oldPath = UPLOADS_PATH . '/promo/' . basename($oldImage);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $filename = 'promo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $promoDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            errorResponse('Error al guardar el archivo.', 500);
        }

        $imageUrl = UPLOADS_URL . '/promo/' . $filename;

        // Save URL in settings
        $upsert = $db->prepare(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES ('promo_image', :url, NOW())
             ON DUPLICATE KEY UPDATE setting_value = :url_update, updated_at = NOW()"
        );
        $upsert->execute([':url' => $imageUrl, ':url_update' => $imageUrl]);

        logActivity('update', 'settings', null, 'Imagen de promocion actualizada');

        successResponse(['url' => $imageUrl], 'Imagen de promocion subida exitosamente.');
    }
}
