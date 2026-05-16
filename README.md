# BBR Fragrance - Sistema de Tienda de Perfumes

Sistema web completo para tienda de perfumes con panel de administracion, POS, gestion de inventario, pedidos online y comprobantes fiscales (NCF).

## Stack Tecnologico

| Capa | Tecnologia |
|------|-----------|
| Frontend | HTML5, Tailwind CSS (CDN), JavaScript Vanilla |
| Backend | PHP 8.x (REST API) |
| Base de datos | MySQL (PDO) |
| Email | PHPMailer (SMTP) |
| Servidor | XAMPP / Apache |

## Estructura del Proyecto

```
web-BBR Fragrance/
├── index.html                         # Pagina principal (tienda)
├── css/style.css                      # Estilos personalizados
├── pages/
│   ├── admin.html                     # Panel de administracion (SPA)
│   ├── admin-login.html               # Login del admin
│   ├── productos.html                 # Catalogo de productos
│   └── producto-detalle.html          # Detalle de producto
├── js/
│   ├── admin/                         # JS del panel admin (12 archivos)
│   │   ├── core.js                    # Globales, API, auth, navegacion
│   │   ├── dashboard.js               # Dashboard y graficas
│   │   ├── products.js                # CRUD productos + inventario
│   │   ├── pos.js                     # Punto de Venta + pagos mixtos
│   │   ├── orders.js                  # Gestion de pedidos
│   │   ├── expenses.js                # Gastos
│   │   ├── cash-register.js           # Caja registradora
│   │   ├── reports.js                 # Reportes
│   │   ├── customers.js               # Clientes (RNC/Cedula)
│   │   ├── sales.js                   # Ventas + NCF
│   │   ├── settings.js                # Config, NCF, promos, marcas, usuarios
│   │   └── init.js                    # Event listeners DOMContentLoaded
│   └── main/                          # JS de la tienda publica (6 archivos)
│       ├── core.js                    # API helper, formatPrice, utilidades
│       ├── products.js                # Carga y render de productos
│       ├── cart.js                    # Carrito (localStorage) + checkout
│       ├── product-detail.js          # Pagina de detalle
│       ├── ui.js                      # Modales, filtros, checkout modal
│       └── init.js                    # Inicializacion de paginas
├── api/
│   ├── index.php                      # Router principal (segment-based)
│   ├── config/database.php            # Conexion PDO a MySQL
│   ├── middleware/auth.php            # Autenticacion, permisos por rol
│   ├── helpers/
│   │   ├── response.php               # JSON responses estandarizadas
│   │   └── validation.php             # Validaciones reutilizables
│   ├── controllers/
│   │   ├── AuthController.php         # Login, logout, sesion
│   │   ├── ProductController.php      # CRUD productos, busqueda, imagenes
│   │   ├── CategoryController.php     # Categorias
│   │   ├── SaleController.php         # Ventas POS + NCF + pagos mixtos
│   │   ├── OrderController.php        # Pedidos online + rate limiting
│   │   ├── CustomerController.php     # Clientes + RNC/Cedula
│   │   ├── ExpenseController.php      # Gastos
│   │   ├── CashRegisterController.php # Apertura/cierre de caja
│   │   ├── NcfController.php          # Secuencias NCF (DGII)
│   │   ├── SettingsController.php     # Configuracion key-value
│   │   ├── UserController.php         # Usuarios del sistema
│   │   ├── RoleController.php         # Roles y permisos
│   │   └── ReportController.php       # Reportes y estadisticas
│   ├── services/
│   │   └── MailService.php            # Emails con PHPMailer
│   └── lib/PHPMailer/                 # PHPMailer v6.9.1
├── database/
│   ├── schema.sql                     # Esquema completo de la BD
│   ├── seed.sql                       # Datos iniciales
│   ├── migration_ncf.sql              # Migracion NCF
│   └── migration_checkout.sql         # Migracion SMTP/banco
├── uploads/                           # Imagenes subidas (productos)
└── images/                            # Imagenes estaticas del sitio
```

