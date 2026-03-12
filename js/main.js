// ===================================
// Bbr_Fragance - Main JavaScript
// Premium Perfume Store
// ===================================

// Configuración global
const CONFIG = {
    whatsappNumber: '1234567890',
    currency: '$',
    cartStorageKey: 'bbr_cart',
    productsStorageKey: 'bbr_products'
};

// ===================================
// Gestión de Productos desde localStorage
// ===================================
function loadProductsFromStorage() {
    const stored = localStorage.getItem(CONFIG.productsStorageKey);
    if (stored) {
        return JSON.parse(stored);
    }
    
    // Si no hay productos en localStorage, inicializar con productos por defecto
    const defaultProducts = [
        {
            id: 1,
            name: "Sauvage",
            brand: "Dior",
            category: "hombre",
            family: "fresco",
            price: 145,
            stock: 25,
            status: "active",
            description: "Un perfume fresco y elegante con notas cítricas y amaderadas",
            createdAt: new Date().toISOString()
        },
        {
            id: 2,
            name: "Coco Mademoiselle",
            brand: "Chanel",
            category: "mujer",
            family: "dulce",
            price: 165,
            stock: 18,
            status: "active",
            description: "Fragancia dulce y sofisticada con toques orientales",
            createdAt: new Date().toISOString()
        },
        {
            id: 3,
            name: "Black Orchid",
            brand: "Tom Ford",
            category: "unisex",
            family: "oriental",
            price: 165,
            stock: 12,
            status: "active",
            description: "Aroma intenso y misterioso con notas de orquídea negra",
            createdAt: new Date().toISOString()
        },
        {
            id: 4,
            name: "Y",
            brand: "Yves Saint Laurent",
            category: "hombre",
            family: "amaderado",
            price: 135,
            stock: 30,
            status: "active",
            description: "Perfume amaderado moderno con toques frutales",
            createdAt: new Date().toISOString()
        },
        {
            id: 5,
            name: "J'adore",
            brand: "Dior",
            category: "mujer",
            family: "citrico",
            price: 155,
            stock: 22,
            status: "active",
            description: "Frescura cítrica elegante con notas florales",
            createdAt: new Date().toISOString()
        },
        {
            id: 6,
            name: "Eros",
            brand: "Versace",
            category: "hombre",
            family: "intenso",
            price: 98,
            stock: 15,
            status: "active",
            description: "Intensidad seductora con notas dulces y amaderadas",
            createdAt: new Date().toISOString()
        }
    ];
    
    // Guardar productos por defecto
    localStorage.setItem(CONFIG.productsStorageKey, JSON.stringify(defaultProducts));
    return defaultProducts;
}

function renderProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;
    
    const products = loadProductsFromStorage();
    
    if (products.length === 0) {
        productsGrid.innerHTML = `
            <div class="col-span-full text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-xl">No hay productos disponibles</p>
                <p class="text-gray-500 text-sm mt-2">Agrega productos desde el panel administrativo</p>
            </div>
        `;
        return;
    }
    
    productsGrid.innerHTML = products
        .filter(p => p.status === 'active')
        .map(product => createProductCard(product))
        .join('');
    
    // Re-agregar event listeners para agregar al carrito
    attachAddToCartListeners();
}

function renderFeaturedProducts() {
    const featuredGrid = document.getElementById('featured-products');
    if (!featuredGrid) return;
    
    const products = loadProductsFromStorage();
    const activeProducts = products.filter(p => p.status === 'active');
    
    if (activeProducts.length === 0) {
        featuredGrid.innerHTML = `
            <div class="col-span-full text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-xl">No hay productos disponibles</p>
                <p class="text-gray-500 text-sm mt-2">Agrega productos desde el panel administrativo</p>
            </div>
        `;
        return;
    }
    
    // Mostrar solo los primeros 4 productos
    const featured = activeProducts.slice(0, 4);
    
    featuredGrid.innerHTML = featured
        .map(product => createFeaturedProductCard(product))
        .join('');
    
    // Re-agregar event listeners para agregar al carrito
    attachAddToCartListeners();
}

