# Guía de Despliegue a Producción — BBR Fragrance

> **Para quién es esta guía:** El desarrollador o administrador encargado de llevar el sistema desde XAMPP local a un hosting/servidor real.  
> **Tiempo estimado:** 2–4 horas la primera vez.

---

## Índice

1. [Requisitos del Servidor](#1-requisitos-del-servidor)
2. [Cambios Obligatorios en el Código](#2-cambios-obligatorios-en-el-código)
3. [Base de Datos](#3-base-de-datos)
4. [Subir los Archivos al Servidor](#4-subir-los-archivos-al-servidor)
5. [Configuración del Servidor Web (Apache)](#5-configuración-del-servidor-web-apache)
6. [Configuración Post-Despliegue (Panel Admin)](#6-configuración-post-despliegue-panel-admin)
7. [Cron Job — Cancelación de Órdenes](#7-cron-job--cancelación-de-órdenes)
8. [Checklist de Seguridad](#8-checklist-de-seguridad)
9. [Verificación Final — Pruebas](#9-verificación-final--pruebas)
10. [Solución de Problemas Comunes](#10-solución-de-problemas-comunes)

---

## 1. Requisitos del Servidor

Antes de empezar, confirma que el hosting cumple con estos requisitos mínimos:

| Requisito | Mínimo | Notas |
|---|---|---|
| PHP | 8.0 o superior | Preferible 8.2+ |
| MySQL / MariaDB | 5.7 / 10.4+ | MariaDB funciona igual |
| Apache | 2.4+ | Necesita `mod_rewrite` habilitado |
| SSL/HTTPS | **Obligatorio** | Let's Encrypt es gratis |
| Acceso FTP / cPanel | Sí | Para subir archivos |
| Cron Jobs | Sí | Para auto-cancelar pedidos |
| Módulos PHP | `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl` | Todos comunes en hosting compartido |

> **Hostings recomendados para República Dominicana:** Hostinger, SiteGround, o cualquier VPS (DigitalOcean, Linode).

---

## 2. Cambios Obligatorios en el Código

Estos cambios son **críticos**. Sin ellos, el sistema no funcionará correctamente en producción. Hazlos **antes** de subir los archivos.

---

### 2.1 — Credenciales de Base de Datos

**Archivo:** `api/config/database.php`  
**Problema:** Las credenciales actuales son las del entorno local XAMPP.

Reemplaza las líneas 11–14 con los datos reales del servidor:

```php
// ANTES (local XAMPP):
private $host     = 'localhost';
private $dbname   = 'bbr_fragance';
private $username = 'root';
private $password = 'raffy1992';

// DESPUÉS (producción):
private $host     = 'localhost';          // Normalmente sigue siendo 'localhost'
private $dbname   = 'tuusuario_bbr';      // El nombre que le diste al crear la BD en cPanel
private $username = 'tuusuario_dbuser';   // Usuario de la BD en cPanel
private $password = 'CONTRASEÑA_SEGURA';  // Contraseña elegida en cPanel
```

> **Cómo obtener estos datos:** En cPanel → Bases de datos MySQL → crea la base de datos y el usuario, y cPanel te mostrará el nombre completo (ej: `rafaelt_bbr`).

---

### 2.2 — Ruta Base de la API (Backend)

**Archivo:** `api/index.php`  
**Línea:** 35  
**Problema:** La ruta tiene `/BBR_FRAGANCE/api` que es la ruta local de XAMPP.

```php
// ANTES:
$basePath = '/BBR_FRAGANCE/api';

// DESPUÉS (si el sistema está en la raíz del dominio, ej: tudominio.com):
$basePath = '/api';

// DESPUÉS (si está en un subdirectorio, ej: tudominio.com/tienda):
$basePath = '/tienda/api';
```

---

### 2.3 — Ruta Base de la API (Frontend Admin)

**Archivo:** `js/admin/core.js`  
**Línea:** 8  

```javascript
// ANTES:
const API = '/BBR_FRAGANCE/api';

// DESPUÉS (raíz del dominio):
const API = '/api';

// DESPUÉS (subdirectorio):
const API = '/tienda/api';
```

---

### 2.4 — Ruta Base de la API (Frontend Tienda)

**Archivo:** `js/main/core.js`  
**Línea:** 8  

```javascript
// ANTES:
const API_BASE = '/BBR_FRAGANCE/api';

// DESPUÉS (raíz del dominio):
const API_BASE = '/api';

// DESPUÉS (subdirectorio):
const API_BASE = '/tienda/api';
```

---

### 2.5 — CORS: Restringir a tu Dominio

**Archivo:** `api/index.php`  
**Línea:** 15  
**Problema:** El `*` permite peticiones desde cualquier sitio en internet.

```php
// ANTES (inseguro):
header('Access-Control-Allow-Origin: *');

// DESPUÉS (reemplaza con tu dominio real):
header('Access-Control-Allow-Origin: https://tudominio.com');
```

---

### 2.6 — Cookie de Sesión Segura (HTTPS)

**Archivo:** `api/index.php`  
**Líneas:** 8–11  
**Problema:** Falta el flag `secure` que obliga a enviar la cookie solo por HTTPS.

```php
// ANTES:
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);

// DESPUÉS:
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => true       // <-- añadir esta línea
]);
```

---

### 2.7 — Número de WhatsApp (Opcional)

**Archivo:** `js/main/core.js`  
**Línea:** 12  

Verifica que el número sea el correcto para producción:

```javascript
const CONFIG = {
    whatsappNumber: '18094855693',   // Confirmar que este sea el número correcto
    ...
};
```

---

### Resumen de Cambios — Lista de Verificación

```
[ ] api/config/database.php   — Credenciales de BD de producción
[ ] api/index.php             — $basePath corregido
[ ] api/index.php             — CORS restringido a tu dominio
[ ] api/index.php             — Cookie con flag 'secure' => true
[ ] js/admin/core.js          — const API corregido
[ ] js/main/core.js           — const API_BASE corregido
```

---

## 3. Base de Datos

### 3.1 — Crear la Base de Datos en cPanel

1. Entra a **cPanel → Bases de datos MySQL**
2. Crea una nueva base de datos: `tuusuario_bbr` (o el nombre que prefieras)
3. Crea un usuario con contraseña fuerte
4. **Asígnale TODOS los privilegios** al usuario sobre esa base de datos
5. Anota el nombre de BD, usuario y contraseña (los necesitas en el paso 2.1)

### 3.2 — Importar el Esquema y Datos

Los archivos deben importarse **en este orden exacto** desde cPanel → phpMyAdmin:

| Orden | Archivo | Qué hace |
|---|---|---|
| 1 | `database/schema.sql` | Crea todas las tablas base |
| 2 | `database/seed.sql` | Datos iniciales (admin, categorías, config) |
| 3 | `database/migration_fase1.sql` | Sistema de roles y permisos |
| 4 | `database/migration_ncf.sql` | Secuencias NCF (comprobantes fiscales DGII) |
| 5 | `database/migration_cardnet.sql` | Columnas de pasarela de pago |
| 6 | `database/migration_checkout.sql` | Config SMTP + método de pago por banco |
| 7 | `database/migration_promo.sql` | Contenido promocional |
| 8 | `database/migration_web_sales.sql` | Integración ventas web |

**Cómo importar en phpMyAdmin:**
1. Selecciona la base de datos recién creada en la columna izquierda
2. Haz clic en la pestaña **Importar**
3. Sube el archivo `.sql`
4. Clic en **Continuar**
5. Repite para cada archivo en orden

> **Si ya tienes datos de prueba en local:** Puedes exportar la BD completa desde XAMPP (phpMyAdmin → Exportar → Quick) e importarla directamente. Esto reemplaza los pasos de arriba.

---

## 4. Subir los Archivos al Servidor

### 4.1 — Qué Subir

Sube **todo el contenido** de la carpeta `BBR_FRAGANCE/` a la carpeta `public_html/` de tu hosting (o a un subdirectorio si lo deseas).

```
Estructura en el servidor (raíz del dominio):
public_html/
├── index.html
├── pages/
├── js/
├── css/
├── images/
├── uploads/       ← asegúrate que exista y tenga permisos 755
├── api/
│   ├── .htaccess
│   ├── index.php
│   ├── config/
│   ├── controllers/
│   ├── services/
│   ├── middleware/
│   ├── helpers/
│   ├── lib/       ← PHPMailer incluido aquí
│   └── cron/
└── docs/          ← puedes omitirlo en producción
```

### 4.2 — Archivos que NO Debes Subir

```
❌ database/          (archivos .sql — solo se usan una vez para importar)
❌ .git/              (repositorio git local)
❌ docs/              (documentación interna)
❌ bbr_fragance.sql   (dump local)
❌ *.md               (documentación)
```

### 4.3 — Permisos de Carpetas

Después de subir, configura los permisos correctos (vía cPanel → Administrador de archivos o FTP):

| Carpeta / Archivo | Permiso | Motivo |
|---|---|---|
| `uploads/` | **755** | PHP puede escribir imágenes |
| `uploads/products/` | **755** | Subida de fotos de productos |
| `uploads/promo/` | **755** | Subida de banners promocionales |
| `api/` | **755** | Ejecución normal |
| `api/config/database.php` | **640** | Nadie externo puede leerlo |
| Resto de archivos `.php` | **644** | Lectura pero no ejecución directa |

---

## 5. Configuración del Servidor Web (Apache)

### 5.1 — Verificar que mod_rewrite está Activo

El sistema requiere que las URLs se reescriban para que la API funcione. En la mayoría de los hostings ya está activo. Si no, contacta al soporte del hosting y pide que habiliten `mod_rewrite`.

### 5.2 — Archivo `.htaccess` de la API

El archivo `api/.htaccess` ya existe y está correcto. Verifica que tenga este contenido:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php [QSA,L]
```

### 5.3 — Archivo `.htaccess` Raíz (Crear si no existe)

Si el sistema está en la raíz del dominio, crea un archivo `.htaccess` en `public_html/`:

```apache
# Seguridad: ocultar archivos de configuración
<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

<Files "*.md">
    Order allow,deny
    Deny from all
</Files>

# Redirigir HTTP a HTTPS (cuando tengas SSL)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5.4 — PHP — Configuración Recomendada

Si tienes acceso a `php.ini` o a las opciones de PHP en cPanel, configura:

```ini
display_errors = Off          ; Nunca mostrar errores en producción
log_errors = On               ; Sí registrarlos en el log
error_reporting = E_ALL
upload_max_filesize = 10M     ; Para subir imágenes de productos
post_max_size = 12M
max_execution_time = 60
session.cookie_secure = 1     ; Solo HTTPS
session.cookie_httponly = 1   ; Sin acceso JS
```

---

## 6. Configuración Post-Despliegue (Panel Admin)

Una vez que el sistema esté en línea, entra al panel admin y configura lo siguiente:

**URL del panel:** `https://tudominio.com/pages/admin-login.html`

> Credenciales por defecto (del seed.sql): usuario `admin` / contraseña `admin123` — **¡Cámbiala de inmediato!**

---

### 6.1 — Cambiar Contraseña del Administrador

1. Entra al panel admin
2. Ve a **Usuarios** → haz clic en el usuario `admin`
3. Cambia la contraseña a una segura (mínimo 12 caracteres, mayúsculas, números y símbolos)

---

### 6.2 — Configurar Email SMTP

Panel Admin → **Configuración → Email (SMTP)**

| Campo | Valor (ejemplo Hostinger) |
|---|---|
| Servidor SMTP | `smtp.hostinger.com` |
| Puerto | `465` (SSL) o `587` (TLS) |
| Usuario | `noreply@tudominio.com` |
| Contraseña | La del correo |
| Nombre del remitente | `BBR Fragrance` |
| Email del remitente | `noreply@tudominio.com` |

Después de guardar, haz clic en **"Enviar email de prueba"** para confirmar que funciona.

---

### 6.3 — Configurar Pasarela de Pago Cardnet

Panel Admin → **Configuración → Pagos → Cardnet**

> Necesitas credenciales reales de Cardnet. Contáctalos en [cardnet.com.do](https://cardnet.com.do) para obtener una cuenta de producción.

| Campo | Descripción |
|---|---|
| Entorno | **Producción** (no sandbox ni simulador) |
| Número de afiliado | 15 dígitos — te lo da Cardnet |
| Terminal | 8 dígitos — te lo da Cardnet |
| Clave secreta | Para firmar las transacciones (HMAC-SHA512) |
| Código de moneda | `214` (Peso Dominicano — DOP) |

Activa el switch **"Habilitar pago con tarjeta online"** cuando todo esté configurado.

---

### 6.4 — Información General de la Tienda

Panel Admin → **Configuración → General**

- Nombre del negocio
- Dirección
- Teléfono
- Moneda
- Impuesto (ITBIS 18%)
- URL de la tienda (para links en emails)

---

### 6.5 — Configurar NCF (Comprobantes Fiscales)

Si el negocio está registrado en la DGII y emite comprobantes fiscales:

Panel Admin → **Configuración → NCF**

- Activa los tipos de comprobante que uses (01, 02, 14, 15)
- Configura los rangos de secuencia según la autorización de la DGII

---

## 7. Cron Job — Cancelación de Órdenes

El sistema tiene un script que cancela automáticamente los pedidos que llevan demasiado tiempo en estado "pendiente". Necesita ejecutarse periódicamente.

### 7.1 — Configurar en cPanel

1. Entra a **cPanel → Cron Jobs**
2. Selecciona frecuencia: **Cada 30 minutos** (recomendado)
3. En el campo de comando:

```bash
php /home/tuusuario/public_html/api/cron/cancel-expired-orders.php >> /home/tuusuario/logs/bbr_cron.log 2>&1
```

> **Nota:** Reemplaza `/home/tuusuario/public_html/` con la ruta real de tu hosting. Puedes verla en cPanel → Terminal o preguntándole al soporte.

### 7.2 — Configurar el Tiempo de Expiración

Panel Admin → **Configuración → Pedidos → Horas de expiración**

Por defecto son 48 horas. Ajusta según la política del negocio.

---

## 8. Checklist de Seguridad

Antes de abrir el sistema al público, verifica cada punto:

```
CÓDIGO
[ ] Credenciales de BD reemplazadas (api/config/database.php)
[ ] CORS restringido al dominio (api/index.php línea 15)
[ ] Cookie de sesión con flag 'secure' => true (api/index.php)
[ ] $basePath corregido (api/index.php línea 35)
[ ] Paths de API corregidos en JS (admin/core.js y main/core.js)

SERVIDOR
[ ] SSL/HTTPS activo y funcionando
[ ] Redirección HTTP → HTTPS configurada
[ ] Carpeta uploads/ sin ejecución PHP (el .htaccess ya lo hace)
[ ] Archivos .sql NO accesibles desde el browser
[ ] display_errors = Off en PHP

PANEL ADMIN
[ ] Contraseña de admin cambiada
[ ] SMTP configurado y probado
[ ] URL del sistema guardada en configuración (para links en emails)

BASE DE DATOS
[ ] Todas las migraciones importadas en orden
[ ] Usuario de BD con solo los permisos necesarios (no root)
[ ] Backup inicial guardado en lugar seguro
```

---

## 9. Verificación Final — Pruebas

Realiza estas pruebas en orden después del despliegue:

### Prueba 1 — Tienda Online

- [ ] `https://tudominio.com` carga la página principal
- [ ] Los productos se muestran correctamente con imágenes
- [ ] Se puede agregar un producto al carrito
- [ ] El proceso de checkout funciona hasta el formulario de pago

### Prueba 2 — Panel Administrativo

- [ ] `https://tudominio.com/pages/admin-login.html` carga
- [ ] Login con usuario admin funciona
- [ ] El dashboard muestra estadísticas
- [ ] Se puede crear un producto nuevo con imagen
- [ ] Se puede ver el listado de pedidos

### Prueba 3 — Email

- [ ] Enviar email de prueba desde Configuración → SMTP
- [ ] Crear un pedido de prueba y verificar que llega el email de confirmación

### Prueba 4 — Pasarela de Pago

- [ ] Con el entorno en **sandbox** primero, hacer un pago de prueba
- [ ] Verificar que el pedido cambia a "pagado" en el panel admin
- [ ] Cambiar a **producción** solo cuando sandbox funcione correctamente

### Prueba 5 — Cron Job

- [ ] Ejecutar el script manualmente una vez para confirmar que no da error:
  ```bash
  php /home/tuusuario/public_html/api/cron/cancel-expired-orders.php
  ```
- [ ] Verificar el archivo de log generado

---

## 10. Solución de Problemas Comunes

### "Error 500 — Internal Server Error"
- Activa temporalmente `display_errors = On` en PHP para ver el error real
- Revisa que todas las rutas en `api/index.php` (línea 35) sean correctas
- Confirma que `mod_rewrite` está habilitado

### "La API no responde (404)"
- Verifica que `api/.htaccess` fue subido al servidor
- Confirma que `$basePath` en `api/index.php` coincide con la URL real
- Asegúrate de que `mod_rewrite` está activo

### "No puedo hacer login en el admin"
- Abre las DevTools del browser (F12) → pestaña Network → intenta el login y revisa la respuesta
- Confirma que la BD fue importada correctamente con el seed.sql
- Si `secure => true` está activo en sesiones, confirma que HTTPS funciona

### "Las imágenes no se suben"
- Verifica permisos de `uploads/` → debe ser **755**
- Confirma el límite `upload_max_filesize` en PHP (mínimo 5M)

### "El email no llega"
- Prueba las credenciales SMTP directamente en un cliente de email
- Verifica que el puerto 465 o 587 no esté bloqueado por el hosting
- Revisa la carpeta de spam del destinatario

### "Error de CORS en el navegador"
- Confirma que `Access-Control-Allow-Origin` en `api/index.php` tiene exactamente la URL de tu dominio (con `https://` y sin `/` al final)

---

## Notas Finales

- **Backups:** Configura backups automáticos de la BD en cPanel (o hazlos manualmente cada semana).
- **Actualizaciones:** Cuando hagas cambios en el código local, sube solo los archivos modificados. Nunca sobreescribas `uploads/` con la versión local.
- **Logs:** Monitorea los logs de PHP y Apache periódicamente, especialmente las primeras semanas.
- **Cardnet:** Guarda las credenciales de producción en un lugar seguro y separado del código (no las subas al repositorio git).

---

*Documento generado para BBR Fragrance — Sistema de gestión y tienda online.*
