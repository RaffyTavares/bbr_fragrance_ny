// BBR Fragance - Admin: POS (Punto de Venta) y Pago Mixto

// ==================== POS (Punto de Venta) ====================
let posCategory = '';
let posAllProducts = [];
let posViewGrid = true;
let posSelectedCustomerId = null;
let posCustomerResults = [];
let posRegisterSessionId = null;

async function loadPOSInit() {
    const res = await api('/settings');
    if (res?.success) {
        taxPercent = parseFloat(res.data.tax_percent) || 18;
        taxEnabled = res.data.tax_enabled === '1';
        ncfEnabled = res.data.ncf_enabled === '1';
    }
    // NCF section visibility
    const ncfSection = document.getElementById('pos-ncf-section');
    if (ncfSection) ncfSection.style.display = ncfEnabled ? 'block' : 'none';
    if (ncfEnabled) loadNcfStatus();
    // Check open register
    await checkPOSRegister();
    renderPOSItems();
    updatePOSTotals();
    await loadPOSCategories();
    await loadPOSProducts();
}

async function checkPOSRegister() {
    const registerRes = await api('/cash-register/current');
    posRegisterSessionId = (registerRes?.success && registerRes.data) ? registerRes.data.id : null;
    const payBtns = document.querySelectorAll('#pos-pay-cash, #pos-pay-card, #pos-pay-transfer, #pos-pay-mixed');
    const warning = document.getElementById('pos-no-register-warning');
    if (!posRegisterSessionId) {
        payBtns.forEach(btn => { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); });
        if (!warning) {
            const container = document.getElementById('pos-pay-cash')?.parentElement;
            if (container) {
                const div = document.createElement('div');
                div.id = 'pos-no-register-warning';
                div.className = 'bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-center text-sm text-red-400';
                div.innerHTML = '<i class="fas fa-cash-register mr-2"></i>Debe abrir una caja para realizar ventas';
                container.parentElement.insertBefore(div, container);
            }
        }
    } else {
        payBtns.forEach(btn => { btn.disabled = false; btn.classList.remove('opacity-50', 'cursor-not-allowed'); });
        if (warning) warning.remove();
    }
}

async function loadPOSCategories() {
    const res = await api('/categories');
    const container = document.getElementById('pos-category-tabs');
    if (!container || !res?.success) return;
    container.innerHTML = `<button class="pos-cat-tab px-3 py-1.5 bg-amber-500 text-black rounded-full text-xs font-semibold whitespace-nowrap" data-cat="">Todos</button>`;
    (res.data || []).forEach(c => {
        container.innerHTML += `<button class="pos-cat-tab px-3 py-1.5 bg-gray-700 text-gray-300 rounded-full text-xs font-semibold whitespace-nowrap hover:bg-gray-600 transition" data-cat="${c.slug}">${c.name}</button>`;
    });
    // Bind tab clicks
    container.querySelectorAll('.pos-cat-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            container.querySelectorAll('.pos-cat-tab').forEach(t => { t.classList.remove('bg-amber-500', 'text-black'); t.classList.add('bg-gray-700', 'text-gray-300'); });
            tab.classList.add('bg-amber-500', 'text-black');
            tab.classList.remove('bg-gray-700', 'text-gray-300');
            posCategory = tab.dataset.cat;
            filterPOSProducts();
        });
    });
}

async function loadPOSProducts() {
    const res = await api('/products?limit=200&status=active');
    if (!res?.success) return;
    posAllProducts = res.data || [];
    filterPOSProducts();
}