function createFeaturedProductCard(product) {
    const gradients = {
        'dulce': 'from-pink-900 to-purple-900',
        'amaderado': 'from-amber-900 to-orange-900',
        'citrico': 'from-yellow-900 to-green-900',
        'oriental': 'from-purple-900 to-indigo-900',
        'fresco': 'from-cyan-900 to-blue-900',
        'intenso': 'from-red-900 to-rose-900'
    };
    
    const gradient = gradients[product.family] || 'from-purple-900 to-pink-900';
    const hasDiscount = product.originalPrice && product.originalPrice > product.price;
    const isNew = product.createdAt && (new Date() - new Date(product.createdAt)) < 7 * 24 * 60 * 60 * 1000; // Nuevo si tiene menos de 7 días
    
    return `
        <div class="product-card bg-gray-800/50 backdrop-blur rounded-lg overflow-hidden hover:transform hover:scale-105 transition duration-300 border border-gray-700 hover:border-amber-500" data-product-id="${product.id}">
            <div class="relative overflow-hidden group">
                <div class="h-72 bg-gradient-to-br ${gradient} flex items-center justify-center">
                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">` : `<i class="fas fa-wine-bottle text-8xl text-white/30"></i>`}
                </div>
                ${hasDiscount ? `<div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">-${Math.round((1 - product.price/product.originalPrice) * 100)}%</div>` : ''}
                ${isNew && !hasDiscount ? `<div class="absolute top-4 right-4 bg-green-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Nuevo</div>` : ''}
            </div>
            <div class="p-6">
                <span class="text-xs text-amber-400 uppercase tracking-wider">${product.brand}</span>
                <h3 class="text-xl font-semibold mt-2 mb-3">${product.name}</h3>
                <div class="flex items-center mb-4">
                    <div class="flex text-amber-400 text-sm">
                        ${Array(5).fill().map((_, i) => `<i class="fas fa-star"></i>`).join('')}
                    </div>
                    <span class="text-gray-400 text-sm ml-2">(${Math.floor(Math.random() * 200)})</span>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        ${hasDiscount ? `<span class="text-gray-400 line-through text-sm">$${product.originalPrice}</span>` : ''}
                        <span class="text-2xl font-bold text-amber-400 ${hasDiscount ? 'ml-2' : ''}">$${product.price}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="flex-1 bg-amber-500 text-black py-3 rounded-lg font-semibold hover:bg-amber-400 transition add-to-cart" ${product.stock === 0 ? 'disabled' : ''}>
                        <i class="fas fa-shopping-cart mr-2"></i>${product.stock === 0 ? 'Agotado' : 'Agregar'}
                    </button>
                    <a href="pages/producto-detalle.html?id=${product.id}" class="bg-gray-700 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
    `;
}

function renderProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;
    
    const products = loadProductsFromStorage();
    
    if (products.length === 0) {
        productsGrid.innerHTML = `
            <div class="col-span-full text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-xl">No hay productos disponibles</p>
                <p class="text-gray-500 text-sm mt-2">Agrega productos desde el panel administrativo</p>
            </div>
        `;
        return;
    }
    
    productsGrid.innerHTML = products
        .filter(p => p.status === 'active')
        .map(product => createProductCard(product))
        .join('');
    
    // Re-agregar event listeners para agregar al carrito
    attachAddToCartListeners();
}

function createProductCard(product) {
    const gradients = {
        'dulce': 'from-pink-900 to-purple-900',
        'amaderado': 'from-amber-900 to-orange-900',
        'citrico': 'from-yellow-900 to-green-900',
        'oriental': 'from-purple-900 to-indigo-900',
        'fresco': 'from-cyan-900 to-blue-900',
        'intenso': 'from-red-900 to-rose-900'
    };
    
    const gradient = gradients[product.family] || 'from-gray-900 to-gray-800';
    const hasDiscount = product.originalPrice && product.originalPrice > product.price;
    
    return `
        <div class="product-item bg-gray-800/50 backdrop-blur rounded-lg overflow-hidden hover:transform hover:scale-105 transition duration-300 border border-gray-700 hover:border-amber-500" 
             data-category="${product.category}" 
             data-family="${product.family}" 
             data-brand="${product.brand.toLowerCase()}" 
             data-price="${product.price}"
             data-product-id="${product.id}">
            <div class="relative overflow-hidden group">
                <div class="h-64 bg-gradient-to-br ${gradient} flex items-center justify-center">
                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">` : `<i class="fas fa-wine-bottle text-7xl text-white/30"></i>`}
                </div>
                ${hasDiscount ? `<div class="absolute top-4 right-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold">-${Math.round((1 - product.price/product.originalPrice) * 100)}%</div>` : ''}
                ${product.stock < 5 && product.stock > 0 ? `<div class="absolute top-4 right-4 bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Últimas ${product.stock}</div>` : ''}
                ${product.stock === 0 ? `<div class="absolute top-4 right-4 bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Agotado</div>` : ''}
                <button class="absolute top-4 left-4 w-10 h-10 bg-white/10 backdrop-blur rounded-full flex items-center justify-center hover:bg-amber-500 hover:text-black transition">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="p-6">
                <span class="text-xs text-amber-400 uppercase tracking-wider">${product.brand}</span>
                <h3 class="text-xl font-semibold mt-2 mb-3">${product.name}</h3>
                <div class="flex items-center mb-4">
                    <div class="flex text-amber-400 text-sm">
                        ${Array(5).fill().map((_, i) => `<i class="fas fa-star"></i>`).join('')}
                    </div>
                    <span class="text-gray-400 text-sm ml-2">(${Math.floor(Math.random() * 200)})</span>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        ${hasDiscount ? `<span class="text-gray-400 line-through text-sm">$${product.originalPrice}</span>` : ''}
                        <span class="text-2xl font-bold text-amber-400 ${hasDiscount ? 'ml-2' : ''}">$${product.price}</span>
                    </div>
                </div>
                ${product.description ? `<p class="text-gray-400 text-sm mb-4 line-clamp-2">${product.description}</p>` : ''}
                <div class="flex gap-2">
                    <button class="flex-1 bg-amber-500 text-black py-3 rounded-lg font-semibold hover:bg-amber-400 transition add-to-cart" ${product.stock === 0 ? 'disabled' : ''}>
                        <i class="fas fa-shopping-cart mr-2"></i>${product.stock === 0 ? 'Agotado' : 'Agregar'}
                    </button>
                    <a href="producto-detalle.html?id=${product.id}" class="bg-gray-700 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition flex items-center">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
    `;
}

function attachAddToCartListeners() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(btn => {
        // Remover listener anterior si existe para evitar duplicados
        btn.replaceWith(btn.cloneNode(true));
    });
    
    // Re-seleccionar después de clonar
    const newButtons = document.querySelectorAll('.add-to-cart');
    newButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Buscar el contenedor del producto (.product-item o .product-card)
            const productCard = this.closest('.product-item') || this.closest('.product-card');
            if (!productCard) return;
            
            const productId = parseInt(productCard.dataset.productId);
            const products = loadProductsFromStorage();
            const product = products.find(p => p.id === productId);
            
            if (product && product.stock > 0 && product.status === 'active') {
                cart.addItem({
                    id: product.id,
                    name: product.name,
                    brand: product.brand,
                    price: product.price
                });
            } else if (product && product.stock === 0) {
                cart.showNotification('Producto agotado');
            }
        });
    });
}

// ===================================
// Carrito de compras
// ===================================
class ShoppingCart {
    constructor() {
        this.items = this.loadCart();
        this.updateCartUI();
    }

    loadCart() {
        const stored = localStorage.getItem(CONFIG.cartStorageKey);
        if (!stored) return [];
        
        const cartItems = JSON.parse(stored);
        
        // Validar que los productos aún existan en el catálogo
        const products = loadProductsFromStorage();
        const validItems = cartItems.filter(item => {
            const product = products.find(p => p.id === item.id);
            return product && product.status === 'active';
        });
        
        // Si se eliminaron productos, actualizar el carrito
        if (validItems.length !== cartItems.length) {
            localStorage.setItem(CONFIG.cartStorageKey, JSON.stringify(validItems));
            console.log('Carrito actualizado: productos inexistentes eliminados');
        }
        
        return validItems;
    }

    saveCart() {
        localStorage.setItem(CONFIG.cartStorageKey, JSON.stringify(this.items));
    }

    addItem(product) {
        // Asegurar que el ID sea consistente (número)
        const productId = typeof product.id === 'string' ? parseInt(product.id) : product.id;
        const existingItem = this.items.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: productId,
                name: product.name,
                brand: product.brand,
                price: product.price,
                quantity: 1
            });
        }
        
        this.saveCart();
        this.updateCartUI();
        this.showNotification('Producto agregado al carrito');
    }

    removeItem(productId) {
        // Convertir a número para comparación consistente
        const id = typeof productId === 'string' ? parseInt(productId) : productId;
        this.items = this.items.filter(item => item.id !== id);
        this.saveCart();
        this.updateCartUI();
    }

    updateQuantity(productId, quantity) {
        // Convertir a número para comparación consistente
        const id = typeof productId === 'string' ? parseInt(productId) : productId;
        const item = this.items.find(item => item.id === id);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(id);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartUI();
            }
        }
    }

    getTotal() {
        return this.items.reduce((total, item) => {
            return total + (item.price * item.quantity);
        }, 0);
    }

    getItemCount() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    }

    updateCartUI() {
        // Actualizar contador del carrito
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const count = this.getItemCount();
            cartCount.textContent = count;
            cartCount.classList.toggle('hidden', count === 0);
        }

        // Actualizar modal del carrito
        this.updateCartModal();
    }

    updateCartModal() {
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');
        
        if (!cartItems || !cartTotal) return;

        if (this.items.length === 0) {
            cartItems.innerHTML = '<p class="text-gray-400 text-center py-12">Tu carrito está vacío</p>';
            cartTotal.textContent = '$0';
            return;
        }

        cartItems.innerHTML = this.items.map(item => `
            <div class="flex gap-4 mb-4 pb-4 border-b border-gray-800">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-900 to-pink-900 rounded flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-wine-bottle text-2xl text-white/30"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold mb-1">${item.name}</h4>
                    <p class="text-sm text-gray-400">${item.brand}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <button onclick="cart.updateQuantity(${item.id}, ${item.quantity - 1})" class="w-6 h-6 bg-gray-700 rounded flex items-center justify-center hover:bg-gray-600">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="w-8 text-center">${item.quantity}</span>
                        <button onclick="cart.updateQuantity(${item.id}, ${item.quantity + 1})" class="w-6 h-6 bg-gray-700 rounded flex items-center justify-center hover:bg-gray-600">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-amber-400">${CONFIG.currency}${(item.price * item.quantity).toFixed(2)}</p>
                    <button onclick="cart.removeItem(${item.id})" class="text-red-400 hover:text-red-300 text-sm mt-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        cartTotal.textContent = `${CONFIG.currency}${this.getTotal().toFixed(2)}`;
    }

    showNotification(message) {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = 'fixed top-20 right-6 bg-green-500 text-white px-6 py-4 rounded-lg shadow-2xl z-50 animate-fade-in-up';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-2xl"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    generateWhatsAppMessage() {
        let message = '*Pedido desde Bbr_Fragance*%0A%0A';
        
        this.items.forEach(item => {
            message += `*${item.name}*%0A`;
            message += `Marca: ${item.brand}%0A`;
            message += `Cantidad: ${item.quantity}%0A`;
            message += `Precio: ${CONFIG.currency}${item.price * item.quantity}%0A%0A`;
        });
        
        message += `*Total: ${CONFIG.currency}${this.getTotal()}*`;
        
        return message;
    }

    sendToWhatsApp() {
        if (this.items.length === 0) {
            alert('Tu carrito está vacío');
            return;
        }

        const message = this.generateWhatsAppMessage();
        const url = `https://wa.me/${CONFIG.whatsappNumber}?text=${message}`;
        window.open(url, '_blank');
    }
}

