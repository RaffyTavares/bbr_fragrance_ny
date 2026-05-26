// BBR Fragrance - Admin: Pedidos

let currentOrderPaymentStatus = '';

// ==================== PEDIDOS ====================
function getPaymentStatusBadge(ps) {
    if (!ps || ps === 'pending') return '<span class="px-2 py-0.5 rounded-full text-xs bg-yellow-500/20 text-yellow-400">Pend.</span>';
    if (ps === 'paid')     return '<span class="px-2 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Pagado</span>';
    if (ps === 'failed')   return '<span class="px-2 py-0.5 rounded-full text-xs bg-red-500/20 text-red-400">Fallido</span>';
    if (ps === 'refunded') return '<span class="px-2 py-0.5 rounded-full text-xs bg-orange-500/20 text-orange-400">Reemb.</span>';
    return `<span class="px-2 py-0.5 rounded-full text-xs bg-gray-500/20 text-gray-400">${ps}</span>`;
}

async function loadOrders(page = 1, status = null) {
    currentOrderPage = page;
    if (status !== null) currentOrderStatus = status;
    let url = `/orders?page=${page}&limit=15`;
    if (currentOrderStatus) url += `&status=${currentOrderStatus}`;
    const payFilter = document.getElementById('order-filter-payment-status')?.value || '';
    if (payFilter) url += `&payment_status=${payFilter}`;
    const res = await api(url);
    const tbody = document.getElementById('orders-table');
    if (!tbody) return;
    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-500">No hay pedidos</td></tr>';
        renderPagination('orders-pagination', null, 'loadOrders');
        return;
    }
    tbody.innerHTML = res.data.map(o => `
        <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
            <td class="px-4 py-3 font-medium text-amber-400">${o.order_number}</td>
            <td class="px-4 py-3">${o.customer_name || '-'}</td>
            <td class="px-4 py-3 text-sm">${o.customer_phone || '-'}</td>
            <td class="px-4 py-3 text-sm">${formatDateShort(o.created_at)}</td>
            <td class="px-4 py-3 font-medium">${formatCurrency(o.total)}</td>
            <td class="px-4 py-3">${getStatusBadge(o.status)}</td>
            <td class="px-4 py-3">${getPaymentStatusBadge(o.payment_status)}</td>
            <td class="px-4 py-3 text-right space-x-2">
                <button onclick="viewOrder(${o.id})" class="text-blue-400 hover:text-blue-300 transition" title="Ver detalle"><i class="fas fa-eye"></i></button>
                <button onclick="downloadInvoice(${o.id})" class="text-amber-400 hover:text-amber-300 transition" title="Descargar Factura"><i class="fas fa-file-invoice"></i></button>
            </td>
        </tr>
    `).join('');
    renderPagination('orders-pagination', res.pagination, 'loadOrders');
}

function downloadInvoice(id) {
    window.open(`${API}/orders/${id}/invoice`, '_blank');
}

