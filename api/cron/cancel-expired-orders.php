<?php
/**
 * BBR Fragrance - Cron: Cancelar pedidos expirados
 *
 * Cancela pedidos con status='pending' que superen el umbral de horas
 * configurado (default 48h). Ejecutar via cron o manualmente:
 *
 *   php /path/to/api/cron/cancel-expired-orders.php
 *
 * Ejemplo crontab (cada 30 minutos):
 *   * /30 * * * * php /xampp/htdocs/BBR_FRAGANCE/api/cron/cancel-expired-orders.php >> /tmp/bbr_cron.log 2>&1
 *
 * Las columnas payment_status='paid' estan protegidas: nunca se cancelan
 * aunque el pedido siga en status='pending'.
 */

// Seguridad: solo ejecutar desde CLI
if (PHP_SAPI !== 'cli' && !getenv('BBR_CRON_ALLOW_WEB')) {
    http_response_code(403);
    exit('Acceso denegado. Este script solo puede ejecutarse desde CLI.');
}

define('BBR_CRON', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/services/MailService.php';

$db = getDB();

// Leer umbral de horas desde settings (default: 48h)
$stmtCfg = $db->prepare(
    "SELECT setting_value FROM settings WHERE setting_key = 'order_expiry_hours' LIMIT 1"
);
$stmtCfg->execute();
$expiryHours = (int)($stmtCfg->fetchColumn() ?: 48);

$now = date('Y-m-d H:i:s');
echo "[{$now}] Buscando pedidos con mas de {$expiryHours}h en estado 'pending'...\n";

// Buscar pedidos expirados (excluir los que ya tienen pago confirmado)
$stmtFind = $db->prepare(
    "SELECT id, order_number, customer_name, customer_email, total
     FROM orders
     WHERE status = 'pending'
       AND (payment_status IS NULL OR payment_status NOT IN ('paid', 'refunded'))
       AND created_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)"
);
$stmtFind->bindValue(':hours', $expiryHours, PDO::PARAM_INT);
$stmtFind->execute();
$expired = $stmtFind->fetchAll();

if (empty($expired)) {
    echo "[{$now}] Sin pedidos expirados. Nada que hacer.\n";
    exit(0);
}

echo "[{$now}] Encontrados " . count($expired) . " pedidos expirados.\n";

$cancelled = 0;
$errors    = 0;

foreach ($expired as $order) {
    try {
        $db->beginTransaction();

        $stmtUpdate = $db->prepare(
            "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = :id"
        );
        $stmtUpdate->execute([':id' => $order['id']]);

        // Log de actividad (user_id NULL = accion automatica)
        $stmtLog = $db->prepare(
            "INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, created_at)
             VALUES (NULL, 'auto_cancel', 'order', :id, :desc, 'cron', NOW())"
        );
        $stmtLog->execute([
            ':id'   => $order['id'],
            ':desc' => "Pedido {$order['order_number']} cancelado automaticamente ({$expiryHours}h sin confirmar)",
        ]);

        $db->commit();
        $cancelled++;
        echo "[{$now}] Cancelado: {$order['order_number']} (RD\$ " . number_format((float)$order['total'], 2) . ")\n";

        // Notificar al cliente por email (fallo silencioso)
        if (!empty($order['customer_email'])) {
            try {
                MailService::sendOrderStatusUpdate($order, 'cancelled');
            } catch (Exception $e) {
                error_log("cron cancel-expired: email fallido para {$order['order_number']}: " . $e->getMessage());
            }
        }

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errors++;
        echo "[{$now}] ERROR cancelando {$order['order_number']}: " . $e->getMessage() . "\n";
        error_log("cron cancel-expired: " . $e->getMessage());
    }
}

$now = date('Y-m-d H:i:s');
echo "[{$now}] Completado. Cancelados: {$cancelled}, Errores: {$errors}.\n";
exit($errors > 0 ? 1 : 0);