## Instalacion

### Requisitos
- XAMPP (Apache + MySQL + PHP 8.x)
- Navegador moderno

### Pasos

1. Clonar el proyecto en la carpeta `htdocs` de XAMPP:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs
git clone <repo-url> web-BBR Fragrance
```

2. Crear la base de datos y ejecutar el esquema:
```bash
mysql -u root -p < web-BBR Fragrance/database/schema.sql
mysql -u root -p BBR Fragrance < web-BBR Fragrance/database/seed.sql
```

3. Si es una actualizacion, ejecutar las migraciones:
```bash
mysql -u root -p BBR Fragrance < web-BBR Fragrance/database/migration_ncf.sql
mysql -u root -p BBR Fragrance < web-BBR Fragrance/database/migration_checkout.sql
```

4. Configurar la conexion en `api/config/database.php` si es necesario.

5. Acceder:
   - Tienda: `http://localhost/web-BBR Fragrance/`
   - Admin: `http://localhost/web-BBR Fragrance/pages/admin-login.html`

## Modulos del Sistema

### Tienda Publica (Frontend)

- **Catalogo**: Productos con filtros por categoria, familia olfativa, marca y precio
- **Busqueda**: Busqueda en tiempo real via API con debounce
- **Carrito**: Persistente en localStorage con modal lateral
- **Checkout profesional**: Modal de 3 pasos (Datos > Pago > Confirmar)
  - Metodos de pago: Efectivo, Tarjeta, Transferencia bancaria
  - Muestra datos bancarios al seleccionar transferencia
  - Validacion de campos obligatorios
  - Pantalla de exito con numero de pedido
- **WhatsApp**: Pedidos y consultas directas

### Panel de Administracion (SPA)

- **Dashboard**: Ventas del dia, productos, estadisticas
- **POS (Punto de Venta)**: Ventas rapidas con busqueda de productos
  - Pagos: Efectivo, tarjeta, transferencia, **pagos mixtos**
  - Calculo automatico de cambio
  - Requiere caja abierta para vender
  - Recibos imprimibles
- **Productos**: CRUD completo con imagenes, stock, SKU/codigo de barras
- **Inventario**: Ajustes de stock con historial
- **Pedidos**: Gestion de pedidos online con cambio de estado
- **Clientes**: Base de datos con RNC/Cedula
- **Gastos**: Registro de gastos operativos
- **Caja Registradora**: Apertura/cierre con monto inicial y desglose
- **Reportes**: Ventas, productos mas vendidos, ingresos por periodo
- **Configuracion**: Tienda, impuestos, envio, SMTP, datos bancarios, NCF
- **Usuarios y Roles**: Sistema de permisos granular por rol

## API REST

Base URL: `/web-BBR Fragrance/api`

### Autenticacion
| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| POST | /auth/login | Iniciar sesion | No |
| POST | /auth/logout | Cerrar sesion | Si |
| GET | /auth/me | Usuario actual | Si |

### Productos
| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| GET | /products | Listar productos | No |
| GET | /products/{id} | Detalle de producto | No |
| GET | /products/search?q= | Buscar productos | No |
| POST | /products | Crear producto | Si |
| PUT | /products/{id} | Actualizar producto | Si |
| DELETE | /products/{id} | Eliminar producto | Si |

### Pedidos (Online)
| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| POST | /orders | Crear pedido (publico) | No |
| GET | /orders | Listar pedidos | Si |
| GET | /orders/{id} | Detalle de pedido | Si |
| PUT | /orders/{id}/status | Cambiar estado | Si |
| DELETE | /orders/{id} | Eliminar pedido | Si |

### Ventas (POS)
| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| POST | /sales | Crear venta | Si |
| GET | /sales | Listar ventas | Si |
| GET | /sales/{id}/receipt | Recibo de venta | Si |

