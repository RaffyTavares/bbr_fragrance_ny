# Integracion con Pasarela de Pago Azul

## Que es Azul?

Azul es la principal pasarela de pagos en Republica Dominicana, operada por Grupo Popular. Permite procesar pagos con tarjetas Visa, Mastercard, y tarjetas locales a traves de terminales fisicos y pagos en linea (e-commerce).

## Requisitos Previos

### 1. Cuenta de Comercio
Debes tener una cuenta de comercio (merchant account) con Azul:
- Contactar a tu banco (Popular, BHD, Banreservas, etc.) o directamente a Azul
- Telefono Azul: (809) 544-2985
- Web: https://www.azul.com.do
- Email: ecommerce@azul.com.do

### 2. Credenciales del API
Una vez aprobado, Azul te proporciona:

| Credencial | Descripcion |
|------------|-------------|
| `MerchantId` | ID unico de tu comercio |
| `MerchantName` | Nombre del comercio registrado |
| `MerchantType` | Tipo de comercio (MCC) |
| `Auth1` | Clave de autenticacion 1 |
| `Auth2` | Clave de autenticacion 2 |
| `CertificatePath` | Certificado SSL (.pfx o .pem) proporcionado por Azul |
| `CertificatePassword`| Contrasena del certificado |

### 3. Ambiente de Pruebas
Azul ofrece un ambiente sandbox para pruebas:
- **URL Sandbox**: `https://pruebas.azul.com.do/webservices/JSON/default.aspx`
- **URL Produccion**: `https://pagos.azul.com.do/webservices/JSON/default.aspx`
- Tarjetas de prueba proporcionadas en la documentacion de Azul

### 4. Certificado SSL
- Tu sitio web DEBE tener HTTPS (certificado SSL valido)
- Azul proporciona un certificado de cliente (.pfx) para autenticar las peticiones al API

## Flujo de Pago

```
Cliente           Tienda (Frontend)         Servidor (PHP)              Azul API
   |                    |                        |                        |
   |  Click "Pagar"     |                        |                        |
   |--1---------------->|                        |                        |
   |                    |  POST /payments/create  |                        |
   |                    |--2-------------------->|                        |
   |                    |                        |  POST ProcessPayment   |
   |                    |                        |--3-------------------->|
   |                    |                        |                        |
   |                    |                        |  Response (aprobado/   |
   |                    |                        |<--4--- rechazado)------|
   |                    |  {success, azulOrderId} |                        |
   |                    |<--5--------------------|                        |
   |  Resultado         |                        |                        |
   |<--6----------------|                        |                        |
```

**Importante**: La tarjeta del cliente se envia directamente al servidor PHP, que se comunica con Azul server-to-server usando el certificado de cliente. Los datos de tarjeta NUNCA se almacenan en tu base de datos.

## Implementacion Paso a Paso

### Paso 1: Almacenar credenciales Azul en Settings

```sql
INSERT INTO settings (setting_key, setting_value) VALUES
('azul_merchant_id', ''),
('azul_auth1', ''),
('azul_auth2', ''),
('azul_merchant_name', 'BBR Fragance'),
('azul_merchant_type', ''),
('azul_environment', 'sandbox'),
('azul_enabled', '0');
```

### Paso 2: Subir certificado Azul

Colocar el certificado `.pem` (o `.pfx` convertido) en:
```
api/config/azul-cert.pem
```

Asegurarse de que NO sea accesible publicamente (agregar regla en `.htaccess`):
```apache
<Files "azul-cert.pem">
    Require all denied
</Files>
```

### Paso 3: Crear AzulService.php

