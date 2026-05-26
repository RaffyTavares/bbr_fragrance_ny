// BBR Fragrance NY - Lightweight i18n runtime (ES / EN)
// Use data-i18n="key" on elements, data-i18n-placeholder="key" for inputs
// and data-i18n-attr="attr:key" (comma-separated for multiple) for other attributes.
// Call window.BBRi18n.refresh() after injecting dynamic DOM to re-translate it.

(function () {
    const STORAGE_KEY = 'bbr_lang';
    const defaultLang = 'es';

    const messages = {
        es: {
            // Brand
            'brand.name': 'BBR Fragrance NY',

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
            'common.to': 'Hasta',

            // Home - Categories section
            'home.cats.title': 'Nuestras Categorias',
            'home.cats.subtitle': 'Descubre la fragancia perfecta para cada ocasion',
            'home.cats.women': 'Para Mujer',
            'home.cats.womenDesc': 'Elegancia femenina',
            'home.cats.men': 'Para Hombre',
            'home.cats.menDesc': 'Masculinidad refinada',
            'home.cats.unisex': 'Unisex',
            'home.cats.unisexDesc': 'Sin etiquetas',
            'home.cats.arabic': 'Arabes',
            'home.cats.arabicDesc': 'Oriente mistico',
            'home.cats.offers': 'Ofertas',
            'home.cats.offersDesc': 'Precios especiales',
            'home.cats.new': 'Nuevos',
            'home.cats.newDesc': 'Recien llegados',

            // Home - Featured
            'home.featured.title': 'Productos Destacados',
            'home.featured.subtitle': 'Las fragancias mas populares de nuestra coleccion',
            'home.featured.viewAll': 'Ver todos los productos',

            // Home - Benefits
            'home.benefits.title': '\u00bfPor que elegir BBR Fragrance NY?',
            'home.benefits.subtitle': 'Tu satisfaccion es nuestra prioridad',
            'home.benefits.b1.title': '100% Originales',
            'home.benefits.b1.desc': 'Todos nuestros perfumes son autenticos y verificados',
            'home.benefits.b2.title': 'Envios Rapidos',
            'home.benefits.b2.desc': 'Entrega rapida en toda New York City',
            'home.benefits.b3.title': 'Atencion 24/7',
            'home.benefits.b3.desc': 'Soporte personalizado en todo momento',
            'home.benefits.b4.title': 'Pagos Seguros',
            'home.benefits.b4.desc': 'Transacciones protegidas y encriptadas',

            // Home - Fragrance families guide
            'home.families.title': 'Encuentra tu fragancia ideal',
            'home.families.subtitle': 'Explora por familia olfativa',
            'home.families.sweet': 'Dulce',
            'home.families.sweetTag': 'Gourmand y calido',
            'home.families.woody': 'Amaderado',
            'home.families.woodyTag': 'Elegante y sofisticado',
            'home.families.citrus': 'Citrico',
            'home.families.citrusTag': 'Fresco y energizante',
            'home.families.oriental': 'Oriental',
            'home.families.orientalTag': 'Especiado y exotico',
            'home.families.fresh': 'Fresco',
            'home.families.freshTag': 'Acuatico y ligero',
            'home.families.intense': 'Intenso',
            'home.families.intenseTag': 'Potente y duradero',
            'home.families.floral': 'Floral',
            'home.families.floralTag': 'Delicado y romantico',
            'home.families.fruity': 'Frutal',
            'home.families.fruityTag': 'Fresco y vibrante',
            'home.families.amber': 'Ambar',
            'home.families.amberTag': 'Calido y envolvente',
            'home.families.fougere': 'Fougere',
            'home.families.fougereTag': 'Clasico y masculino',
            'home.families.chypre': 'Chipre',
            'home.families.chypreTag': 'Terroso y sofisticado',
            'home.families.aromatic': 'Aromatica',
            'home.families.aromaticTag': 'Herbal y fresco',
            'home.families.floralFruity': 'Floral Afrutada',
            'home.families.floralFruityTag': 'Floral con toque frutal',
            'home.families.orientalWoody': 'Oriental Amaderada',
            'home.families.orientalWoodyTag': 'Profundo y misterioso',
            'home.families.gourmand': 'Gourmand',
            'home.families.gourmandTag': 'Dulce y adictivo',
            'home.families.leather': 'Cuero',
            'home.families.leatherTag': 'Intenso y animal',

            // Home - Testimonials
            'home.testi.title': 'Lo que dicen nuestros clientes',
            'home.testi.subtitle': 'Testimonios reales de personas satisfechas',
            'home.testi.verifiedF': 'Cliente verificada',
            'home.testi.verifiedM': 'Cliente verificado',
            'home.testi.t1': '"Excelente aroma y duracion. 100% original como prometen. La entrega fue rapida y el empaque impecable."',
            'home.testi.t2': '"Mejor precio que en tiendas fisicas y la atencion por WhatsApp es excelente. Me ayudaron a elegir el perfume ideal."',
            'home.testi.t3': '"Compre varios perfumes y todos llegaron perfectos. La calidad es increible y los precios muy competitivos."',

            // Home - Promo
            'home.promo.badge': 'Oferta Especial',
            'home.promo.title': 'Promocion del Mes',
            'home.promo.subtitle': 'Hasta 30% de descuento en perfumes seleccionados + envio gratis en compras mayores a $100',
            'home.promo.b1': 'Combos 2x1 en fragancias seleccionadas',
            'home.promo.b2': 'Regalo sorpresa en compras mayores a $200',
            'home.promo.b3': 'Muestras gratis con cada pedido',
            'home.promo.cta': 'Ver ofertas',

            // Home - About
            'home.about.title': 'Sobre BBR Fragrance NY',
            'home.about.p1': 'En BBR Fragrance NY nos dedicamos a ofrecer las mejores fragancias originales del mercado. Nuestra pasion por los perfumes y el compromiso con la autenticidad nos han convertido en la opcion preferida de miles de clientes en New York City.',
            'home.about.p2': 'Trabajamos directamente con distribuidores autorizados para garantizar que cada fragancia que llega a tus manos sea 100% original. Nuestro equipo de expertos esta siempre disponible para asesorarte y ayudarte a encontrar el perfume perfecto para ti o para regalar.',
            'home.about.stat1': 'Clientes Felices',
            'home.about.stat2': 'Fragancias',
            'home.about.stat3': 'Satisfaccion',

            // Home - Contact
            'home.contact.title': 'Contacto',
            'home.contact.subtitle': 'Estamos aqui para ayudarte',
            'home.contact.have': '\u00bfTienes alguna pregunta?',
            'home.contact.waDesc': 'Respuesta inmediata',
            'home.contact.emailLabel': 'Email',
            'home.contact.emailDesc': 'Respuesta en menos de 12h',
            'home.contact.phoneLabel': 'Telefono',
            'home.contact.hours': 'Lun - Vie: 10AM - 6:30PM, Sab 10:00AM - 6:00PM, Dom: Cerrados',
            'home.contact.location': 'Ubicacion',
            'home.contact.follow': 'Siguenos',
            'home.contact.formTitle': 'Envianos un mensaje',
            'home.contact.fName': 'Nombre',
            'home.contact.fNamePh': 'Tu nombre',
            'home.contact.fEmail': 'Email',
            'home.contact.fEmailPh': 'tu@email.com',
            'home.contact.fPhone': 'Telefono',
            'home.contact.fPhonePh': '+1 234 567 890',
            'home.contact.fMsg': 'Mensaje',
            'home.contact.fMsgPh': '\u00bfEn que podemos ayudarte?',
            'home.contact.send': 'Enviar mensaje',

            // Home - Footer
            'home.footer.newsTitle': 'No te pierdas nuestras ofertas',
            'home.footer.newsSubtitle': 'Siguenos en nuestras redes sociales para enterarte de promociones exclusivas',
            'home.footer.about': 'Perfumes originales que definen tu esencia. Trabajamos con distribuidores autorizados para garantizar autenticidad en cada fragancia.',
            'home.footer.guarantee': '100% Productos Originales',
            'home.footer.catalog': 'Catalogo',
            'home.footer.contact': 'Contacto',
            'home.footer.hours': 'Horario',
            'home.footer.monFri': 'Lunes - Viernes',
            'home.footer.sat': 'Sabado',
            'home.footer.sun': 'Domingo',
            'home.footer.closed': 'Cerrado',
            'home.footer.waCta': 'Escribenos por WhatsApp',
            'home.footer.rights': '\u00a9 2026 BBR Fragrance NY. Todos los derechos reservados.',
            'home.footer.secure': 'Pagos seguros:',
            'home.footer.adminLink': 'Admin',

            // Modals
            'modal.search.placeholder': 'Buscar perfumes por nombre o marca...',
            'modal.search.hint': 'Escribe para buscar productos...',
            'modal.cart.title': 'Tu Carrito',
            'modal.cart.empty': 'Tu carrito esta vacio',
            'modal.cart.total': 'Total:',
            'modal.cart.checkout': 'Proceder al pago',
            'modal.cart.waOrder': 'Ordenar por WhatsApp',
            'modal.checkout.title': 'Finalizar Compra',
            'modal.checkout.step1': 'Datos',
            'modal.checkout.step2': 'Pago',
            'modal.checkout.step3': 'Confirmar',
            'modal.checkout.dataTitle': 'Datos del Cliente',
            'modal.checkout.payTitle': 'Metodo de Pago',
            'modal.checkout.summaryTitle': 'Resumen del Pedido',
            'modal.checkout.cash': 'Efectivo',
            'modal.checkout.cashDesc': 'Pago contra entrega',
            'modal.checkout.card': 'Tarjeta',
            'modal.checkout.cardDesc': 'Se cobra al momento de la entrega',
            'modal.checkout.transfer': 'Transferencia Bancaria',
            'modal.checkout.transferDesc': 'Datos bancarios a continuacion',
            'modal.checkout.fullName': 'Nombre completo',
            'modal.checkout.phone': 'Telefono',
            'modal.checkout.email': 'Correo electronico',
            'modal.checkout.address': 'Direccion de envio',
            'modal.checkout.addressPh': 'Calle, sector, ciudad...',
            'modal.checkout.namePh': 'Tu nombre completo',
            'modal.checkout.phonePh': '646-000-0000',
            'modal.checkout.success': '\u00a1Pedido Creado!'
        },
        en: {
            // Brand
            'brand.name': 'BBR Fragrance NY',

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
            'common.to': 'To',

            // Home - Categories section
            'home.cats.title': 'Our Categories',
            'home.cats.subtitle': 'Find the perfect fragrance for every occasion',
            'home.cats.women': 'For Women',
            'home.cats.womenDesc': 'Feminine elegance',
            'home.cats.men': 'For Men',
            'home.cats.menDesc': 'Refined masculinity',
            'home.cats.unisex': 'Unisex',
            'home.cats.unisexDesc': 'No labels',
            'home.cats.arabic': 'Arabic',
            'home.cats.arabicDesc': 'Mystical East',
            'home.cats.offers': 'Deals',
            'home.cats.offersDesc': 'Special prices',
            'home.cats.new': 'New',
            'home.cats.newDesc': 'Just arrived',

            // Home - Featured
            'home.featured.title': 'Featured Products',
            'home.featured.subtitle': 'The most popular fragrances in our collection',
            'home.featured.viewAll': 'View all products',

            // Home - Benefits
            'home.benefits.title': 'Why choose BBR Fragrance NY?',
            'home.benefits.subtitle': 'Your satisfaction is our priority',
            'home.benefits.b1.title': '100% Authentic',
            'home.benefits.b1.desc': 'All our perfumes are genuine and verified',
            'home.benefits.b2.title': 'Fast Shipping',
            'home.benefits.b2.desc': 'Quick delivery across New York City',
            'home.benefits.b3.title': '24/7 Support',
            'home.benefits.b3.desc': 'Personalized assistance anytime',
            'home.benefits.b4.title': 'Secure Payments',
            'home.benefits.b4.desc': 'Protected and encrypted transactions',

            // Home - Fragrance families guide
            'home.families.title': 'Find your ideal fragrance',
            'home.families.subtitle': 'Browse by olfactory family',
            'home.families.sweet': 'Sweet',
            'home.families.sweetTag': 'Gourmand and warm',
            'home.families.woody': 'Woody',
            'home.families.woodyTag': 'Elegant and sophisticated',
            'home.families.citrus': 'Citrus',
            'home.families.citrusTag': 'Fresh and energizing',
            'home.families.oriental': 'Oriental',
            'home.families.orientalTag': 'Spiced and exotic',
            'home.families.fresh': 'Fresh',
            'home.families.freshTag': 'Aquatic and light',
            'home.families.intense': 'Intense',
            'home.families.intenseTag': 'Powerful and long-lasting',
            'home.families.floral': 'Floral',
            'home.families.floralTag': 'Delicate and romantic',
            'home.families.fruity': 'Fruity',
            'home.families.fruityTag': 'Fresh and vibrant',
            'home.families.amber': 'Amber',
            'home.families.amberTag': 'Warm and enveloping',
            'home.families.fougere': 'Fougere',
            'home.families.fougereTag': 'Classic and masculine',
            'home.families.chypre': 'Chypre',
            'home.families.chypreTag': 'Earthy and sophisticated',
            'home.families.aromatic': 'Aromatic',
            'home.families.aromaticTag': 'Herbal and fresh',
            'home.families.floralFruity': 'Floral Fruity',
            'home.families.floralFruityTag': 'Floral with a fruity touch',
            'home.families.orientalWoody': 'Oriental Woody',
            'home.families.orientalWoodyTag': 'Deep and mysterious',
            'home.families.gourmand': 'Gourmand',
            'home.families.gourmandTag': 'Sweet and addictive',
            'home.families.leather': 'Leather',
            'home.families.leatherTag': 'Intense and animalic',

            // Home - Testimonials
            'home.testi.title': 'What our customers say',
            'home.testi.subtitle': 'Real testimonials from satisfied people',
            'home.testi.verifiedF': 'Verified customer',
            'home.testi.verifiedM': 'Verified customer',
            'home.testi.t1': '"Excellent scent and longevity. 100% original as promised. Delivery was fast and the packaging impeccable."',
            'home.testi.t2': '"Better prices than physical stores and the WhatsApp service is excellent. They helped me pick the ideal perfume."',
            'home.testi.t3': '"I bought several perfumes and all arrived perfect. The quality is incredible and the prices very competitive."',

            // Home - Promo
            'home.promo.badge': 'Special Offer',
            'home.promo.title': 'Deal of the Month',
            'home.promo.subtitle': 'Up to 30% off selected perfumes + free shipping on orders over $100',
            'home.promo.b1': '2x1 combos on selected fragrances',
            'home.promo.b2': 'Surprise gift on orders over $200',
            'home.promo.b3': 'Free samples with every order',
            'home.promo.cta': 'See deals',

            // Home - About
            'home.about.title': 'About BBR Fragrance NY',
            'home.about.p1': 'At BBR Fragrance NY we are dedicated to offering the best original fragrances on the market. Our passion for perfumes and commitment to authenticity have made us the preferred choice of thousands of customers across New York City.',
            'home.about.p2': 'We work directly with authorized distributors to guarantee that every fragrance that reaches you is 100% original. Our team of experts is always available to advise you and help you find the perfect perfume for yourself or as a gift.',
            'home.about.stat1': 'Happy Customers',
            'home.about.stat2': 'Fragrances',
            'home.about.stat3': 'Satisfaction',

            // Home - Contact
            'home.contact.title': 'Contact',
            'home.contact.subtitle': 'We are here to help',
            'home.contact.have': 'Have a question?',
            'home.contact.waDesc': 'Instant reply',
            'home.contact.emailLabel': 'Email',
            'home.contact.emailDesc': 'Reply in under 12h',
            'home.contact.phoneLabel': 'Phone',
            'home.contact.hours': 'Mon - Fri: 10AM - 6:30PM, Sat 10:00AM - 6:00PM, Sun: Closed',
            'home.contact.location': 'Location',
            'home.contact.follow': 'Follow us',
            'home.contact.formTitle': 'Send us a message',
            'home.contact.fName': 'Name',
            'home.contact.fNamePh': 'Your name',
            'home.contact.fEmail': 'Email',
            'home.contact.fEmailPh': 'you@email.com',
            'home.contact.fPhone': 'Phone',
            'home.contact.fPhonePh': '+1 234 567 890',
            'home.contact.fMsg': 'Message',
            'home.contact.fMsgPh': 'How can we help you?',
            'home.contact.send': 'Send message',

            // Home - Footer
            'home.footer.newsTitle': "Don't miss our deals",
            'home.footer.newsSubtitle': 'Follow us on social media to find out about exclusive promotions',
            'home.footer.about': 'Original perfumes that define your essence. We work with authorized distributors to guarantee authenticity in every fragrance.',
            'home.footer.guarantee': '100% Original Products',
            'home.footer.catalog': 'Catalog',
            'home.footer.contact': 'Contact',
            'home.footer.hours': 'Hours',
            'home.footer.monFri': 'Monday - Friday',
            'home.footer.sat': 'Saturday',
            'home.footer.sun': 'Sunday',
            'home.footer.closed': 'Closed',
            'home.footer.waCta': 'Message us on WhatsApp',
            'home.footer.rights': '\u00a9 2026 BBR Fragrance NY. All rights reserved.',
            'home.footer.secure': 'Secure payments:',
            'home.footer.adminLink': 'Admin',

            // Modals
            'modal.search.placeholder': 'Search perfumes by name or brand...',
            'modal.search.hint': 'Type to search products...',
            'modal.cart.title': 'Your Cart',
            'modal.cart.empty': 'Your cart is empty',
            'modal.cart.total': 'Total:',
            'modal.cart.checkout': 'Proceed to checkout',
            'modal.cart.waOrder': 'Order via WhatsApp',
            'modal.checkout.title': 'Checkout',
            'modal.checkout.step1': 'Details',
            'modal.checkout.step2': 'Payment',
            'modal.checkout.step3': 'Confirm',
            'modal.checkout.dataTitle': 'Customer Details',
            'modal.checkout.payTitle': 'Payment Method',
            'modal.checkout.summaryTitle': 'Order Summary',
            'modal.checkout.cash': 'Cash',
            'modal.checkout.cashDesc': 'Pay on delivery',
            'modal.checkout.card': 'Card',
            'modal.checkout.cardDesc': 'Charged on delivery',
            'modal.checkout.transfer': 'Bank Transfer',
            'modal.checkout.transferDesc': 'Bank details below',
            'modal.checkout.fullName': 'Full name',
            'modal.checkout.phone': 'Phone',
            'modal.checkout.email': 'Email',
            'modal.checkout.address': 'Shipping address',
            'modal.checkout.addressPh': 'Street, neighborhood, city...',
            'modal.checkout.namePh': 'Your full name',
            'modal.checkout.phonePh': '646-000-0000',
            'modal.checkout.success': 'Order Placed!'
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
