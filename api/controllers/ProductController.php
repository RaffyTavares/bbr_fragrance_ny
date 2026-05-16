<?php
/**
 * BBR Fragrance - Product Controller
 * Gestiona productos, imagenes, stock e inventario
 */

class ProductController {

    /**
     * GET /products
     * Listar productos con filtros, busqueda y paginacion
     */
    public static function index() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Filtro por categoria (slug)
        if (!empty($_GET['category'])) {
            $where[] = "c.slug = :category";
            $params[':category'] = $_GET['category'];
        }

        // Filtro por familia olfativa (slug)
        if (!empty($_GET['family'])) {
            $where[] = "f.slug = :family";
            $params[':family'] = $_GET['family'];
        }

        // Filtro por marca (slug)
        if (!empty($_GET['brand'])) {
            $where[] = "b.slug = :brand";
            $params[':brand'] = $_GET['brand'];
        }

        // Filtro por estado
        if (!empty($_GET['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $_GET['status'];
        }

        // Filtro por precio minimo
        if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
            $where[] = "p.price >= :min_price";
            $params[':min_price'] = (float)$_GET['min_price'];
        }

        // Filtro por precio maximo
        if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
            $where[] = "p.price <= :max_price";
            $params[':max_price'] = (float)$_GET['max_price'];
        }

        // Filtro por destacado
        if (isset($_GET['featured']) && $_GET['featured'] !== '') {
            $where[] = "p.is_featured = :featured";
            $params[':featured'] = (int)$_GET['featured'];
        }

        // Filtro por ofertas (productos con original_price > price)
        if (isset($_GET['offers']) && $_GET['offers'] === '1') {
            $where[] = "p.original_price IS NOT NULL AND p.original_price > 0 AND p.original_price > p.price";
        }

        // Busqueda por nombre, marca, codigo de barras o SKU
        if (!empty($_GET['search'])) {
            $where[] = "(p.name LIKE :search1 OR b.name LIKE :search2 OR p.barcode LIKE :search3 OR p.sku LIKE :search4)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
            $params[':search4'] = $searchTerm;
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Ordenamiento
        $orderBy = 'p.created_at DESC'; // default: newest
        if (!empty($_GET['sort'])) {
            switch ($_GET['sort']) {
                case 'price_asc':  $orderBy = 'p.price ASC'; break;
                case 'price_desc': $orderBy = 'p.price DESC'; break;
                case 'name_asc':   $orderBy = 'p.name ASC'; break;
                case 'name_desc':  $orderBy = 'p.name DESC'; break;
                case 'newest':     $orderBy = 'p.created_at DESC'; break;
            }
        }

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM products p
                     LEFT JOIN brands b ON p.brand_id = b.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     LEFT JOIN olfactory_families f ON p.family_id = f.id
                     {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener productos con imagen principal
        $sql = "SELECT p.id, p.name, p.price, p.original_price, p.cost, p.stock, p.min_stock,
                       p.barcode, p.sku, p.description, p.volume_ml, p.status, p.is_featured,
                       p.created_at, p.updated_at,
                       b.id AS brand_id, b.name AS brand_name, b.slug AS brand_slug,
                       c.id AS category_id, c.name AS category_name, c.slug AS category_slug,
                       f.id AS family_id, f.name AS family_name, f.slug AS family_slug,
                       pi.filename AS image_filename
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN olfactory_families f ON p.family_id = f.id
                LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                {$whereSQL}
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        // Agregar URL de imagen
        foreach ($products as &$product) {
            $product['image_url'] = $product['image_filename']
                ? PRODUCT_IMAGES_URL . '/' . $product['image_filename']
                : null;
            unset($product['image_filename']);
        }
        unset($product);

        paginatedResponse($products, $total, $page, $limit);
    }

    /**
     * GET /products/{id}
     * Detalle de un producto con toda su informacion relacionada
     */
    public static function show($id) {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT p.*,
                    b.name AS brand_name, b.slug AS brand_slug,
                    c.name AS category_name, c.slug AS category_slug,
                    f.name AS family_name, f.slug AS family_slug
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN olfactory_families f ON p.family_id = f.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            errorResponse('Producto no encontrado.', 404);
        }

        // Obtener todas las imagenes del producto
        $stmtImages = $db->prepare(
            "SELECT id, filename, original_name, is_primary, sort_order
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, is_primary DESC"
        );
        $stmtImages->execute([':product_id' => $id]);
        $images = $stmtImages->fetchAll();

        foreach ($images as &$img) {
            $img['url'] = PRODUCT_IMAGES_URL . '/' . $img['filename'];
        }
        unset($img);

        $product['images'] = $images;

        successResponse($product);
    }

