// BBR Fragance - Admin: Cuadre de Caja

// ==================== CUADRE DE CAJA ====================
let registerExpectedCash = 0;

async function loadCashRegister() {
    const res = await api('/cash-register/current');
    const openDiv = document.getElementById('register-open');
    const closedDiv = document.getElementById('register-closed');
    if (!openDiv || !closedDiv) return;
    if (res?.success && res.data) {
        const s = res.data;
        const live = s.live_totals || s;
        openDiv.classList.remove('hidden');
        closedDiv.classList.add('hidden');
        setText('register-opened-at', formatDate(s.opened_at));
        setText('register-opening', formatCurrency(s.opening_amount));
        setText('register-cash-sales', formatCurrency(live.cash_sales || live.total_cash_sales || 0));
        setText('register-card-sales', formatCurrency(live.card_sales || live.total_card_sales || 0));
        setText('register-transfer-sales', formatCurrency(live.transfer_sales || live.total_transfer_sales || 0));
        const totalSales = parseFloat(live.total_sales || 0) || (parseFloat(live.cash_sales || 0) + parseFloat(live.card_sales || 0) + parseFloat(live.transfer_sales || 0));
        setText('register-total-sales', formatCurrency(totalSales));
        setText('register-sales-count', live.sales_count || live.total_sales_count || 0);
        setText('register-expenses', formatCurrency(live.total_expenses || 0));
        registerExpectedCash = parseFloat(s.opening_amount) + parseFloat(live.cash_sales || live.total_cash_sales || 0) - parseFloat(live.total_expenses || 0);
        setText('register-expected', formatCurrency(registerExpectedCash));

        // Duration
        const opened = new Date(s.opened_at);
        const now = new Date();
        const diffMs = now - opened;
        const hours = Math.floor(diffMs / 3600000);
        const mins = Math.floor((diffMs % 3600000) / 60000);
        setText('register-duration', `${hours}h ${mins}m`);
    } else {
        openDiv.classList.add('hidden');
        closedDiv.classList.remove('hidden');
    }
}

async function openRegister() {
    const input = document.getElementById('opening-amount');
    const amount = parseFloat(input?.value);
    if (isNaN(amount) || amount < 0) { showNotification('Ingrese un monto valido para la apertura', 'error'); return; }
    const res = await api('/cash-register/open', 'POST', { opening_amount: amount });
    if (res?.success) { showNotification('Caja abierta exitosamente', 'success'); loadCashRegister(); }
    else showNotification(res?.message || 'Error al abrir caja', 'error');
}

function openCloseRegisterModal() {
    const modal = document.getElementById('close-register-modal');
    if (!modal) return;
    document.getElementById('closing-amount').value = '';
    setText('close-register-expected', formatCurrency(registerExpectedCash));
    setText('close-difference', 'RD$ 0.00');
    const diffEl = document.getElementById('close-difference');
    if (diffEl) diffEl.className = 'text-2xl font-bold text-gray-400';
    const notes = document.getElementById('closing-notes');
    if (notes) notes.value = '';
    modal.classList.remove('hidden');
    document.getElementById('closing-amount')?.focus();
}

async function closeRegister() {
    const amount = parseFloat(document.getElementById('closing-amount')?.value);
    if (isNaN(amount) || amount < 0) { showNotification('Ingrese el monto contado en caja', 'error'); return; }
    const notes = document.getElementById('closing-notes')?.value || '';
    const res = await api('/cash-register/close', 'POST', { closing_amount: amount, notes });
    if (res?.success) {
        const diff = parseFloat(res.data?.difference || 0);
        const msg = diff >= 0 ? `Caja cerrada. Sobrante: ${formatCurrency(diff)}` : `Caja cerrada. Faltante: ${formatCurrency(Math.abs(diff))}`;
        showNotification(msg, diff >= 0 ? 'success' : 'info');
        document.getElementById('close-register-modal')?.classList.add('hidden');
        loadCashRegister();
        loadRegisterHistory();
    } else showNotification(res?.message || 'Error al cerrar caja', 'error');
}

async function loadRegisterHistory() {
    const dateFrom = document.getElementById('register-history-from')?.value || '';
    const dateTo = document.getElementById('register-history-to')?.value || '';
    let url = '/cash-register/history?limit=20';
    if (dateFrom) url += `&date_from=${dateFrom}`;
    if (dateTo) url += `&date_to=${dateTo}`;
    const res = await api(url);
    const tbody = document.getElementById('register-history');
    if (!tbody) return;
    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500">No hay registros anteriores</td></tr>';
        return;
    }
    tbody.innerHTML = res.data.map(s => {
        const diff = parseFloat(s.difference) || 0;
        const totalSales = parseFloat(s.total_cash_sales || 0) + parseFloat(s.total_card_sales || 0) + parseFloat(s.total_transfer_sales || 0);
        return `<tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
            <td class="px-4 py-3 text-sm">${formatDate(s.opened_at)}</td>
            <td class="px-4 py-3 text-sm">${s.user_name || s.full_name || ''}</td>
            <td class="px-4 py-3 text-sm text-right">${formatCurrency(s.opening_amount)}</td>
            <td class="px-4 py-3 text-sm text-right font-medium text-amber-400">${formatCurrency(totalSales)}</td>
            <td class="px-4 py-3 text-sm text-right">${s.closing_amount != null ? formatCurrency(s.closing_amount) : '-'}</td>
            <td class="px-4 py-3 text-sm text-right">${s.expected_amount != null ? formatCurrency(s.expected_amount) : '-'}</td>
            <td class="px-4 py-3 text-sm text-right font-medium ${diff > 0 ? 'text-green-400' : diff < 0 ? 'text-red-400' : ''}">${s.status === 'closed' ? ((diff > 0 ? '+' : '') + formatCurrency(diff)) : '-'}</td>
            <td class="px-4 py-3 text-center">${getStatusBadge(s.status)}</td>
        </tr>`;
    }).join('');
    if (res.pagination) {
        renderPagination('register-history-pagination', res.pagination, 'loadRegisterHistory');
    }
}
