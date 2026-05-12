<?php
/**
 * BBR Fragance - Constants
 */

define('APP_NAME', 'BBR Fragance');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', dirname(dirname(__DIR__)));
define('API_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('PRODUCT_IMAGES_PATH', UPLOADS_PATH . '/products');

// URLs
define('BASE_URL', '/web-BBR_Fragance');
define('API_URL', BASE_URL . '/api');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('PRODUCT_IMAGES_URL', UPLOADS_URL . '/products');

// Upload limits
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);