// Inicializar carrito
const cart = new ShoppingCart();

// ===================================
// Menú móvil
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
});

// ===================================
// Modal del carrito
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const cartBtn = document.getElementById('cart-btn');
    const cartModal = document.getElementById('cart-modal');
    const closeCartBtn = document.getElementById('close-cart');
    const whatsappOrderBtn = document.getElementById('whatsapp-order');

    if (cartBtn && cartModal) {
        cartBtn.addEventListener('click', () => {
            cartModal.classList.remove('hidden');
        });
    }

    if (closeCartBtn && cartModal) {
        closeCartBtn.addEventListener('click', () => {
            cartModal.classList.add('hidden');
        });

        // Cerrar al hacer clic fuera del modal
        cartModal.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.classList.add('hidden');
            }
        });
    }

    if (whatsappOrderBtn) {
        whatsappOrderBtn.addEventListener('click', () => {
            cart.sendToWhatsApp();
        });
    }
});

// ===================================
// Agregar productos al carrito
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    // Cargar productos dinámicamente en la página de productos
    const productsGrid = document.getElementById('products-grid');
    if (productsGrid) {
        renderProducts();
    }

    // Botón específico para página de detalle del producto
    const addToCartDetail = document.getElementById('add-to-cart-detail');
    if (addToCartDetail) {
        addToCartDetail.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener información del producto desde la página de detalle
            const productName = document.querySelector('h1')?.textContent || 'Producto';
            const productBrand = document.querySelector('.text-amber-400.uppercase')?.textContent || 'Marca';
            const priceElement = document.querySelector('.text-4xl.font-bold.text-amber-400');
            const productPrice = priceElement ? parseFloat(priceElement.textContent.replace('$', '')) : 0;
            const quantity = parseInt(document.getElementById('qty-input')?.value || 1);

            // Buscar el producto real por nombre y marca en localStorage
            const products = loadProductsFromStorage();
            const foundProduct = products.find(p => 
                p.name.toLowerCase() === productName.toLowerCase() && 
                p.brand.toLowerCase() === productBrand.toLowerCase()
            );

            if (foundProduct) {
                // Agregar la cantidad especificada
                for (let i = 0; i < quantity; i++) {
                    cart.addItem({
                        id: foundProduct.id,
                        name: foundProduct.name,
                        brand: foundProduct.brand,
                        price: foundProduct.price
                    });
                }
            } else {
                // Si no se encuentra, usar un ID basado en el nombre
                const productId = Math.abs(productName.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0));
                
                for (let i = 0; i < quantity; i++) {
                    cart.addItem({
                        id: productId,
                        name: productName,
                        brand: productBrand,
                        price: productPrice
                    });
                }
            }
        });
    }

    // Botón de WhatsApp en página de detalle
    const whatsappBuy = document.getElementById('whatsapp-buy');
    if (whatsappBuy) {
        whatsappBuy.addEventListener('click', () => {
            const productName = document.querySelector('h1')?.textContent || 'Producto';
            const productBrand = document.querySelector('.text-amber-400.uppercase')?.textContent || 'Marca';
            const priceElement = document.querySelector('.text-4xl.font-bold.text-amber-400');
            const productPrice = priceElement ? priceElement.textContent : '$0';
            const quantity = document.getElementById('qty-input')?.value || 1;

            const message = `*Hola! Me interesa este producto:*%0A%0A` +
                          `*${productName}*%0A` +
                          `Marca: ${productBrand}%0A` +
                          `Precio: ${productPrice}%0A` +
                          `Cantidad: ${quantity}`;

            const url = `https://wa.me/${CONFIG.whatsappNumber}?text=${message}`;
            window.open(url, '_blank');
        });
    }
});

