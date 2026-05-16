<?php
/**
 * BBR Fragrance - Payment Controller
 * Pasarela Cardnet (pago en linea con tarjeta)
 */

require_once __DIR__ . '/../services/CardnetService.php';

class PaymentController {

    /**
     * GET /payments/status
     * Devuelve la configuracion publica (si las pasarelas estan activas)
     * para que el frontend sepa que opciones mostrar.
     */
    public static function status() {
        $config = CardnetService::getConfig();
        successResponse([
            'cardnet' => [
                'enabled'     => CardnetService::isEnabled($config),
                'environment' => $config['cardnet_environment'] ?? 'sandbox',
            ],
        ]);
    }

    /**
     * POST /payments/cardnet/session
     * Crear una sesion de Cardnet para un pedido recien creado.
     * Body: { order_id: int }
     */
    public static function cardnetSession() {
        $data = getJsonInput();
        $orderId = (int)($data['order_id'] ?? 0);
        if (!$orderId) {
            errorResponse('Falta el order_id.', 400);
        }

        $db = getDB();
        $stmt = $db->prepare(
            "SELECT id, order_number, total, payment_status, payment_method
             FROM orders WHERE id = :id"
        );
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }
        if (($order['payment_status'] ?? 'pending') === 'paid') {
            errorResponse('Este pedido ya fue pagado.', 400);
        }

        // Monto en centavos (Cardnet espera entero, sin decimales)
        $amountCents = (int)round(((float)$order['total']) * 100);

        $result = CardnetService::createSession([
            'order_id'     => (int)$order['id'],
            'order_number' => $order['order_number'],
            'amount'       => $amountCents,
        ]);

        if (!$result['success']) {
            errorResponse($result['message'], 502);
        }

        // Guardar la session_key en el pedido
        $stmtUpd = $db->prepare(
            "UPDATE orders SET
                payment_gateway = 'cardnet',
                payment_session_key = :sk,
                updated_at = NOW()
             WHERE id = :id"
        );
        $stmtUpd->execute([
            ':sk' => $result['session'],
            ':id' => $orderId,
        ]);

        logActivity('payment_session', 'order', $orderId,
            "Sesion Cardnet creada para pedido {$order['order_number']}: {$result['session']}");

