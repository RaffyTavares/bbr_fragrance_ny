# 🌟 Bbr_Fragance - Tienda de Perfumes Premium

Sitio web completo para tienda de perfumes originales con diseño elegante y funcionalidad completa.

## 📋 Características

### ✨ Diseño y Estilo
- **Diseño Premium**: Colores negro, dorado y blanco para transmitir elegancia y lujo
- **Responsive**: Completamente adaptable a móviles, tablets y desktop
- **Animaciones suaves**: Efectos visuales profesionales con transiciones fluidas
- **Tipografía elegante**: Playfair Display para títulos y Inter para textos

### 🛍️ Funcionalidades Principales

#### Página Principal (index.html)
- Hero section impactante con llamado a la acción
- Categorías de perfumes (Mujer, Hombre, Unisex, Árabes, Ofertas, Nuevos)
- Productos destacados con calificaciones y precios
- Beneficios (100% originales, envíos rápidos, atención 24/7, pagos seguros)
- Guía de fragancias por familia olfativa
- Testimonios de clientes verificados
- Sección de promociones especiales
- Información sobre la empresa
- Formulario de contacto
- Footer completo con enlaces y métodos de pago

#### Catálogo de Productos (pages/productos.html)
- Sistema de filtros avanzado:
  - Por categoría (Mujer, Hombre, Unisex, Árabes)
  - Por familia olfativa (Dulce, Amaderado, Cítrico, Oriental, Fresco, Intenso)
  - Por rango de precio
  - Por marca
- Ordenamiento de productos (precio, nombre, valoración)
- Vista en grid o lista
- Paginación
- Contador de resultados

#### Ficha de Producto (pages/producto-detalle.html)
- Galería de imágenes con miniaturas
- Información completa del producto:
  - Nombre y marca
  - Rating y reseñas
  - Precio con descuentos
  - Disponibilidad en tiempo real
  - Selector de tamaño
  - Selector de cantidad
- Tabs de información detallada:
  - **Descripción**: Historia y características del perfume
  - **Notas Olfativas**: Pirámide completa (salida, corazón, fondo)
  - **Información Técnica**: 
    - Tipo de concentración (EDT, EDP, Parfum)
    - Duración y estela
    - Perfumista
    - Año de lanzamiento
    - Temporada ideal
    - Recomendaciones de uso
  - **Reseñas**: Sistema completo de valoraciones con gráficos
- Botones de acción:
  - Agregar al carrito
  - Comprar por WhatsApp
- Beneficios destacados
- Productos relacionados

### 🛒 Sistema de Carrito
- Agregar/eliminar productos
- Actualizar cantidades
- Cálculo automático de totales
- Persistencia en localStorage
- Modal lateral deslizante
- Contador visible en navegación
- Envío directo por WhatsApp con resumen del pedido

### 📱 Integraciones
- **WhatsApp**: 
  - Botón flotante animado
  - Envío de pedidos automático
  - Consultas rápidas desde productos
- **Redes Sociales**: Enlaces a Facebook, Instagram, TikTok, Twitter
- **Compartir productos**: Funcionalidad para compartir en redes

### 🎨 Tecnologías Utilizadas
- **HTML5**: Estructura semántica
- **Tailwind CSS**: Framework CSS utility-first
- **CSS Personalizado**: Estilos premium adicionales
- **JavaScript Vanilla**: Funcionalidad sin dependencias
- **Font Awesome**: Iconos profesionales
- **Google Fonts**: Tipografías elegantes

## 📁 Estructura del Proyecto

```
web-BBR_Fragance/
├── index.html                    # Página principal
├── css/
│   └── style.css                # Estilos personalizados
├── js/
│   └── main.js                  # Funcionalidad JavaScript
├── images/                      # Carpeta para imágenes de productos
└── pages/
    ├── productos.html           # Catálogo completo
    └── producto-detalle.html    # Detalle del producto
```

## 🚀 Instalación y Uso

### Opción 1: Uso Local
1. Descarga todos los archivos
2. Abre `index.html` en tu navegador
3. ¡Listo! El sitio funciona sin necesidad de servidor

### Opción 2: Con Servidor Local
```bash
# Usando Python 3
python -m http.server 8000

# Usando Node.js (npx)
npx serve

# Usando PHP
php -S localhost:8000
```

