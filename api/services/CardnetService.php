<?php
/**
 * BBR Fragrance - Cardnet Service
 * ----------------------------------------------------------------
 * Integracion con la pasarela de pagos Cardnet (Republica Dominicana)
 * usando el flujo de "Pagina de Pago Hospedada" (HPP / Lightbox).
 *
 * Flujo:
 *   1) Servidor (PHP)  -> Cardnet:  POST /sessions  (HMAC-SHA512)
 *      Cardnet responde con SESSION (id de sesion) y SESSION-KEY.
 *   2) Navegador       -> Cardnet:  POST a la pagina de pago
 *      con la SESSION. El cliente ingresa los datos de tarjeta
 *      directamente en Cardnet (PCI-DSS a su cargo).
 *   3) Cardnet         -> Navegador: redirige a ReturnUrl/CancelUrl
 *      con los parametros de respuesta (SESSION, ResponseCode...)
 *   4) Servidor (PHP)  -> Cardnet:  POST /transactions/<SESSION>
 *      para validar el resultado server-to-server (recomendado).
 *
 * Las URLs/Endpoints exactos los proporciona Cardnet al activar
 * la cuenta de comercio. Aqui dejamos los valores por defecto del
 * ambiente de pruebas oficial; pueden sobreescribirse via settings.
 */

class CardnetService {

    /** Default endpoints (pueden ser sobreescritos por settings) */
    const DEFAULT_SANDBOX_BASE = 'https://lab.cardnet.com.do';
    const DEFAULT_PRODUCTION_BASE = 'https://ecommerce.cardnet.com.do';

    /** Cargar configuracion de Cardnet desde settings */
    public static function getConfig() {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key LIKE 'cardnet_%'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Obtener URL base segun ambiente */
    public static function getBaseUrl(array $config = null) {
        $config = $config ?? self::getConfig();
        $env = $config['cardnet_environment'] ?? 'sandbox';
        return $env === 'production'
            ? self::DEFAULT_PRODUCTION_BASE
            : self::DEFAULT_SANDBOX_BASE;
    }

    /** True si el ambiente actual es el simulador local (sin credenciales reales) */
    public static function isSimulator(array $config = null) {
        $config = $config ?? self::getConfig();
        return ($config['cardnet_environment'] ?? '') === 'simulator';
    }

    /** Verificar si la pasarela esta lista para usarse */
    public static function isEnabled(array $config = null) {
        $config = $config ?? self::getConfig();
        if (($config['cardnet_enabled'] ?? '0') !== '1') return false;
        // Modo simulador: no requiere credenciales reales
        if (self::isSimulator($config)) return true;
        return !empty($config['cardnet_merchant_number'])
            && !empty($config['cardnet_merchant_terminal'])
            && !empty($config['cardnet_secret_key']);
    }

    /** Auto-detectar URL base del sitio (para Return/Cancel URLs) */
    public static function getSiteBaseUrl() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/BBR_FRAGANCE';
    }

    /**
     * Construir el HMAC-SHA512 que firma la peticion de sesion.
     * El orden de los campos sigue el patron oficial de Cardnet:
     *   MerchantNumber + MerchantTerminal + TransactionType +
     *   CurrencyCode + Amount + OrderNumber + ReturnUrl + CancelUrl + SecretKey
     */
    private static function buildSignature(array $payload, $secret) {
        $raw = ($payload['MerchantNumber']   ?? '')
             . ($payload['MerchantTerminal'] ?? '')
             . ($payload['TransactionType']  ?? '')
             . ($payload['CurrencyCode']     ?? '')
             . ($payload['Amount']           ?? '')
             . ($payload['OrderNumber']      ?? '')
             . ($payload['ReturnUrl']        ?? '')
             . ($payload['CancelUrl']        ?? '')
             . $secret;
        return strtoupper(hash('sha512', $raw));
    }