        successResponse([
            'session'      => $result['session'],
            'session_key'  => $result['session_key'],
            'redirect_url' => $result['redirect_url'],
            'return_url'   => $result['return_url'],
            'cancel_url'   => $result['cancel_url'],
            'order_id'     => $orderId,
            'order_number' => $order['order_number'],
            'amount_cents' => $amountCents,
            'simulator'    => !empty($result['simulator']),
        ], 'Sesion creada.');
    }

    /**
     * GET|POST /payments/cardnet/return?order_id=N
     * Endpoint al que Cardnet redirige al cliente despues del pago.
     * Verifica server-to-server y actualiza el pedido.
     * Finaliza redirigiendo al index.html con un hash de resultado.
     */
    public static function cardnetReturn() {
        $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
        $session = $_GET['SESSION']
                  ?? $_POST['SESSION']
                  ?? $_GET['session']
                  ?? $_POST['session']
                  ?? '';

        $db = getDB();
        $order = null;
        if ($orderId) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
            $stmt->execute([':id' => $orderId]);
            $order = $stmt->fetch();
        }
        if (!$order && $session) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE payment_session_key = :sk LIMIT 1");
            $stmt->execute([':sk' => $session]);
            $order = $stmt->fetch();
        }

        if (!$order) {
            self::redirectToResult('error', null, 'Pedido no encontrado.');
            return;
        }

        if (!$session) {
            $session = $order['payment_session_key'];
        }

        if (!$session) {
            self::markFailed($db, $order, 'Sin sesion de pago.');
            self::redirectToResult('error', $order['order_number'], 'No se recibio confirmacion de Cardnet.');
            return;
        }

        $result = CardnetService::verifyTransaction($session);

        if ($result['approved']) {
            $stmt = $db->prepare(
                "UPDATE orders SET
                    payment_status = 'paid',
                    payment_method = 'card_online',
                    payment_transaction_id = :tx,
                    payment_authorization = :auth,
                    payment_response_code = :code,
                    payment_response_raw = :raw,
                    payment_paid_at = NOW(),
                    status = CASE WHEN status = 'pending' THEN 'confirmed' ELSE status END,
                    updated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([
                ':tx'   => $result['transaction_id'] ?? '',
                ':auth' => $result['authorization'] ?? '',
                ':code' => $result['response_code'] ?? '',
                ':raw'  => json_encode($result['raw'] ?? []),
                ':id'   => $order['id'],
            ]);

            logActivity('payment_approved', 'order', $order['id'],
                "Pago Cardnet aprobado: {$order['order_number']} Auth:{$result['authorization']}");

            self::redirectToResult('success', $order['order_number']);
        } else {
            self::markFailed($db, $order, $result['message'] ?? 'Pago rechazado.', $result);
            self::redirectToResult('error', $order['order_number'], $result['message'] ?? 'Pago rechazado.');
        }
    }

    /**
     * GET|POST /payments/cardnet/cancel?order_id=N
     * Endpoint al que Cardnet redirige cuando el cliente cancela.
     */
    public static function cardnetCancel() {
        $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
        $db = getDB();
        $orderNumber = null;

        if ($orderId) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
            $stmt->execute([':id' => $orderId]);
            $order = $stmt->fetch();
            if ($order) {
                $orderNumber = $order['order_number'];
                self::markFailed($db, $order, 'Pago cancelado por el cliente.');
            }
        }

        self::redirectToResult('cancelled', $orderNumber, 'Pago cancelado.');
    }

    /** Marcar el pedido como pago fallido */
    private static function markFailed($db, $order, $message, $result = null) {
        $stmt = $db->prepare(
            "UPDATE orders SET
                payment_status = 'failed',
                payment_response_code = :code,
                payment_response_raw = :raw,
                updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            ':code' => $result['response_code'] ?? null,
            ':raw'  => $result ? json_encode($result['raw'] ?? $result) : json_encode(['message' => $message]),
            ':id'   => $order['id'],
        ]);
        logActivity('payment_failed', 'order', $order['id'],
            "Pago Cardnet fallido: {$order['order_number']} - {$message}");
    }

    /**
     * GET /payments/cardnet/mock
     * Pagina HTML simulada que reemplaza la pasarela real cuando
     * cardnet_environment = 'simulator'. Permite al tester escoger
     * Aprobar / Rechazar / Cancelar y vuelve al callback estandar.
     */
    public static function cardnetMock() {
        if (!CardnetService::isSimulator()) {
            http_response_code(404);
            echo 'Mock disponible solo cuando cardnet_environment = simulator.';
            exit;
        }

        $session     = preg_replace('/[^A-Z0-9-]/i', '', $_GET['session'] ?? '');
        $orderId     = (int)($_GET['order_id'] ?? 0);
        $orderNumber = htmlspecialchars($_GET['order_number'] ?? '', ENT_QUOTES, 'UTF-8');
        $amountCents = (int)($_GET['amount'] ?? 0);

        // Si llega POST con la decision -> persistir resultado y redirigir al callback
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $decision = $_POST['decision'] ?? '';
            $code     = $_POST['code']     ?? '00';
            $base     = CardnetService::getSiteBaseUrl();

            if ($decision === 'cancel') {
                header('Location: ' . $base . '/api/payments/cardnet/cancel?order_id=' . $orderId);
                exit;
            }

            // Guardar outcome para que verifyTransaction() lo lea
            $file = sys_get_temp_dir() . '/bbr_sim_' . $session . '.json';
            file_put_contents($file, json_encode([
                'code'   => $code,
                'amount' => $amountCents,
            ]));

            $url = $base . '/api/payments/cardnet/return'
                 . '?order_id=' . $orderId
                 . '&SESSION=' . urlencode($session);
            header('Location: ' . $url);
            exit;
        }

        // Render del formulario simulado
        $amountFmt = number_format($amountCents / 100, 2);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="robots" content="notranslate">
