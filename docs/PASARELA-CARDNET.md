# Integracion con la Pasarela de Pago Cardnet

> Este documento es la **guia operativa** para activar el cobro en linea con
> Cardnet en la tienda BBR Fragrance. Toda la implementacion tecnica ya esta
> hecha; solo necesitas obtener las credenciales con Cardnet, llenarlas en
> el panel de administracion y ejecutar la migracion SQL.

---

## 0. Probar el flujo SIN credenciales (Modo Simulador)

Si todavia no tienes los datos de Cardnet pero quieres validar el flujo
completo (checkout -> redireccion -> aprobacion/rechazo -> actualizacion del
pedido en admin), usa el **modo simulador local**:

1. Ejecuta la migracion (paso 2.1 mas abajo) si aun no lo hiciste.
2. En el panel admin -> Configuracion -> Pasarela Cardnet:
   - **Ambiente:** `Simulador (Local, sin credenciales)`
   - Toggle **Activar pasarela Cardnet**: ON
   - Deja vacios Merchant Number, Terminal, Secret Key.
   - Guardar.
3. En *Pedidos Online -> Metodos de pago aceptados*, activa
   **Tarjeta en Linea (Cardnet)** y guarda.
4. Atajo SQL equivalente (sin tocar el panel):
   ```sql
   UPDATE settings SET setting_value='1'         WHERE setting_key='cardnet_enabled';
   UPDATE settings SET setting_value='simulator' WHERE setting_key='cardnet_environment';
   UPDATE settings SET setting_value='1'         WHERE setting_key='checkout_pay_card_online';
   ```
5. Ve a la tienda publica, agrega un producto al carrito y elige
   *Pagar en linea con Tarjeta*. Seras redirigido a una pagina local
   `/api/payments/cardnet/mock` que simula la HPP de Cardnet con tres
   botones: **Aprobar / Rechazar / Cancelar**.
6. Al volver, el modal de resultado debe aparecer y el pedido en
   *Admin -> Pedidos* debe mostrar `payment_status = paid` (o `failed`
   segun lo que elegiste) con `payment_authorization` simulado
   (`SIM######`).

> Cuando recibas las credenciales reales, solo cambia el ambiente a
> `sandbox` o `production`, completa los campos y todo seguira funcionando
> sin tocar codigo.

---

## 1. Resumen de lo que YA esta implementado

| Componente | Archivo | Estado |
|---|---|---|
| Migracion BD | [database/migration_cardnet.sql](../database/migration_cardnet.sql) | Listo |
| Servicio HTTP a Cardnet | [api/services/CardnetService.php](../api/services/CardnetService.php) | Listo |
| Controlador de pagos | [api/controllers/PaymentController.php](../api/controllers/PaymentController.php) | Listo |
| Rutas `/payments/*` | [api/index.php](../api/index.php) | Listo |
| Soporte `card_online` en pedidos | [api/controllers/OrderController.php](../api/controllers/OrderController.php) | Listo |
| Boton "Pagar en linea" en checkout | [index.html](../index.html) | Listo |
| Modal de resultado de pago | [index.html](../index.html) | Listo |
| Flujo JS de redireccion + retorno | [js/main/ui.js](../js/main/ui.js), [js/main/cart.js](../js/main/cart.js) | Listo |
| UI de configuracion (admin) | [pages/admin.html](../pages/admin.html), [js/admin/settings.js](../js/admin/settings.js) | Listo |
| Ocultar `secret_key` del API | [api/controllers/SettingsController.php](../api/controllers/SettingsController.php) | Listo |

El metodo de pago **NO aparecera** en el checkout publico hasta que:
1. Hayas ejecutado la migracion SQL.
2. Hayas llenado las credenciales en el panel admin.
3. Hayas activado los toggles **"Activar pasarela Cardnet"** y
   **"Tarjeta en Linea (Cardnet)"** en *Configuracion -> Pedidos Online*.

---

## 2. Pasos de activacion (CHECKLIST)

### 2.1. Ejecutar la migracion SQL

```bash
mysql -u root BBR Fragrance < database/migration_cardnet.sql
```

Esto agrega:
- 12 settings nuevos (todos vacios o en `0` por defecto).
- 8 columnas a la tabla `orders` para auditar el pago.

### 2.2. Solicitar credenciales a Cardnet

Contactos:
- **Telefono:** (809) 200-2727
- **Web comercio:** <https://www.cardnet.com.do/comercios>
- **Soporte ecommerce:** ecommerce@cardnet.com.do

Pide especificamente acceso al **Sandbox** primero. Cardnet te entregara un PDF
("Guia de Integracion E-Commerce") y un correo con los siguientes valores:

### 2.3. Datos que necesitas obtener de Cardnet

> Estos son los UNICOS datos que faltan para que la pasarela funcione.

