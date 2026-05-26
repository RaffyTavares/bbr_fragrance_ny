// BBR Fragrance - Main: Core (API Helper, Format Currency, Product Image Helper, Utility Functions)

// ===================================
// BBR Fragrance - Main JavaScript
// Premium Perfume Store (API Version)
// ===================================

const API_BASE = (() => {
    const src = document.currentScript && document.currentScript.src;
    if (src) return new URL(src).pathname.replace(/\/js\/.*$/, '') + '/api';
    return window.location.pathname.replace(/\/pages\/[^\/]*$/, '') + '/api';
})();

const CONFIG = {
    whatsappNumber: '16462285892',
    currency: 'USD$',
    cartStorageKey: 'bbr_cart'
};

// ===================================
// API Helper
// ===================================
async function apiGet(endpoint) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, { cache: 'no-store' });
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
    return `${CONFIG.currency} ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
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
        'dulce':               'from-pink-900 to-purple-900',
        'amaderado':           'from-amber-900 to-orange-900',
        'citrico':             'from-yellow-900 to-green-900',
        'oriental':            'from-purple-900 to-indigo-900',
        'fresco':              'from-cyan-900 to-blue-900',
        'intenso':             'from-red-900 to-rose-900',
        'floral':              'from-pink-800 to-rose-900',
        'frutal':              'from-orange-700 to-yellow-900',
        'ambar':               'from-amber-700 to-orange-900',
        'fougere':             'from-green-800 to-emerald-900',
        'chipre':              'from-lime-800 to-green-900',
        'aromatica':           'from-teal-700 to-cyan-900',
        'floral-afrutada':     'from-fuchsia-800 to-pink-900',
        'oriental-amaderada':  'from-orange-900 to-red-900',
        'gourmand':            'from-amber-800 to-red-900',
        'cuero':               'from-stone-700 to-neutral-900'
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

// ===================================
// Site Settings Loader
// Carga ajustes del negocio desde la API y actualiza el DOM
// ===================================
async function loadSiteSettings() {
    const res = await apiGet('/settings');
    if (!res?.success || !res.data) return;
    const s = res.data;

    // 1. WhatsApp: actualizar CONFIG y todos los links wa.me/
    if (s.whatsapp_number) {
        const waNum = s.whatsapp_number.replace(/\D/g, '');
        CONFIG.whatsappNumber = waNum;
        document.querySelectorAll('[data-setting="whatsapp_link"]').forEach(el => {
            el.href = `https://wa.me/${waNum}`;
            el.textContent = s.whatsapp_number;
        });
        // Actualizar el boton flotante y cualquier otro wa.me que no tenga data-setting
        document.querySelectorAll('a[href^="https://wa.me/"]').forEach(el => {
            el.href = `https://wa.me/${waNum}`;
        });
    }

    // 2. Email
    if (s.contact_email) {
        document.querySelectorAll('[data-setting="email_link"]').forEach(el => {
            el.href = `mailto:${s.contact_email}`;
            el.textContent = s.contact_email;
        });
    }

    // 3. Teléfono
    if (s.contact_phone) {
        const phoneDigits = s.contact_phone.replace(/[^0-9+]/g, '');
        document.querySelectorAll('[data-setting="phone_link"]').forEach(el => {
            el.href = `tel:${phoneDigits}`;
            el.textContent = s.contact_phone;
        });
    }

    // 4. Dirección, horario y cualquier otro campo con data-setting
    const textFields = ['address', 'store_hours', 'store_name'];
    textFields.forEach(key => {
        if (s[key]) {
            document.querySelectorAll(`[data-setting="${key}"]`).forEach(el => {
                el.textContent = s[key];
            });
        }
    });
}

console.log('BBR Fragrance - Sistema cargado correctamente (API Mode)');