```php
<?php
// api/services/AzulService.php

class AzulService {

    private static function getConfig() {
        $db = getDB();
        $stmt = $db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key LIKE 'azul_%'"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private static function getUrl($config) {
        return ($config['azul_environment'] ?? 'sandbox') === 'production'
            ? 'https://pagos.azul.com.do/webservices/JSON/default.aspx'
            : 'https://pruebas.azul.com.do/webservices/JSON/default.aspx';
    }

    /**
     * Procesar un pago con tarjeta
     *
     * @param array $paymentData [
     *   'card_number'   => '4000000000000001',
     *   'expiration'    => '202812',           // YYYYMM
     *   'cvc'           => '123',
     *   'amount'        => 850000,             // En centavos (RD$ 8,500.00 = 850000)
     *   'order_number'  => 'PED-20260403-001',
     *   'customer_name' => 'Juan Perez',
     * ]
     * @return array ['success' => bool, 'azul_order_id' => string, 'message' => string]
     */
    public static function processPayment($paymentData) {
        $config = self::getConfig();

        if (empty($config['azul_merchant_id']) || $config['azul_enabled'] !== '1') {
            return ['success' => false, 'message' => 'Pasarela Azul no configurada.'];
        }

        $requestBody = [
            'Channel'         => 'EC',  // E-Commerce
            'Store'           => $config['azul_merchant_id'],
            'CardNumber'      => $paymentData['card_number'],
            'Expiration'      => $paymentData['expiration'],
            'CVC'             => $paymentData['cvc'],
            'PosInputMode'    => 'E-Commerce',
            'TrxType'         => 'Sale',
            'Amount'          => (int)$paymentData['amount'],  // Centavos
            'Itbis'           => 0,  // ITBIS ya incluido en el monto
            'CurrencyPosCode' => '$',  // RD$
            'Payments'        => '1',  // Pago unico (no cuotas)
            'Plan'            => '0',
            'AcquirerRefData' => '1',
            'OrderNumber'     => $paymentData['order_number'],
            'CustomerServicePhone' => '',
            'ECommerceUrl'    => $_SERVER['HTTP_HOST'] ?? '',
            'CustomOrderId'   => $paymentData['order_number'],
            'DataVaultToken'  => '',  // No tokenizar
            'SaveToDataVault' => '0',
        ];

        $url = self::getUrl($config);
        $certPath = __DIR__ . '/../config/azul-cert.pem';
        $certPass = $config['azul_cert_password'] ?? '';

        // Headers de autenticacion
        $headers = [
            'Content-Type: application/json',
            'Auth1: ' . ($config['azul_auth1'] ?? ''),
            'Auth2: ' . ($config['azul_auth2'] ?? ''),
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($requestBody),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSLCERT        => $certPath,
            CURLOPT_SSLCERTPASSWD  => $certPass,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("AzulService cURL error: {$curlError}");
            return ['success' => false, 'message' => 'Error de comunicacion con la pasarela.'];
        }

        $result = json_decode($response, true);

        if (!$result) {
            error_log("AzulService: invalid response: {$response}");
            return ['success' => false, 'message' => 'Respuesta invalida de la pasarela.'];
        }

        // Azul retorna ISOCode "00" cuando es aprobado
        $approved = ($result['IsoCode'] ?? '') === '00';

        return [
            'success'        => $approved,
            'azul_order_id'  => $result['AzulOrderId'] ?? null,
            'auth_code'      => $result['AuthorizationCode'] ?? null,
            'rrn'            => $result['RRN'] ?? null,
            'message'        => $approved
                ? 'Pago aprobado'
                : ($result['ErrorDescription'] ?? $result['ResponseMessage'] ?? 'Pago rechazado'),
            'iso_code'       => $result['IsoCode'] ?? null,
            'raw'            => $result,
        ];
    }

    /**
     * Anular un pago (void)
     */
    public static function voidPayment($azulOrderId) {
        $config = self::getConfig();

        $requestBody = [
            'Channel'      => 'EC',
            'Store'        => $config['azul_merchant_id'],
            'TrxType'      => 'Void',
            'AzulOrderId'  => $azulOrderId,
        ];

        // ... mismo flujo cURL que processPayment
    }
}
```

### Paso 4: Crear PaymentController.php

```php
<?php
// api/controllers/PaymentController.php

require_once __DIR__ . '/../services/AzulService.php';

class PaymentController {

    /**
     * POST /payments/process
     * Procesar pago con tarjeta via Azul
     */
    public static function process() {
        $data = getJsonInput();

        $errors = validateRequired($data, ['order_id', 'card_number', 'expiration', 'cvc']);
        if (!empty($errors)) {
            errorResponse($errors[0], 400);
        }

        $db = getDB();

        // Obtener pedido
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND status = 'pending'");
        $stmt->execute([':id' => $data['order_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            errorResponse('Pedido no encontrado o ya fue procesado.', 404);
        }

        // Procesar pago (monto en centavos)
        $result = AzulService::processPayment([
            'card_number'   => preg_replace('/\D/', '', $data['card_number']),
            'expiration'    => $data['expiration'],
            'cvc'           => $data['cvc'],
            'amount'        => (int)($order['total'] * 100),
            'order_number'  => $order['order_number'],
            'customer_name' => $order['customer_name'],
        ]);

        if ($result['success']) {
            // Actualizar pedido
            $stmtUpdate = $db->prepare(
                "UPDATE orders SET
                    status = 'confirmed',
                    payment_method = 'card',
                    notes = CONCAT(IFNULL(notes,''), :payment_note),
                    updated_at = NOW()
                 WHERE id = :id"
            );
            $stmtUpdate->execute([
                ':id' => $order['id'],
                ':payment_note' => '[Azul OK: ' . ($result['auth_code'] ?? '') . ']',
            ]);

            logActivity('payment', 'order', $order['id'],
                "Pago aprobado Azul: {$order['order_number']} Auth:{$result['auth_code']}");

            successResponse([
                'approved'     => true,
                'order_number' => $order['order_number'],
                'auth_code'    => $result['auth_code'],
            ], 'Pago procesado exitosamente.');
        } else {
            logActivity('payment_failed', 'order', $order['id'],
                "Pago rechazado Azul: {$order['order_number']} - {$result['message']}");

            errorResponse('Pago rechazado: ' . $result['message'], 400);
        }
    }
}
```