async function viewOrder(id) {
    const res = await api('/orders/' + id);
    if (!res?.success) return;
    const o = res.data;
    const modal = document.getElementById('order-modal');
    if (!modal) return;
    setText('order-detail-number', o.order_number);
    setText('order-detail-date', formatDate(o.created_at));
    const statusEl = document.getElementById('order-detail-status');
    if (statusEl) statusEl.innerHTML = getStatusBadge(o.status);
    setText('order-detail-customer', o.customer_name || '-');
    setText('order-detail-phone', o.customer_phone || '-');
    setText('order-detail-email', o.customer_email || '-');
    setText('order-detail-address', o.customer_address || '-');
    const itemsEl = document.getElementById('order-detail-items');
    if (itemsEl) {
        itemsEl.innerHTML = (o.items || []).map(i => `
            <tr class="border-b border-gray-700/50">
                <td class="py-2">${i.product_name} <span class="text-gray-400 text-xs">(${i.product_brand})</span></td>
                <td class="py-2 text-center">${i.quantity}</td>
                <td class="py-2 text-right">${formatCurrency(i.unit_price)}</td>
                <td class="py-2 text-right font-medium">${formatCurrency(i.subtotal)}</td>
            </tr>`).join('');
    }
    setText('order-detail-subtotal', formatCurrency(o.subtotal));
    setText('order-detail-shipping', formatCurrency(o.shipping_cost));
    setText('order-detail-tax', formatCurrency(o.tax_amount));
    setText('order-detail-total', formatCurrency(o.total));

    // Mostrar info de pago en linea
    const payInfoEl = document.getElementById('order-payment-info');
    if (payInfoEl) {
        const ps = o.payment_status || 'pending';
        const psLabel = { pending: 'Pendiente', paid: 'Pagado', failed: 'Fallido', refunded: 'Reembolsado' }[ps] || ps;
        const gwLabel = o.payment_gateway ? o.payment_gateway.charAt(0).toUpperCase() + o.payment_gateway.slice(1) : '-';
        payInfoEl.innerHTML = `
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-gray-400">Estado de pago:</span> <span class="ml-1">${getPaymentStatusBadge(ps)}</span></div>
                <div><span class="text-gray-400">Pasarela:</span> <span class="ml-1 font-medium">${gwLabel}</span></div>
                ${o.payment_transaction_id ? `<div><span class="text-gray-400">ID Transacc.:</span> <span class="ml-1 font-mono text-xs">${o.payment_transaction_id}</span></div>` : ''}
                ${o.payment_authorization  ? `<div><span class="text-gray-400">Autorizacion:</span> <span class="ml-1 font-medium text-green-400">${o.payment_authorization}</span></div>` : ''}
                ${o.payment_paid_at ? `<div class="col-span-2"><span class="text-gray-400">Pagado el:</span> <span class="ml-1">${formatDate(o.payment_paid_at)}</span></div>` : ''}
            </div>`;
        payInfoEl.closest('.bg-gray-900')?.classList.toggle('hidden', !o.payment_gateway);
    }

    // Mostrar/ocultar boton de reembolso (solo para pagos en linea confirmados)
    const refundBtn = document.getElementById('refund-order-btn');
    if (refundBtn) {
        refundBtn.classList.toggle('hidden', o.payment_status !== 'paid' || o.payment_gateway !== 'cardnet');
    }

    // Mostrar/ocultar boton de marcar como pagado (efectivo / transferencia / tarjeta POS)
    const markPaidBtn = document.getElementById('mark-paid-btn');
    if (markPaidBtn) {
        const manualMethods = ['cash', 'card', 'transfer', 'pending'];
        const showMarkPaid  = manualMethods.includes(o.payment_method) && o.payment_status !== 'paid';
        markPaidBtn.classList.toggle('hidden', !showMarkPaid);
    }

    const statusSelect = document.getElementById('order-status-update');
    if (statusSelect) statusSelect.value = o.status;
    modal.dataset.orderId = o.id;
    modal.dataset.paymentStatus = o.payment_status || 'pending';
    modal.classList.remove('hidden');
}

async function updateOrderStatus() {
    const modal = document.getElementById('order-modal');
    const id = modal?.dataset.orderId;
    const status = document.getElementById('order-status-update')?.value;
    if (!id || !status) return;
    const res = await api('/orders/' + id + '/status', 'POST', { status });
    if (res?.success) {
        showNotification('Estado del pedido actualizado', 'success');
        modal.classList.add('hidden');
        loadOrders(currentOrderPage);
    } else showNotification(res?.message || 'Error al actualizar estado', 'error');
}

async function markOrderPaid() {
    const modal = document.getElementById('order-modal');
    const id = modal?.dataset.orderId;
    if (!id) return;
    const ok = await showConfirm('Confirmas que ya recibiste el pago de este pedido?');
    if (!ok) return;
    const notes = prompt('Referencia del pago (opcional, ej: No. de transferencia):') || '';
    const res = await api(`/orders/${id}/mark-paid`, 'POST', { notes });
    if (res?.success) {
        showNotification('Pago confirmado. Pedido actualizado a Confirmado.', 'success');
        modal.classList.add('hidden');
        loadOrders(currentOrderPage);
    } else showNotification(res?.message || 'Error al marcar como pagado', 'error');
}

async function refundOrder() {
    const modal = document.getElementById('order-modal');
    const id = modal?.dataset.orderId;
    if (!id) return;
    const ok = await showConfirm('Confirmas el reembolso de este pedido? Esta accion no se puede deshacer.');
    if (!ok) return;
    const reason = prompt('Motivo del reembolso (opcional):') || 'Reembolso solicitado';
    const res = await api('/payments/cardnet/refund', 'POST', { order_id: parseInt(id), reason });
    if (res?.success) {
        showNotification('Reembolso procesado correctamente', 'success');
        modal.classList.add('hidden');
        loadOrders(currentOrderPage);
    } else showNotification(res?.message || 'Error al procesar reembolso', 'error');
}