    /**
     * POST /products
     * Crear un nuevo producto
     */
    public static function store() {
        $db = getDB();

        // Soportar JSON y multipart/form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $data = getJsonInput();
        } else {
            $data = $_POST;
        }

        // Validar campos requeridos
        $errors = validateRequired($data, ['name', 'brand_id', 'category_id', 'price']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar precio
        $priceError = validateNumeric($data['price'], 'price', 0);
        if ($priceError) {
            errorResponse($priceError, 400);
        }

        // Validar precio original si viene
        if (isset($data['original_price']) && $data['original_price'] !== '' && $data['original_price'] !== null) {
            $origPriceError = validateNumeric($data['original_price'], 'original_price', 0);
            if ($origPriceError) {
                errorResponse($origPriceError, 400);
            }
        }

        // Validar costo si viene
        if (isset($data['cost']) && $data['cost'] !== '' && $data['cost'] !== null) {
            $costError = validateNumeric($data['cost'], 'cost', 0);
            if ($costError) {
                errorResponse($costError, 400);
            }
        }

        // Validar status si viene
        if (!empty($data['status'])) {
            $statusError = validateEnum($data['status'], ['active', 'inactive'], 'status');
            if ($statusError) {
                errorResponse($statusError, 400);
            }
        }

        // Insertar producto
        $stmt = $db->prepare(
            "INSERT INTO products (name, brand_id, category_id, family_id, price, original_price,
                                   cost, stock, min_stock, barcode, sku, description, volume_ml, status, is_featured,
                                   created_at, updated_at)
             VALUES (:name, :brand_id, :category_id, :family_id, :price, :original_price,
                     :cost, :stock, :min_stock, :barcode, :sku, :description, :volume_ml, :status, :is_featured,
                     NOW(), NOW())"
        );

        $stmt->execute([
            ':name'           => sanitizeString($data['name']),
            ':brand_id'       => (int)$data['brand_id'],
            ':category_id'    => (int)$data['category_id'],
            ':family_id'      => !empty($data['family_id']) ? (int)$data['family_id'] : null,
            ':price'          => (float)$data['price'],
            ':original_price' => isset($data['original_price']) && $data['original_price'] !== '' ? (float)$data['original_price'] : null,
            ':cost'           => isset($data['cost']) && $data['cost'] !== '' ? (float)$data['cost'] : null,
            ':stock'          => isset($data['stock']) ? (int)$data['stock'] : 0,
            ':min_stock'      => isset($data['min_stock']) ? (int)$data['min_stock'] : 0,
            ':barcode'        => !empty($data['barcode']) ? sanitizeString($data['barcode']) : null,
            ':sku'            => !empty($data['sku']) ? sanitizeString($data['sku']) : null,
            ':description'    => !empty($data['description']) ? sanitizeString($data['description']) : null,
            ':volume_ml'      => isset($data['volume_ml']) && $data['volume_ml'] !== '' && $data['volume_ml'] !== null ? (int)$data['volume_ml'] : null,
            ':status'         => $data['status'] ?? 'active',
            ':is_featured'    => isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
        ]);

        $productId = (int)$db->lastInsertId();

