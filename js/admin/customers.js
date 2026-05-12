// BBR Fragance - Admin: Clientes

// ==================== CLIENTES ====================
async function loadCustomers(page = 1) {
    currentCustomerPage = page;
    const search = document.getElementById('customer-search')?.value || '';
    let url = `/customers?page=${page}&limit=15`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    const res = await api(url);
    const tbody = document.getElementById('customers-table');
    if (!tbody) return;
    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No hay clientes registrados</td></tr>';
        return;
    }
    tbody.innerHTML = res.data.map(c => `
        <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
            <td class="px-4 py-3 font-medium">${c.name}</td>
            <td class="px-4 py-3 text-sm">${c.phone || '-'}</td>
            <td class="px-4 py-3 text-sm">${c.email || '-'}</td>
            <td class="px-4 py-3 font-medium text-amber-400">${formatCurrency(c.total_purchases)}</td>
            <td class="px-4 py-3 text-sm">${c.visit_count || 0}</td>
            <td class="px-4 py-3">
                <button onclick="viewCustomer(${c.id})" class="text-blue-400 hover:text-blue-300 mr-2" title="Ver"><i class="fas fa-eye"></i></button>
                <button onclick="openCustomerModal(${c.id})" class="text-amber-400 hover:text-amber-300" title="Editar"><i class="fas fa-edit"></i></button>
            </td>
        </tr>
    `).join('');
}

async function openCustomerModal(id = null) {
    const modal = document.getElementById('customer-modal');
    if (!modal) return;
    const title = modal.querySelector('.modal-title');
    if (title) title.textContent = id ? 'Editar Cliente' : 'Agregar Cliente';
    document.getElementById('customer-id').value = '';
    ['customer-name', 'customer-rnc', 'customer-cedula', 'customer-phone', 'customer-email', 'customer-address', 'customer-notes'].forEach(f => { const el = document.getElementById(f); if (el) el.value = ''; });
    if (id) {
        const res = await api('/customers/' + id);
        if (res?.success) {
            const c = res.data;
            document.getElementById('customer-id').value = c.id;
            document.getElementById('customer-name').value = c.name || '';
            document.getElementById('customer-rnc').value = c.rnc || '';
            document.getElementById('customer-cedula').value = c.cedula || '';
            document.getElementById('customer-phone').value = c.phone || '';
            document.getElementById('customer-email').value = c.email || '';
            document.getElementById('customer-address').value = c.address || '';
            document.getElementById('customer-notes').value = c.notes || '';
        }
    }
    modal.classList.remove('hidden');
}

async function saveCustomer() {
    const id = document.getElementById('customer-id')?.value;
    const body = {
        name: document.getElementById('customer-name')?.value,
        rnc: document.getElementById('customer-rnc')?.value,
        cedula: document.getElementById('customer-cedula')?.value,
        phone: document.getElementById('customer-phone')?.value,
        email: document.getElementById('customer-email')?.value,
        address: document.getElementById('customer-address')?.value,
        notes: document.getElementById('customer-notes')?.value
    };
    const res = id ? await api('/customers/' + id, 'PUT', body) : await api('/customers', 'POST', body);
    if (res?.success) {
        showNotification(id ? 'Cliente actualizado' : 'Cliente creado', 'success');
        document.getElementById('customer-modal')?.classList.add('hidden');
        loadCustomers(currentCustomerPage);
    } else showNotification(res?.message || 'Error al guardar cliente', 'error');
}

async function viewCustomer(id) {
    const res = await api('/customers/' + id);
    if (!res?.success) return;
    const c = res.data;
    const modal = document.getElementById('customer-detail-modal');
    if (!modal) return;
    setText('detail-customer-name', c.name);
    setText('detail-customer-phone', c.phone || '-');
    setText('detail-customer-email', c.email || '-');
    setText('detail-customer-address', c.address || 'No registrada');
    setText('detail-customer-purchases', formatCurrency(c.total_purchases));
    setText('detail-customer-visits', c.visit_count || 0);
    const historyEl = document.getElementById('detail-customer-history');
    if (historyEl) {
        historyEl.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">Historial no disponible</td></tr>';
    }
    modal.classList.remove('hidden');
}
