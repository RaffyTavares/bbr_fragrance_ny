// BBR Fragrance - Admin: Core (Estado Global, API Wrapper, Utilidades, Autenticacion, Navegacion)

/**
 * BBR Fragrance - Panel Administrativo
 * JavaScript principal con API backend
 */

const API = '/BBR_FRAGANCE/api';

// ==================== Estado Global ====================
let salesChart = null;
let reportSalesChart = null;
let reportExpensesChart = null;
let reportProfitChart = null;
let posItems = [];
let taxPercent = 18;
let taxEnabled = true;
let currentProductPage = 1;
let currentOrderPage = 1;
let currentOrderStatus = '';
let currentExpensePage = 1;
let currentCustomerPage = 1;
let currentUserPage = 1;
let userPermissions = [];
let currentRoleTab = 'vendedor';
let currentSalePage = 1;
let currentSaleId = null;
let ncfEnabled = false;
let ncfSequenceStatus = {};

// ==================== API Wrapper ====================
async function api(endpoint, method = 'GET', body = null) {
    try {
        const opts = { method, credentials: 'include' };
        if (body instanceof FormData) {
            opts.body = body;
        } else if (body) {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(API + endpoint, opts);
        if (res.status === 401) {
            window.location.replace('admin-login.html');
            return null;
        }
        return await res.json();
    } catch (e) {
        showNotification('Error de conexion con el servidor', 'error');
        return null;
    }
}

// ==================== Utilidades ====================
function formatCurrency(amount) {
    const n = parseFloat(amount) || 0;
    return 'RD$ ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('es-DO', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function formatDateShort(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('es-DO', { year: 'numeric', month: 'short', day: 'numeric' });
}

function showNotification(message, type = 'success') {
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600' };
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    const div = document.createElement('div');
    div.className = `fixed top-20 right-4 z-[100] ${colors[type]} text-white px-6 py-3 rounded-lg shadow-xl flex items-center space-x-2`;
    div.style.animation = 'fadeInUp 0.3s ease';
    div.innerHTML = `<i class="fas ${icons[type]}"></i><span>${message}</span>`;
    document.body.appendChild(div);
    setTimeout(() => { div.style.opacity = '0'; div.style.transition = 'opacity 0.3s'; setTimeout(() => div.remove(), 300); }, 3000);
}

function showConfirm(message) {
    return new Promise(resolve => {
        const modal = document.getElementById('confirm-modal');
        if (!modal) { resolve(confirm(message)); return; }
        const msgEl = modal.querySelector('#confirm-message');
        if (msgEl) msgEl.textContent = message;
        modal.classList.remove('hidden');
        const yesBtn = modal.querySelector('#confirm-yes');
        const noBtn = modal.querySelector('#confirm-no');
        const handler = (val) => () => { modal.classList.add('hidden'); yesBtn.replaceWith(yesBtn.cloneNode(true)); noBtn.replaceWith(noBtn.cloneNode(true)); resolve(val); };
        yesBtn.addEventListener('click', handler(true), { once: true });
        noBtn.addEventListener('click', handler(false), { once: true });
    });
}

function debounce(fn, ms) {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
}

function autoResizeTextarea(el) {
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = el.scrollHeight + 'px';
}

function getStatusBadge(status) {
    const map = {
        'pending': ['Pendiente', 'bg-yellow-500/20 text-yellow-400'],
        'confirmed': ['Confirmado', 'bg-blue-500/20 text-blue-400'],
        'processing': ['Procesando', 'bg-purple-500/20 text-purple-400'],
        'shipped': ['Enviado', 'bg-indigo-500/20 text-indigo-400'],
        'delivered': ['Entregado', 'bg-green-500/20 text-green-400'],
        'cancelled': ['Cancelado', 'bg-red-500/20 text-red-400'],
        'completed': ['Completada', 'bg-green-500/20 text-green-400'],
        'refunded': ['Reembolsada', 'bg-orange-500/20 text-orange-400'],
        'active': ['Activo', 'bg-green-500/20 text-green-400'],
        'inactive': ['Inactivo', 'bg-red-500/20 text-red-400'],
        'open': ['Abierta', 'bg-green-500/20 text-green-400'],
        'closed': ['Cerrada', 'bg-gray-500/20 text-gray-400'],
    };
    const [label, cls] = map[status] || [status, 'bg-gray-500/20 text-gray-400'];
    return `<span class="px-2 py-1 rounded-full text-xs font-medium ${cls}">${label}</span>`;
}

function getPaymentLabel(method) {
    return { cash: 'Efectivo', card: 'Tarjeta', transfer: 'Transferencia', mixed: 'Mixto' }[method] || method;
}

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function populateSelect(id, items, valueKey, labelKey, placeholder = null) {
    const sel = document.getElementById(id);
    if (!sel) return;
    sel.innerHTML = placeholder ? `<option value="">${placeholder}</option>` : '';
    (items || []).forEach(item => {
        sel.innerHTML += `<option value="${item[valueKey]}">${item[labelKey]}${item.product_count !== undefined ? ' (' + item.product_count + ')' : ''}</option>`;
    });
}

function renderPagination(containerId, pagination, loadFnName) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const totalPages = pagination.total_pages || pagination.pages || 0;
    const currentPage = pagination.current_page || pagination.page || 1;
    if (!pagination || totalPages <= 1) { container.innerHTML = ''; return; }
    let html = '<div class="flex items-center justify-center space-x-2 mt-6">';
    if (currentPage > 1) html += `<button onclick="${loadFnName}(${currentPage - 1})" class="px-3 py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm"><i class="fas fa-chevron-left"></i></button>`;
    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);
    if (start > 1) { html += `<button onclick="${loadFnName}(1)" class="px-3 py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm">1</button>`; if (start > 2) html += '<span class="text-gray-500">...</span>'; }
    for (let i = start; i <= end; i++) {
        html += i === currentPage
            ? `<button class="px-3 py-1 bg-amber-500 text-black rounded font-bold text-sm">${i}</button>`
            : `<button onclick="${loadFnName}(${i})" class="px-3 py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm">${i}</button>`;
    }
    if (end < totalPages) { if (end < totalPages - 1) html += '<span class="text-gray-500">...</span>'; html += `<button onclick="${loadFnName}(${totalPages})" class="px-3 py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm">${totalPages}</button>`; }
    if (currentPage < totalPages) html += `<button onclick="${loadFnName}(${currentPage + 1})" class="px-3 py-1 bg-gray-700 rounded hover:bg-gray-600 transition text-sm"><i class="fas fa-chevron-right"></i></button>`;
    html += '</div>';
    container.innerHTML = html;
}