    /**
     * Crear una sesion de pago en Cardnet.
     *
     * @param array $orderData [
     *   'order_number' => 'PED-20260512-001',
     *   'amount'       => 850000,        // En centavos (RD$ 8,500.00)
     *   'order_id'     => 123,           // ID interno (para callback)
     * ]
     * @return array [
     *   'success'      => bool,
     *   'session'      => string|null,   // ID de sesion en Cardnet
     *   'session_key'  => string|null,   // Llave para POST del navegador
     *   'redirect_url' => string|null,   // URL a la que el navegador debe POSTear
     *   'message'      => string,
     * ]
     */
    public static function createSession(array $orderData) {
        $config = self::getConfig();

        if (!self::isEnabled($config)) {
            return [
                'success' => false,
                'message' => 'La pasarela Cardnet no esta habilitada o le faltan credenciales.'
            ];
        }

        // ============== MODO SIMULADOR (sin Cardnet real) ==============
        if (self::isSimulator($config)) {
            $siteBase = self::getSiteBaseUrl();
            $session  = 'SIM-' . strtoupper(bin2hex(random_bytes(8)));
            $mockUrl  = $siteBase . '/api/payments/cardnet/mock'
                      . '?session=' . urlencode($session)
                      . '&order_id=' . (int)$orderData['order_id']
                      . '&order_number=' . urlencode($orderData['order_number'])
                      . '&amount=' . (int)$orderData['amount'];
            return [
                'success'      => true,
                'session'      => $session,
                'session_key'  => 'SIMKEY',
                'redirect_url' => $mockUrl,
                'return_url'   => $siteBase . '/api/payments/cardnet/return?order_id=' . (int)$orderData['order_id'],
                'cancel_url'   => $siteBase . '/api/payments/cardnet/cancel?order_id=' . (int)$orderData['order_id'],
                'message'      => 'Sesion simulada creada.',
                'simulator'    => true,
            ];
        }

        $base = self::getBaseUrl($config);
        $siteBase = self::getSiteBaseUrl();

        $returnUrl = !empty($config['cardnet_return_page'])
            ? $config['cardnet_return_page']
            : ($siteBase . '/api/payments/cardnet/return');
        $cancelUrl = !empty($config['cardnet_cancel_page'])
            ? $config['cardnet_cancel_page']
            : ($siteBase . '/api/payments/cardnet/cancel');

        // Anexar order_id como parametro para identificar el pedido en el callback
        $glue = (strpos($returnUrl, '?') !== false) ? '&' : '?';
        $returnUrl .= $glue . 'order_id=' . (int)$orderData['order_id'];
        $glue = (strpos($cancelUrl, '?') !== false) ? '&' : '?';
        $cancelUrl .= $glue . 'order_id=' . (int)$orderData['order_id'];

        $payload = [
            'MerchantNumber'           => $config['cardnet_merchant_number'],
            'MerchantTerminal'         => $config['cardnet_merchant_terminal'],
            'MerchantName'             => $config['cardnet_merchant_name'] ?? 'BBR Fragrance',
            'MerchantType'             => $config['cardnet_merchant_type'] ?? '',
            'TransactionType'          => '200',                        // 200 = Sale
            'CurrencyCode'             => $config['cardnet_currency_code'] ?? '214',
            'AcquiringInstitutionCode' => $config['cardnet_acquiring_inst_code'] ?? '349',
            'Amount'                   => (string)(int)$orderData['amount'],
            'Tax'                      => '000000000000',
            'OrderNumber'              => $orderData['order_number'],
            'ReturnUrl'                => $returnUrl,
            'CancelUrl'                => $cancelUrl,
            'PageLanguaje'             => 'ESP',
        ];

        $payload['MAC'] = self::buildSignature($payload, $config['cardnet_secret_key']);

        $endpoint = rtrim($base, '/') . '/sessions';

        $response = self::httpPost($endpoint, $payload);

        if (!$response['ok']) {
            error_log('CardnetService createSession error: ' . $response['error']);
            return [
                'success' => false,
                'message' => 'Error de comunicacion con Cardnet: ' . $response['error']
            ];
        }

        $body = $response['body'];
        $session = $body['SESSION'] ?? $body['session'] ?? null;
        $sessionKey = $body['SESSION-KEY'] ?? $body['session_key'] ?? null;

        if (!$session) {
            error_log('CardnetService createSession: respuesta invalida: ' . json_encode($body));
            return [
                'success' => false,
                'message' => 'Cardnet rechazo la creacion de sesion.',
                'raw'     => $body,
            ];
        }

        // URL a la que el navegador debe POSTear con la SESSION para mostrar
        // el formulario de tarjeta hospedado por Cardnet
        $redirectUrl = rtrim($base, '/') . '/payments';

        return [
            'success'      => true,
            'session'      => $session,
            'session_key'  => $sessionKey,
            'redirect_url' => $redirectUrl,
            'return_url'   => $returnUrl,
            'cancel_url'   => $cancelUrl,
            'message'      => 'Sesion creada exitosamente.',
        ];
    }

