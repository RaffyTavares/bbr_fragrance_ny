# 🛡️ Panel Administrativo - BBR Fragrance

Sistema de gestión completo para administrar productos, categorías y configuraciones de la tienda.

## 📋 Características Principales

### ✅ Autenticación
- Sistema de login seguro
- Sesión persistente (recordar sesión)
- Protección de rutas administrativas
- Logout con confirmación

### 📊 Dashboard
- Estadísticas en tiempo real
- Total de productos
- Ventas del mes
- Pedidos totales
- Clientes registrados
- Actividad reciente
- Productos más vendidos

### 📦 Gestión de Productos (CRUD Completo)
- **Crear**: Agregar nuevos productos con todos sus datos
- **Leer**: Visualizar lista completa de productos
- **Actualizar**: Editar información de productos existentes
- **Eliminar**: Borrar productos del inventario

#### Campos de Producto:
- Nombre del producto
- Marca
- Categoría (Mujer, Hombre, Unisex)
- Familia olfativa (Dulce, Amaderado, Cítrico, Oriental, Fresco, Intenso)
- Precio
- Stock
- Estado (Activo/Inactivo)
- Descripción

### 🔍 Búsqueda y Filtros
- Búsqueda por nombre o marca
- Filtrar por categoría
- Filtrar por familia olfativa
- Limpiar todos los filtros

### 📱 Características Adicionales
- Diseño responsive (funciona en móviles y tablets)
- Interfaz moderna con Tailwind CSS
- Notificaciones de éxito/error
- Almacenamiento local (localStorage)
- Navegación intuitiva

## 🚀 Acceso al Panel

### URL de Acceso:
```
/pages/admin-login.html
```

### Credenciales de Prueba:
```
Usuario: admin
Contraseña: admin123
```

### Enlaces de Acceso:
1. **Footer del sitio principal**: Enlace "Admin" en la sección Legal
2. **URL directa**: `pages/admin-login.html`

## 📁 Estructura de Archivos

```
pages/
├── admin-login.html    # Página de login
└── admin.html          # Panel administrativo principal

js/
└── admin.js           # Lógica del panel admin
```

## 🔐 Seguridad

### Protección Implementada:
- ✅ Verificación de sesión en cada carga
- ✅ Redirección automática si no hay sesión
- ✅ Logout con confirmación
- ✅ Sesión en sessionStorage (temporal) o localStorage (persistente)

### ⚠️ Importante para Producción:
Este es un sistema de demostración. Para producción real se recomienda:
- Implementar backend con autenticación JWT
- Usar base de datos real (MySQL, PostgreSQL, MongoDB)
- Agregar cifrado de contraseñas (bcrypt)
- Implementar roles y permisos
- Agregar autenticación de dos factores (2FA)
- HTTPS obligatorio
- Rate limiting en login
- Logs de auditoría

## 💾 Almacenamiento de Datos

### localStorage Keys:
```javascript
admin_session      // Sesión del administrador
bbr_products      // Base de datos de productos
```

### Productos Iniciales:
El sistema viene con 6 productos de ejemplo:
1. Sauvage - Dior
2. Coco Mademoiselle - Chanel
3. Black Orchid - Tom Ford
4. Y - Yves Saint Laurent
5. J'adore - Dior
6. Eros - Versace

## 🎨 Secciones del Panel

### 1. Dashboard
- Vista general del sistema
- Tarjetas con estadísticas
- Actividad reciente
- Productos más vendidos

### 2. Productos
- Tabla completa de productos
- Botón agregar producto
- Búsqueda en tiempo real
- Filtros múltiples
- Acciones: Editar y Eliminar

### 3. Categorías
- Vista de categorías (Mujer, Hombre, Unisex)
- Vista de familias olfativas
- Contador de productos por categoría

### 4. Pedidos
- Sección preparada para funcionalidad futura
- Gestión de órdenes de clientes

### 5. Configuración
- Número de WhatsApp
- Email de contacto
- Otras preferencias del sistema

## 🛠️ Uso del Sistema

### Agregar un Producto:
1. Ir a la sección "Productos"
2. Clic en "Agregar Producto"
3. Llenar el formulario
4. Clic en "Guardar"

### Editar un Producto:
1. En la tabla de productos, clic en el ícono de editar (lápiz)
2. Modificar los campos deseados
3. Clic en "Guardar"

### Eliminar un Producto:
1. En la tabla de productos, clic en el ícono de eliminar (papelera)
2. Confirmar la eliminación

### Buscar/Filtrar:
1. Usar el campo de búsqueda para buscar por nombre o marca
2. Usar los selectores para filtrar por categoría o familia
3. Clic en "Limpiar" para resetear filtros

## 📱 Navegación

### Menú Lateral:
- Dashboard: Vista general
- Productos: CRUD de productos
- Categorías: Organización de productos
- Pedidos: Gestión de órdenes
- Configuración: Ajustes del sistema
- Ver Sitio Web: Regresa al sitio público

### Header:
- Notificaciones (badge con contador)
- Usuario actual
- Botón de logout

## 🔄 Sincronización con el Sitio Web

Los productos en el panel admin usan la misma estructura de datos que el sitio web principal, por lo que:
- Los productos agregados/editados/eliminados se reflejan instantáneamente
- Usa la misma clave de localStorage
- Mantiene consistencia en toda la aplicación

## 📊 Características Técnicas

### Tecnologías Utilizadas:
- **HTML5**: Estructura semántica
- **Tailwind CSS**: Estilos modernos y responsive
- **JavaScript Vanilla**: Sin frameworks, código puro
- **Font Awesome 6**: Iconografía
- **Google Fonts**: Tipografía premium
- **localStorage API**: Persistencia de datos

### Clases JavaScript:
```javascript
ProductManager          // Gestión CRUD de productos
```

### Funciones Principales:
```javascript
checkAuthentication()   // Verifica sesión activa
loadDashboardData()     // Carga estadísticas
loadProducts()          // Carga tabla de productos
openProductModal()      // Abre formulario
saveProduct()          // Guarda/actualiza producto
deleteProduct()        // Elimina producto
showNotification()     // Muestra alertas
```

## 🎯 Próximas Mejoras

### Funcionalidades Futuras:
- [ ] Gestión de imágenes de productos
- [ ] Sistema de pedidos completo
- [ ] Reportes y gráficas avanzadas
- [ ] Exportar datos a CSV/Excel
- [ ] Gestión de clientes
- [ ] Sistema de cupones/descuentos
- [ ] Historial de cambios (audit log)
- [ ] Múltiples usuarios con roles
- [ ] Notificaciones push
- [ ] Integración con APIs de pago

## 🐛 Solución de Problemas

### No puedo acceder al panel:
- Verifica las credenciales: `admin` / `admin123`
- Limpia el localStorage si hay problemas
- Verifica que JavaScript esté habilitado

### Los cambios no se guardan:
- Verifica que localStorage no esté deshabilitado
- Revisa la consola del navegador por errores
- Asegúrate de hacer clic en "Guardar"

### La sesión se cierra automáticamente:
- Si no marcaste "Recordar sesión", la sesión expira al cerrar el navegador
- Vuelve a iniciar sesión y marca la opción

## 📞 Soporte

Para ayuda o reportar problemas con el panel administrativo, contacta al equipo de desarrollo.

---

**Desarrollado para BBR Fragrance** 🌟
*Sistema de gestión profesional para tiendas de perfumes*