| Campo en el panel | Lo entrega Cardnet como | Obligatorio | Notas |
|---|---|---|---|
| **Merchant Number** | `MerchantNumber` / `Numero de Comercio` | SI | 9-15 digitos numericos. |
| **Merchant Terminal** | `MerchantTerminal` / `Terminal` | SI | 6-8 digitos numericos. |
| **Secret Key (HMAC)** | `Llave de cifrado` / `MAC Secret` | SI | Cadena alfanumerica larga. **No se muestra despues de guardada**. |
| **Merchant Name** | `MerchantName` | Opcional | Por defecto "BBR Fragrance". |
| **Merchant Type (MCC)** | `MerchantType` (MCC) | Opcional | 4 digitos. Ej: `5912` (perfumeria/farmacia). |
| **Codigo de Moneda** | `CurrencyCode` | SI (preconfigurado) | `214` para Pesos Dominicanos. |
| **Acquiring Institution Code** | `AcquiringInstitutionCode` | SI (preconfigurado) | `349` para Cardnet. |
| **Ambiente** | -- | SI | `sandbox` para pruebas, `production` para cobro real. |
| **URL de Retorno** | -- | Opcional | Si la dejas vacia se usa `https://TU-DOMINIO/BBR Fragrance/api/payments/cardnet/return`. **Esta URL DEBE estar registrada con Cardnet.** |
| **URL de Cancelacion** | -- | Opcional | Si la dejas vacia se usa `https://TU-DOMINIO/BBR Fragrance/api/payments/cardnet/cancel`. Tambien debe registrarse con Cardnet. |

> **IMPORTANTE:** Las URLs de retorno y cancelacion deben ser comunicadas a tu
> ejecutivo de Cardnet para que las agregue a la *whitelist* de tu comercio.
> De lo contrario, Cardnet rechazara la peticion de sesion.

### 2.4. Llenar el panel admin

1. Iniciar sesion en `pages/admin.html` con un usuario que tenga `settings.manage`.
2. Ir a **Configuracion**.
3. Bajar hasta la seccion **"Pasarela Cardnet (Pago en Linea)"**.
4. Llenar los campos.
5. Activar el toggle **"Activar pasarela Cardnet"**.
6. Guardar.
7. Bajar a **"Pedidos Online -> Metodos de pago aceptados"** y activar
   **"Tarjeta en Linea (Cardnet)"**.
8. Guardar.

### 2.5. Probar en Sandbox

Cardnet entrega tarjetas de prueba con la guia. Tipicamente:
- Visa aprobada: `4012000033330026` venc. `12/25` CVV `123`
- Mastercard rechazada: `5424000000000015`

(Confirma con tu ejecutivo, los numeros pueden variar por comercio).

Verifica:
- El boton "Pagar en linea con Tarjeta" aparece en el checkout publico.
- Al confirmar, eres redirigido a `lab.cardnet.com.do`.
- Despues del pago, regresas a la tienda con un modal de resultado.
- En `pages/admin.html -> Pedidos`, el pedido aparece con `payment_status = paid`,
  `payment_authorization` y `payment_transaction_id` poblados.

### 2.6. Pasar a Produccion

1. En el panel, cambiar **Ambiente** a `production`.
2. Reemplazar `MerchantNumber`, `MerchantTerminal` y `SecretKey` por los de produccion.
3. Asegurar que el sitio se sirva por **HTTPS** con certificado valido.
4. Confirmar con Cardnet que las URLs de retorno/cancelacion de produccion estan
   registradas.

---

## 3. Flujo del cliente (UX)

```
Cliente              Tienda (Frontend)        BBR API (PHP)         Cardnet HPP
   |                       |                      |                      |
   |  Step 1: datos        |                      |                      |
   |---->                  |                      |                      |
   |  Step 2: elige        |                      |                      |
   |   "Pagar en linea"    |                      |                      |
   |---->                  |                      |                      |
   |  Step 3: confirma     |                      |                      |
   |---->                  |                      |                      |
   |                       | POST /orders         |                      |
   |                       |-------------------->|                      |
   |                       |  {order_id, total}  |                      |
   |                       |<--------------------|                      |
   |                       | POST /payments/      |                      |
   |                       |   cardnet/session   |                      |
   |                       |-------------------->|                      |
   |                       |                      | POST /sessions      |
   |                       |                      |--------------------->
   |                       |                      | {SESSION, KEY}      |
   |                       |                      |<---------------------
   |                       | {redirect_url,...}  |                      |
   |                       |<--------------------|                      |
   |                       | auto-POST a Cardnet (form oculto)          |
   |                       |--------------------------------------------|
   |  Pagina segura HPP <--------------------------------------------|
   |  ingresa tarjeta + 3DS                                          |
   |---------------------------------------------------------------->|
   |                                          GET /api/payments/      |
   |                                            cardnet/return        |
   |<------------------------------------------------------------------|
   |                       |                      | POST /transactions/ |
   |                       |                      |   {SESSION}         |
   |                       |                      |--------------------->
   |                       |                      | {ResponseCode,Auth} |
   |                       |                      |<---------------------
   |                       |                      | UPDATE orders       |
   |                       |                      | (paid/failed)       |
   |  Redirect a index.html?payment=success&order=PED-...               |
   |<------------------------------------------------------------------|
   |  Modal de resultado   |                      |                      |
```

