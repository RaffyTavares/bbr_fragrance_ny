// ===================================
// Panel Administrativo - Bbr_Fragance
// ===================================

// Verificar autenticación al cargar la página
window.addEventListener('DOMContentLoaded', () => {
    checkAuthentication();
    loadDashboardData();
    initializeNavigation();
    initializeProductManagement();
});

// ===================================
// Autenticación
// ===================================
function checkAuthentication() {
    const sessionStorage = window.sessionStorage.getItem('admin_session');
    const localStorage = window.localStorage.getItem('admin_session');
    
    if (!sessionStorage && !localStorage) {
        window.location.href = 'admin-login.html';
        return;
    }
    
    // Cargar datos del usuario
    const session = JSON.parse(sessionStorage || localStorage);
    document.getElementById('admin-username').textContent = session.username;
}

// Cerrar sesión
document.getElementById('logout-btn').addEventListener('click', () => {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        sessionStorage.removeItem('admin_session');
        localStorage.removeItem('admin_session');
        window.location.href = 'admin-login.html';
    }
});

// ===================================
// Navegación entre secciones
// ===================================
function initializeNavigation() {
    const navButtons = document.querySelectorAll('.nav-btn');
    const sections = document.querySelectorAll('.content-section');
    
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const sectionName = btn.dataset.section;
            
            // Actualizar botones
            navButtons.forEach(b => {
                b.classList.remove('bg-amber-500/10', 'text-amber-400', 'border', 'border-amber-500/30');
                b.classList.add('text-gray-300');
            });
            btn.classList.add('bg-amber-500/10', 'text-amber-400', 'border', 'border-amber-500/30');
            btn.classList.remove('text-gray-300');
            
            // Mostrar sección correspondiente
            sections.forEach(section => section.classList.add('hidden'));
            document.getElementById(`${sectionName}-section`).classList.remove('hidden');
            
            // Cargar datos específicos de la sección
            if (sectionName === 'products') {
                loadProducts();
            }
        });
    });
}

// ===================================
// Gestión de Productos - Almacenamiento
// ===================================
class ProductManager {
    constructor() {
        this.storageKey = 'bbr_products';
        this.products = this.loadProducts();
    }
    
    loadProducts() {
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
            return JSON.parse(stored);
        }
        
        // Productos iniciales de ejemplo
        return [
            {
                id: 1,
                name: "Sauvage",
                brand: "Dior",
                category: "hombre",
                family: "fresco",
                price: 145,
                stock: 25,
                status: "active",
                description: "Un perfume fresco y elegante",
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
                description: "Fragancia dulce y sofisticada",
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
                description: "Aroma intenso y misterioso",
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
                description: "Perfume amaderado moderno",
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
                description: "Frescura cítrica elegante",
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
                description: "Intensidad seductora",
                createdAt: new Date().toISOString()
            }
        ];
    }
    
    saveProducts() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.products));
    }
    
    getAllProducts() {
        return this.products;
    }
    
    getProduct(id) {
        return this.products.find(p => p.id === id);
    }
    
    addProduct(product) {
        const newProduct = {
            ...product,
            id: this.products.length > 0 ? Math.max(...this.products.map(p => p.id)) + 1 : 1,
            createdAt: new Date().toISOString()
        };
        this.products.push(newProduct);
        this.saveProducts();
        return newProduct;
    }
    
    updateProduct(id, updates) {
        const index = this.products.findIndex(p => p.id === id);
        if (index !== -1) {
            this.products[index] = { ...this.products[index], ...updates };
            this.saveProducts();
            return true;
        }
        return false;
    }
    
    deleteProduct(id) {
        const index = this.products.findIndex(p => p.id === id);
        if (index !== -1) {
            this.products.splice(index, 1);
            this.saveProducts();
            
            // Eliminar también del carrito si existe
            this.removeFromCart(id);
            
            return true;
        }
        return false;
    }
    
    removeFromCart(productId) {
        try {
            const cartKey = 'bbr_cart';
            const cartData = localStorage.getItem(cartKey);
            if (cartData) {
                let cart = JSON.parse(cartData);
                // Filtrar el producto eliminado del carrito
                const originalLength = cart.length;
                cart = cart.filter(item => item.id !== productId);
                
                if (cart.length !== originalLength) {
                    localStorage.setItem(cartKey, JSON.stringify(cart));
                    console.log(`Producto ${productId} eliminado del carrito`);
                }
            }
        } catch (error) {
            console.error('Error al eliminar producto del carrito:', error);
        }
    }
    
    searchProducts(query) {
        return this.products.filter(p => 
            p.name.toLowerCase().includes(query.toLowerCase()) ||
            p.brand.toLowerCase().includes(query.toLowerCase())
        );
    }
    
    filterProducts(filters) {
        return this.products.filter(p => {
            if (filters.category && p.category !== filters.category) return false;
            if (filters.family && p.family !== filters.family) return false;
            if (filters.status && p.status !== filters.status) return false;
            return true;
        });
    }
}