// ===================================
// Filtros de productos
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const filterCheckboxes = document.querySelectorAll('.filter-category, .filter-family, .filter-brand');
    const filterPrice = document.querySelectorAll('.filter-price');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const sortSelect = document.getElementById('sort-select');
    
    // Aplicar filtros desde URL al cargar la página
    function applyFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        const family = urlParams.get('family');
        
        // Marcar checkbox de categoría si existe en URL
        if (category) {
            const categoryCheckbox = document.querySelector(`.filter-category[value="${category}"]`);
            if (categoryCheckbox) {
                categoryCheckbox.checked = true;
            }
        }
        
        // Marcar checkbox de familia olfativa si existe en URL
        if (family) {
            const familyCheckbox = document.querySelector(`.filter-family[value="${family}"]`);
            if (familyCheckbox) {
                familyCheckbox.checked = true;
            }
        }
        
        // Aplicar filtros si hay parámetros en la URL
        if (category || family) {
            applyFilters();
        }
    }
    
    function applyFilters() {
        const products = document.querySelectorAll('.product-item');
        let visibleCount = 0;

        // Obtener filtros seleccionados
        const selectedCategories = Array.from(document.querySelectorAll('.filter-category:checked')).map(cb => cb.value);
        const selectedFamilies = Array.from(document.querySelectorAll('.filter-family:checked')).map(cb => cb.value);
        const selectedBrands = Array.from(document.querySelectorAll('.filter-brand:checked')).map(cb => cb.value);
        const selectedPrice = document.querySelector('.filter-price:checked')?.value;

        products.forEach(product => {
            const category = product.dataset.category;
            const family = product.dataset.family;
            const brand = product.dataset.brand;
            const price = parseFloat(product.dataset.price);

            let show = true;

            // Filtrar por categoría
            if (selectedCategories.length > 0 && !selectedCategories.includes(category)) {
                show = false;
            }

            // Filtrar por familia
            if (selectedFamilies.length > 0 && !selectedFamilies.includes(family)) {
                show = false;
            }

            // Filtrar por marca
            if (selectedBrands.length > 0 && !selectedBrands.includes(brand)) {
                show = false;
            }

            // Filtrar por precio
            if (selectedPrice) {
                const [min, max] = selectedPrice.split('-').map(p => p === '+' ? Infinity : parseFloat(p));
                if (price < min || price > max) {
                    show = false;
                }
            }

            // Mostrar u ocultar producto
            if (show) {
                product.style.display = '';
                visibleCount++;
            } else {
                product.style.display = 'none';
            }
        });

        // Actualizar contador de resultados
        const resultsCount = document.getElementById('results-count');
        if (resultsCount) {
            resultsCount.textContent = visibleCount;
        }

        // Resetear paginación a la primera página
        if (window.pagination) {
            window.pagination.reset();
        }
    }
    
    // Inicializar contador de productos al cargar
    const products = loadProductsFromStorage();
    const activeProducts = products.filter(p => p.status === 'active');
    const resultsCount = document.getElementById('results-count');
    if (resultsCount) {
        resultsCount.textContent = activeProducts.length;
    }

    // Aplicar filtros al cambiar checkboxes
    if (filterCheckboxes.length > 0) {
        filterCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', applyFilters);
        });
    }

    if (filterPrice.length > 0) {
        filterPrice.forEach(radio => {
            radio.addEventListener('change', applyFilters);
        });
    }

    // Limpiar filtros
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            filterCheckboxes.forEach(cb => cb.checked = false);
            filterPrice.forEach(rb => rb.checked = false);
            applyFilters();
        });
    }

    // Ordenar productos
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const productsGrid = document.getElementById('products-grid');
            const products = Array.from(document.querySelectorAll('.product-item'));
            const sortValue = sortSelect.value;

            products.sort((a, b) => {
                switch (sortValue) {
                    case 'price-asc':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-desc':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'name-asc':
                        return a.querySelector('h3').textContent.localeCompare(b.querySelector('h3').textContent);
                    case 'name-desc':
                        return b.querySelector('h3').textContent.localeCompare(a.querySelector('h3').textContent);
                    default:
                        return 0;
                }
            });

            // Reorganizar productos
            products.forEach(product => productsGrid.appendChild(product));
        });
    }
    
    // Aplicar filtros desde URL al cargar la página
    applyFiltersFromURL();
});