// ==================== AUTENTICACION ====================
const roleLabels = { admin: 'Administrador', vendedor: 'Vendedor', cajero: 'Cajero', tecnico: 'Tecnico' };

async function checkAuth() {
    const res = await api('/auth/check');
    if (!res || !res.success) { window.location.href = 'admin-login.html'; return false; }
    const user = res.data;
    setText('admin-fullname', user.full_name || user.username);
    const avatar = document.getElementById('admin-avatar');
    if (avatar) avatar.textContent = (user.full_name || user.username).charAt(0).toUpperCase();
    const role = document.getElementById('admin-role');
    if (role) role.textContent = roleLabels[user.role] || user.role;

    // Store permissions for UI control
    userPermissions = user.permissions || [];

    // Hide sidebar sections the user doesn't have permission for
    const sectionPermMap = {
        'dashboard': 'dashboard.view',
        'pos': 'pos.access',
        'products': 'products.view',
        'promotions': 'products.edit',
        'brands': 'products.view',
        'orders': 'orders.view',
        'sales': 'pos.access',
        'expenses': 'expenses.view',
        'cash-register': 'cash_register.access',
        'reports': 'reports.view',
        'customers': 'customers.view',
        'users': 'users.view',
        'settings': 'settings.view'
    };
    document.querySelectorAll('[data-section]').forEach(btn => {
        const section = btn.dataset.section;
        const perm = sectionPermMap[section];
        if (perm && !hasPermission(perm)) {
            btn.style.display = 'none';
        }
    });

    return true;
}

function hasPermission(permKey) {
    return userPermissions.includes(permKey);
}

// ==================== NAVEGACION ====================
function showSection(name) {
    const btn = document.querySelector(`[data-section="${name}"]`);
    if (btn) { btn.click(); return; }
    // Fallback: navigate directly
    document.querySelectorAll('.admin-section').forEach(s => s.classList.add('hidden'));
    const sec = document.getElementById(name + '-section');
    if (sec) sec.classList.remove('hidden');
    onSectionShow(name);
}

function initNavigation() {
    const btns = document.querySelectorAll('[data-section]');
    const sections = document.querySelectorAll('.admin-section');
    btns.forEach(btn => {
        if (btn.tagName === 'A') return; // skip links like "Ver Sitio Web"
        btn.addEventListener('click', () => {
            const name = btn.dataset.section;
            btns.forEach(b => { if (b.tagName !== 'A') { b.classList.remove('bg-amber-500/10', 'text-amber-400'); b.classList.add('text-gray-400'); } });
            btn.classList.add('bg-amber-500/10', 'text-amber-400');
            btn.classList.remove('text-gray-400');
            sections.forEach(s => s.classList.add('hidden'));
            const sec = document.getElementById(name + '-section');
            if (sec) sec.classList.remove('hidden');
            onSectionShow(name);
            // Close mobile sidebar
            document.getElementById('admin-sidebar')?.classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay')?.classList.add('hidden');
        });
    });
    // Mobile sidebar toggle
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        document.getElementById('admin-sidebar')?.classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay')?.classList.toggle('hidden');
    });
    document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
        document.getElementById('admin-sidebar')?.classList.add('-translate-x-full');
        document.getElementById('sidebar-overlay')?.classList.add('hidden');
    });
}

function onSectionShow(name) {
    switch (name) {
        case 'dashboard': loadDashboard(); break;
        case 'pos': loadPOSInit(); break;
        case 'products': {
            loadFilterOptions();
            const activeTab = document.querySelector('.products-tab.active')?.dataset?.productsTab || 'catalog';
            if (activeTab === 'inventory') loadInventory();
            else loadProducts();
            break;
        }
        case 'orders': loadOrders(); break;
        case 'sales': loadSales(); break;
        case 'expenses': loadExpenseCategories(); loadExpenses(); break;
        case 'cash-register': loadCashRegister(); break;
        case 'reports': initReportDates(); break;
        case 'customers': loadCustomers(); break;
        case 'settings': loadSettings(); loadRolePermissions(); break;
        case 'promotions': loadPromotions(); break;
        case 'brands': loadBrands(); break;
        case 'users': loadUsers(); break;
    }
}
