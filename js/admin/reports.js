// BBR Fragance - Admin: Reportes

// ==================== REPORTES ====================
function initReportDates() {
    const today = new Date().toISOString().split('T')[0];
    const firstOfMonth = today.substring(0, 8) + '01';
    const fromEl = document.getElementById('report-date-from');
    const toEl = document.getElementById('report-date-to');
    if (fromEl && !fromEl.value) fromEl.value = firstOfMonth;
    if (toEl && !toEl.value) toEl.value = today;
    const activeTab = document.querySelector('.report-tab.active')?.dataset?.report || 'sales';
    loadReportTab(activeTab);
}

function setReportPreset(preset) {
    const today = new Date();
    let from, to = today.toISOString().split('T')[0];
    switch (preset) {
        case 'today': from = to; break;
        case 'week': { const d = new Date(today); d.setDate(d.getDate() - d.getDay()); from = d.toISOString().split('T')[0]; break; }
        case 'month': from = to.substring(0, 8) + '01'; break;
        case 'custom': return; // Allow manual date input
        default: return;
    }
    document.getElementById('report-date-from').value = from;
    document.getElementById('report-date-to').value = to;
    const activeTab = document.querySelector('.report-tab.active')?.dataset?.report || 'sales';
    loadReportTab(activeTab);
}

function loadReportTab(tab) {
    switch (tab) {
        case 'sales': loadSalesReport(); break;
        case 'products': loadProductsReport(); break;
        case 'expenses': loadExpensesReport(); break;
        case 'profit': loadProfitReport(); break;
    }
}

async function loadSalesReport() {
    const from = document.getElementById('report-date-from')?.value;
    const to = document.getElementById('report-date-to')?.value;
    const res = await api(`/reports/sales?date_from=${from}&date_to=${to}&group_by=date`);
    if (!res?.success) return;
    const summary = res.summary || res.data?.summary || {};
    const data = res.data?.data || res.data || [];
    setText('report-total-sales', formatCurrency(summary.total_revenue || 0));
    setText('report-sales-count', summary.total_sales_count || 0);
    setText('report-avg-sale', formatCurrency(summary.average_sale || 0));
    // Chart
    const ctx = document.getElementById('report-sales-chart');
    if (ctx && Array.isArray(data) && data.length) {
        if (reportSalesChart) reportSalesChart.destroy();
        reportSalesChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: data.map(d => formatDateShort(d.date)), datasets: [{ label: 'Ventas', data: data.map(d => parseFloat(d.total_sales) || 0), backgroundColor: 'rgba(201,169,110,0.6)', borderColor: '#C9A96E', borderWidth: 1 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9CA3AF' }, grid: { display: false } }, y: { ticks: { color: '#9CA3AF', callback: v => 'RD$' + v.toLocaleString() }, grid: { color: 'rgba(75,85,99,0.3)' } } } }
        });
    }
    const tbody = document.getElementById('report-sales-table');
    if (tbody && Array.isArray(data)) {
        tbody.innerHTML = data.map(d => `<tr class="border-b border-gray-700/50"><td class="py-2 text-sm">${formatDateShort(d.date)}</td><td class="py-2 text-sm text-center">${d.total_count || 0}</td><td class="py-2 text-sm text-right font-medium">${formatCurrency(d.total_sales)}</td></tr>`).join('');
    }
}

async function loadProductsReport() {
    const from = document.getElementById('report-date-from')?.value;
    const to = document.getElementById('report-date-to')?.value;
    const res = await api(`/reports/top-products?date_from=${from}&date_to=${to}&limit=20`);
    const tbody = document.getElementById('report-products-table');
    if (!tbody || !res?.success) return;
    const data = res.data?.by_quantity || res.data || [];
    if (!data.length) { tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500">Sin datos para este periodo</td></tr>'; return; }
    tbody.innerHTML = data.map((p, i) => `<tr class="border-b border-gray-700/50"><td class="py-2 text-sm text-amber-400 font-bold">${i + 1}</td><td class="py-2">${p.product_name} <span class="text-gray-400 text-xs">(${p.brand || ''})</span></td><td class="py-2 text-center">${p.total_quantity}</td><td class="py-2 text-right font-medium">${formatCurrency(p.total_revenue)}</td></tr>`).join('');
}

async function loadExpensesReport() {
    const from = document.getElementById('report-date-from')?.value;
    const to = document.getElementById('report-date-to')?.value;
    const res = await api(`/reports/expenses?date_from=${from}&date_to=${to}`);
    if (!res?.success) return;
    const d = res.data || {};
    setText('report-total-expenses', formatCurrency(d.overall?.total_amount || 0));
    const ctx = document.getElementById('report-expenses-chart');
    if (ctx && d.by_category?.length) {
        if (reportExpensesChart) reportExpensesChart.destroy();
        reportExpensesChart = new Chart(ctx, {
            type: 'doughnut',
            data: { labels: d.by_category.map(c => c.category_name), datasets: [{ data: d.by_category.map(c => parseFloat(c.total_amount)), backgroundColor: ['#EF4444', '#F59E0B', '#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#6B7280'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: '#9CA3AF' } } } }
        });
    }
    const tbody = document.getElementById('report-expenses-table');
    if (tbody && d.by_category) {
        tbody.innerHTML = d.by_category.map(c => `<tr class="border-b border-gray-700/50"><td class="py-2">${c.category_name}</td><td class="py-2 text-center">${c.count || c.total_count || 0}</td><td class="py-2 text-right font-medium">${formatCurrency(c.total_amount)}</td><td class="py-2 text-right text-sm text-gray-400">${c.percentage || 0}%</td></tr>`).join('');
    }
}

async function loadProfitReport() {
    const from = document.getElementById('report-date-from')?.value;
    const to = document.getElementById('report-date-to')?.value;
    const res = await api(`/reports/profit?date_from=${from}&date_to=${to}`);
    if (!res?.success) return;
    const d = res.data || {};
    setText('report-revenue', formatCurrency(d.total_revenue));
    setText('report-costs', formatCurrency(d.total_cost));
    setText('report-expenses-total', formatCurrency(d.total_expenses));
    const netEl = document.getElementById('report-net-profit');
    if (netEl) { netEl.textContent = formatCurrency(d.net_profit); netEl.className = 'text-2xl font-bold ' + (parseFloat(d.net_profit) >= 0 ? 'text-green-400' : 'text-red-400'); }
    const ctx = document.getElementById('report-profit-chart');
    if (ctx && d.daily_breakdown?.length) {
        if (reportProfitChart) reportProfitChart.destroy();
        reportProfitChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: d.daily_breakdown.map(db => formatDateShort(db.date)),
                datasets: [
                    { label: 'Ingresos', data: d.daily_breakdown.map(db => parseFloat(db.revenue) || 0), borderColor: '#10B981', backgroundColor: 'transparent', tension: 0.4 },
                    { label: 'Costos+Gastos', data: d.daily_breakdown.map(db => (parseFloat(db.cost) || 0) + (parseFloat(db.expenses) || 0)), borderColor: '#EF4444', backgroundColor: 'transparent', tension: 0.4 },
                    { label: 'Ganancia', data: d.daily_breakdown.map(db => parseFloat(db.profit) || 0), borderColor: '#C9A96E', backgroundColor: 'rgba(201,169,110,0.1)', fill: true, tension: 0.4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#9CA3AF' } } }, scales: { x: { ticks: { color: '#9CA3AF' }, grid: { display: false } }, y: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(75,85,99,0.3)' } } } }
        });
    }
}