// ===================================
// Vista grid/list de productos
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const gridViewBtn = document.getElementById('grid-view');
    const listViewBtn = document.getElementById('list-view');
    const productsGrid = document.getElementById('products-grid');

    if (gridViewBtn && listViewBtn && productsGrid) {
        gridViewBtn.addEventListener('click', () => {
            productsGrid.classList.remove('list-view');
            gridViewBtn.classList.add('bg-amber-500', 'text-black');
            gridViewBtn.classList.remove('bg-gray-800', 'text-white');
            listViewBtn.classList.remove('bg-amber-500', 'text-black');
            listViewBtn.classList.add('bg-gray-800', 'text-white');
        });

        listViewBtn.addEventListener('click', () => {
            productsGrid.classList.add('list-view');
            listViewBtn.classList.add('bg-amber-500', 'text-black');
            listViewBtn.classList.remove('bg-gray-800', 'text-white');
            gridViewBtn.classList.remove('bg-amber-500', 'text-black');
            gridViewBtn.classList.add('bg-gray-800', 'text-white');
        });
    }
});

// ===================================
// Tabs en página de detalle
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.dataset.tab;

            // Remover active de todos los botones
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Agregar active al botón clickeado
            button.classList.add('active');

            // Ocultar todo el contenido
            tabContents.forEach(content => content.classList.add('hidden'));

            // Mostrar el contenido seleccionado
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        });
    });
});