const posCustomerSearchHandler = debounce(async function() {
    const input = document.getElementById('pos-customer');
    const dropdown = document.getElementById('pos-customer-dropdown');
    if (!input || !dropdown) return;
    const query = input.value.trim();
    if (query.length < 2) { dropdown.classList.add('hidden'); posCustomerResults = []; return; }
    const res = await api('/customers?search=' + encodeURIComponent(query) + '&limit=5');
    if (!res?.success || !res.data?.length) { dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500 text-xs">No se encontraron clientes</div>'; dropdown.classList.remove('hidden'); posCustomerResults = []; return; }
    posCustomerResults = res.data;
    dropdown.innerHTML = res.data.map((c, i) => `<div class="px-3 py-2 hover:bg-gray-700 cursor-pointer text-sm transition" onclick="selectPOSCustomer(${i})"><span class="font-medium">${c.name}</span><span class="text-gray-400 text-xs ml-2">${c.phone || ''}</span></div>`).join('');
    dropdown.classList.remove('hidden');
}, 300);

function selectPOSCustomer(index) {
    const c = posCustomerResults[index];
    if (!c) return;
    posSelectedCustomerId = c.id;
    const input = document.getElementById('pos-customer');
    if (input) input.value = c.name;
    document.getElementById('pos-customer-dropdown')?.classList.add('hidden');
}

function filterPOSProducts() {
    const query = (document.getElementById('pos-search')?.value || '').toLowerCase().trim();
    let filtered = posAllProducts;

    if (posCategory) {
        filtered = filtered.filter(p => p.category_slug === posCategory);
    }
    if (query.length >= 2) {
        filtered = filtered.filter(p =>
            (p.name || '').toLowerCase().includes(query) ||
            (p.brand_name || '').toLowerCase().includes(query) ||
            (p.barcode || '').toLowerCase().includes(query) ||
            (p.sku || '').toLowerCase().includes(query)
        );
    }

    renderPOSProductGrid(filtered);
}

function togglePOSView() {
    posViewGrid = !posViewGrid;
    const btn = document.getElementById('pos-view-toggle');
    const grid = document.getElementById('pos-products-grid');
    if (btn) {
        btn.innerHTML = posViewGrid ? '<i class="fas fa-th"></i>' : '<i class="fas fa-list"></i>';
    }
    if (grid) {
        if (posViewGrid) {
            grid.className = 'flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 content-start';
        } else {
            grid.className = 'flex-1 overflow-y-auto flex flex-col gap-2 content-start';
        }
    }
    filterPOSProducts();
}

function renderPOSProductGrid(products) {
    const grid = document.getElementById('pos-products-grid');
    if (!grid) return;

    if (!products.length) {
        grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-12"><i class="fas fa-box-open text-3xl mb-3 block"></i><p class="text-sm">No se encontraron productos</p></div>';
        return;
    }

    if (posViewGrid) {
        grid.innerHTML = products.map(p => {
            const inCart = posItems.find(i => i.id === p.id);
            const cartQty = inCart ? inCart.quantity : 0;
            const lowStock = p.stock <= (p.min_stock || 5);
            const noStock = p.stock <= 0;
            const productData = encodeURIComponent(JSON.stringify({ id: p.id, name: p.name, brand: p.brand_name, price: parseFloat(p.price), stock: parseInt(p.stock), image: p.image_url }));
            return `
            <div class="bg-gray-800 rounded-lg border ${cartQty > 0 ? 'border-amber-500/50 ring-1 ring-amber-500/20' : 'border-gray-700'} p-3 cursor-pointer hover:border-amber-500 transition relative ${noStock ? 'opacity-50' : ''}" onclick="${noStock ? '' : `addToPOSFromCard('${productData}')`}">
                ${cartQty > 0 ? `<div class="absolute -top-2 -right-2 w-6 h-6 bg-amber-500 text-black rounded-full flex items-center justify-center text-xs font-bold">${cartQty}</div>` : ''}
                <div class="aspect-square rounded-lg overflow-hidden bg-gray-700 mb-2">
                    ${p.image_url ? `<img src="${p.image_url}" class="w-full h-full object-cover" loading="lazy">` : '<div class="w-full h-full flex items-center justify-center"><i class="fas fa-spray-can text-gray-500 text-2xl"></i></div>'}
                </div>
                <p class="text-xs font-medium truncate">${p.name}</p>
                <p class="text-xs text-gray-500 truncate">${p.brand_name || ''}</p>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-amber-400 font-bold text-sm">${formatCurrency(p.price)}</span>
                    <span class="text-xs ${lowStock ? 'text-red-400 font-bold' : 'text-gray-500'}">${noStock ? 'Agotado' : p.stock}</span>
                </div>
            </div>`;
        }).join('');
    } else {
        grid.innerHTML = products.map(p => {
            const inCart = posItems.find(i => i.id === p.id);
            const cartQty = inCart ? inCart.quantity : 0;
            const lowStock = p.stock <= (p.min_stock || 5);
            const noStock = p.stock <= 0;
            const productData = encodeURIComponent(JSON.stringify({ id: p.id, name: p.name, brand: p.brand_name, price: parseFloat(p.price), stock: parseInt(p.stock), image: p.image_url }));
            return `
            <div class="bg-gray-800 rounded-lg border ${cartQty > 0 ? 'border-amber-500/50 ring-1 ring-amber-500/20' : 'border-gray-700'} p-2 cursor-pointer hover:border-amber-500 transition flex items-center gap-3 ${noStock ? 'opacity-50' : ''}" onclick="${noStock ? '' : `addToPOSFromCard('${productData}')`}">
                <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-700 flex-shrink-0">
                    ${p.image_url ? `<img src="${p.image_url}" class="w-full h-full object-cover" loading="lazy">` : '<div class="w-full h-full flex items-center justify-center"><i class="fas fa-spray-can text-gray-500 text-sm"></i></div>'}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">${p.name}</p>
                    <p class="text-xs text-gray-500 truncate">${p.brand_name || ''}</p>
                </div>
                <div class="text-right flex-shrink-0 flex items-center gap-3">
                    <span class="text-xs ${lowStock ? 'text-red-400 font-bold' : 'text-gray-500'}">${noStock ? 'Agotado' : 'Stock: ' + p.stock}</span>
                    <span class="text-amber-400 font-bold text-sm">${formatCurrency(p.price)}</span>
                    ${cartQty > 0 ? `<span class="w-6 h-6 bg-amber-500 text-black rounded-full flex items-center justify-center text-xs font-bold">${cartQty}</span>` : ''}
                </div>
            </div>`;
        }).join('');
    }
}

const posSearchHandler = debounce(function () {
    filterPOSProducts();
}, 250);

function addToPOSFromCard(encodedProduct) {
    addToPOS(JSON.parse(decodeURIComponent(encodedProduct)));
}

function addToPOS(product) {
    const existing = posItems.find(i => i.id === product.id);
    if (existing) {
        if (existing.quantity >= product.stock) { showNotification('Stock insuficiente', 'error'); return; }
        existing.quantity++;
    } else {
        if (product.stock <= 0) { showNotification('Producto sin stock', 'error'); return; }
        posItems.push({ ...product, quantity: 1 });
    }
    renderPOSItems();
    updatePOSTotals();
    filterPOSProducts(); // refresh grid to show cart badges
    showNotification(product.name + ' agregado', 'success');
}

function removeFromPOS(index) {
    posItems.splice(index, 1);
    renderPOSItems();
    updatePOSTotals();
    filterPOSProducts();
}

function updatePOSQty(index, delta) {
    const item = posItems[index];
    if (!item) return;
    const newQty = item.quantity + delta;
    if (newQty <= 0) { removeFromPOS(index); return; }
    if (newQty > item.stock) { showNotification('Stock insuficiente', 'error'); return; }
    item.quantity = newQty;
    renderPOSItems();
    updatePOSTotals();
    filterPOSProducts();
}

function renderPOSItems() {
    const tbody = document.getElementById('pos-items');
    if (!tbody) return;
    // Update item count badge
    const countEl = document.getElementById('pos-item-count');
    if (countEl) countEl.textContent = posItems.reduce((s, i) => s + i.quantity, 0);

    if (!posItems.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500 text-sm">Agrega productos a la venta</td></tr>';
        return;
    }
    tbody.innerHTML = posItems.map((item, i) => `
        <tr class="border-b border-gray-700/50">
            <td class="px-3 py-2"><p class="text-sm font-medium">${item.name}</p><p class="text-xs text-gray-400">${item.brand || ''}</p></td>
            <td class="px-3 py-2">
                <div class="flex items-center space-x-1">
                    <button onclick="updatePOSQty(${i},-1)" class="w-6 h-6 bg-gray-700 rounded text-xs hover:bg-gray-600 transition">-</button>
                    <span class="w-8 text-center text-sm font-medium">${item.quantity}</span>
                    <button onclick="updatePOSQty(${i},1)" class="w-6 h-6 bg-gray-700 rounded text-xs hover:bg-gray-600 transition">+</button>
                </div>
            </td>
            <td class="px-3 py-2 text-sm">${formatCurrency(item.price)}</td>
            <td class="px-3 py-2 text-sm font-medium text-amber-400">${formatCurrency(item.price * item.quantity)}</td>
            <td class="px-3 py-2"><button onclick="removeFromPOS(${i})" class="text-red-400 hover:text-red-300 transition"><i class="fas fa-times"></i></button></td>
        </tr>
    `).join('');
}

function calculatePOSTotals() {
    const subtotal = posItems.reduce((s, i) => s + (i.price * i.quantity), 0);
    const discountPct = parseFloat(document.getElementById('pos-discount')?.value) || 0;
    const discountAmt = subtotal * (discountPct / 100);
    const taxable = subtotal - discountAmt;

    // ITBIS: con NCF se suma al precio, sin NCF ya esta incluido
    const isNcfSale = document.getElementById('pos-ncf-toggle')?.checked || false;
    let tax = 0;
    let total = taxable;

    if (taxEnabled && taxPercent > 0) {
        if (isNcfSale) {
            // Con NCF: ITBIS se agrega encima
            tax = taxable * (taxPercent / 100);
            total = taxable + tax;
        } else {
            // Sin NCF: ITBIS incluido, se extrae para mostrar
            tax = taxable - (taxable / (1 + taxPercent / 100));
            total = taxable;
        }
    }

    return { subtotal, discountAmt, tax, total };
}

function updatePOSTotals() {
    const { subtotal, discountAmt, tax, total } = calculatePOSTotals();
    setText('pos-subtotal', formatCurrency(subtotal));
    setText('pos-discount-amount', '- ' + formatCurrency(discountAmt));
    setText('pos-tax', formatCurrency(tax));
    setText('pos-total', formatCurrency(total));

    // Update ITBIS label based on NCF toggle
    const isNcfSale = document.getElementById('pos-ncf-toggle')?.checked || false;
    const taxLabel = document.getElementById('pos-tax-label');
    if (taxLabel) taxLabel.textContent = isNcfSale ? 'ITBIS (+):' : 'ITBIS (incl.):';
}

function openCashModal() {
    if (!posItems.length) { showNotification('Agrega productos a la venta', 'error'); return; }
    const { total } = calculatePOSTotals();
    const modal = document.getElementById('cash-modal');
    if (!modal) return;
    const totalDisplay = modal.querySelector('#cash-total-display');
    if (totalDisplay) totalDisplay.textContent = formatCurrency(total);
    document.getElementById('cash-received').value = '';
    setText('cash-change', formatCurrency(0));
    modal.classList.remove('hidden');
    document.getElementById('cash-received')?.focus();
}

// ==================== PAGO MIXTO ====================
let mixedSecondMethod = 'card';

function openMixedModal() {
    if (!posItems.length) { showNotification('Agrega productos a la venta', 'error'); return; }
    const { total } = calculatePOSTotals();
    const modal = document.getElementById('mixed-modal');
    if (!modal) return;
    setText('mixed-total-display', formatCurrency(total));
    document.getElementById('mixed-cash-amount').value = '';
    mixedSecondMethod = 'card';
    updateMixedMethodButtons();
    updateMixedSummary();
    modal.classList.remove('hidden');
    document.getElementById('mixed-cash-amount')?.focus();
}

function updateMixedMethodButtons() {
    document.querySelectorAll('.mixed-method-btn').forEach(btn => {
        const m = btn.dataset.method;
        if (m === mixedSecondMethod) {
            btn.className = m === 'card'
                ? 'mixed-method-btn bg-blue-600/20 border-2 border-blue-500 text-blue-400 py-2 rounded-lg text-sm font-semibold transition'
                : 'mixed-method-btn bg-purple-600/20 border-2 border-purple-500 text-purple-400 py-2 rounded-lg text-sm font-semibold transition';
        } else {
            btn.className = 'mixed-method-btn bg-gray-700 border-2 border-gray-600 text-gray-400 py-2 rounded-lg text-sm font-semibold transition hover:border-' + (m === 'card' ? 'blue' : 'purple') + '-500';
        }
    });
    const label = document.getElementById('mixed-other-label');
    const display = document.getElementById('mixed-other-display');
    if (label) label.textContent = mixedSecondMethod === 'card' ? 'Tarjeta:' : 'Transferencia:';
    if (display) display.className = 'font-medium ' + (mixedSecondMethod === 'card' ? 'text-blue-400' : 'text-purple-400');
}

function updateMixedSummary() {
    const { total } = calculatePOSTotals();
    const cashAmt = parseFloat(document.getElementById('mixed-cash-amount')?.value) || 0;
    const otherAmt = Math.max(0, total - cashAmt);
    const sum = cashAmt + otherAmt;
    const remaining = Math.max(0, total - sum);

    setText('mixed-cash-display', formatCurrency(cashAmt));
    setText('mixed-other-display', formatCurrency(otherAmt));
    setText('mixed-sum-display', formatCurrency(sum));

    const remainingRow = document.getElementById('mixed-remaining-row');
    if (remainingRow) remainingRow.style.display = remaining > 0 ? 'flex' : 'none';
    setText('mixed-remaining-display', formatCurrency(remaining));

    // Visual feedback if cash exceeds total
    const sumEl = document.getElementById('mixed-sum-display');
    if (sumEl) {
        if (cashAmt > total) {
            sumEl.className = 'font-bold text-red-400';
        } else if (Math.abs(sum - total) < 0.01) {
            sumEl.className = 'font-bold text-green-400';
        } else {
            sumEl.className = 'font-bold';
        }
    }
}

function confirmMixedPayment() {
    const { total } = calculatePOSTotals();
    const cashAmt = parseFloat(document.getElementById('mixed-cash-amount')?.value) || 0;

    if (cashAmt <= 0) {
        showNotification('Ingresa el monto en efectivo', 'error');
        return;
    }
    if (cashAmt >= total) {
        showNotification('El efectivo cubre el total. Usa pago en efectivo en su lugar.', 'error');
        return;
    }

    document.getElementById('mixed-modal')?.classList.add('hidden');
    processSale('mixed', cashAmt, mixedSecondMethod);
}

async function processSale(paymentMethod, cashReceived = null, mixedOtherMethod = null) {
    if (!posItems.length) { showNotification('Agrega productos a la venta', 'error'); return; }
    if (!posRegisterSessionId) { showNotification('Debe abrir una caja antes de realizar ventas', 'error'); return; }
    const discountPct = parseFloat(document.getElementById('pos-discount')?.value) || 0;

    const body = {
        items: posItems.map(i => ({ product_id: i.id, quantity: i.quantity, discount: 0 })),
        payment_method: paymentMethod,
        discount_percent: discountPct,
        notes: document.getElementById('pos-notes')?.value || '',
        register_session_id: posRegisterSessionId
    };
    if (posSelectedCustomerId) body.customer_id = posSelectedCustomerId;
    if (cashReceived !== null) body.cash_received = parseFloat(cashReceived);
    if (paymentMethod === 'mixed' && mixedOtherMethod) body.mixed_other_method = mixedOtherMethod;

    // NCF
    const ncfToggle = document.getElementById('pos-ncf-toggle');
    if (ncfToggle?.checked) {
        body.ncf_requested = true;
        body.ncf_type = document.getElementById('pos-ncf-type')?.value || 'B02';
    }

    const res = await api('/sales', 'POST', body);
    if (res?.success) {
        showNotification('Venta registrada: ' + (res.data.sale_number || ''), 'success');
        document.getElementById('cash-modal')?.classList.add('hidden');
        showReceipt(res.data.id);
        clearPOS();
    } else {
        showNotification(res?.message || 'Error al procesar venta', 'error');
    }
}

async function showReceipt(saleId) {
    const res = await api('/sales/' + saleId + '/receipt');
    if (!res?.success) return;
    const r = res.data;
    const win = window.open('', '_blank', 'width=380,height=650');
    if (!win) return;

    // NCF section for receipt
    let ncfHtml = '';
    if (r.ncf?.ncf_number) {
        ncfHtml = `<div class="line"></div>
<div class="center bold" style="font-size:13px;margin:4px 0">NCF: ${r.ncf.ncf_number}</div>
<div class="center" style="font-size:10px">Tipo: ${r.ncf.ncf_label || r.ncf.ncf_type || ''}</div>
${r.ncf.customer_rnc ? `<div class="center" style="font-size:10px">RNC Cliente: ${r.ncf.customer_rnc}</div>` : ''}`;
    }

    win.document.write(`<!DOCTYPE html><html><head><title>Recibo</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Courier New',monospace;font-size:12px;max-width:300px;margin:0 auto;padding:15px;color:#000}
.center{text-align:center}.bold{font-weight:bold}.line{border-top:1px dashed #333;margin:8px 0}
.row{display:flex;justify-content:space-between;margin:2px 0}.big{font-size:16px}
@media print{.no-print{display:none}}</style></head><body>
<div class="center bold big">${r.store?.store_name || 'BBR Fragance'}</div>
<div class="center" style="font-size:10px;margin:4px 0">${r.store?.address || ''}</div>
<div class="center" style="font-size:10px">Tel: ${r.store?.store_phone || ''}</div>
${r.store?.store_rnc ? `<div class="center" style="font-size:10px">RNC: ${r.store.store_rnc}</div>` : ''}
${ncfHtml}
<div class="line"></div>
<div class="row"><span>Recibo:</span><span class="bold">${r.sale?.sale_number || ''}</span></div>
<div class="row"><span>Fecha:</span><span>${r.sale?.date || ''}</span></div>
<div class="row"><span>Cajero:</span><span>${r.sale?.cashier || ''}</span></div>
${r.sale?.customer ? `<div class="row"><span>Cliente:</span><span>${r.sale.customer}</span></div>` : ''}
<div class="line"></div>
<div class="bold" style="margin-bottom:4px">ARTICULOS:</div>
${(r.items || []).map(i => `<div class="row"><span>${i.quantity}x ${i.product_name}</span><span>RD$${parseFloat(i.subtotal).toFixed(2)}</span></div>`).join('')}
<div class="line"></div>
<div class="row"><span>Subtotal:</span><span>RD$${parseFloat(r.totals?.subtotal || 0).toFixed(2)}</span></div>
${parseFloat(r.totals?.discount_amount) > 0 ? `<div class="row"><span>Desc. (${r.totals.discount_percent}%):</span><span>-RD$${parseFloat(r.totals.discount_amount).toFixed(2)}</span></div>` : ''}
${parseFloat(r.totals?.tax_amount) > 0 ? `<div class="row"><span>ITBIS ${r.ncf?.ncf_number ? '(+)' : '(incl.)'} ${r.totals.tax_percent}%:</span><span>RD$${parseFloat(r.totals.tax_amount).toFixed(2)}</span></div>` : ''}
<div class="line"></div>
<div class="row bold big"><span>TOTAL:</span><span>RD$${parseFloat(r.totals?.total || 0).toFixed(2)}</span></div>
<div class="line"></div>
<div class="row"><span>Pago:</span><span>${r.sale?.payment_method || ''}</span></div>
${r.payment?.cash_received ? `<div class="row"><span>Recibido:</span><span>RD$${parseFloat(r.payment.cash_received).toFixed(2)}</span></div>
<div class="row bold"><span>Cambio:</span><span>RD$${parseFloat(r.payment.cash_change).toFixed(2)}</span></div>` : ''}
<div class="line"></div>
<div class="center" style="margin:8px 0;font-size:11px">Gracias por su compra!</div>
<div class="center" style="font-size:10px">${r.store?.store_footer || 'BBR Fragance - Santo Domingo, R.D.'}</div>
<br><button onclick="window.print()" class="no-print" style="width:100%;padding:10px;cursor:pointer;font-size:14px;background:#C9A96E;border:none;border-radius:5px;font-weight:bold">Imprimir Recibo</button>
</body></html>`);
    win.document.close();
}

function clearPOS() {
    posItems = [];
    posSelectedCustomerId = null;
    posCustomerResults = [];
    renderPOSItems();
    updatePOSTotals();
    ['pos-search', 'pos-notes', 'pos-discount', 'pos-customer'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('pos-discount').value = '0';
    document.getElementById('pos-customer-dropdown')?.classList.add('hidden');
    // Reset NCF
    const ncfToggle = document.getElementById('pos-ncf-toggle');
    if (ncfToggle) ncfToggle.checked = false;
    const ncfType = document.getElementById('pos-ncf-type');
    if (ncfType) { ncfType.value = 'B02'; ncfType.disabled = true; }
    document.getElementById('pos-ncf-warning')?.classList.add('hidden');
    document.getElementById('pos-ncf-error')?.classList.add('hidden');
    filterPOSProducts();
}
