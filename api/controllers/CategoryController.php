<?php
/**
 * BBR Fragance - Category Controller
 * Gestiona categorias, familias olfativas y marcas
 */

class CategoryController {

    /**
     * GET /categories
     * Listar todas las categorias activas con conteo de productos activos
     */
    public static function index() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT c.id, c.name, c.slug, c.description, c.icon, c.sort_order, c.is_active,
                    COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.status = 'active'
             WHERE c.is_active = 1
             GROUP BY c.id, c.name, c.slug, c.description, c.icon, c.sort_order, c.is_active
             ORDER BY c.sort_order ASC"
        );
        $stmt->execute();
        $categories = $stmt->fetchAll();

        successResponse($categories, 'Categorias obtenidas exitosamente.');
    }

    /**
     * GET /families
     * Listar todas las familias olfativas activas con conteo de productos activos
     */
    public static function families() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT f.id, f.name, f.slug, f.description, f.icon,
                    f.gradient_from, f.gradient_to, f.sort_order, f.is_active,
                    COUNT(p.id) AS product_count
             FROM olfactory_families f
             LEFT JOIN products p ON p.family_id = f.id AND p.status = 'active'
             WHERE f.is_active = 1
             GROUP BY f.id, f.name, f.slug, f.description, f.icon,
                      f.gradient_from, f.gradient_to, f.sort_order, f.is_active
             ORDER BY f.sort_order ASC"
        );
        $stmt->execute();
        $families = $stmt->fetchAll();

        successResponse($families, 'Familias olfativas obtenidas exitosamente.');
    }

    /**
     * GET /brands
     * Listar todas las marcas activas con conteo de productos activos
     */
    public static function brands() {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT b.id, b.name, b.slug, b.is_active,
                    COUNT(p.id) AS product_count
             FROM brands b
             LEFT JOIN products p ON p.brand_id = b.id AND p.status = 'active'
             WHERE b.is_active = 1
             GROUP BY b.id, b.name, b.slug, b.is_active
             ORDER BY b.name ASC"
        );
        $stmt->execute();
        $brands = $stmt->fetchAll();

        successResponse($brands, 'Marcas obtenidas exitosamente.');
    }

    /**
     * GET /brands/all
     * Listar todas las marcas (activas e inactivas) - para admin
     */
    public static function brandsAll() {
        requirePermission('products.view');
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT b.id, b.name, b.slug, b.is_active,
                    COUNT(p.id) AS product_count
             FROM brands b
             LEFT JOIN products p ON p.brand_id = b.id AND p.status = 'active'
             GROUP BY b.id, b.name, b.slug, b.is_active
             ORDER BY b.name ASC"
        );
        $stmt->execute();
        $brands = $stmt->fetchAll();

        successResponse($brands, 'Marcas obtenidas exitosamente.');
    }

    /**
     * POST /brands
     * Crear una nueva marca
     */
    public static function storeBrand() {
        $data = getJsonInput();
        $errors = validateRequired($data, ['name']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        $name = sanitizeString($data['name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $db = getDB();

        // Check uniqueness
        $check = $db->prepare("SELECT id FROM brands WHERE name = :name OR slug = :slug");
        $check->execute([':name' => $name, ':slug' => $slug]);
        if ($check->fetch()) {
            errorResponse('Ya existe una marca con ese nombre.', 409);
        }

        $stmt = $db->prepare(
            "INSERT INTO brands (name, slug, is_active) VALUES (:name, :slug, :is_active)"
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);

        $brandId = $db->lastInsertId();
        logActivity('create', 'brand', $brandId, "Marca creada: {$name}");

        successResponse(['id' => (int)$brandId, 'name' => $name, 'slug' => $slug], 'Marca creada exitosamente.', 201);
    }

    /**
     * PUT /brands/{id}
     * Actualizar una marca
     */
    public static function updateBrand($id) {
        $data = getJsonInput();
        $errors = validateRequired($data, ['name']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        $db = getDB();

        // Verify brand exists
        $check = $db->prepare("SELECT id FROM brands WHERE id = :id");
        $check->execute([':id' => $id]);
        if (!$check->fetch()) {
            errorResponse('Marca no encontrada.', 404);
        }

        $name = sanitizeString($data['name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        // Check uniqueness (exclude current)
        $dup = $db->prepare("SELECT id FROM brands WHERE (name = :name OR slug = :slug) AND id != :id");
        $dup->execute([':name' => $name, ':slug' => $slug, ':id' => $id]);
        if ($dup->fetch()) {
            errorResponse('Ya existe otra marca con ese nombre.', 409);
        }

        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $stmt = $db->prepare(
            "UPDATE brands SET name = :name, slug = :slug, is_active = :is_active WHERE id = :id"
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':is_active' => $isActive,
            ':id' => $id
        ]);

        logActivity('update', 'brand', $id, "Marca actualizada: {$name}");
        successResponse(['id' => (int)$id, 'name' => $name, 'slug' => $slug, 'is_active' => $isActive], 'Marca actualizada exitosamente.');
    }

    /**
     * DELETE /brands/{id}
     * Eliminar una marca (solo si no tiene productos asociados)
     */
    public static function destroyBrand($id) {
        $db = getDB();

        // Check if brand exists
        $check = $db->prepare("SELECT id, name FROM brands WHERE id = :id");
        $check->execute([':id' => $id]);
        $brand = $check->fetch();
        if (!$brand) {
            errorResponse('Marca no encontrada.', 404);
        }

        // Check if brand has products
        $prodCheck = $db->prepare("SELECT COUNT(*) FROM products WHERE brand_id = :id");
        $prodCheck->execute([':id' => $id]);
        if ((int)$prodCheck->fetchColumn() > 0) {
            errorResponse('No se puede eliminar la marca porque tiene productos asociados. Desactivela en su lugar.', 400);
        }

        $stmt = $db->prepare("DELETE FROM brands WHERE id = :id");
        $stmt->execute([':id' => $id]);

        logActivity('delete', 'brand', $id, "Marca eliminada: {$brand['name']}");
        successResponse(null, 'Marca eliminada exitosamente.');
    }

    /**
     * PUT /brands/{id}/toggle
     * Activar/desactivar una marca
     */
    public static function toggleBrand($id) {
        $db = getDB();

        $check = $db->prepare("SELECT id, name, is_active FROM brands WHERE id = :id");
        $check->execute([':id' => $id]);
        $brand = $check->fetch();
        if (!$brand) {
            errorResponse('Marca no encontrada.', 404);
        }

        $newStatus = $brand['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE brands SET is_active = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $id]);

        $statusText = $newStatus ? 'activada' : 'desactivada';
        logActivity('update', 'brand', $id, "Marca {$statusText}: {$brand['name']}");
        successResponse(['id' => (int)$id, 'is_active' => $newStatus], "Marca {$statusText} exitosamente.");
    }
}
