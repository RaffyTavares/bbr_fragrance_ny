// BBR Fragance - Admin: Productos e Inventario

// ==================== PRODUCTOS ====================
let filterOptions = { categories: [], families: [], brands: [] };

async function loadFilterOptions() {
    const [cats, fams, brands] = await Promise.all([api('/categories'), api('/families'), api('/brands')]);
    if (cats?.success) filterOptions.categories = cats.data;
    if (fams?.success) filterOptions.families = fams.data;
    if (brands?.success) filterOptions.brands = brands.data;
    populateSelect('filter-category', filterOptions.categories, 'slug', 'name', 'Todas las categorias');
    populateSelect('filter-family', filterOptions.families, 'slug', 'name', 'Todas las familias');
    populateSelect('product-brand_id', filterOptions.brands, 'id', 'name');
    populateSelect('product-category_id', filterOptions.categories, 'id', 'name');
    populateSelect('product-family_id', filterOptions.families, 'id', 'name');
}

let productsViewGrid = false;

function toggleProductsView() {
    productsViewGrid = !productsViewGrid;
    const btn = document.getElementById('products-view-toggle');
    if (btn) btn.innerHTML = productsViewGrid ? '<i class="fas fa-list"></i>' : '<i class="fas fa-th"></i>';
    loadProducts(currentProductPage);
}