// ==================== CONCILIACION ====================
async function loadReconciliation() {
    const res = await api('/orders/reconciliation');
    if (!res?.success) return;
    const d = res.data;

    // Tarjetas resumen
    const cards = { paid: d.summary.paid, pending: d.summary.pending, failed: d.summary.failed, refunded: d.summary.refunded };
    const recon = document.getElementById('recon-summary');
    if (recon) {
        recon.innerHTML = `
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">Pagados</p>
                <p class="text-2xl font-bold text-green-400">${cards.paid.count}</p>
                <p class="text-sm text-green-300">${formatCurrency(cards.paid.amount)}</p>
            </div>
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">Pendientes</p>
                <p class="text-2xl font-bold text-yellow-400">${cards.pending.count}</p>
                <p class="text-sm text-yellow-300">${formatCurrency(cards.pending.amount)}</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">Fallidos</p>
                <p class="text-2xl font-bold text-red-400">${cards.failed.count}</p>
                <p class="text-sm text-red-300">${formatCurrency(cards.failed.amount)}</p>
            </div>
            <div class="bg-orange-500/10 border border-orange-500/30 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">Reembolsados</p>
                <p class="text-2xl font-bold text-orange-400">${cards.refunded.count}</p>
                <p class="text-sm text-orange-300">${formatCurrency(cards.refunded.amount)}</p>
            </div>`;
    }

    // Tabla: pagados pero no confirmados
    const paidTbody = document.getElementById('recon-paid-table');
    if (paidTbody) {
        if (!d.paid_unconfirmed.length) {
            paidTbody.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-500">Sin pedidos pendientes de confirmacion</td></tr>';
        } else {
            paidTbody.innerHTML = d.paid_unconfirmed.map(o => `
                <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                    <td class="px-4 py-3 font-medium text-amber-400">${o.order_number}</td>
                    <td class="px-4 py-3">${o.customer_name}</td>
                    <td class="px-4 py-3 font-medium">${formatCurrency(o.total)}</td>
                    <td class="px-4 py-3 text-xs font-mono">${o.payment_authorization || '-'}</td>
                    <td class="px-4 py-3 text-sm">${o.payment_paid_at ? formatDateShort(o.payment_paid_at) : '-'}</td>
                    <td class="px-4 py-3">
                        <button onclick="confirmPaidOrder(${o.id})" class="text-xs bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded-lg transition">Confirmar</button>
                    </td>
                </tr>`).join('');
        }
    }

    // Tabla: fallos recientes
    const failedTbody = document.getElementById('recon-failed-table');
    if (failedTbody) {
        if (!d.recent_failed.length) {
            failedTbody.innerHTML = '<tr><td colspan="5" class="text-center py-6 text-gray-500">Sin pagos fallidos recientes</td></tr>';
        } else {
            failedTbody.innerHTML = d.recent_failed.map(o => `
                <tr class="border-b border-gray-700 hover:bg-gray-700/30">
                    <td class="px-4 py-3 font-medium text-amber-400">${o.order_number}</td>
                    <td class="px-4 py-3">${o.customer_name}</td>
                    <td class="px-4 py-3 font-medium">${formatCurrency(o.total)}</td>
                    <td class="px-4 py-3 text-center"><span class="font-mono text-xs bg-red-900/40 text-red-300 px-2 py-0.5 rounded">${o.payment_response_code || '-'}</span></td>
                    <td class="px-4 py-3 text-sm">${formatDateShort(o.updated_at)}</td>
                </tr>`).join('');
        }
    }
}

async function confirmPaidOrder(id) {
    const ok = await showConfirm('Confirmar este pedido como recibido y procesado?');
    if (!ok) return;
    const res = await api(`/orders/${id}/status`, 'POST', { status: 'confirmed' });
    if (res?.success) {
        showNotification('Pedido confirmado', 'success');
        loadReconciliation();
    } else showNotification(res?.message || 'Error al confirmar pedido', 'error');
}
