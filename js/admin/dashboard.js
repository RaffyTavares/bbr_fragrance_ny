// BBR Fragance - Admin: Dashboard

// ==================== DASHBOARD ====================
async function loadDashboard() {
    const res = await api('/dashboard/stats');
    if (res?.success) {
        const d = res.data;
        setText('stat-today-sales', formatCurrency(d.today_sales));
        setText('stat-today-count', (d.today_sales_count || 0) + ' ventas');
        setText('stat-month-sales', formatCurrency(d.month_sales));
        setText('stat-month-count', (d.month_sales_count || 0) + ' ventas');
        setText('stat-pending-orders', d.pending_orders || 0);
        setText('stat-total-products', d.total_products || 0);
        setText('stat-low-stock', d.low_stock_count || 0);
        setText('stat-month-expenses', formatCurrency(d.month_expenses));
        // Notification badge
        const pendingOrders = d.pending_orders || 0;
        const lowStockCount = d.low_stock_count || 0;
        const totalNotif = pendingOrders + lowStockCount;
        const notif = document.getElementById('notification-count');
        if (notif) {
            notif.textContent = totalNotif;
            notif.style.display = totalNotif > 0 ? 'flex' : 'none';
        }
        // Populate notification panel
        buildNotificationPanel(pendingOrders, lowStockCount, d.low_stock_products || []);
    }
    loadSalesChart();
    loadRecentActivity();
    loadTopProducts();
}

function buildNotificationPanel(pendingOrders, lowStockCount, lowStockProducts) {
    const list = document.getElementById('notification-list');
    const summary = document.getElementById('notif-summary');
    if (!list) return;
    const total = pendingOrders + lowStockCount;
    if (summary) summary.textContent = total > 0 ? total + ' alerta' + (total > 1 ? 's' : '') : '';

    if (total === 0) {
        list.innerHTML = '<div class="px-4 py-6 text-center text-gray-500 text-sm"><i class="fas fa-check-circle text-xl mb-2 block text-green-500"></i>Todo en orden</div>';
        return;
    }

    let html = '';
    if (pendingOrders > 0) {
        html += `<div class="px-4 py-3 hover:bg-gray-700/50 cursor-pointer transition" onclick="document.getElementById('notification-panel').classList.add('hidden'); showSection('orders')">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-amber-500/20 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-clock text-amber-400 text-sm"></i></div>
                <div>
                    <p class="text-sm font-medium">${pendingOrders} pedido${pendingOrders > 1 ? 's' : ''} pendiente${pendingOrders > 1 ? 's' : ''}</p>
                    <p class="text-xs text-gray-400">Requieren atencion</p>
                </div>
            </div>
        </div>`;
    }
    if (lowStockCount > 0) {
        html += `<div class="px-4 py-3 hover:bg-gray-700/50 cursor-pointer transition" onclick="document.getElementById('notification-panel').classList.add('hidden'); showSection('products')">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center flex-shrink-0"><i class="fas fa-exclamation-triangle text-red-400 text-sm"></i></div>
                <div>
                    <p class="text-sm font-medium">${lowStockCount} producto${lowStockCount > 1 ? 's' : ''} con stock bajo</p>
                    <p class="text-xs text-gray-400">Revisar inventario</p>
                </div>
            </div>
        </div>`;
        if (lowStockProducts.length) {
            lowStockProducts.slice(0, 5).forEach(p => {
                html += `<div class="px-4 py-2 bg-gray-900/30">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-300 truncate">${p.name || p.product_name}</span>
                        <span class="text-xs text-red-400 font-bold ml-2 flex-shrink-0">Stock: ${p.stock}</span>
                    </div>
                </div>`;
            });
        }
    }
    list.innerHTML = html;
}

async function loadSalesChart() {
    const res = await api('/dashboard/sales-chart?days=7');
    if (!res?.success || !res.data) return;
    const labels = res.data.map(d => { const dt = new Date(d.date); return dt.toLocaleDateString('es-DO', { weekday: 'short', day: 'numeric' }); });
    const values = res.data.map(d => parseFloat(d.total) || 0);
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    if (salesChart) salesChart.destroy();
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Ventas (RD$)', data: values,
                borderColor: '#C9A96E', backgroundColor: 'rgba(201,169,110,0.1)',
                fill: true, tension: 0.4, pointBackgroundColor: '#C9A96E', pointBorderWidth: 2, pointRadius: 5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(75,85,99,0.3)' } },
                y: { ticks: { color: '#9CA3AF', callback: v => 'RD$' + v.toLocaleString() }, grid: { color: 'rgba(75,85,99,0.3)' } }
            }
        }
    });
}

async function loadRecentActivity() {
    const res = await api('/dashboard/recent-activity');
    const el = document.getElementById('recent-activity-list');
    if (!el) return;
    if (!res?.success || !res.data?.length) { el.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Sin actividad reciente</p>'; return; }
    el.innerHTML = res.data.map(a => `
        <div class="flex items-start space-x-3 py-2 border-b border-gray-700/50 last:border-0">
            <div class="w-8 h-8 bg-amber-500/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="fas fa-circle text-xs text-amber-400"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm truncate">${a.description || a.action}</p>
                <p class="text-xs text-gray-500">${formatDate(a.created_at)}</p>
            </div>
        </div>
    `).join('');
}

async function loadTopProducts() {
    const res = await api('/dashboard/top-products');
    const el = document.getElementById('top-products-list');
    if (!el) return;
    if (!res?.success || !res.data?.length) { el.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Sin datos de ventas aun</p>'; return; }
    el.innerHTML = res.data.map((p, i) => `
        <div class="flex items-center justify-between py-2 border-b border-gray-700/50 last:border-0">
            <div class="flex items-center space-x-3">
                <span class="w-6 h-6 bg-amber-500/20 rounded-full flex items-center justify-center text-amber-400 text-xs font-bold">${i + 1}</span>
                <div><p class="text-sm font-medium">${p.product_name}</p><p class="text-xs text-gray-500">${p.brand || ''}</p></div>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold text-amber-400">${p.total_quantity || 0} uds</p>
                <p class="text-xs text-gray-500">${formatCurrency(p.total_revenue)}</p>
            </div>
        </div>
    `).join('');
}