const productManager = new ProductManager();

// ===================================
// Dashboard
// ===================================
function loadDashboardData() {
    // Actualizar contador de productos
    const totalProducts = productManager.getAllProducts().length;
    document.getElementById('total-products').textContent = totalProducts;
    
    // Actividad reciente
    const recentActivity = [
        { icon: 'fa-plus', text: 'Nuevo producto agregado: "Sauvage"', time: 'Hace 2 horas', color: 'text-green-400' },
        { icon: 'fa-edit', text: 'Producto actualizado: "Coco Mademoiselle"', time: 'Hace 5 horas', color: 'text-blue-400' },
        { icon: 'fa-shopping-cart', text: 'Nueva orden recibida #1234', time: 'Hace 1 día', color: 'text-purple-400' },
        { icon: 'fa-trash', text: 'Producto eliminado del inventario', time: 'Hace 2 días', color: 'text-red-400' }
    ];
    
    const activityContainer = document.getElementById('recent-activity');
    activityContainer.innerHTML = recentActivity.map(activity => `
        <div class="flex items-start space-x-3 p-3 bg-gray-700/50 rounded-lg">
            <div class="w-8 h-8 bg-gray-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas ${activity.icon} ${activity.color}"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm">${activity.text}</p>
                <p class="text-xs text-gray-400 mt-1">${activity.time}</p>
            </div>
        </div>
    `).join('');
    
    // Productos más vendidos
    const topProducts = [
        { name: 'Sauvage', brand: 'Dior', sales: 145, trend: '+12%' },
        { name: 'Coco Mademoiselle', brand: 'Chanel', sales: 132, trend: '+8%' },
        { name: 'Black Orchid', brand: 'Tom Ford', sales: 98, trend: '+15%' }
    ];
    
    const topProductsContainer = document.getElementById('top-products');
    topProductsContainer.innerHTML = topProducts.map(product => `
        <div class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg">
            <div>
                <p class="font-semibold">${product.name}</p>
                <p class="text-sm text-gray-400">${product.brand}</p>
            </div>
            <div class="text-right">
                <p class="font-semibold text-amber-400">${product.sales} ventas</p>
                <p class="text-xs text-green-400">${product.trend}</p>
            </div>
        </div>
    `).join('');
}

// ===================================
// Gestión de Productos - UI
// ===================================
function initializeProductManagement() {
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const closeModal = document.getElementById('close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const productForm = document.getElementById('product-form');
    const searchInput = document.getElementById('search-products');
    const filterCategory = document.getElementById('filter-category');
    const filterFamily = document.getElementById('filter-family');
    const clearFilters = document.getElementById('clear-filters');
    
    // Abrir modal para agregar
    addProductBtn.addEventListener('click', () => {
        openProductModal();
    });
    
    // Cerrar modal
    closeModal.addEventListener('click', () => {
        productModal.classList.add('hidden');
    });
    
    cancelBtn.addEventListener('click', () => {
        productModal.classList.add('hidden');
    });
    
    // Enviar formulario
    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveProduct();
    });
    
    // Búsqueda y filtros
    searchInput.addEventListener('input', () => {
        loadProducts();
    });
    
    filterCategory.addEventListener('change', () => {
        loadProducts();
    });
    
    filterFamily.addEventListener('change', () => {
        loadProducts();
    });
    
    clearFilters.addEventListener('click', () => {
        searchInput.value = '';
        filterCategory.value = '';
        filterFamily.value = '';
        loadProducts();
    });
}

