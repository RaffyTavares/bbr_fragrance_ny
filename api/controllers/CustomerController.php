<?php
/**
 * BBR Fragrance - Customer Controller
 * Gestiona clientes, historial de compras y pedidos
 */

class CustomerController {

    /**
     * GET /customers
     * Listar clientes con busqueda y paginacion
     */
    public static function index() {
        $db = getDB();
        list($page, $limit, $offset) = getPaginationParams();

        $where = [];
        $params = [];

        // Busqueda por nombre, telefono o email
        if (!empty($_GET['search'])) {
            $where[] = "(name LIKE :search1 OR phone LIKE :search2 OR email LIKE :search3 OR rnc LIKE :search4)";
            $params[':search1'] = '%' . $_GET['search'] . '%';
            $params[':search2'] = '%' . $_GET['search'] . '%';
            $params[':search3'] = '%' . $_GET['search'] . '%';
            $params[':search4'] = '%' . $_GET['search'] . '%';
        }

        $whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Contar total
        $countSQL = "SELECT COUNT(*) FROM customers {$whereSQL}";
        $stmtCount = $db->prepare($countSQL);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Obtener clientes
        $sql = "SELECT id, name, rnc, cedula, phone, email, address, notes,
                       total_purchases, visit_count, created_at, updated_at
                FROM customers
                {$whereSQL}
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $customers = $stmt->fetchAll();

        paginatedResponse($customers, $total, $page, $limit);
    }

    /**
     * GET /customers/{id}
     * Detalle de un cliente con sus ultimas ventas y pedidos
     */
    public static function show($id) {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT id, name, rnc, cedula, phone, email, address, notes,
                    total_purchases, visit_count, created_at, updated_at
             FROM customers
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            errorResponse('Cliente no encontrado.', 404);
        }

        // Ultimas 10 ventas
        $stmtSales = $db->prepare(
            "SELECT sale_number, total, status, created_at
             FROM sales
             WHERE customer_id = :customer_id
             ORDER BY created_at DESC
             LIMIT 10"
        );
        $stmtSales->execute([':customer_id' => $id]);
        $customer['recent_sales'] = $stmtSales->fetchAll();

        // Ultimos 10 pedidos
        $stmtOrders = $db->prepare(
            "SELECT order_number, total, status, created_at
             FROM orders
             WHERE customer_id = :customer_id
             ORDER BY created_at DESC
             LIMIT 10"
        );
        $stmtOrders->execute([':customer_id' => $id]);
        $customer['recent_orders'] = $stmtOrders->fetchAll();

        successResponse($customer);
    }

    /**
     * POST /customers
     * Crear un nuevo cliente
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        // Validar campo requerido
        $errors = validateRequired($data, ['name']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        // Validar email si se proporciona
        if (!empty($data['email'])) {
            if (!validateEmail($data['email'])) {
                errorResponse('El email proporcionado no es valido.', 400);
            }
        }

        $stmt = $db->prepare(
            "INSERT INTO customers (name, rnc, cedula, phone, email, address, notes,
                                    total_purchases, visit_count, created_at, updated_at)
             VALUES (:name, :rnc, :cedula, :phone, :email, :address, :notes,
                     0, 0, NOW(), NOW())"
        );

        $stmt->execute([
            ':name'    => sanitizeString($data['name']),
            ':rnc'     => !empty($data['rnc']) ? sanitizeString($data['rnc']) : null,
            ':cedula'  => !empty($data['cedula']) ? sanitizeString($data['cedula']) : null,
            ':phone'   => !empty($data['phone']) ? sanitizeString($data['phone']) : null,
            ':email'   => !empty($data['email']) ? sanitizeString($data['email']) : null,
            ':address' => !empty($data['address']) ? sanitizeString($data['address']) : null,
            ':notes'   => !empty($data['notes']) ? sanitizeString($data['notes']) : null,
        ]);

        $customerId = (int)$db->lastInsertId();

        logActivity('create', 'customer', $customerId, "Cliente creado: " . sanitizeString($data['name']));

        // Retornar cliente creado
        $stmtNew = $db->prepare(
            "SELECT id, name, rnc, cedula, phone, email, address, notes,
                    total_purchases, visit_count, created_at, updated_at
             FROM customers
             WHERE id = :id"
        );
        $stmtNew->execute([':id' => $customerId]);
        $customer = $stmtNew->fetch();

        successResponse($customer, 'Cliente creado exitosamente.', 201);
    }

    /**
     * PUT /customers/{id}
     * Actualizar un cliente existente
     */
    public static function update($id) {
        $db = getDB();

        // Verificar que el cliente existe
        $stmtCheck = $db->prepare("SELECT id, name FROM customers WHERE id = :id");
        $stmtCheck->execute([':id' => $id]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            errorResponse('Cliente no encontrado.', 404);
        }

        $data = getJsonInput();

        if (empty($data)) {
            errorResponse('No se enviaron datos para actualizar.', 400);
        }

        // Validar email si se proporciona
        if (isset($data['email']) && $data['email'] !== '' && $data['email'] !== null) {
            if (!validateEmail($data['email'])) {
                errorResponse('El email proporcionado no es valido.', 400);
            }
        }

        // Construir SET dinamico solo con los campos enviados
        $fields = [];
        $params = [':id' => $id];

        $updatable = [
            'name'    => 'string',
            'rnc'     => 'string_nullable',
            'cedula'  => 'string_nullable',
            'phone'   => 'string_nullable',
            'email'   => 'string_nullable',
            'address' => 'string_nullable',
            'notes'   => 'string_nullable',
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
                }
            }
        }

        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $sql = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        logActivity('update', 'customer', $id, "Cliente actualizado: " . ($data['name'] ?? $existing['name']));

        // Retornar cliente actualizado
        $stmtUpdated = $db->prepare(
            "SELECT id, name, rnc, cedula, phone, email, address, notes,
                    total_purchases, visit_count, created_at, updated_at
             FROM customers
             WHERE id = :id"
        );
        $stmtUpdated->execute([':id' => $id]);
        $customer = $stmtUpdated->fetch();

        successResponse($customer, 'Cliente actualizado exitosamente.');
    }
}
