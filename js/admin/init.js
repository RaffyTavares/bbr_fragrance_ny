// BBR Fragance - Admin: Inicializacion Principal

// ==================== INICIALIZACION PRINCIPAL ====================
document.addEventListener('DOMContentLoaded', async () => {
    // 1. Verificar autenticacion
    const authed = await checkAuth();
    if (!authed) return;

    // 2. Setup navegacion
    initNavigation();

    // 3. Cargar dashboard
    loadDashboard();

    // 4. Logout button
    document.getElementById('logout-btn')?.addEventListener('click', async () => {
        const ok = await showConfirm('Desea cerrar sesion?');
        if (!ok) return;
        await api('/auth/logout', 'POST');
        window.location.href = 'admin-login.html';
    });

    // 4b. Notification panel toggle
    document.getElementById('notification-btn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        const panel = document.getElementById('notification-panel');
        if (panel) panel.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        const panel = document.getElementById('notification-panel');
        const btn = document.getElementById('notification-btn');
        if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.add('hidden');
        }
    });

    // 5. POS event listeners
    document.getElementById('pos-search')?.addEventListener('input', posSearchHandler);
    document.getElementById('pos-view-toggle')?.addEventListener('click', togglePOSView);
    document.getElementById('pos-discount')?.addEventListener('input', updatePOSTotals);
    document.getElementById('pos-customer')?.addEventListener('input', function() {
        posSelectedCustomerId = null;
        posCustomerSearchHandler();
    });
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('pos-customer-dropdown');
        const input = document.getElementById('pos-customer');
        if (dropdown && input && !input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    document.getElementById('pos-pay-cash')?.addEventListener('click', openCashModal);
    document.getElementById('pos-pay-card')?.addEventListener('click', () => processSale('card'));
    document.getElementById('pos-pay-transfer')?.addEventListener('click', () => processSale('transfer'));
    document.getElementById('pos-pay-mixed')?.addEventListener('click', openMixedModal);
    document.getElementById('cash-received')?.addEventListener('input', e => {
        const received = parseFloat(e.target.value) || 0;
        const { total } = calculatePOSTotals();
        setText('cash-change', formatCurrency(Math.max(0, received - total)));
    });
    document.getElementById('cash-confirm')?.addEventListener('click', () => {
        const received = parseFloat(document.getElementById('cash-received')?.value);
        const { total } = calculatePOSTotals();
        if (!received || received < total) { showNotification('El monto recibido es insuficiente', 'error'); return; }
        processSale('cash', received);
    });
    // Mixed payment modal events
    document.getElementById('mixed-cash-amount')?.addEventListener('input', updateMixedSummary);
    document.querySelectorAll('.mixed-method-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            mixedSecondMethod = btn.dataset.method;
            updateMixedMethodButtons();
            updateMixedSummary();
        });
    });
    document.getElementById('mixed-confirm')?.addEventListener('click', confirmMixedPayment);

    // 6. Product event listeners
    document.getElementById('add-product-btn')?.addEventListener('click', () => openProductModal());
    document.getElementById('save-product-btn')?.addEventListener('click', saveProduct);
    document.getElementById('products-view-toggle')?.addEventListener('click', toggleProductsView);
    document.getElementById('admin-product-search')?.addEventListener('input', debounce(() => loadProducts(), 400));
    document.getElementById('filter-category')?.addEventListener('change', () => loadProducts());
    document.getElementById('filter-family')?.addEventListener('change', () => loadProducts());
    document.getElementById('filter-status')?.addEventListener('change', () => loadProducts());
    document.getElementById('filter-featured')?.addEventListener('change', () => loadProducts());
    document.getElementById('clear-filters-btn')?.addEventListener('click', () => {
        ['admin-product-search', 'filter-category', 'filter-family', 'filter-status', 'filter-featured'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        loadProducts();
    });

    // 6b. Products tab switching (Catalogo / Inventario)
    document.querySelectorAll('.products-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.products-tab').forEach(t => {
                t.classList.remove('active', 'bg-amber-500', 'text-black');
                t.classList.add('text-gray-400');
            });
            tab.classList.add('active', 'bg-amber-500', 'text-black');
            tab.classList.remove('text-gray-400');
            const view = tab.dataset.productsTab;
            document.getElementById('products-catalog-view').classList.toggle('hidden', view !== 'catalog');
            document.getElementById('products-inventory-view').classList.toggle('hidden', view !== 'inventory');
            document.getElementById('add-product-btn').classList.toggle('hidden', view !== 'catalog');
            if (view === 'inventory') loadInventory();
        });
    });

    // 6c. Inventory filter tabs
    document.querySelectorAll('.inv-filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.inv-filter-tab').forEach(t => {
                t.classList.remove('bg-amber-500', 'text-black');
                t.classList.add('bg-gray-700', 'text-gray-300');
            });
            tab.classList.add('bg-amber-500', 'text-black');
            tab.classList.remove('bg-gray-700', 'text-gray-300');
            currentInventoryFilter = tab.dataset.invFilter;
            loadInventory(1);
        });
    });
    document.getElementById('inv-search')?.addEventListener('input', debounce(() => loadInventory(1), 400));

    // 7. Order event listeners
    document.querySelectorAll('.order-status-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.order-status-tab').forEach(t => { t.classList.remove('bg-amber-500', 'text-black'); t.classList.add('bg-gray-700', 'text-gray-300'); });
            tab.classList.add('bg-amber-500', 'text-black');
            tab.classList.remove('bg-gray-700', 'text-gray-300');
            loadOrders(1, tab.dataset.status || '');
        });
    });
    document.getElementById('update-order-status-btn')?.addEventListener('click', updateOrderStatus);

    // 8. Expense event listeners
    document.getElementById('add-expense-btn')?.addEventListener('click', () => openExpenseModal());
    document.getElementById('save-expense-btn')?.addEventListener('click', saveExpense);
    document.getElementById('filter-expenses-btn')?.addEventListener('click', () => loadExpenses());

    // 9. Cash register event listeners
    document.getElementById('open-register-btn')?.addEventListener('click', openRegister);
    document.getElementById('close-register-btn')?.addEventListener('click', openCloseRegisterModal);
    document.getElementById('confirm-close-register')?.addEventListener('click', closeRegister);
    document.getElementById('closing-amount')?.addEventListener('input', e => {
        const amount = parseFloat(e.target.value) || 0;
        const diff = amount - registerExpectedCash;
        const el = document.getElementById('close-difference');
        if (el) {
            el.textContent = (diff >= 0 ? '+' : '') + formatCurrency(diff);
            el.className = 'text-2xl font-bold ' + (diff >= 0 ? 'text-green-400' : 'text-red-400');
        }
    });
    // Register tabs
    document.getElementById('register-tab-current')?.addEventListener('click', () => {
        document.getElementById('register-tab-current').classList.add('bg-amber-500', 'text-black');
        document.getElementById('register-tab-current').classList.remove('text-gray-400');
        document.getElementById('register-tab-history').classList.remove('bg-amber-500', 'text-black');
        document.getElementById('register-tab-history').classList.add('text-gray-400');
        document.getElementById('register-current-view').classList.remove('hidden');
        document.getElementById('register-history-view').classList.add('hidden');
    });
    document.getElementById('register-tab-history')?.addEventListener('click', () => {
        document.getElementById('register-tab-history').classList.add('bg-amber-500', 'text-black');
        document.getElementById('register-tab-history').classList.remove('text-gray-400');
        document.getElementById('register-tab-current').classList.remove('bg-amber-500', 'text-black');
        document.getElementById('register-tab-current').classList.add('text-gray-400');
        document.getElementById('register-history-view').classList.remove('hidden');
        document.getElementById('register-current-view').classList.add('hidden');
        loadRegisterHistory();
    });
    document.getElementById('register-history-filter-btn')?.addEventListener('click', () => loadRegisterHistory());

    // 10. Report event listeners
    document.querySelectorAll('.report-preset').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.report-preset').forEach(b => { b.classList.remove('bg-amber-500', 'text-black'); b.classList.add('bg-gray-700', 'text-gray-300'); });
        btn.classList.add('bg-amber-500', 'text-black'); btn.classList.remove('bg-gray-700', 'text-gray-300');
        setReportPreset(btn.dataset.preset);
    }));
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.report-tab').forEach(t => { t.classList.remove('active', 'bg-amber-500', 'text-black'); t.classList.add('bg-gray-700', 'text-gray-300'); });
            tab.classList.add('active', 'bg-amber-500', 'text-black'); tab.classList.remove('bg-gray-700', 'text-gray-300');
            document.querySelectorAll('.report-content').forEach(c => c.classList.add('hidden'));
            const target = document.getElementById('report-' + tab.dataset.report);
            if (target) target.classList.remove('hidden');
            loadReportTab(tab.dataset.report);
        });
    });
    document.getElementById('load-report-btn')?.addEventListener('click', () => {
        const activeTab = document.querySelector('.report-tab.active')?.dataset?.report || 'sales';
        loadReportTab(activeTab);
    });

    // 11. Customer event listeners
    document.getElementById('add-customer-btn')?.addEventListener('click', () => openCustomerModal());
    document.getElementById('save-customer-btn')?.addEventListener('click', saveCustomer);
    document.getElementById('customer-search')?.addEventListener('input', debounce(() => loadCustomers(), 400));

    // 12. Settings event listeners
    document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);

    // 12b. NCF event listeners
    document.getElementById('save-ncf-btn')?.addEventListener('click', saveNcfSequence);
    document.getElementById('pos-ncf-toggle')?.addEventListener('change', function() {
        const typeSelect = document.getElementById('pos-ncf-type');
        if (typeSelect) typeSelect.disabled = !this.checked;
        validateNcfSelection();
        updatePOSTotals();
    });
    document.getElementById('pos-ncf-type')?.addEventListener('change', validateNcfSelection);
    document.getElementById('setting-ncf_enabled')?.addEventListener('change', function() {
        const ncfSeqSection = document.getElementById('ncf-sequences-section');
        if (ncfSeqSection) ncfSeqSection.style.display = this.checked ? 'block' : 'none';
        if (this.checked) loadNcfSequences();
    });

    // 12a. Promotions event listeners
    document.getElementById('save-promo-month-btn')?.addEventListener('click', savePromoMonth);
    document.getElementById('promo-image-btn')?.addEventListener('click', () => document.getElementById('promo-image-input')?.click());
    document.getElementById('promo-image-input')?.addEventListener('change', uploadPromoImage);
    // Auto-resize textareas on input
    ['promo-month-subtitle', 'promo-bullet-1', 'promo-bullet-2', 'promo-bullet-3'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', function() { autoResizeTextarea(this); });
    });
    document.getElementById('add-offer-btn')?.addEventListener('click', openOfferModal);
    document.getElementById('close-offer-modal')?.addEventListener('click', closeOfferModal);
    document.getElementById('cancel-offer-modal')?.addEventListener('click', closeOfferModal);
    document.getElementById('save-offer-btn')?.addEventListener('click', saveOffer);
    document.getElementById('offer-product-search')?.addEventListener('input', debounce(searchOfferProducts, 300));
    document.getElementById('offer-clear-product')?.addEventListener('click', clearOfferProduct);
    document.getElementById('offer-original-price')?.addEventListener('input', updateOfferDiscountPreview);

    // 12b. Brands event listeners
    document.getElementById('add-brand-btn')?.addEventListener('click', () => openBrandModal());
    document.getElementById('brandForm')?.addEventListener('submit', saveBrand);
    document.getElementById('close-brand-modal')?.addEventListener('click', closeBrandModal);
    document.getElementById('cancel-brand-modal')?.addEventListener('click', closeBrandModal);

    // 12c. Sales section event listeners
    document.getElementById('sales-filter-btn')?.addEventListener('click', () => loadSales(1));
    document.getElementById('sales-clear-filters-btn')?.addEventListener('click', () => {
        ['sales-date-from', 'sales-date-to', 'sales-search', 'sales-filter-min'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        ['sales-filter-payment', 'sales-filter-status'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        loadSales(1);
    });

    // 13. User management event listeners
    document.getElementById('add-user-btn')?.addEventListener('click', () => openUserModal());
    document.getElementById('save-user-btn')?.addEventListener('click', saveUser);
    document.getElementById('user-search')?.addEventListener('input', debounce(() => loadUsers(), 400));
    document.getElementById('filter-user-role')?.addEventListener('change', () => loadUsers());
    document.getElementById('clear-user-filters-btn')?.addEventListener('click', () => {
        const search = document.getElementById('user-search');
        const role = document.getElementById('filter-user-role');
        if (search) search.value = '';
        if (role) role.value = '';
        loadUsers();
    });

    // 14. Role permissions event listeners
    document.querySelectorAll('.role-perm-tab').forEach(tab => {
        tab.addEventListener('click', () => loadRolePermissions(tab.dataset.roleTab));
    });
    document.getElementById('save-role-permissions-btn')?.addEventListener('click', saveRolePermissions);

    // 15. Image preview on file select
    document.getElementById('product-image')?.addEventListener('change', e => {
        const preview = document.getElementById('image-preview');
        if (!preview || !e.target.files[0]) return;
        const reader = new FileReader();
        reader.onload = ev => { preview.innerHTML = `<img src="${ev.target.result}" class="w-24 h-24 rounded-lg object-cover border border-amber-500">`; };
        reader.readAsDataURL(e.target.files[0]);
    });

    // 16. Modal close handlers - close on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });
    });
    // Close buttons
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.closeModal;
            const modal = modalId ? document.getElementById(modalId) : btn.closest('.modal-backdrop');
            if (modal) modal.classList.add('hidden');
        });
    });

    // 18. Load tax settings for POS
    const settingsRes = await api('/settings');
    if (settingsRes?.success) {
        taxPercent = parseFloat(settingsRes.data.tax_percent) || 18;
        taxEnabled = settingsRes.data.tax_enabled === '1';
    }
});
