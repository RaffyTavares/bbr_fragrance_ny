<?php
/**
 * BBR Fragance - Upload Helper
 */

function uploadProductImage($file, $productId) {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamano maximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamano maximo permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subio parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se selecciono ningun archivo.',
        ];
        return ['success' => false, 'message' => $errors[$file['error']] ?? 'Error al subir el archivo.'];
    }

    // Check size
    if ($file['size'] > MAX_IMAGE_SIZE) {
        return ['success' => false, 'message' => 'El archivo excede el tamano maximo de 5MB.'];
    }

    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG y WEBP.'];
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_IMAGE_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Extension de archivo no permitida.'];
    }

    // Create directory if not exists
    if (!is_dir(PRODUCT_IMAGES_PATH)) {
        mkdir(PRODUCT_IMAGES_PATH, 0755, true);
    }

    // Generate unique filename
    $filename = 'product_' . $productId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = PRODUCT_IMAGES_PATH . '/' . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Error al guardar el archivo.'];
    }

    return [
        'success' => true,
        'filename' => $filename,
        'original_name' => $file['name'],
        'url' => PRODUCT_IMAGES_URL . '/' . $filename
    ];
}

function deleteProductImage($filename) {
    $filepath = PRODUCT_IMAGES_PATH . '/' . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}
