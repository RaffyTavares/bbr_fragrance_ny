// BBR Fragrance NY - Lightweight i18n runtime (ES / EN)
// Use data-i18n="key" on elements, data-i18n-placeholder="key" for inputs
// and data-i18n-attr="attr:key" (comma-separated for multiple) for other attributes.
// Call window.BBRi18n.refresh() after injecting dynamic DOM to re-translate it.

(function () {
    const STORAGE_KEY = 'bbr_lang';
    const defaultLang = 'es';

    const messages = {
        es: {
            // Navigation
            'nav.home': 'Inicio',
            'nav.categories': 'Categorias',
            'nav.products': 'Productos',
            'nav.promotions': 'Promociones',
            'nav.about': 'Nosotros',
            'nav.contact': 'Contacto',
            'nav.cart': 'Carrito',
            'nav.search': 'Buscar',

            // Hero
            'hero.subtitle': 'Fragancias de lujo con actitud de New York',
            'hero.badge': 'Sede principal en New York City',
            'hero.shopNow': 'Comprar ahora',
            'hero.whatsapp': 'WhatsApp',

            // Categories
            'categories.title': 'Explora por categoria',
            'categories.subtitle': 'Encuentra la fragancia perfecta para cada ocasion',
            'categories.men': 'Hombre',
            'categories.women': 'Mujer',
            'categories.unisex': 'Unisex',
            'categories.niche': 'De Nicho',
            'categories.viewAll': 'Ver todo',

            // Products (home)
            'products.featured': 'Productos destacados',
            'products.bestsellers': 'Mas vendidos',
            'products.new': 'Novedades',
            'products.promo': 'Promociones',
            'products.viewMore': 'Ver mas',
            'products.addToCart': 'Agregar al carrito',
            'products.outOfStock': 'Agotado',

            // About
            'about.title': 'Sobre BBR Fragrance NY',
            'about.subtitle': 'Perfumeria de lujo en el corazon de Manhattan',
            'about.body': 'Desde nuestra tienda en el Alto Manhattan ofrecemos fragancias originales seleccionadas para el ritmo de New York. Calidad garantizada, atencion personalizada y envios en toda el area metropolitana.',

            // Contact / footer
            'contact.title': 'Visitanos',
            'contact.address': '601 West 162 Street, New York, NY 10032',
            'contact.phone': '+1 (646) 228-5892',
            'contact.hours': 'Lun - Sab: 10AM - 8PM',
            'contact.followUs': 'Siguenos',
            'contact.writeUs': 'Escribenos por WhatsApp',
            'footer.shop': 'Tienda',
            'footer.help': 'Ayuda',
            'footer.company': 'Empresa',
            'footer.rights': 'Todos los derechos reservados.',
            'footer.tagline': 'Fragancias originales en New York City',

            // Productos page
            'productos.title': 'Todos los productos',
            'productos.subtitle': 'Explora nuestra coleccion completa',
            'productos.filters': 'Filtros',
            'productos.clear': 'Limpiar',
            'productos.brand': 'Marca',
            'productos.category': 'Categoria',
            'productos.price': 'Precio',
            'productos.gender': 'Genero',
            'productos.sortBy': 'Ordenar por',
            'productos.sort.newest': 'Mas recientes',
            'productos.sort.priceAsc': 'Precio: menor a mayor',
            'productos.sort.priceDesc': 'Precio: mayor a menor',
            'productos.sort.nameAsc': 'Nombre: A-Z',
            'productos.results': 'productos encontrados',
            'productos.noResults': 'No se encontraron productos',
            'productos.loadMore': 'Cargar mas',
            'productos.viewGrid': 'Vista en cuadricula',
            'productos.viewList': 'Vista en lista',

            // Product detail
            'detail.back': 'Volver',
            'detail.brand': 'Marca',
            'detail.category': 'Categoria',
            'detail.gender': 'Genero',
            'detail.size': 'Tamano',
            'detail.quantity': 'Cantidad',
            'detail.addToCart': 'Agregar al carrito',
            'detail.buyNow': 'Comprar ahora',
            'detail.description': 'Descripcion',
            'detail.notes': 'Notas olfativas',
            'detail.benefits': 'Beneficios',
            'detail.related': 'Productos relacionados',
            'detail.shipping': 'Envios en toda New York City',
            'detail.authentic': '100% Original',
            'detail.secure': 'Pago seguro',

            // Cart
            'cart.title': 'Tu carrito',
            'cart.empty': 'Tu carrito esta vacio',
            'cart.continue': 'Seguir comprando',
            'cart.subtotal': 'Subtotal',
            'cart.shipping': 'Envio',
            'cart.tax': 'Sales Tax',
            'cart.total': 'Total',
            'cart.checkout': 'Finalizar compra',
            'cart.remove': 'Eliminar',

            // Checkout
            'checkout.title': 'Finalizar compra',
            'checkout.step.info': 'Datos',
            'checkout.step.shipping': 'Envio',
            'checkout.step.payment': 'Pago',
            'checkout.step.review': 'Revisar',
            'checkout.fullName': 'Nombre completo',
            'checkout.email': 'Correo electronico',
            'checkout.phone': 'Telefono',
            'checkout.address': 'Direccion',
            'checkout.city': 'Ciudad',
            'checkout.state': 'Estado',
            'checkout.zip': 'Codigo postal',
            'checkout.notes': 'Notas (opcional)',
            'checkout.payCash': 'Efectivo (contra entrega)',
            'checkout.payCard': 'Tarjeta (al entregar)',
            'checkout.payTransfer': 'Transferencia bancaria',
            'checkout.back': 'Atras',
            'checkout.next': 'Continuar',
            'checkout.placeOrder': 'Confirmar pedido',
            'checkout.success': 'Pedido confirmado',

            // Common
            'common.close': 'Cerrar',
            'common.loading': 'Cargando...',
            'common.search': 'Buscar...',
            'common.from': 'Desde',
            'common.to': 'Hasta'
        },
        en: {
            // Navigation
            'nav.home': 'Home',
            'nav.categories': 'Categories',
            'nav.products': 'Products',
            'nav.promotions': 'Deals',
            'nav.about': 'About',
            'nav.contact': 'Contact',
            'nav.cart': 'Cart',
            'nav.search': 'Search',

            // Hero
            'hero.subtitle': 'Luxury scents with New York attitude',
            'hero.badge': 'New York City flagship',
            'hero.shopNow': 'Shop now',
            'hero.whatsapp': 'WhatsApp',

            // Categories
            'categories.title': 'Shop by category',
            'categories.subtitle': 'Find the perfect fragrance for every occasion',
            'categories.men': 'Men',
            'categories.women': 'Women',
            'categories.unisex': 'Unisex',
            'categories.niche': 'Niche',
            'categories.viewAll': 'View all',

            // Products (home)
            'products.featured': 'Featured products',
            'products.bestsellers': 'Best sellers',
            'products.new': 'New arrivals',
            'products.promo': 'Deals',
            'products.viewMore': 'See more',
            'products.addToCart': 'Add to cart',
            'products.outOfStock': 'Out of stock',

            // About
            'about.title': 'About BBR Fragrance NY',
            'about.subtitle': 'Luxury perfumery in the heart of Manhattan',
            'about.body': 'From our store in Upper Manhattan we offer authentic fragrances curated for the New York pace. Guaranteed quality, personalized service and delivery across the metropolitan area.',

            // Contact / footer
            'contact.title': 'Visit us',
            'contact.address': '601 West 162 Street, New York, NY 10032',
            'contact.phone': '+1 (646) 228-5892',
            'contact.hours': 'Mon - Sat: 10AM - 8PM',
            'contact.followUs': 'Follow us',
            'contact.writeUs': 'Message us on WhatsApp',
            'footer.shop': 'Shop',
            'footer.help': 'Help',
            'footer.company': 'Company',
            'footer.rights': 'All rights reserved.',
            'footer.tagline': 'Authentic fragrances in New York City',

            // Productos page
            'productos.title': 'All products',
            'productos.subtitle': 'Browse our full collection',
            'productos.filters': 'Filters',
            'productos.clear': 'Clear',
            'productos.brand': 'Brand',
            'productos.category': 'Category',
            'productos.price': 'Price',
            'productos.gender': 'Gender',
            'productos.sortBy': 'Sort by',
            'productos.sort.newest': 'Newest',
            'productos.sort.priceAsc': 'Price: low to high',
            'productos.sort.priceDesc': 'Price: high to low',
            'productos.sort.nameAsc': 'Name: A-Z',
            'productos.results': 'products found',
            'productos.noResults': 'No products found',
            'productos.loadMore': 'Load more',
            'productos.viewGrid': 'Grid view',
            'productos.viewList': 'List view',

            // Product detail
            'detail.back': 'Back',
            'detail.brand': 'Brand',
            'detail.category': 'Category',
            'detail.gender': 'Gender',
            'detail.size': 'Size',
            'detail.quantity': 'Quantity',
            'detail.addToCart': 'Add to cart',
            'detail.buyNow': 'Buy now',
            'detail.description': 'Description',
            'detail.notes': 'Fragrance notes',
            'detail.benefits': 'Benefits',
            'detail.related': 'Related products',
            'detail.shipping': 'Delivery across New York City',
            'detail.authentic': '100% Authentic',
            'detail.secure': 'Secure checkout',

            // Cart
            'cart.title': 'Your cart',
            'cart.empty': 'Your cart is empty',
            'cart.continue': 'Continue shopping',
            'cart.subtotal': 'Subtotal',
            'cart.shipping': 'Shipping',
            'cart.tax': 'Sales Tax',
            'cart.total': 'Total',
            'cart.checkout': 'Checkout',
            'cart.remove': 'Remove',

            // Checkout
            'checkout.title': 'Checkout',
            'checkout.step.info': 'Details',
            'checkout.step.shipping': 'Shipping',
            'checkout.step.payment': 'Payment',
            'checkout.step.review': 'Review',
            'checkout.fullName': 'Full name',
            'checkout.email': 'Email',
            'checkout.phone': 'Phone',
            'checkout.address': 'Address',
            'checkout.city': 'City',
            'checkout.state': 'State',
            'checkout.zip': 'ZIP code',
            'checkout.notes': 'Notes (optional)',
            'checkout.payCash': 'Cash on delivery',
            'checkout.payCard': 'Card on delivery',
            'checkout.payTransfer': 'Bank transfer',
            'checkout.back': 'Back',
            'checkout.next': 'Continue',
            'checkout.placeOrder': 'Place order',
            'checkout.success': 'Order confirmed',

            // Common
            'common.close': 'Close',
            'common.loading': 'Loading...',
            'common.search': 'Search...',
            'common.from': 'From',
            'common.to': 'To'
        }
    };

    function currentLang() {
        return localStorage.getItem(STORAGE_KEY) || defaultLang;
    }

    function getText(lang, key) {
        return (messages[lang] && messages[lang][key]) || messages.es[key] || key;
    }

    function applyLanguage(lang) {
        document.documentElement.lang = lang === 'en' ? 'en' : 'es';

        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.getAttribute('data-i18n');
            el.textContent = getText(lang, key);
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.getAttribute('data-i18n-placeholder');
            el.setAttribute('placeholder', getText(lang, key));
        });

        document.querySelectorAll('[data-i18n-attr]').forEach((el) => {
            const mapping = el.getAttribute('data-i18n-attr');
            if (!mapping) return;
            mapping.split(',').forEach((pair) => {
                const [attr, key] = pair.split(':').map((s) => s && s.trim());
                if (attr && key) {
                    el.setAttribute(attr, getText(lang, key));
                }
            });
        });

        const nextLangLabel = lang === 'es' ? 'EN' : 'ES';
        const desktopToggle = document.getElementById('language-toggle-text');
        const mobileToggle = document.getElementById('language-toggle-mobile-text');
        if (desktopToggle) desktopToggle.textContent = nextLangLabel;
        if (mobileToggle) mobileToggle.textContent = nextLangLabel;

        localStorage.setItem(STORAGE_KEY, lang);
    }

    function toggleLanguage() {
        applyLanguage(currentLang() === 'es' ? 'en' : 'es');
    }

    // Expose API so dynamically injected content can re-translate.
    window.BBRi18n = {
        apply: applyLanguage,
        refresh: () => applyLanguage(currentLang()),
        toggle: toggleLanguage,
        t: (key) => getText(currentLang(), key),
        current: currentLang
    };

    document.addEventListener('DOMContentLoaded', () => {
        applyLanguage(currentLang());

        document.getElementById('language-toggle')?.addEventListener('click', toggleLanguage);
        document.getElementById('language-toggle-mobile')?.addEventListener('click', toggleLanguage);
    });
})();