// ===================================
// Cantidad de producto
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const qtyInput = document.getElementById('qty-input');
    const qtyMinus = document.getElementById('qty-minus');
    const qtyPlus = document.getElementById('qty-plus');

    if (qtyMinus && qtyInput) {
        qtyMinus.addEventListener('click', () => {
            const currentValue = parseInt(qtyInput.value);
            if (currentValue > 1) {
                qtyInput.value = currentValue - 1;
            }
        });
    }

    if (qtyPlus && qtyInput) {
        qtyPlus.addEventListener('click', () => {
            const currentValue = parseInt(qtyInput.value);
            const max = parseInt(qtyInput.max) || 99;
            if (currentValue < max) {
                qtyInput.value = currentValue + 1;
            }
        });
    }
});

// ===================================
// Smooth scroll para enlaces internos
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            // Ignorar enlaces que no tienen contenido después del #
            if (href === '#' || href.length <= 1) return;
            
            e.preventDefault();
            
            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80; // Ajustar por el navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
});

// ===================================
// Animación de aparición al scroll
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observar elementos con animación
    const animatedElements = document.querySelectorAll('.product-card, .category-card, .testimonial-card');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });
});

// ===================================
// Búsqueda de productos
// ===================================

// Base de datos de productos (puedes expandir esto)
const productsDatabase = [
    { name: 'Sauvage', brand: 'Dior', price: 120, category: 'hombre' },
    { name: 'Coco Mademoiselle', brand: 'Chanel', price: 185, category: 'mujer' },
    { name: 'Black Orchid', brand: 'Tom Ford', price: 165, category: 'unisex' },
    { name: 'Y', brand: 'Yves Saint Laurent', price: 135, category: 'hombre' },
    { name: "J'adore", brand: 'Dior', price: 155, category: 'mujer' },
    { name: 'Eros', brand: 'Versace', price: 98, category: 'hombre' }
];

// Función para obtener la ruta correcta según la página actual
function getProductUrl() {
    const currentPath = window.location.pathname;
    // Si estamos en una página dentro de /pages/, usar ruta relativa
    if (currentPath.includes('/pages/')) {
        return 'producto-detalle.html';
    }
    // Si estamos en la raíz, usar pages/
    return 'pages/producto-detalle.html';
}

