// BBR Fragance - Admin: Gastos

// ==================== GASTOS ====================
async function loadExpenseCategories() {
    const res = await api('/expense-categories');
    if (!res?.success) return;
    populateSelect('expense-filter-category', res.data, 'id', 'name', 'Todas las categorias');
    populateSelect('expense-category_id', res.data, 'id', 'name');
}

async function loadExpenses(page = 1) {
    currentExpensePage = page;
    let url = `/expenses?page=${page}&limit=15`;
    const cat = document.getElementById('expense-filter-category')?.value;
    const from = document.getElementById('expense-date-from')?.value;
    const to = document.getElementById('expense-date-to')?.value;
    if (cat) url += `&category_id=${cat}`;
    if (from) url += `&date_from=${from}`;
    if (to) url += `&date_to=${to}`;
    const res = await api(url);
    const tbody = document.getElementById('expenses-table');
    if (!tbody) return;
    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No hay gastos registrados</td></tr>';
        return;
    }
    tbody.innerHTML = res.data.map(e => `
        <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
            <td class="px-4 py-3 text-sm">${formatDateShort(e.expense_date)}</td>
            <td class="px-4 py-3">${e.description}</td>
            <td class="px-4 py-3 text-sm">${e.category_name || ''}</td>
            <td class="px-4 py-3 font-medium text-red-400">${formatCurrency(e.amount)}</td>
            <td class="px-4 py-3 text-sm">${getPaymentLabel(e.payment_method)}</td>
            <td class="px-4 py-3">
                <button onclick="openExpenseModal(${e.id})" class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                <button onclick="deleteExpense(${e.id})" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

async function openExpenseModal(id = null) {
    const modal = document.getElementById('expense-modal');
    if (!modal) return;
    const title = modal.querySelector('.modal-title');
    if (title) title.textContent = id ? 'Editar Gasto' : 'Agregar Gasto';
    document.getElementById('expense-id').value = '';
    document.getElementById('expense-description').value = '';
    document.getElementById('expense-amount').value = '';
    document.getElementById('expense-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('expense-payment_method').value = 'cash';
    const rn = document.getElementById('expense-receipt_number'); if (rn) rn.value = '';
    const notes = document.getElementById('expense-notes'); if (notes) notes.value = '';
    if (id) {
        const res = await api('/expenses/' + id);
        if (res?.success) {
            const e = res.data;
            document.getElementById('expense-id').value = e.id;
            document.getElementById('expense-category_id').value = e.expense_category_id;
            document.getElementById('expense-description').value = e.description;
            document.getElementById('expense-amount').value = e.amount;
            document.getElementById('expense-date').value = e.expense_date;
            document.getElementById('expense-payment_method').value = e.payment_method;
            if (rn) rn.value = e.receipt_number || '';
            if (notes) notes.value = e.notes || '';
        }
    }
    modal.classList.remove('hidden');
}

async function saveExpense() {
    const id = document.getElementById('expense-id')?.value;
    const body = {
        expense_category_id: document.getElementById('expense-category_id')?.value,
        description: document.getElementById('expense-description')?.value,
        amount: document.getElementById('expense-amount')?.value,
        expense_date: document.getElementById('expense-date')?.value,
        payment_method: document.getElementById('expense-payment_method')?.value,
        receipt_number: document.getElementById('expense-receipt_number')?.value || '',
        notes: document.getElementById('expense-notes')?.value || ''
    };
    const res = id ? await api('/expenses/' + id, 'PUT', body) : await api('/expenses', 'POST', body);
    if (res?.success) {
        showNotification(id ? 'Gasto actualizado' : 'Gasto registrado', 'success');
        document.getElementById('expense-modal')?.classList.add('hidden');
        loadExpenses(currentExpensePage);
    } else showNotification(res?.message || 'Error al guardar gasto', 'error');
}

async function deleteExpense(id) {
    const ok = await showConfirm('Eliminar este gasto?');
    if (!ok) return;
    const res = await api('/expenses/' + id, 'DELETE');
    if (res?.success) { showNotification('Gasto eliminado', 'success'); loadExpenses(currentExpensePage); }
    else showNotification(res?.message || 'Error al eliminar', 'error');
}
