<?php
/**
 * BBR Fragrance - NCF Controller
 * Gestiona secuencias de Comprobantes Fiscales (NCF) de la DGII
 */

class NcfController {

    private static $validTypes = [
        'B01' => 'Credito Fiscal',
        'B02' => 'Consumo',
        'B14' => 'Regimenes Especiales',
        'B15' => 'Gubernamental',
    ];

    /**
     * GET /ncf-sequences
     * Listar todas las secuencias NCF
     */
    public static function index() {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT * FROM ncf_sequences ORDER BY ncf_type ASC, id ASC"
        );
        $stmt->execute();
        $sequences = $stmt->fetchAll();

        $today = date('Y-m-d');
        foreach ($sequences as &$s) {
            $s['remaining'] = max(0, (int)$s['end_number'] - (int)$s['current_number']);
            $s['is_expired'] = $s['expiration_date'] < $today ? 1 : 0;
        }

        successResponse($sequences, 'Secuencias NCF obtenidas exitosamente.');
    }

    /**
     * POST /ncf-sequences
     * Crear nueva secuencia NCF
     */
    public static function store() {
        $db = getDB();
        $data = getJsonInput();

        $errors = validateRequired($data, ['ncf_type', 'start_number', 'end_number', 'expiration_date']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        $ncfType = strtoupper(trim($data['ncf_type']));
        if (!isset(self::$validTypes[$ncfType])) {
            errorResponse('Tipo de NCF no valido. Opciones: B01, B02, B14, B15.', 400);
        }

        $startNumber = (int)$data['start_number'];
        $endNumber = (int)$data['end_number'];

        if ($startNumber < 1) {
            errorResponse('El numero de inicio debe ser mayor a 0.', 400);
        }
        if ($endNumber <= $startNumber) {
            errorResponse('El numero final debe ser mayor al numero de inicio.', 400);
        }

        $expirationDate = $data['expiration_date'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expirationDate)) {
            errorResponse('La fecha de vencimiento no tiene un formato valido (YYYY-MM-DD).', 400);
        }

        $stmt = $db->prepare(
            "INSERT INTO ncf_sequences (ncf_type, type_name, prefix, current_number, start_number, end_number, expiration_date, is_active)
             VALUES (:ncf_type, :type_name, :prefix, :current_number, :start_number, :end_number, :expiration_date, 1)"
        );
        $stmt->execute([
            ':ncf_type'        => $ncfType,
            ':type_name'       => self::$validTypes[$ncfType],
            ':prefix'          => $ncfType,
            ':current_number'  => $startNumber - 1,
            ':start_number'    => $startNumber,
            ':end_number'      => $endNumber,
            ':expiration_date' => $expirationDate,
        ]);

        $id = (int)$db->lastInsertId();
        logActivity('create', 'ncf_sequence', $id, "Secuencia NCF creada: {$ncfType} ({$startNumber}-{$endNumber})");

        $stmtGet = $db->prepare("SELECT * FROM ncf_sequences WHERE id = :id");
        $stmtGet->execute([':id' => $id]);
        $sequence = $stmtGet->fetch();

        successResponse($sequence, 'Secuencia NCF creada exitosamente.', 201);
    }

    /**
     * PUT /ncf-sequences/{id}
     * Actualizar secuencia NCF (end_number, expiration_date, is_active)
     */
    public static function update($id) {
        $db = getDB();
        $data = getJsonInput();

        $stmt = $db->prepare("SELECT * FROM ncf_sequences WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $sequence = $stmt->fetch();

        if (!$sequence) {
            errorResponse('Secuencia NCF no encontrada.', 404);
        }

        $fields = [];
        $params = [':id' => $id];

        if (isset($data['end_number'])) {
            $endNumber = (int)$data['end_number'];
            if ($endNumber <= $sequence['current_number']) {
                errorResponse('El numero final debe ser mayor al numero actual (' . $sequence['current_number'] . ').', 400);
            }
            $fields[] = "end_number = :end_number";
            $params[':end_number'] = $endNumber;
        }

        if (isset($data['expiration_date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['expiration_date'])) {
                errorResponse('La fecha de vencimiento no tiene un formato valido.', 400);
            }
            $fields[] = "expiration_date = :expiration_date";
            $params[':expiration_date'] = $data['expiration_date'];
        }

        if (isset($data['is_active'])) {
            $fields[] = "is_active = :is_active";
            $params[':is_active'] = $data['is_active'] ? 1 : 0;
        }

        if (empty($fields)) {
            errorResponse('No se proporcionaron campos para actualizar.', 400);
        }

        $sql = "UPDATE ncf_sequences SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmtUpdate = $db->prepare($sql);
        $stmtUpdate->execute($params);

        logActivity('update', 'ncf_sequence', $id, "Secuencia NCF actualizada: {$sequence['ncf_type']}");

        $stmtGet = $db->prepare("SELECT * FROM ncf_sequences WHERE id = :id");
        $stmtGet->execute([':id' => $id]);

        successResponse($stmtGet->fetch(), 'Secuencia NCF actualizada exitosamente.');
    }

    /**
     * DELETE /ncf-sequences/{id}
     * Eliminar secuencia NCF (solo si no ha sido usada)
     */
    public static function destroy($id) {
        $db = getDB();

        $stmt = $db->prepare("SELECT * FROM ncf_sequences WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $sequence = $stmt->fetch();

        if (!$sequence) {
            errorResponse('Secuencia NCF no encontrada.', 404);
        }

        if ($sequence['current_number'] >= $sequence['start_number']) {
            errorResponse('No se puede eliminar una secuencia que ya ha sido utilizada. Desactivela en su lugar.', 400);
        }

        $stmtDel = $db->prepare("DELETE FROM ncf_sequences WHERE id = :id");
        $stmtDel->execute([':id' => $id]);

        logActivity('delete', 'ncf_sequence', $id, "Secuencia NCF eliminada: {$sequence['ncf_type']}");

        successResponse(null, 'Secuencia NCF eliminada exitosamente.');
    }

    /**
     * GET /ncf-sequences/status
     * Estado de disponibilidad de NCF por tipo (para el POS)
     */
    public static function status() {
        $db = getDB();
        $today = date('Y-m-d');

        $stmt = $db->prepare(
            "SELECT ncf_type, SUM(end_number - current_number) AS remaining,
                    MIN(expiration_date) AS nearest_expiration
             FROM ncf_sequences
             WHERE is_active = 1 AND expiration_date >= :today AND current_number < end_number
             GROUP BY ncf_type"
        );
        $stmt->execute([':today' => $today]);
        $rows = $stmt->fetchAll();

        $status = [];
        foreach (self::$validTypes as $type => $name) {
            $status[$type] = [
                'type_name' => $name,
                'available' => false,
                'remaining' => 0,
                'warning'   => false,
            ];
        }

        foreach ($rows as $row) {
            $remaining = (int)$row['remaining'];
            $status[$row['ncf_type']] = [
                'type_name' => self::$validTypes[$row['ncf_type']] ?? $row['ncf_type'],
                'available' => $remaining > 0,
                'remaining' => $remaining,
                'warning'   => $remaining > 0 && $remaining <= 10,
            ];
        }

        successResponse($status, 'Estado de secuencias NCF obtenido.');
    }
}