Luego abre tu navegador en `http://localhost:8000`

## ⚙️ Configuración

### Número de WhatsApp
Edita el archivo `js/main.js` y cambia el número:

```javascript
const CONFIG = {
    whatsappNumber: '1234567890', // Cambia este número
    currency: '$',
    cartStorageKey: 'bbr_cart'
};
```

### Agregar Imágenes Reales
1. Coloca tus imágenes de productos en la carpeta `images/`
2. Actualiza las referencias en los HTML:
   - Reemplaza los placeholders con `<img src="../images/nombre-producto.jpg">`

### Personalizar Colores
En `css/style.css`, modifica las variables CSS:

```css
:root {
    --color-gold: #F59E0B;
    --color-gold-dark: #D97706;
    --color-black: #000000;
    --color-gray-dark: #1F2937;
    --color-purple: #7C3AED;
}
```

## 🎯 Funcionalidades del JavaScript

### Carrito de Compras
```javascript
// Agregar producto
cart.addItem({
    id: '123',
    name: 'Dior Sauvage',
    brand: 'Dior',
    price: 120
});

// Ver total
cart.getTotal();

// Enviar por WhatsApp
cart.sendToWhatsApp();
```

### Filtros de Productos
Los filtros se aplican automáticamente según los atributos `data-*` de cada producto:
- `data-category`: mujer, hombre, unisex, arabe
- `data-family`: dulce, amaderado, citrico, oriental, fresco, intenso
- `data-brand`: dior, chanel, tomford, ysl, versace
- `data-price`: precio numérico

### Búsqueda de Productos
```javascript
searchProducts('sauvage'); // Busca productos por nombre o marca
```

## 📱 Responsive Design

El sitio se adapta perfectamente a:
- **Móviles**: < 768px
- **Tablets**: 768px - 1024px
- **Desktop**: > 1024px

Breakpoints principales en Tailwind CSS:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px

## 🎨 Paleta de Colores

| Color | Hex | Uso |
|-------|-----|-----|
| Dorado | #F59E0B | Acentos, botones, precios |
| Dorado Oscuro | #D97706 | Hover states |
| Negro | #000000 | Fondo principal |
| Gris Oscuro | #1F2937 | Fondo secundario |
| Púrpura | #7C3AED | Acentos decorativos |
| Blanco | #FFFFFF | Texto principal |

## ✅ Checklist de Implementación

- [x] Página principal con hero
- [x] Categorías de productos
- [x] Productos destacados
- [x] Sistema de beneficios
- [x] Guía de fragancias
- [x] Testimonios
- [x] Promociones
- [x] Sobre nosotros
- [x] Contacto
- [x] Catálogo con filtros
- [x] Ficha de producto detallada
- [x] Notas olfativas completas
- [x] Sistema de carrito
- [x] Integración WhatsApp
- [x] Diseño responsive
- [x] Animaciones suaves
- [x] SEO básico

## 🔜 Próximas Mejoras (Opcional)

- [ ] Sistema de login/registro
- [ ] Pasarela de pagos (Stripe, PayPal)
- [ ] Panel de administración
- [ ] Base de datos MySQL/PostgreSQL
- [ ] Backend con PHP/Node.js
- [ ] Sistema de reseñas dinámico
- [ ] Buscador avanzado con autocompletado
- [ ] Lista de deseos
- [ ] Comparador de productos
- [ ] Programa de puntos/recompensas
- [ ] Blog de fragancias
- [ ] Chat en vivo

## 🌐 SEO

El sitio incluye:
- Meta tags descriptivos
- Estructura semántica HTML5
- URLs amigables
- Títulos optimizados
- Alt text para imágenes (cuando se agreguen)

Para mejorar el SEO:
1. Agrega descripciones meta únicas por página
2. Crea un sitemap.xml
3. Implementa Schema.org para productos
4. Optimiza velocidad de carga
5. Crea contenido de blog

## 📄 Licencia

Este proyecto fue creado para Bbr_Fragance. Todos los derechos reservados © 2026.

## 🤝 Soporte

Para soporte o consultas:
- WhatsApp: +1 (234) 567-890
- Email: info@bbrfragance.com

---

**Desarrollado con ❤️ para Bbr_Fragance**

*Perfumes originales que definen tu esencia*