<title>Cardnet - Simulador de Pago</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0f172a;color:#e2e8f0;
       display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
  .card{background:#1e293b;border:1px solid #334155;border-radius:16px;padding:32px;max-width:480px;width:100%;
        box-shadow:0 20px 60px rgba(0,0,0,.5)}
  .badge{display:inline-block;background:#f59e0b;color:#0f172a;font-weight:700;padding:4px 12px;border-radius:999px;
         font-size:11px;letter-spacing:1px}
  h1{font-size:22px;margin:16px 0 8px}
  .muted{color:#94a3b8;font-size:13px}
  .row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #334155}
  .row:last-child{border:none}
  .total{font-size:24px;color:#fbbf24;font-weight:700}
  .actions{display:grid;gap:10px;margin-top:24px}
  button{font-size:15px;font-weight:600;padding:14px;border:none;border-radius:10px;cursor:pointer;
         transition:transform .1s,opacity .2s}
  button:hover{transform:translateY(-1px)}
  .btn-ok{background:#16a34a;color:white}
  .btn-fail{background:#dc2626;color:white}
  .btn-cancel{background:#475569;color:white}
  select{background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:10px;border-radius:8px;width:100%;margin-top:6px}
  .warn{background:#7c2d12;border:1px solid #ea580c;color:#fed7aa;padding:10px;border-radius:8px;
        font-size:12px;margin-top:16px}
</style>
</head>
<body>
<div class="card">
  <span class="badge">SIMULADOR</span>
  <h1>Cardnet - Pagina de Pago Hospedada</h1>
  <p class="muted">Esta es una pagina <strong>simulada</strong> para probar el flujo end-to-end sin credenciales reales de Cardnet.</p>

  <div style="margin:20px 0;background:#0f172a;border-radius:10px;padding:16px">
    <div class="row"><span>Comercio</span><span>BBR Fragrance</span></div>
    <div class="row"><span>Pedido</span><span>{$orderNumber}</span></div>
    <div class="row"><span>Sesion</span><span style="font-family:monospace;font-size:11px">{$session}</span></div>
    <div class="row"><span>Total</span><span class="total">RD\$ {$amountFmt}</span></div>
  </div>

  <form method="POST" class="actions">
    <button type="submit" name="decision" value="approve" class="btn-ok">
      Aprobar pago (codigo 00)
    </button>

    <label class="muted" style="margin-top:12px">Rechazar con codigo:
      <select name="code">
        <option value="05">05 - No autorizado</option>
        <option value="14">14 - Tarjeta invalida</option>
        <option value="51">51 - Fondos insuficientes</option>
        <option value="54">54 - Tarjeta expirada</option>
        <option value="61">61 - Excede limite</option>
        <option value="91">91 - Emisor no disponible</option>
        <option value="96">96 - Falla del sistema</option>
      </select>
    </label>
    <button type="submit" name="decision" value="reject" class="btn-fail">
      Rechazar pago
    </button>

    <button type="submit" name="decision" value="cancel" class="btn-cancel">
      Cancelar y volver a la tienda
    </button>
  </form>

  <div class="warn">
    Modo simulador activo. Para procesar cobros reales, cambia <code>cardnet_environment</code>
    a <code>sandbox</code> o <code>production</code> y completa las credenciales.
  </div>
</div>
</body>
</html>
HTML;
        exit;
    }

    /** Redirigir al storefront con el resultado */
    private static function redirectToResult($status, $orderNumber = null, $message = null) {
        $base = CardnetService::getSiteBaseUrl();
        $params = ['payment' => $status];
        if ($orderNumber) $params['order'] = $orderNumber;
        if ($message) $params['msg'] = $message;
        $url = $base . '/index.html?' . http_build_query($params) . '#payment-result';
        header('Location: ' . $url);
        exit;
    }
}
