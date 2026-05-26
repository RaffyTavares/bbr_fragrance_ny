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

            // Descontar stock si el pedido pasaba de pending a confirmed
            if (($order['status'] ?? '') === 'pending') {
                $stmtItems = $db->prepare(
                    "SELECT product_id, quantity FROM order_items WHERE order_id = :oid"
                );
                $stmtItems->execute([':oid' => $order['id']]);
                $orderItems = $stmtItems->fetchAll();

                $stmtDeduct = $db->prepare(
                    "UPDATE products SET stock = stock - :qty WHERE id = :pid"
                );
                foreach ($orderItems as $oi) {
                    $stmtDeduct->execute([':qty' => $oi['quantity'], ':pid' => $oi['product_id']]);
                }
            }

            logActivity('payment_approved', 'order', $order['id'],
                "Pago Cardnet aprobado: {$order['order_number']} Auth:{$result['authorization']}");

            // Registrar venta en el sistema de ventas
            require_once __DIR__ . '/OrderController.php';
            $paidOrder = array_merge($order, [
                'payment_method' => 'card_online',
                'payment_status' => 'paid',
                'status'         => 'confirmed',
            ]);
            OrderController::createSaleFromOrder($db, $paidOrder);

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
     * POST /payments/cardnet/refund
     * Reembolsar un pedido pagado via Cardnet.
     * Requiere permiso orders.manage (validado en el router).
     * Body: { order_id: int, reason: string }
     */
    public static function cardnetRefund() {
        $data    = getJsonInput();
        $orderId = (int)($data['order_id'] ?? 0);
        $reason  = trim($data['reason'] ?? 'Reembolso solicitado');

        if (!$orderId) {
            errorResponse('Falta el order_id.', 400);
        }

        $db   = getDB();
        $stmt = $db->prepare(
            "SELECT id, order_number, total, payment_status,
                    payment_gateway, payment_transaction_id
             FROM orders WHERE id = :id"
        );
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado.', 404);
        }
        if (($order['payment_status'] ?? '') !== 'paid') {
            errorResponse('Solo se pueden reembolsar pedidos con pago confirmado (status = paid).', 400);
        }

        // Llamar a Cardnet para anular la transaccion
        $amountCents = (int)round(((float)$order['total']) * 100);
        $result = CardnetService::voidTransaction(
            $order['payment_transaction_id'] ?? '',
            $amountCents
        );

        if (!$result['success']) {
            errorResponse('Error al procesar reembolso: ' . $result['message'], 502);
        }

        // Actualizar estado del pedido
        $stmtUpd = $db->prepare(
            "UPDATE orders SET
                payment_status = 'refunded',
                status = 'cancelled',
                payment_response_raw = :raw,
                updated_at = NOW()
             WHERE id = :id"
        );
        $stmtUpd->execute([
            ':raw' => json_encode($result['raw'] ?? []),
            ':id'  => $orderId,
        ]);

        logActivity('payment_refunded', 'order', $orderId,
            "Reembolso procesado: {$order['order_number']} - {$reason}");

        successResponse(['order_id' => $orderId], 'Reembolso procesado correctamente.');
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
            $base     = CardnetService::getSiteBaseUrl();

            if ($decision === 'cancel') {
                header('Location: ' . $base . '/api/payments/cardnet/cancel?order_id=' . $orderId);
                exit;
            }

            // 'approve' siempre usa codigo 00; 'reject' usa el codigo del dropdown
            $code = ($decision === 'approve') ? '00' : ($_POST['code'] ?? '05');

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
  .radio-group{display:grid;gap:8px;margin-top:20px}
  .radio-opt{display:flex;align-items:center;gap:12px;background:#0f172a;border:2px solid #334155;
             border-radius:10px;padding:14px 16px;cursor:pointer;transition:border-color .15s}
  .radio-opt:has(input:checked){border-color:#22c55e}
  .radio-opt.opt-reject:has(input:checked){border-color:#ef4444}
  .radio-opt.opt-cancel:has(input:checked){border-color:#64748b}
  .radio-opt input[type=radio]{accent-color:#22c55e;width:18px;height:18px;flex-shrink:0;cursor:pointer}
  .opt-reject input[type=radio]{accent-color:#ef4444}
  .opt-cancel input[type=radio]{accent-color:#64748b}
  .radio-label{flex:1}
  .radio-label strong{display:block;font-size:15px}
  .radio-label span{font-size:12px;color:#94a3b8}
  #reject-codes{background:#0f172a;border:1px solid #334155;border-radius:8px;
                padding:10px 12px;margin-top:4px;display:none}
  select{background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:8px;border-radius:6px;
         width:100%;margin-top:4px;font-size:13px}
  .btn-submit{width:100%;margin-top:20px;font-size:15px;font-weight:700;padding:15px;border:none;
              border-radius:10px;cursor:pointer;background:#22c55e;color:white;transition:background .15s,transform .1s}
  .btn-submit:hover{background:#16a34a;transform:translateY(-1px)}
  .warn{background:#7c2d12;border:1px solid #ea580c;color:#fed7aa;padding:10px;border-radius:8px;
        font-size:12px;margin-top:16px}
</style>
</head>
<body>
<div class="card">
  <span class="badge">SIMULADOR</span>
  <h1>Cardnet - Pagina de Pago Hospedada</h1>
  <p class="muted">Pagina <strong>simulada</strong> para probar el flujo end-to-end sin credenciales reales.</p>

  <div style="margin:20px 0;background:#0f172a;border-radius:10px;padding:16px">
    <div class="row"><span>Comercio</span><span>BBR Fragrance</span></div>
    <div class="row"><span>Pedido</span><span>{$orderNumber}</span></div>
    <div class="row"><span>Sesion</span><span style="font-family:monospace;font-size:11px">{$session}</span></div>
    <div class="row"><span>Total</span><span class="total">RD\$ {$amountFmt}</span></div>
  </div>

  <form method="POST" id="sim-form">
    <p class="muted" style="margin-bottom:8px">Selecciona el resultado del pago:</p>

    <div class="radio-group">
      <label class="radio-opt">
        <input type="radio" name="decision" value="approve" checked>
        <div class="radio-label">
          <strong style="color:#22c55e">Aprobada</strong>
          <span>Codigo 00 — pago autorizado correctamente</span>
        </div>
      </label>

      <label class="radio-opt opt-reject">
        <input type="radio" name="decision" value="reject" id="radio-reject">
        <div class="radio-label">
          <strong style="color:#ef4444">Rechazada</strong>
          <span>Simula un rechazo del banco emisor</span>
          <div id="reject-codes">
            <select name="code">
              <option value="05">05 - No autorizado</option>
              <option value="14">14 - Tarjeta invalida</option>
              <option value="51">51 - Fondos insuficientes</option>
              <option value="54">54 - Tarjeta expirada</option>
              <option value="61">61 - Excede limite</option>
              <option value="91">91 - Emisor no disponible</option>
              <option value="96">96 - Falla del sistema</option>
            </select>
          </div>
        </div>
      </label>

      <label class="radio-opt opt-cancel">
        <input type="radio" name="decision" value="cancel">
        <div class="radio-label">
          <strong style="color:#94a3b8">Cancelada</strong>
          <span>El cliente abandona sin pagar</span>
        </div>
      </label>
    </div>

    <button type="submit" class="btn-submit" id="sim-btn">Continuar &rarr;</button>
  </form>

  <div class="warn">
    Modo simulador activo. Para cobros reales cambia <code>cardnet_environment</code>
    a <code>sandbox</code> o <code>production</code> y completa las credenciales.
  </div>
</div>
<script>
  const radios   = document.querySelectorAll('input[name="decision"]');
  const rejectEl = document.getElementById('reject-codes');
  const btn      = document.getElementById('sim-btn');
  const colors   = { approve: '#22c55e', reject: '#ef4444', cancel: '#475569' };

  function sync() {
    const val = document.querySelector('input[name="decision"]:checked').value;
    rejectEl.style.display = val === 'reject' ? 'block' : 'none';
    btn.style.background   = colors[val];
    btn.textContent        = val === 'cancel' ? 'Cancelar y volver →' : 'Continuar →';
  }
  radios.forEach(r => r.addEventListener('change', sync));
  sync();
</script>
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