        // Si hay imagen subida, guardarla
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload = uploadProductImage($_FILES['image'], $productId);
                if ($upload['success']) {
                    $stmtImg = $db->prepare(
                        "INSERT INTO product_images (product_id, filename, original_name, is_primary, sort_order)
                         VALUES (:product_id, :filename, :original_name, 1, 0)"
                    );
                    $stmtImg->execute([
                        ':product_id'    => $productId,
                        ':filename'      => $upload['filename'],
                        ':original_name' => $upload['original_name'],
                    ]);
                }
            }
        }

        logActivity('create', 'product', $productId, "Producto creado: " . sanitizeString($data['name']));

        // Retornar producto creado
        self::_getProductById($db, $productId);
    }

    /**
     * PUT|POST /products/{id}
     * Actualizar un producto existente
     */
    public static function update($id) {
        $db = getDB();

        // Verificar que el producto existe
        $stmtCheck = $db->prepare("SELECT id, name FROM products WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            errorResponse('Producto no encontrado.', 404);
        }

        // Soportar JSON y multipart/form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $data = getJsonInput();
        } else {
            $data = $_POST;
        }

        if (empty($data) && empty($_FILES)) {
            errorResponse('No se enviaron datos para actualizar.', 400);
        }

        // Validar precio si viene
        if (isset($data['price'])) {
            $priceError = validateNumeric($data['price'], 'price', 0);
            if ($priceError) {
                errorResponse($priceError, 400);
            }
        }

        // Validar precio original si viene
        if (isset($data['original_price']) && $data['original_price'] !== '' && $data['original_price'] !== null) {
            $origPriceError = validateNumeric($data['original_price'], 'original_price', 0);
            if ($origPriceError) {
                errorResponse($origPriceError, 400);
            }
        }

        // Validar costo si viene
        if (isset($data['cost']) && $data['cost'] !== '' && $data['cost'] !== null) {
            $costError = validateNumeric($data['cost'], 'cost', 0);
            if ($costError) {
                errorResponse($costError, 400);
            }
        }

        // Validar status si viene
        if (!empty($data['status'])) {
            $statusError = validateEnum($data['status'], ['active', 'inactive'], 'status');
            if ($statusError) {
                errorResponse($statusError, 400);
            }
        }

        // Construir SET dinamico solo con los campos enviados
        $fields = [];
        $params = [':id' => $id];

        $updatable = [
            'name'           => 'string',
            'brand_id'       => 'int',
            'category_id'    => 'int',
            'family_id'      => 'int_nullable',
            'price'          => 'float',
            'original_price' => 'float_nullable',
            'cost'           => 'float_nullable',
            'stock'          => 'int',
            'min_stock'      => 'int',
            'barcode'        => 'string_nullable',
            'sku'            => 'string_nullable',
            'description'    => 'string_nullable',
            'volume_ml'      => 'int_nullable',
            'status'         => 'raw',
            'is_featured'    => 'int',
        ];

        foreach ($updatable as $field => $type) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $value = $data[$field];

                switch ($type) {
                    case 'string':
                        $params[":{$field}"] = sanitizeString($value);
                        break;
                    case 'string_nullable':
                        $params[":{$field}"] = ($value !== '' && $value !== null) ? sanitizeString($value) : null;
                        break;
                    case 'int':
                        $params[":{$field}"] = (int)$value;
                        break;
                    case 'int_nullable':
                        $params[":{$field}"] = ($value !== '' && $value !== null) ? (int)$value : null;
                        break;
                    case 'float':
                        $params[":{$field}"] = (float)$value;
                        break;
                    case 'float_nullable':
                        $params[":{$field}"] = ($value !== '' && $value !== null) ? (float)$value : null;
                        break;
                    case 'raw':
                        $params[":{$field}"] = $value;
                        break;
                }
            }
        }

        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // Si hay imagen subida, guardarla
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadProductImage($_FILES['image'], $id);
            if ($upload['success']) {
                // Verificar si ya existe una imagen principal
                $stmtPrimary = $db->prepare(
                    "SELECT id, filename FROM product_images WHERE product_id = :product_id AND is_primary = 1"
                );
                $stmtPrimary->execute([':product_id' => $id]);
                $existingImage = $stmtPrimary->fetch();

                if ($existingImage) {
                    // Reemplazar la imagen principal existente
                    deleteProductImage($existingImage['filename']);
                    $stmtUpdate = $db->prepare(
                        "UPDATE product_images SET filename = :filename, original_name = :original_name
                         WHERE id = :img_id"
                    );
                    $stmtUpdate->execute([
                        ':filename'      => $upload['filename'],
                        ':original_name' => $upload['original_name'],
                        ':img_id'        => $existingImage['id'],
                    ]);
                } else {
                    // Crear nueva imagen principal
                    $stmtImg = $db->prepare(
                        "INSERT INTO product_images (product_id, filename, original_name, is_primary, sort_order)
                         VALUES (:product_id, :filename, :original_name, 1, 0)"
                    );
                    $stmtImg->execute([
                        ':product_id'    => $id,
                        ':filename'      => $upload['filename'],
                        ':original_name' => $upload['original_name'],
                    ]);
                }
            }
        }

        logActivity('update', 'product', $id, "Producto actualizado: " . ($data['name'] ?? $existing['name']));

        // Retornar producto actualizado
        self::_getProductById($db, $id);
    }

    /**
     * DELETE /products/{id}
     * Eliminar un producto y todas sus imagenes
     */
    public static function destroy($id) {
        $db = getDB();

        // Verificar que el producto existe
        $stmtCheck = $db->prepare("SELECT id, name FROM products WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $product = $stmtCheck->fetch();

        if (!$product) {
            errorResponse('Producto no encontrado.', 404);
        }

        // Obtener y eliminar archivos de imagenes
        $stmtImages = $db->prepare("SELECT filename FROM product_images WHERE product_id = :product_id");
        $stmtImages->execute([':product_id' => $id]);
        $images = $stmtImages->fetchAll();

        foreach ($images as $img) {
            deleteProductImage($img['filename']);
        }

        // Eliminar registros de imagenes de la BD
        $db->prepare("DELETE FROM product_images WHERE product_id = :product_id")
           ->execute([':product_id' => $id]);

        // Eliminar producto
        $db->prepare("DELETE FROM products WHERE id = :id")
           ->execute([':id' => $id]);

        logActivity('delete', 'product', $id, "Producto eliminado: {$product['name']}");

        successResponse(null, 'Producto eliminado exitosamente.');
    }

    /**
     * GET /products/search?q=
     * Busqueda ligera para autocompletado en POS
     */
    public static function search() {
        $db = getDB();
        $query = $_GET['q'] ?? '';

        if (strlen(trim($query)) < 1) {
            successResponse([]);
            return;
        }

        $searchTerm = '%' . trim($query) . '%';

        $stmt = $db->prepare(
            "SELECT p.id, p.name, b.name AS brand_name, p.price, p.stock,
                    pi.filename AS image_filename
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             WHERE p.status = 'active'
               AND (p.name LIKE :search1
                    OR b.name LIKE :search2
                    OR p.barcode LIKE :search3
                    OR p.sku LIKE :search4)
             ORDER BY p.name ASC
             LIMIT 10"
        );
        $stmt->execute([
            ':search1' => $searchTerm,
            ':search2' => $searchTerm,
            ':search3' => $searchTerm,
            ':search4' => $searchTerm,
        ]);
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['image_url'] = $product['image_filename']
                ? PRODUCT_IMAGES_URL . '/' . $product['image_filename']
                : null;
            unset($product['image_filename']);
        }
        unset($product);

        successResponse($products);
    }

    /**
     * GET /products/low-stock
     * Productos con stock bajo (stock <= min_stock) y activos
     */
    public static function lowStock() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT p.id, p.name, p.stock, p.min_stock, p.price, p.sku, p.barcode,
                    b.name AS brand_name,
                    c.name AS category_name,
                    pi.filename AS image_filename
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             WHERE p.status = 'active' AND p.stock <= p.min_stock
             ORDER BY (p.stock - p.min_stock) ASC, p.name ASC"
        );
        $stmt->execute();
        $products = $stmt->fetchAll();

        foreach ($products as &$product) {
            $product['image_url'] = $product['image_filename']
                ? PRODUCT_IMAGES_URL . '/' . $product['image_filename']
                : null;
            unset($product['image_filename']);
        }
        unset($product);

        successResponse($products, 'Productos con stock bajo.');
    }

    /**
     * PUT /products/{id}/stock
     * Actualizacion rapida de stock
     * Body: { "stock": number }
     */
    public static function updateStock($id) {
        $db = getDB();

        // Verificar que el producto existe
        $stmtCheck = $db->prepare("SELECT id, name, stock FROM products WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $product = $stmtCheck->fetch();

        if (!$product) {
            errorResponse('Producto no encontrado.', 404);
        }

        $data = getJsonInput();

        if (!isset($data['stock'])) {
            errorResponse("El campo 'stock' es requerido.", 400);
        }

        $stockError = validateNumeric($data['stock'], 'stock', 0);
        if ($stockError) {
            errorResponse($stockError, 400);
        }

        $newStock = (int)$data['stock'];

        $stmt = $db->prepare("UPDATE products SET stock = :stock, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':stock' => $newStock,
            ':id'    => $id,
        ]);

        logActivity(
            'stock_update',
            'product',
            $id,
            "Stock actualizado de {$product['stock']} a {$newStock}: {$product['name']}"
        );

        successResponse(
            ['id' => (int)$id, 'name' => $product['name'], 'stock' => $newStock],
            'Stock actualizado exitosamente.'
        );
    }

    /**
     * POST /products/{id}/images
     * Subir una imagen para un producto
     */
    public static function uploadImage($productId) {
        $db = getDB();

        // Verificar que el producto existe
        $stmtCheck = $db->prepare("SELECT id, name FROM products WHERE id = :id");
        $stmtCheck->execute([':id' => $productId]);
        $product = $stmtCheck->fetch();

        if (!$product) {
            errorResponse('Producto no encontrado.', 404);
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            errorResponse('No se envio ninguna imagen.', 400);
        }

        $upload = uploadProductImage($_FILES['image'], $productId);

        if (!$upload['success']) {
            errorResponse($upload['message'], 400);
        }

        // Determinar si es la imagen principal (si no tiene ninguna, sera la principal)
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = :product_id");
        $stmtCount->execute([':product_id' => $productId]);
        $imageCount = (int)$stmtCount->fetchColumn();

        $isPrimary = ($imageCount === 0) ? 1 : 0;
        $sortOrder = $imageCount;

        $stmtImg = $db->prepare(
            "INSERT INTO product_images (product_id, filename, original_name, is_primary, sort_order)
             VALUES (:product_id, :filename, :original_name, :is_primary, :sort_order)"
        );
        $stmtImg->execute([
            ':product_id'    => $productId,
            ':filename'      => $upload['filename'],
            ':original_name' => $upload['original_name'],
            ':is_primary'    => $isPrimary,
            ':sort_order'    => $sortOrder,
        ]);

        $imageId = (int)$db->lastInsertId();

        logActivity('upload_image', 'product', $productId, "Imagen subida para producto: {$product['name']}");

        successResponse([
            'id'            => $imageId,
            'product_id'    => (int)$productId,
            'filename'      => $upload['filename'],
            'original_name' => $upload['original_name'],
            'url'           => PRODUCT_IMAGES_URL . '/' . $upload['filename'],
            'is_primary'    => $isPrimary,
            'sort_order'    => $sortOrder,
        ], 'Imagen subida exitosamente.', 201);
    }

    /**
     * DELETE /product-images/{id}
     * Eliminar una imagen de producto
     */
    public static function deleteImage($imageId) {
        $db = getDB();

        // Obtener la imagen
        $stmt = $db->prepare(
            "SELECT pi.id, pi.product_id, pi.filename, pi.is_primary, p.name AS product_name
             FROM product_images pi
             JOIN products p ON p.id = pi.product_id
             WHERE pi.id = :id"
        );
        $stmt->execute([':id' => $imageId]);
        $image = $stmt->fetch();

        if (!$image) {
            errorResponse('Imagen no encontrada.', 404);
        }

        // Eliminar archivo fisico
        deleteProductImage($image['filename']);

        // Eliminar registro de la BD
        $db->prepare("DELETE FROM product_images WHERE id = :id")
           ->execute([':id' => $imageId]);

        // Si era la imagen principal, asignar la siguiente como principal
        if ($image['is_primary']) {
            $stmtNext = $db->prepare(
                "SELECT id FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC LIMIT 1"
            );
            $stmtNext->execute([':product_id' => $image['product_id']]);
            $next = $stmtNext->fetch();

            if ($next) {
                $db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = :id")
                   ->execute([':id' => $next['id']]);
            }
        }

        logActivity('delete_image', 'product', $image['product_id'], "Imagen eliminada del producto: {$image['product_name']}");

        successResponse(null, 'Imagen eliminada exitosamente.');
    }

    // =========================================================================
    // Metodos privados auxiliares
    // =========================================================================

    /**
     * Obtener un producto por ID y devolver como successResponse
     */
    private static function _getProductById($db, $id) {
        $stmt = $db->prepare(
            "SELECT p.*,
                    b.name AS brand_name, b.slug AS brand_slug,
                    c.name AS category_name, c.slug AS category_slug,
                    f.name AS family_name, f.slug AS family_slug
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN olfactory_families f ON p.family_id = f.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            errorResponse('Producto no encontrado.', 404);
        }

        // Obtener imagenes
        $stmtImages = $db->prepare(
            "SELECT id, filename, original_name, is_primary, sort_order
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY sort_order ASC, is_primary DESC"
        );
        $stmtImages->execute([':product_id' => $id]);
        $images = $stmtImages->fetchAll();

        foreach ($images as &$img) {
            $img['url'] = PRODUCT_IMAGES_URL . '/' . $img['filename'];
        }
        unset($img);

        $product['images'] = $images;

        successResponse($product, 'Producto guardado exitosamente.', 201);
    }
}