    /**
     * Validar el resultado de una transaccion consultando server-to-server.
     * Se invoca en el ReturnUrl despues que Cardnet redirige al cliente.
     *
     * @param string $session  ID de sesion devuelto por Cardnet
     * @return array ['approved' => bool, 'response_code' => string,
     *                'authorization' => string, 'rrn' => string,
     *                'amount' => int, 'message' => string, 'raw' => array]
     */
    public static function verifyTransaction($session) {
        $config = self::getConfig();

        // ============== MODO SIMULADOR ==============
        // El mock guarda el resultado en un archivo temporal indexado por SESSION.
        if (self::isSimulator($config)) {
            $file = sys_get_temp_dir() . '/bbr_sim_' . preg_replace('/[^A-Z0-9-]/i', '', $session) . '.json';
            $outcome = is_file($file) ? json_decode(file_get_contents($file), true) : null;
            @unlink($file);
            $code = $outcome['code'] ?? '00';
            $approved = ($code === '00');
            return [
                'approved'      => $approved,
                'response_code' => $code,
                'authorization' => $approved ? ('SIM' . random_int(100000, 999999)) : '',
                'rrn'           => $approved ? (string)random_int(10000000, 99999999) : '',
                'amount'        => (int)($outcome['amount'] ?? 0),
                'transaction_id'=> $session,
                'message'       => $approved ? 'Pago aprobado (simulador)' : ('Pago rechazado (simulador, codigo ' . $code . ')'),
                'raw'           => array_merge(['simulator' => true, 'session' => $session], $outcome ?? []),
            ];
        }

        $base = self::getBaseUrl($config);

        $endpoint = rtrim($base, '/') . '/transactions/' . urlencode($session);

        $payload = [
            'MerchantNumber'   => $config['cardnet_merchant_number'],
            'MerchantTerminal' => $config['cardnet_merchant_terminal'],
            'Session'          => $session,
        ];
        // MAC simple (Merchant + Terminal + Session + Secret)
        $payload['MAC'] = strtoupper(hash('sha512',
            $payload['MerchantNumber'] . $payload['MerchantTerminal'] . $session
            . $config['cardnet_secret_key']
        ));

        $response = self::httpPost($endpoint, $payload);

        if (!$response['ok']) {
            error_log('CardnetService verifyTransaction error: ' . $response['error']);
            return [
                'approved' => false,
                'message'  => 'No se pudo verificar la transaccion: ' . $response['error']
            ];
        }

        $body = $response['body'];
        $code = $body['ResponseCode'] ?? $body['response_code'] ?? '';
        $approved = ($code === '00');

        return [
            'approved'      => $approved,
            'response_code' => $code,
            'authorization' => $body['AuthorizationCode'] ?? $body['authorization_code'] ?? '',
            'rrn'           => $body['RRN'] ?? '',
            'amount'        => (int)($body['Amount'] ?? 0),
            'transaction_id'=> $body['TransactionId'] ?? $body['transaction_id'] ?? '',
            'message'       => $approved
                ? 'Pago aprobado'
                : ($body['ResponseMessage'] ?? $body['response_message'] ?? "Pago rechazado (codigo {$code})"),
            'raw'           => $body,
        ];
    }

    /** Helper interno: POST JSON con cURL */
    private static function httpPost($url, array $payload) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            return ['ok' => false, 'error' => $err ?: 'cURL desconocido'];
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            return [
                'ok'    => false,
                'error' => "Respuesta no-JSON (HTTP {$httpCode}): " . substr($rawResponse, 0, 200)
            ];
        }

        if ($httpCode >= 400) {
            return [
                'ok'    => false,
                'error' => "HTTP {$httpCode}: " . ($decoded['ErrorMessage'] ?? json_encode($decoded))
            ];
        }

        return ['ok' => true, 'body' => $decoded, 'http_code' => $httpCode];
    }
}