function openProductModal(productId = null) {
    const modal = document.getElementById('product-modal');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('product-form');
    
    // Limpiar formulario
    form.reset();
    document.getElementById('product-id').value = '';
    
    if (productId) {
        // Modo edición
        modalTitle.textContent = 'Editar Producto';
        const product = productManager.getProduct(productId);
        
        if (product) {
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-name').value = product.name;
            document.getElementById('product-brand').value = product.brand;
            document.getElementById('product-category').value = product.category;
            document.getElementById('product-family').value = product.family;
            document.getElementById('product-price').value = product.price;
            document.getElementById('product-stock').value = product.stock;
            document.getElementById('product-status').value = product.status;
            document.getElementById('product-description').value = product.description || '';
        }
    } else {
        // Modo agregar
        modalTitle.textContent = 'Agregar Producto';
    }
    
    modal.classList.remove('hidden');
}

function saveProduct() {
    const productId = document.getElementById('product-id').value;
    const productData = {
        name: document.getElementById('product-name').value,
        brand: document.getElementById('product-brand').value,
        category: document.getElementById('product-category').value,
        family: document.getElementById('product-family').value,
        price: parseFloat(document.getElementById('product-price').value),
        stock: parseInt(document.getElementById('product-stock').value),
        status: document.getElementById('product-status').value,
        description: document.getElementById('product-description').value
    };
    
    if (productId) {
        // Actualizar producto existente
        productManager.updateProduct(parseInt(productId), productData);
        showNotification('Producto actualizado exitosamente', 'success');
    } else {
        // Agregar nuevo producto
        productManager.addProduct(productData);
        showNotification('Producto agregado exitosamente', 'success');
    }
    
    // Cerrar modal y recargar tabla
    document.getElementById('product-modal').classList.add('hidden');
    loadProducts();
    loadDashboardData(); // Actualizar contador en dashboard
}

function loadProducts() {
    const searchQuery = document.getElementById('search-products').value;
    const categoryFilter = document.getElementById('filter-category').value;
    const familyFilter = document.getElementById('filter-family').value;
    
    let products = productManager.getAllProducts();
    
    // Aplicar búsqueda
    if (searchQuery) {
        products = productManager.searchProducts(searchQuery);
    }
    
    // Aplicar filtros
    products = products.filter(p => {
        if (categoryFilter && p.category !== categoryFilter) return false;
        if (familyFilter && p.family !== familyFilter) return false;
        return true;
    });
    
    renderProductsTable(products);
}

function renderProductsTable(products) {
    const tbody = document.getElementById('products-table-body');
    
    if (products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                    <i class="fas fa-box-open text-4xl mb-3 block"></i>
                    No se encontraron productos
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr class="hover:bg-gray-700/30 transition">
            <td class="px-6 py-4 text-sm">#${product.id}</td>
            <td class="px-6 py-4">
                <div class="w-16 h-16 rounded-lg overflow-hidden bg-gradient-to-br from-purple-900 to-pink-900 flex items-center justify-center">
                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">` : `<i class="fas fa-wine-bottle text-2xl text-white/30"></i>`}
                </div>
            </td>
            <td class="px-6 py-4">
                <div>
                    <p class="font-semibold">${product.name}</p>
                    <p class="text-sm text-gray-400">${product.brand}</p>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 bg-purple-500/20 text-purple-400 rounded text-xs">
                    ${getCategoryLabel(product.category)}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-xs">
                    ${getFamilyLabel(product.family)}
                </span>
            </td>
            <td class="px-6 py-4 font-semibold text-amber-400">$${product.price}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 ${product.stock > 10 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'} rounded text-xs">
                    ${product.stock} uds
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 ${product.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'} rounded text-xs">
                    ${product.status === 'active' ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td class="px-6 py-4 text-right">
                <button onclick="editProduct(${product.id})" class="text-blue-400 hover:text-blue-300 transition mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteProduct(${product.id})" class="text-red-400 hover:text-red-300 transition">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getCategoryLabel(category) {
    const labels = {
        'mujer': 'Mujer',
        'hombre': 'Hombre',
        'unisex': 'Unisex'
    };
    return labels[category] || category;
}

function getFamilyLabel(family) {
    const labels = {
        'dulce': 'Dulce',
        'amaderado': 'Amaderado',
        'citrico': 'Cítrico',
        'oriental': 'Oriental',
        'fresco': 'Fresco',
        'intenso': 'Intenso'
    };
    return labels[family] || family;
}

function editProduct(id) {
    openProductModal(id);
}

function deleteProduct(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
        productManager.deleteProduct(id);
        showNotification('Producto eliminado exitosamente', 'success');
        loadProducts();
        loadDashboardData(); // Actualizar contador en dashboard
    }
}

// ===================================
// Notificaciones
// ===================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-2xl transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Hacer funciones globales para los botones onclick
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
