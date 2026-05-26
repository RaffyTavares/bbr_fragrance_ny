<?php
/**
 * BBR Fragrance - Constants
 */

define('APP_NAME', 'BBR Fragrance NY');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(dirname(__DIR__)));
define('API_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('PRODUCT_IMAGES_PATH', UPLOADS_PATH . '/products');

// URLs — derived dynamically from file location so no hardcoded paths needed
$_projectRoot = str_replace('\\', '/', dirname(dirname(dirname(__FILE__))));
$_docRoot     = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
define('BASE_URL', $_docRoot !== '' ? str_replace($_docRoot, '', $_projectRoot) : '');
unset($_projectRoot, $_docRoot);
define('API_URL', BASE_URL . '/api');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('PRODUCT_IMAGES_URL', UPLOADS_URL . '/products');

// Upload limits
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Video upload limits (used for promo media)
define('MAX_VIDEO_SIZE', 25 * 1024 * 1024); // 25MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);
define('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'webm', 'mov']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);