### Paso 5: Agregar ruta en api/index.php

```php
case 'payments':
    require_once __DIR__ . '/controllers/PaymentController.php';
    if ($action === 'process' && $method === 'POST') {
        PaymentController::process();
    }
    break;
```

### Paso 6: Formulario de tarjeta en el checkout (frontend)

Agregar un paso adicional en el checkout cuando el metodo de pago es "Tarjeta con Azul":

```html
<!-- Dentro del step 2 de checkout, al seleccionar tarjeta con Azul habilitado -->
<div id="ck-card-form" class="hidden mt-4 bg-gray-800 border border-blue-500/30 rounded-xl p-4">
    <h4 class="text-sm font-semibold text-blue-400 mb-3">Datos de la Tarjeta</h4>
    <div class="space-y-3">
        <div>
            <label class="block text-xs text-gray-400 mb-1">Numero de tarjeta</label>
            <input type="text" id="ck-card-number" maxlength="19" placeholder="0000 0000 0000 0000"
                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white font-mono">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Vencimiento</label>
                <input type="text" id="ck-card-exp" maxlength="5" placeholder="MM/AA"
                       class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white font-mono">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">CVV</label>
                <input type="password" id="ck-card-cvc" maxlength="4" placeholder="***"
                       class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white font-mono">
            </div>
        </div>
    </div>
    <p class="text-xs text-gray-500 mt-3">
        <i class="fas fa-lock mr-1"></i>Pago seguro procesado por Azul.
        Los datos de tu tarjeta no se almacenan.
    </p>
</div>
```

### Paso 7: JavaScript para procesar el pago

```javascript
// Dentro de submitCheckoutOrder(), si el metodo es 'card' y Azul esta habilitado:
async function processAzulPayment(orderId) {
    const cardNumber = document.getElementById('ck-card-number').value.replace(/\s/g, '');
    const expParts = document.getElementById('ck-card-exp').value.split('/');
    const expiration = '20' + expParts[1] + expParts[0]; // YYYYMM
    const cvc = document.getElementById('ck-card-cvc').value;

    const res = await fetch(`${API_BASE}/payments/process`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, card_number: cardNumber, expiration, cvc })
    });
    return await res.json();
}
```

## Flujo Modificado con Azul

1. Cliente llena datos y selecciona "Tarjeta"
2. Se muestra formulario de tarjeta (numero, vencimiento, CVV)
3. Al confirmar, primero se crea el pedido via `POST /orders` (status pending)
4. Luego se procesa el pago via `POST /payments/process` con el `order_id`
5. Si Azul aprueba -> pedido pasa a `confirmed`, stock se descuenta
6. Si Azul rechaza -> pedido queda `pending`, se muestra error

## Consideraciones de Seguridad

1. **HTTPS obligatorio**: El sitio DEBE tener certificado SSL activo
2. **No almacenar datos de tarjeta**: Los numeros de tarjeta se envian directamente al API y se descartan
3. **Certificado de cliente**: El certificado .pem de Azul no debe ser accesible via web
4. **PCI DSS**: Al no almacenar datos de tarjeta, calificas para SAQ A-EP (nivel mas bajo de cumplimiento)
5. **Logs**: Nunca loguear numeros de tarjeta completos

## Codigos de Respuesta Azul

| IsoCode | Significado |
|---------|-------------|
| 00 | Aprobado |
| 01 | Referir a emisor |
| 05 | No autorizado |
| 12 | Transaccion invalida |
| 14 | Tarjeta invalida |
| 51 | Fondos insuficientes |
| 54 | Tarjeta expirada |
| 61 | Excede limite |
| 91 | Emisor no disponible |

## Costos

- **Comision por transaccion**: Generalmente 3.5% - 4.5% del monto (negociable)
- **Cuota mensual**: Varia segun el banco y el volumen
- **Tiempo de deposito**: Generalmente 48-72 horas habiles
- **No hay costo de integracion**: Solo el contrato de comercio

## Contacto Azul para E-Commerce

- **Departamento E-Commerce**: ecommerce@azul.com.do
- **Telefono**: (809) 544-2985
- **Documentacion**: Se proporciona al firmar el contrato de comercio
- **Soporte tecnico**: Disponible durante la integracion

## Pasos para Activar

1. Contactar a Azul o tu banco y solicitar servicio de e-commerce
2. Firmar contrato de comercio
3. Recibir credenciales de sandbox (MerchantId, Auth1, Auth2, certificado)
4. Implementar y probar con tarjetas de prueba
5. Solicitar revision de Azul (ellos revisan tu integracion)
6. Recibir credenciales de produccion
7. Cambiar `azul_environment` a `production` y actualizar credenciales