### Configuracion
| Metodo | Ruta | Descripcion | Auth |
|--------|------|-------------|------|
| GET | /settings | Obtener config | No* |
| POST | /settings | Guardar config | Si |

*Los campos SMTP sensibles se ocultan para usuarios no autenticados.

### Otros endpoints
- `/categories`, `/brands`, `/customers`, `/expenses`, `/cash-register`, `/reports`, `/users`, `/roles`, `/ncf-sequences`

## Proteccion contra Pedidos Falsos

El sistema implementa 3 capas de proteccion para evitar pedidos falsos y proteger el stock:

### 1. Stock NO se descuenta al crear pedido
Los pedidos online se crean con status `pending`. El stock **solo se descuenta** cuando un administrador cambia el estado a `confirmed` o `processing`. Si se cancela un pedido confirmado, el stock se restaura automaticamente.

### 2. Rate Limiting
En `OrderController::store()` se limita a **3 pedidos por hora** por:
- **Telefono**: Cuenta pedidos con el mismo `customer_phone` en la ultima hora
- **IP**: Cuenta pedidos creados desde la misma IP (via `activity_log`) en la ultima hora

Si se excede el limite, responde con HTTP 429: *"Has alcanzado el limite de 3 pedidos por hora..."*

### 3. Auto-cancelacion de Pedidos Expirados
En `OrderController::index()`, cada vez que se consulta la lista de pedidos, el sistema ejecuta:
```sql
UPDATE orders SET status = 'cancelled'
WHERE status = 'pending'
AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
```
Pedidos pendientes con mas de **48 horas** se cancelan automaticamente. Esto previene la acumulacion de pedidos falsos o abandonados.

### 4. Monto Minimo de Pedido
Configurable desde el admin (`min_order_amount`). Si el subtotal del pedido es menor al monto minimo, se rechaza con HTTP 400.

## Comprobantes Fiscales (NCF)

Sistema compatible con la DGII (Republica Dominicana):

- **Tipos soportados**: B01 (Credito Fiscal), B02 (Consumo), B14 (Regimenes Especiales), B15 (Gubernamental)
- **Secuencias**: Configurables con rango (inicio-fin), numero actual y fecha de vencimiento
- **Asignacion**: Con optimistic locking para evitar duplicados en concurrencia
- **Activacion**: Se habilita/deshabilita desde configuracion
- **RNC**: Se almacena el RNC de la tienda y del cliente

## Sistema de Email

Usa PHPMailer con SMTP para enviar:
- **Confirmacion de pedido**: Email al cliente con resumen completo (productos, totales, metodo de pago)
- **Actualizacion de estado**: Email al cliente cuando el admin cambia el estado del pedido

### Configuracion SMTP
Desde el panel admin (Configuracion > Email SMTP):
- Servidor SMTP, puerto, usuario, contrasena
- Nombre y email del remitente

Para **Gmail**: usar `smtp.gmail.com`, puerto `587`, y generar un App Password en [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords).

## Caja Registradora

- Apertura de caja con monto inicial
- Cierre con desglose automatico (efectivo, tarjeta, transferencia)
- **Se requiere caja abierta** para realizar ventas en el POS
- Soporte para pagos mixtos (ej: parte efectivo + parte tarjeta)

## Configuracion del Sistema

Toda la configuracion se almacena en la tabla `settings` como pares key-value:

| Categoria | Settings |
|-----------|----------|
| Tienda | `store_name`, `address`, `contact_phone`, `contact_email` |
| WhatsApp | `whatsapp_number` |
| Impuestos | `tax_enabled`, `tax_name`, `tax_percent` |
| Envio | `min_free_shipping` |
| NCF | `ncf_enabled`, `store_rnc` |
| SMTP | `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_from_name`, `smtp_from_email` |
| Banco | `bank_name`, `bank_account_type`, `bank_account_number`, `bank_account_holder` |
| Pedidos | `min_order_amount` |

## Licencia

Proyecto privado. Todos los derechos reservados - BBR Fragrance.