// Modal de búsqueda
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('search-btn');
    const searchModal = document.getElementById('search-modal');
    const closeSearch = document.getElementById('close-search');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    // Abrir modal de búsqueda
    if (searchBtn && searchModal) {
        searchBtn.addEventListener('click', () => {
            searchModal.classList.remove('hidden');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        });
    }

    // Cerrar modal de búsqueda
    if (closeSearch && searchModal) {
        closeSearch.addEventListener('click', () => {
            searchModal.classList.add('hidden');
            if (searchInput) searchInput.value = '';
            if (searchResults) {
                searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe para buscar productos...</p>';
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !searchModal.classList.contains('hidden')) {
                searchModal.classList.add('hidden');
                if (searchInput) searchInput.value = '';
            }
        });

        // Cerrar al hacer clic fuera
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.add('hidden');
                if (searchInput) searchInput.value = '';
            }
        });
    }

    // Búsqueda en tiempo real
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();

            if (query.length === 0) {
                searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe para buscar productos...</p>';
                return;
            }

            if (query.length < 2) {
                searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe al menos 2 caracteres...</p>';
                return;
            }

            // Buscar en la base de datos
            const results = productsDatabase.filter(product => 
                product.name.toLowerCase().includes(query) || 
                product.brand.toLowerCase().includes(query)
            );

            // Mostrar resultados
            if (results.length === 0) {
                searchResults.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-search text-4xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400">No se encontraron productos para "${query}"</p>
                    </div>
                `;
            } else {
                const productUrl = getProductUrl();
                searchResults.innerHTML = results.map(product => `
                    <a href="${productUrl}" class="flex items-center gap-4 p-4 hover:bg-gray-800 rounded-lg transition border-b border-gray-800 last:border-0">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-900 to-pink-900 rounded flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-wine-bottle text-2xl text-white/30"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-white">${product.name}</h4>
                            <p class="text-sm text-amber-400">${product.brand}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-amber-400">$${product.price}</p>
                            <p class="text-xs text-gray-400 capitalize">${product.category}</p>
                        </div>
                    </a>
                `).join('');
            }

            // También filtrar productos en la página actual si existen
            const pageProducts = document.querySelectorAll('.product-item, .product-card');
            if (pageProducts.length > 0) {
                let visibleCount = 0;
                pageProducts.forEach(product => {
                    const title = product.querySelector('h3')?.textContent.toLowerCase() || '';
                    const brand = product.querySelector('.text-amber-400')?.textContent.toLowerCase() || '';
                    
                    if (title.includes(query) || brand.includes(query)) {
                        product.style.display = '';
                        visibleCount++;
                    } else {
                        product.style.display = 'none';
                    }
                });

                // Actualizar contador si existe
                const resultsCount = document.getElementById('results-count');
                if (resultsCount) {
                    resultsCount.textContent = visibleCount;
                }
            }
        });
    }
});

// Función de búsqueda legacy (mantener para compatibilidad)
function searchProducts(query) {
    const products = document.querySelectorAll('.product-item, .product-card');
    const searchLower = query.toLowerCase();
    let visibleCount = 0;

    products.forEach(product => {
        const title = product.querySelector('h3')?.textContent.toLowerCase() || '';
        const brand = product.querySelector('.text-amber-400')?.textContent.toLowerCase() || '';
        
        if (title.includes(searchLower) || brand.includes(searchLower)) {
            product.style.display = '';
            visibleCount++;
        } else {
            product.style.display = 'none';
        }
    });

    // Actualizar contador si existe
    const resultsCount = document.getElementById('results-count');
    if (resultsCount) {
        resultsCount.textContent = visibleCount;
    }

    return visibleCount;
}

// ===================================
// Newsletter (si se implementa)
// ===================================
function subscribeNewsletter(email) {
    // Aquí iría la lógica para suscribir al newsletter
    console.log('Suscribiendo:', email);
    alert('¡Gracias por suscribirte! Recibirás nuestras ofertas exclusivas.');
}

// ===================================
// Formulario de contacto
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.querySelector('form');
    
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Aquí iría la lógica para enviar el formulario
            alert('¡Gracias por tu mensaje! Te responderemos pronto.');
            contactForm.reset();
        });
    }
});

// ===================================
// Funciones auxiliares
// ===================================

// Formatear precio
function formatPrice(price) {
    return `${CONFIG.currency}${price.toFixed(2)}`;
}

// Validar email
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Mostrar loader
function showLoader() {
    const loader = document.createElement('div');
    loader.id = 'page-loader';
    loader.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
}

// Ocultar loader
function hideLoader() {
    const loader = document.getElementById('page-loader');
    if (loader) {
        loader.remove();
    }
}

// Copiar al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('¡Copiado al portapapeles!');
    });
}

// ===================================
// Sistema de Paginación
// ===================================
class Pagination {
    constructor(itemsPerPage = 9) {
        this.itemsPerPage = itemsPerPage;
        this.currentPage = 1;
        this.totalPages = 1;
        this.init();
    }

    init() {
        // Solo inicializar si estamos en la página de productos
        const paginationContainer = document.getElementById('pagination-container');
        if (!paginationContainer) return;

        this.updatePagination();
        
        // Observar cambios en productos (cuando se aplican filtros)
        const productsGrid = document.getElementById('products-grid');
        if (productsGrid) {
            const observer = new MutationObserver(() => {
                this.updatePagination();
            });
            observer.observe(productsGrid, { 
                childList: true, 
                attributes: true, 
                subtree: true,
                attributeFilter: ['style'] 
            });
        }
    }

    getVisibleProducts() {
        const products = document.querySelectorAll('.product-item');
        return Array.from(products).filter(product => {
            return product.style.display !== 'none';
        });
    }

    updatePagination() {
        const visibleProducts = this.getVisibleProducts();
        this.totalPages = Math.ceil(visibleProducts.length / this.itemsPerPage);
        
        // Asegurar que la página actual no sea mayor al total
        if (this.currentPage > this.totalPages) {
            this.currentPage = Math.max(1, this.totalPages);
        }

        this.displayProducts();
        this.renderPaginationButtons();
    }

    displayProducts() {
        const visibleProducts = this.getVisibleProducts();
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;

        visibleProducts.forEach((product, index) => {
            if (index >= startIndex && index < endIndex) {
                product.classList.remove('hidden');
            } else {
                product.classList.add('hidden');
            }
        });

        // Scroll suave hacia arriba
        const productsSection = document.querySelector('#productos-section');
        if (productsSection) {
            productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    renderPaginationButtons() {
        const buttonsContainer = document.getElementById('pagination-buttons');
        const paginationContainer = document.getElementById('pagination-container');
        
        if (!buttonsContainer || !paginationContainer) return;

        // Ocultar paginación si solo hay una página
        if (this.totalPages <= 1) {
            paginationContainer.classList.add('hidden');
            return;
        } else {
            paginationContainer.classList.remove('hidden');
        }

        let buttonsHTML = '';

        // Botón anterior
        buttonsHTML += `
            <button 
                class="px-4 py-2 bg-gray-800 rounded-lg hover:bg-gray-700 transition ${this.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}"
                onclick="pagination.goToPage(${this.currentPage - 1})"
                ${this.currentPage === 1 ? 'disabled' : ''}
            >
                <i class="fas fa-chevron-left"></i>
            </button>
        `;

        // Botones de páginas
        const maxVisibleButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisibleButtons / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisibleButtons - 1);

        // Ajustar si estamos cerca del final
        if (endPage - startPage < maxVisibleButtons - 1) {
            startPage = Math.max(1, endPage - maxVisibleButtons + 1);
        }

        // Primera página si no está visible
        if (startPage > 1) {
            buttonsHTML += `
                <button 
                    class="px-4 py-2 bg-gray-800 rounded-lg hover:bg-gray-700 transition"
                    onclick="pagination.goToPage(1)"
                >
                    1
                </button>
            `;
            if (startPage > 2) {
                buttonsHTML += `<span class="px-2 py-2 text-gray-400">...</span>`;
            }
        }

        // Páginas visibles
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage;
            buttonsHTML += `
                <button 
                    class="px-4 py-2 rounded-lg font-semibold transition ${
                        isActive 
                            ? 'bg-amber-500 text-black' 
                            : 'bg-gray-800 hover:bg-gray-700'
                    }"
                    onclick="pagination.goToPage(${i})"
                    ${isActive ? 'disabled' : ''}
                >
                    ${i}
                </button>
            `;
        }

        // Última página si no está visible
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                buttonsHTML += `<span class="px-2 py-2 text-gray-400">...</span>`;
            }
            buttonsHTML += `
                <button 
                    class="px-4 py-2 bg-gray-800 rounded-lg hover:bg-gray-700 transition"
                    onclick="pagination.goToPage(${this.totalPages})"
                >
                    ${this.totalPages}
                </button>
            `;
        }

        // Botón siguiente
        buttonsHTML += `
            <button 
                class="px-4 py-2 bg-gray-800 rounded-lg hover:bg-gray-700 transition ${this.currentPage === this.totalPages ? 'opacity-50 cursor-not-allowed' : ''}"
                onclick="pagination.goToPage(${this.currentPage + 1})"
                ${this.currentPage === this.totalPages ? 'disabled' : ''}
            >
                <i class="fas fa-chevron-right"></i>
            </button>
        `;

        buttonsContainer.innerHTML = buttonsHTML;
    }

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.displayProducts();
        this.renderPaginationButtons();
    }

    reset() {
        this.currentPage = 1;
        this.updatePagination();
    }
}

// Instanciar paginación
let pagination;
document.addEventListener('DOMContentLoaded', () => {
    const paginationContainer = document.getElementById('pagination-container');
    if (paginationContainer) {
        pagination = new Pagination(6); // 6 productos por página
        window.pagination = pagination;
    }
    
    // Renderizar productos destacados en index.html
    const featuredProducts = document.getElementById('featured-products');
    if (featuredProducts) {
        renderFeaturedProducts();
    }
});

// ===================================
// Inicialización
// ===================================
console.log('Bbr_Fragance - Sistema cargado correctamente');
console.log('Productos en carrito:', cart.getItemCount());

// Exportar funciones globales
window.cart = cart;
window.searchProducts = searchProducts;
window.subscribeNewsletter = subscribeNewsletter;
window.copyToClipboard = copyToClipboard;
