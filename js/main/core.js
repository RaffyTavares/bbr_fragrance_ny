// BBR Fragance - Main: Core (API Helper, Format Currency, Product Image Helper, Utility Functions)

// ===================================
// Bbr_Fragance - Main JavaScript
// Premium Perfume Store (API Version)
// ===================================

const API_BASE = '/web-BBR_Fragance/api';

const CONFIG = {
    whatsappNumber: '18094855693',
    currency: 'RD$',
    cartStorageKey: 'bbr_cart'
};

// ===================================
// API Helper
// ===================================
async function apiGet(endpoint) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, data: [] };
    }
}

// ===================================
// Format Currency
// ===================================
function formatPrice(price) {
    const num = parseFloat(price);
    return `${CONFIG.currency} ${num.toLocaleString('es-DO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// ===================================
// Product Image Helper
// ===================================
function getProductImage(product) {
    if (product.image_url) {
        return product.image_url;
    }
    if (product.images && product.images.length > 0) {
        return product.images[0].url || product.images[0].image_url;
    }
    return null;
}

function getProductGradient(family) {
    const gradients = {
        'dulce': 'from-pink-900 to-purple-900',
        'amaderado': 'from-amber-900 to-orange-900',
        'citrico': 'from-yellow-900 to-green-900',
        'oriental': 'from-purple-900 to-indigo-900',
        'fresco': 'from-cyan-900 to-blue-900',
        'intenso': 'from-red-900 to-rose-900'
    };
    return gradients[(family || '').toLowerCase()] || 'from-purple-900 to-pink-900';
}

// Get detail page URL based on current path
function getDetailUrl(productId) {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/pages/')) {
        return `producto-detalle.html?id=${productId}`;
    }
    return `pages/producto-detalle.html?id=${productId}`;
}

// ===================================
// Utility Functions
// ===================================
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showLoader() {
    const loader = document.createElement('div');
    loader.id = 'page-loader';
    loader.className = 'fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loader);
}

function hideLoader() {
    const loader = document.getElementById('page-loader');
    if (loader) loader.remove();
}

console.log('Bbr_Fragance - Sistema cargado correctamente (API Mode)');