async function loadProducts(page = 1) {
    currentProductPage = page;
    const search = document.getElementById('admin-product-search')?.value || '';
    const category = document.getElementById('filter-category')?.value || '';
    const family = document.getElementById('filter-family')?.value || '';
    const status = document.getElementById('filter-status')?.value || '';
    const featured = document.getElementById('filter-featured')?.value ?? '';
    let url = `/products?page=${page}&limit=15`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (category) url += `&category=${category}`;
    if (family) url += `&family=${family}`;
    if (status) url += `&status=${status}`;
    if (featured !== '') url += `&featured=${featured}`;
    const res = await api(url);
    const tbody = document.getElementById('products-table');
    const tableWrapper = document.getElementById('products-table-wrapper');
    const gridWrapper = document.getElementById('products-grid-wrapper');

    if (productsViewGrid) {
        if (tableWrapper) tableWrapper.classList.add('hidden');
        if (gridWrapper) gridWrapper.classList.remove('hidden');
    } else {
        if (tableWrapper) tableWrapper.classList.remove('hidden');
        if (gridWrapper) gridWrapper.classList.add('hidden');
    }

    if (!res?.success || !res.data?.length) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-gray-500">No se encontraron productos</td></tr>';
        if (gridWrapper) gridWrapper.innerHTML = '<div class="col-span-full text-center text-gray-500 py-12"><i class="fas fa-box-open text-3xl mb-3 block"></i><p class="text-sm">No se encontraron productos</p></div>';
        renderPagination('products-pagination', null, 'loadProducts');
        return;
    }

    if (tbody) {
        tbody.innerHTML = res.data.map(p => `
            <tr class="border-b border-gray-700 hover:bg-gray-700/30 transition">
                <td class="px-4 py-3 text-sm text-gray-400">${p.id}</td>
                <td class="px-4 py-3">
                    ${p.image_url ? `<img src="${p.image_url}" class="w-12 h-12 rounded-lg object-cover">` : '<div class="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center"><i class="fas fa-image text-gray-500"></i></div>'}
                </td>
                <td class="px-4 py-3"><p class="font-medium">${p.name}</p><p class="text-xs text-gray-400">${p.brand_name || ''}</p></td>
                <td class="px-4 py-3 text-sm">${p.category_name || ''}</td>
                <td class="px-4 py-3 text-sm">${p.family_name || ''}</td>
                <td class="px-4 py-3 text-sm font-medium text-amber-400">${formatCurrency(p.price)}</td>
                <td class="px-4 py-3 text-sm ${p.stock <= (p.min_stock || 5) ? 'text-red-400 font-bold' : ''}">${p.stock}</td>
                <td class="px-4 py-3">${getStatusBadge(p.status)}</td>
                <td class="px-4 py-3">
                    <div class="flex space-x-2">
                        <button onclick="openProductModal(${p.id})" class="text-blue-400 hover:text-blue-300 transition" title="Editar"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteProduct(${p.id})" class="text-red-400 hover:text-red-300 transition" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    if (gridWrapper) {
        gridWrapper.innerHTML = res.data.map(p => {
            const lowStock = p.stock <= (p.min_stock || 5);
            return `
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden hover:border-amber-500/50 transition group">
                <div class="aspect-square bg-gray-700 overflow-hidden">
                    ${p.image_url ? `<img src="${p.image_url}" class="w-full h-full object-cover group-hover:scale-105 transition">` : '<div class="w-full h-full flex items-center justify-center"><i class="fas fa-spray-can text-gray-500 text-3xl"></i></div>'}
                </div>
                <div class="p-3">
                    <p class="font-medium text-sm truncate">${p.name}</p>
                    <p class="text-xs text-gray-400 truncate">${p.brand_name || ''} ${p.category_name ? '· ' + p.category_name : ''}</p>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-amber-400 font-bold text-sm">${formatCurrency(p.price)}</span>
                        <span class="text-xs ${lowStock ? 'text-red-400 font-bold' : 'text-gray-500'}">Stock: ${p.stock}</span>
                    </div>
                    <div class="mt-2">${getStatusBadge(p.status)}</div>
                    <div class="flex gap-2 mt-2">
                        <button onclick="openProductModal(${p.id})" class="flex-1 bg-gray-700 hover:bg-gray-600 text-blue-400 py-1.5 rounded-lg text-xs transition"><i class="fas fa-edit mr-1"></i>Editar</button>
                        <button onclick="deleteProduct(${p.id})" class="bg-gray-700 hover:bg-gray-600 text-red-400 px-3 py-1.5 rounded-lg text-xs transition"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    renderPagination('products-pagination', res.pagination, 'loadProducts');
}

async function openProductModal(id = null) {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('modal-title');
    if (!modal) return;
    // Ensure filter options (brands, categories, families) are loaded
    if (!filterOptions.brands.length || !filterOptions.categories.length) {
        await loadFilterOptions();
    }
    // Reset form
    document.getElementById('product-id').value = '';
    ['name', 'price', 'original_price', 'cost', 'stock', 'min_stock', 'barcode', 'sku', 'description'].forEach(f => {
        const el = document.getElementById('product-' + f);
        if (el) el.value = '';
    });
    document.getElementById('product-status').value = 'active';
    const feat = document.getElementById('product-is_featured');
    if (feat) feat.checked = false;
    const preview = document.getElementById('image-preview');
    if (preview) preview.innerHTML = '';
    const imageInput = document.getElementById('product-image');
    if (imageInput) imageInput.value = '';

    if (id) {
        title.textContent = 'Editar Producto';
        const res = await api('/products/' + id);
        if (res?.success) {
            const p = res.data;
            document.getElementById('product-id').value = p.id;
            ['name', 'price', 'original_price', 'cost', 'stock', 'min_stock', 'barcode', 'sku', 'description'].forEach(f => {
                const el = document.getElementById('product-' + f);
                if (el && p[f] != null) el.value = p[f];
            });
            ['brand_id', 'category_id', 'family_id', 'status'].forEach(f => {
                const el = document.getElementById('product-' + f);
                if (el) el.value = p[f];
            });
            if (feat) feat.checked = p.is_featured == 1;
            if (preview && p.images?.length) {
                preview.innerHTML = p.images.map(img => `<img src="${img.url}" class="w-20 h-20 rounded-lg object-cover border border-gray-600">`).join('');
            }
        }
    } else {
        title.textContent = 'Agregar Producto';
        document.getElementById('product-min_stock').value = '5';
    }
    modal.classList.remove('hidden');
}

async function saveProduct() {
    const id = document.getElementById('product-id')?.value;
    const form = new FormData();
    ['name', 'brand_id', 'category_id', 'family_id', 'price', 'original_price', 'cost', 'stock', 'min_stock', 'barcode', 'sku', 'description', 'status'].forEach(f => {
        const el = document.getElementById('product-' + f);
        if (el && el.value !== '') form.append(f, el.value);
    });
    form.append('is_featured', document.getElementById('product-is_featured')?.checked ? '1' : '0');
    const imageInput = document.getElementById('product-image');
    if (imageInput?.files[0]) form.append('image', imageInput.files[0]);
    const url = id ? '/products/' + id : '/products';
    const res = await api(url, 'POST', form);
    if (res?.success) {
        showNotification(id ? 'Producto actualizado' : 'Producto creado', 'success');
        document.getElementById('product-modal')?.classList.add('hidden');
        loadProducts(currentProductPage);
    } else {
        showNotification(res?.message || 'Error al guardar producto', 'error');
    }
}

async function deleteProduct(id) {
    const ok = await showConfirm('Estas seguro de eliminar este producto?');
    if (!ok) return;
    const res = await api('/products/' + id, 'DELETE');
    if (res?.success) { showNotification('Producto eliminado', 'success'); loadProducts(currentProductPage); }
    else showNotification(res?.message || 'Error al eliminar', 'error');
}

// ==================== INVENTARIO ====================
let currentInventoryPage = 1;
let currentInventoryFilter = 'all';

async function loadInventory(page = 1) {
    currentInventoryPage = page;
    const search = document.getElementById('inv-search')?.value?.trim() || '';
    let url;

    if (currentInventoryFilter === 'low') {
        url = '/products/low-stock';
    } else {
        url = `/products?page=${page}&limit=20&status=active`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
    }

    const res = await api(url);
    let products = [];
    let pagination = null;

    if (res?.success) {
        products = Array.isArray(res.data) ? res.data : [];
        pagination = res.pagination || null;
    }

    // Apply client-side filters for "out" and search on low-stock
    if (currentInventoryFilter === 'out') {
        products = products.filter(p => parseInt(p.stock) === 0);
    } else if (currentInventoryFilter === 'low') {
        if (search) {
            const q = search.toLowerCase();
            products = products.filter(p =>
                (p.name || '').toLowerCase().includes(q) ||
                (p.sku || '').toLowerCase().includes(q) ||
                (p.barcode || '').toLowerCase().includes(q)
            );
        }
    }

    // Calculate summary KPIs
    updateInventoryKPIs(products, currentInventoryFilter);

    // Render inventory table
    const tbody = document.getElementById('inventory-table');
    if (!tbody) return;

    if (!products.length) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-gray-500">
            <i class="fas fa-box-open text-3xl mb-2 block"></i>
            No se encontraron productos${currentInventoryFilter !== 'all' ? ' con este filtro' : ''}</td></tr>`;
        renderPagination('inventory-pagination', null, 'loadInventory');
        return;
    }

    tbody.innerHTML = products.map(p => {
        const stock = parseInt(p.stock) || 0;
        const minStock = parseInt(p.min_stock) || 5;
        const cost = parseFloat(p.cost) || 0;
        const invValue = stock * cost;
        const isLow = stock > 0 && stock <= minStock;
        const isOut = stock === 0;

        let stockClass = 'text-green-400';
        let stockIcon = 'fa-check-circle';
        if (isOut) { stockClass = 'text-red-400'; stockIcon = 'fa-times-circle'; }
        else if (isLow) { stockClass = 'text-amber-400'; stockIcon = 'fa-exclamation-triangle'; }

        return `<tr class="border-b border-gray-700 hover:bg-gray-700/30 transition" id="inv-row-${p.id}">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-700 flex-shrink-0">
                        ${p.image_url ? `<img src="${p.image_url}" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center"><i class="fas fa-spray-can text-gray-500 text-sm"></i></div>'}
                    </div>
                    <div>
                        <p class="font-medium text-sm">${p.name}</p>
                        <p class="text-xs text-gray-400">${p.brand_name || ''}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-400 font-mono">${p.sku || '-'}</td>
            <td class="px-4 py-3 text-sm text-gray-400 font-mono">${p.barcode || '-'}</td>
            <td class="px-4 py-3 text-center">
                <span class="${stockClass} font-bold text-sm"><i class="fas ${stockIcon} mr-1"></i>${stock}</span>
            </td>
            <td class="px-4 py-3 text-center text-sm text-gray-400">${minStock}</td>
            <td class="px-4 py-3 text-right text-sm">${cost > 0 ? formatCurrency(cost) : '<span class="text-gray-500">-</span>'}</td>
            <td class="px-4 py-3 text-right text-sm font-medium">${cost > 0 ? formatCurrency(invValue) : '<span class="text-gray-500">-</span>'}</td>
            <td class="px-4 py-3 text-center">${isOut ? '<span class="bg-red-500/20 text-red-400 px-2 py-1 rounded-full text-xs">Agotado</span>' : isLow ? '<span class="bg-amber-500/20 text-amber-400 px-2 py-1 rounded-full text-xs">Bajo</span>' : '<span class="bg-green-500/20 text-green-400 px-2 py-1 rounded-full text-xs">OK</span>'}</td>
            <td class="px-4 py-3 text-center">
                <div class="flex items-center justify-center gap-1">
                    <input type="number" min="0" value="${stock}" id="inv-stock-${p.id}" class="w-16 bg-gray-900 border border-gray-700 rounded px-2 py-1 text-sm text-center focus:outline-none focus:border-amber-500">
                    <button onclick="quickUpdateStock(${p.id})" class="text-amber-400 hover:text-amber-300 px-2 py-1 transition" title="Guardar"><i class="fas fa-save"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');

    if (currentInventoryFilter === 'all' && pagination) {
        renderPagination('inventory-pagination', pagination, 'loadInventory');
    } else {
        renderPagination('inventory-pagination', null, 'loadInventory');
    }
}

async function updateInventoryKPIs(currentProducts, filter) {
    // If filter is 'all' and we have data, use it to compute KPIs
    // Otherwise load a broader dataset for accurate KPIs
    let allProducts = currentProducts;

    if (filter !== 'all' || !currentProducts.length) {
        const res = await api('/products?limit=500&status=active');
        if (res?.success) allProducts = res.data || [];
    }

    let totalActive = 0, lowStock = 0, outOfStock = 0, totalValue = 0;

    allProducts.forEach(p => {
        const stock = parseInt(p.stock) || 0;
        const minStock = parseInt(p.min_stock) || 5;
        const cost = parseFloat(p.cost) || 0;
        totalActive++;
        if (stock === 0) outOfStock++;
        else if (stock <= minStock) lowStock++;
        totalValue += stock * cost;
    });

    setText('inv-total-products', totalActive);
    setText('inv-low-stock', lowStock);
    setText('inv-out-of-stock', outOfStock);
    setText('inv-total-value', formatCurrency(totalValue));
}

async function quickUpdateStock(productId) {
    const input = document.getElementById(`inv-stock-${productId}`);
    if (!input) return;
    const newStock = parseInt(input.value);
    if (isNaN(newStock) || newStock < 0) {
        showNotification('El stock debe ser un numero mayor o igual a 0', 'error');
        return;
    }
    const res = await api(`/products/${productId}/stock`, 'PUT', { stock: newStock });
    if (res?.success) {
        showNotification('Stock actualizado', 'success');
        loadInventory(currentInventoryPage);
    } else {
        showNotification(res?.message || 'Error al actualizar stock', 'error');
    }
}