Si el cliente elige *Efectivo*, *Tarjeta al entregar* o *Transferencia*, el flujo
sigue siendo el mismo de siempre (el pedido se guarda como `pending` y el equipo
contacta al cliente). **No se rompio nada del flujo actual.**

---

## 4. Esquema de base de datos (referencia)

Columnas agregadas a `orders`:

| Columna | Tipo | Proposito |
|---|---|---|
| `payment_status` | ENUM(pending, paid, failed, refunded) | Estado del cobro |
| `payment_gateway` | VARCHAR(30) | Siempre `cardnet` para pagos online |
| `payment_session_key` | VARCHAR(255) | ID de sesion Cardnet (para reconciliacion) |
| `payment_transaction_id` | VARCHAR(100) | ID de transaccion Cardnet |
| `payment_authorization` | VARCHAR(50) | Codigo de autorizacion del banco |
| `payment_response_code` | VARCHAR(10) | `00` = aprobado |
| `payment_response_raw` | TEXT | Respuesta JSON completa de Cardnet (auditoria) |
| `payment_paid_at` | TIMESTAMP | Cuando se confirmo el pago |

---

## 5. Endpoints API expuestos

| Metodo | Ruta | Auth | Proposito |
|---|---|---|---|
| `GET` | `/api/payments/status` | Publico | Saber si la pasarela esta activa |
| `POST` | `/api/payments/cardnet/session` | Publico | Crear sesion para un pedido (despues de POST /orders) |
| `GET/POST` | `/api/payments/cardnet/return` | Publico (callback Cardnet) | Procesar respuesta exitosa |
| `GET/POST` | `/api/payments/cardnet/cancel` | Publico (callback Cardnet) | Procesar cancelacion |

---

## 6. Codigos de respuesta (ResponseCode)

| Codigo | Significado |
|---|---|
| `00` | Aprobado |
| `01` | Referir a emisor |
| `05` | No autorizado |
| `12` | Transaccion invalida |
| `14` | Tarjeta invalida |
| `51` | Fondos insuficientes |
| `54` | Tarjeta expirada |
| `61` | Excede limite |
| `91` | Emisor no disponible |
| `96` | Falla del sistema |

---

## 7. Seguridad

- La **Secret Key (HMAC)** se almacena en `settings` y se filtra en
  `SettingsController` para no exponerse en `GET /api/settings`.
- El campo del formulario admin es `type="password"` y solo se actualiza si el
  usuario escribe un valor nuevo (asi no se sobreescribe con vacio).
- Los datos de tarjeta NUNCA tocan el servidor de BBR: el cliente los entrega
  directamente a la pagina hospedada de Cardnet.
- El pedido permanece en `payment_status = pending` hasta que el callback
  server-to-server (`/payments/cardnet/return`) verifique con Cardnet.
- Si el cliente cierra el navegador a mitad del pago, el pedido queda como
  `pending` y el `OrderController::index()` lo cancelara automaticamente
  despues de 48 horas (logica ya existente).

---

## 8. Troubleshooting

| Problema | Causa probable | Solucion |
|---|---|---|
| El boton no aparece en el checkout | Toggles desactivados o credenciales vacias | Revisar admin -> Configuracion |
| `cURL error: SSL certificate problem` | Servidor sin CA-bundle | Actualizar `cacert.pem` de PHP o configurar `CURLOPT_CAINFO` |
| Cardnet responde `Invalid MAC` | Secret Key mal copiada o orden de campos cambiado | Re-pegar la clave; el orden del HMAC esta documentado en `CardnetService::buildSignature` |
| Cardnet responde `Invalid ReturnUrl` | URL no registrada en whitelist | Enviar las URLs exactas a tu ejecutivo de Cardnet |
| Pedido queda `pending` despues del pago | Cardnet no pudo llamar al callback (firewall/HTTP) | Verificar que `/api/payments/cardnet/return` sea accesible publicamente y use HTTPS |

---

## 9. Resumen ejecutivo: que pedirle a Cardnet

> Copia esta lista y envialaa tu ejecutivo de Cardnet:

```
Solicito activar el ambiente de pruebas (Sandbox) y produccion para
e-commerce con redireccion (HPP). Necesito que me entreguen:

1. MerchantNumber (Sandbox y Produccion)
2. MerchantTerminal (Sandbox y Produccion)
3. Secret Key / Llave HMAC (Sandbox y Produccion)
4. MerchantType (MCC) asignado
5. CurrencyCode confirmado (esperado: 214 - DOP)
6. AcquiringInstitutionCode (esperado: 349)
7. Tarjetas de prueba para Sandbox
8. Confirmacion de las URLs de retorno y cancelacion (las entrego a continuacion):
   - Retorno: https://MIDOMINIO/BBR Fragrance/api/payments/cardnet/return
   - Cancelacion: https://MIDOMINIO/BBR Fragrance/api/payments/cardnet/cancel
9. Documento de codigos de respuesta vigente.
```

Cuando los recibas, llenas el panel y la pasarela queda operativa.
