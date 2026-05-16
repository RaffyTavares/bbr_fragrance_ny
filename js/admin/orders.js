// BBR Fragrance - Admin: Pedidos

// ==================== PEDIDOS ====================
async function loadOrders(page = 1, status = null) {
    currentOrderPage = page;
    if (status !== null) currentOrderStatus = status;
    let url = `/orders?page=${page}&limit=15`;
    if (currentOrderStatus) url += `&status=${currentOrderStatus}`;
    const res = await api(url);
    const tbody = document.getElementById('orders-table');
    if (!tbody) return;
    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500">No hay pedidos</td></tr>';
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
            <td class="px-4 py-3"><button onclick="viewOrder(${o.id})" class="text-blue-400 hover:text-blue-300 transition" title="Ver detalle"><i class="fas fa-eye"></i></button></td>
        </tr>
    `).join('');
    renderPagination('orders-pagination', res.pagination, 'loadOrders');
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
    const statusSelect = document.getElementById('order-status-update');
    if (statusSelect) statusSelect.value = o.status;
    modal.dataset.orderId = o.id;
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
