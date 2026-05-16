// BBR Fragrance - Admin: Ventas (CRUD)

// ==================== VENTAS (CRUD) ====================
async function loadSales(page = 1) {
    currentSalePage = page;
    const dateFrom = document.getElementById('sales-date-from')?.value || '';
    const dateTo = document.getElementById('sales-date-to')?.value || '';
    const payment = document.getElementById('sales-filter-payment')?.value || '';
    const status = document.getElementById('sales-filter-status')?.value || '';
    const search = document.getElementById('sales-search')?.value?.trim() || '';
    const minAmount = document.getElementById('sales-filter-min')?.value || '';

    // Set default dates if empty (current month)
    if (!dateFrom && !dateTo && page === 1) {
        const today = new Date().toISOString().split('T')[0];
        const firstOfMonth = today.substring(0, 8) + '01';
        const fromEl = document.getElementById('sales-date-from');
        const toEl = document.getElementById('sales-date-to');
        if (fromEl) fromEl.value = firstOfMonth;
        if (toEl) toEl.value = today;
    }

    const df = document.getElementById('sales-date-from')?.value || '';
    const dt = document.getElementById('sales-date-to')?.value || '';

    let url = `/sales?page=${page}&limit=20`;
    if (df) url += `&date_from=${df}`;
    if (dt) url += `&date_to=${dt}`;
    if (payment) url += `&payment_method=${payment}`;
    if (status) url += `&status=${status}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (minAmount) url += `&min_amount=${minAmount}`;

    const res = await api(url);
    const tbody = document.getElementById('sales-table');
    if (!tbody) return;

    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">No se encontraron ventas para este periodo</td></tr>';
        renderPagination('sales-pagination', null, 'loadSales');
        const info = document.getElementById('sales-pagination-info');
        if (info) info.textContent = 'Mostrando 0 ventas';
        setText('sales-summary-total', 'RD$0.00');
        setText('sales-summary-count', '0');
        setText('sales-summary-cash', 'RD$0.00');
        setText('sales-summary-other', 'RD$0.00');
        return;
    }

    // Calculate summaries from all returned data
    let sumTotal = 0, sumCash = 0, sumOther = 0;
    res.data.forEach(s => {
        const t = parseFloat(s.total) || 0;
        if (s.status === 'completed') {
            sumTotal += t;
            if (s.payment_method === 'cash') sumCash += t;
            else sumOther += t;
        }
    });
    setText('sales-summary-total', formatCurrency(sumTotal));
    setText('sales-summary-count', res.pagination?.total || res.data.length);
    setText('sales-summary-cash', formatCurrency(sumCash));
    setText('sales-summary-other', formatCurrency(sumOther));

    tbody.innerHTML = res.data.map(s => `
        <tr class="hover:bg-gray-700/30 transition cursor-pointer" onclick="viewSale(${s.id})">
            <td class="px-4 py-3 font-medium text-amber-400 text-sm">${s.sale_number}</td>
            <td class="px-4 py-3 text-sm text-gray-300">${formatDate(s.created_at)}</td>
            <td class="px-4 py-3 text-sm">${s.user_name || '-'}</td>
            <td class="px-4 py-3 text-sm">${s.customer_name || '-'}</td>
            <td class="px-4 py-3">${getPaymentBadge(s.payment_method)}</td>
            <td class="px-4 py-3 text-right font-bold text-sm">${formatCurrency(s.total)}</td>
            <td class="px-4 py-3 text-center">${getStatusBadge(s.status)}</td>
            <td class="px-4 py-3">
                <div class="flex justify-end space-x-2">
                    <button onclick="event.stopPropagation(); viewSale(${s.id})" class="text-blue-400 hover:text-blue-300 transition" title="Ver detalle"><i class="fas fa-eye"></i></button>
                    <button onclick="event.stopPropagation(); printSaleById(${s.id})" class="text-gray-400 hover:text-gray-300 transition" title="Imprimir"><i class="fas fa-print"></i></button>
                    ${s.status === 'completed' ? `<button onclick="event.stopPropagation(); cancelSale(${s.id})" class="text-red-400 hover:text-red-300 transition" title="Cancelar"><i class="fas fa-ban"></i></button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');

    if (res.pagination) {
        renderPagination('sales-pagination', res.pagination, 'loadSales');
        const info = document.getElementById('sales-pagination-info');
        if (info) info.textContent = `Mostrando ${res.data.length} de ${res.pagination.total} ventas`;
    }
}

function getPaymentBadge(method) {
    const map = {
        cash: ['Efectivo', 'bg-green-500/20 text-green-400', 'fa-money-bill-wave'],
        card: ['Tarjeta', 'bg-blue-500/20 text-blue-400', 'fa-credit-card'],
        transfer: ['Transf.', 'bg-purple-500/20 text-purple-400', 'fa-exchange-alt'],
        mixed: ['Mixto', 'bg-orange-500/20 text-orange-400', 'fa-coins']
    };
    const [label, cls, icon] = map[method] || [method, 'bg-gray-500/20 text-gray-400', 'fa-question'];
    return `<span class="px-2 py-1 rounded-full text-xs font-medium ${cls}"><i class="fas ${icon} mr-1"></i>${label}</span>`;
}

async function viewSale(id) {
    currentSaleId = id;
    const res = await api(`/sales/${id}`);
    if (!res?.success) return;
    const s = res.data;
    const modal = document.getElementById('sale-detail-modal');
    if (!modal) return;

    setText('sale-detail-number', s.sale_number);
    document.getElementById('sale-detail-status').innerHTML = getStatusBadge(s.status);
    setText('sale-detail-date', formatDate(s.created_at));
    setText('sale-detail-user', s.user_name || '-');
    setText('sale-detail-customer', s.customer_name || 'Sin cliente');
    setText('sale-detail-payment', getPaymentLabel(s.payment_method));

    // NCF info
    const ncfSection = document.getElementById('sale-detail-ncf-section');
    if (ncfSection) {
        if (s.ncf_number) {
            ncfSection.style.display = 'block';
            setText('sale-detail-ncf-number', s.ncf_number);
            const ncfLabels = { B01: 'Credito Fiscal', B02: 'Consumo', B14: 'Reg. Especiales', B15: 'Gubernamental' };
            setText('sale-detail-ncf-type-label', ncfLabels[s.ncf_type] || s.ncf_type || '');
            setText('sale-detail-ncf-rnc', s.customer_rnc ? 'RNC: ' + s.customer_rnc : '');
        } else {
            ncfSection.style.display = 'none';
        }
    }

    // Items
    const itemsTbody = document.getElementById('sale-detail-items');
    if (itemsTbody) {
        itemsTbody.innerHTML = (s.items || []).map(i => `
            <tr class="border-b border-gray-700/30">
                <td class="px-4 py-2">${i.product_name} <span class="text-gray-500 text-xs">${i.product_brand || ''}</span></td>
                <td class="px-4 py-2 text-center">${i.quantity}</td>
                <td class="px-4 py-2 text-right">${formatCurrency(i.unit_price)}</td>
                <td class="px-4 py-2 text-right font-medium">${formatCurrency(i.subtotal)}</td>
            </tr>
        `).join('');
    }

    // Totals
    setText('sale-detail-subtotal', formatCurrency(s.subtotal));
    const discountRow = document.getElementById('sale-detail-discount-row');
    if (discountRow) discountRow.style.display = parseFloat(s.discount_amount) > 0 ? 'flex' : 'none';
    setText('sale-detail-discount-pct', s.discount_percent || 0);
    setText('sale-detail-discount-amt', '-' + formatCurrency(s.discount_amount));
    const taxRow = document.getElementById('sale-detail-tax-row');
    if (taxRow) taxRow.style.display = parseFloat(s.tax_amount) > 0 ? 'flex' : 'none';
    setText('sale-detail-tax-pct', s.tax_percent || 0);
    setText('sale-detail-tax-amt', formatCurrency(s.tax_amount));
    setText('sale-detail-total', formatCurrency(s.total));

    // Cash info
    const cashRow = document.getElementById('sale-detail-cash-row');
    const changeRow = document.getElementById('sale-detail-change-row');
    if (s.cash_received) {
        if (cashRow) cashRow.style.display = 'flex';
        if (changeRow) changeRow.style.display = 'flex';
        setText('sale-detail-cash-received', formatCurrency(s.cash_received));
        setText('sale-detail-cash-change', formatCurrency(s.cash_change));
    } else {
        if (cashRow) cashRow.style.display = 'none';
        if (changeRow) changeRow.style.display = 'none';
    }

    // Notes
    const notesSection = document.getElementById('sale-detail-notes-section');
    if (s.notes) {
        if (notesSection) notesSection.style.display = 'block';
        setText('sale-detail-notes', s.notes);
    } else {
        if (notesSection) notesSection.style.display = 'none';
    }

    // Cancel button visibility
    const cancelBtn = document.getElementById('sale-cancel-btn');
    if (cancelBtn) cancelBtn.style.display = s.status === 'completed' ? 'inline-flex' : 'none';

    modal.classList.remove('hidden');
}

async function cancelSale(id) {
    const ok = await showConfirm('Cancelar esta venta? Se revertira el stock y los totales del cliente.');
    if (!ok) return;
    const res = await api(`/sales/${id}/cancel`, 'POST');
    if (res?.success) {
        showNotification('Venta cancelada exitosamente', 'success');
        loadSales(currentSalePage);
    } else {
        showNotification(res?.message || 'Error al cancelar venta', 'error');
    }
}

async function cancelSaleFromDetail() {
    if (!currentSaleId) return;
    await cancelSale(currentSaleId);
    document.getElementById('sale-detail-modal')?.classList.add('hidden');
}

function printSaleReceipt() {
    if (currentSaleId) showReceipt(currentSaleId);
}

function printSaleById(id) {
    showReceipt(id);
}
