// BBR Fragrance - Admin: Promociones, Marcas, Configuracion, NCF, Usuarios, Permisos por Rol

// ==================== PROMOCIONES ====================
async function loadPromotions() {
    loadPromoMonthSettings();
    loadOffersTable();
}

async function loadPromoMonthSettings() {
    const res = await api('/settings');
    if (!res?.success) return;
    const s = res.data || {};

    // Valores por defecto (los mismos del index.html)
    const defaults = {
        promo_active: '1',
        promo_title: 'Promocion del Mes',
        promo_subtitle: 'Hasta 30% de descuento en perfumes seleccionados + envio gratis en compras mayores a $100',
        promo_link: 'pages/productos.html?offers=1',
        promo_bullets: '["Combos 2x1 en fragancias seleccionadas","Regalo sorpresa en compras mayores a $200","Muestras gratis con cada pedido"]'
    };

    const activeToggle = document.getElementById('promo-active-toggle');
    if (activeToggle) activeToggle.checked = (s.promo_active ?? defaults.promo_active) !== '0';

    const titleEl = document.getElementById('promo-month-title');
    if (titleEl) titleEl.value = s.promo_title || defaults.promo_title;

    const subtitleEl = document.getElementById('promo-month-subtitle');
    if (subtitleEl) {
        subtitleEl.value = s.promo_subtitle || defaults.promo_subtitle;
        autoResizeTextarea(subtitleEl);
    }

    const linkEl = document.getElementById('promo-month-link');
    if (linkEl) linkEl.value = s.promo_link || defaults.promo_link;

    // Parse bullets
    let bulletsStr = s.promo_bullets || defaults.promo_bullets;
    // Fix HTML-escaped quotes from legacy data
    bulletsStr = bulletsStr.replace(/&quot;/g, '"').replace(/&amp;/g, '&');
    try {
        const bullets = JSON.parse(bulletsStr);
        if (Array.isArray(bullets)) {
            for (let i = 0; i < 3; i++) {
                const el = document.getElementById('promo-bullet-' + (i + 1));
                if (el) {
                    el.value = bullets[i] || '';
                    autoResizeTextarea(el);
                }
            }
        }
    } catch (e) { /* ignore */ }

    // Promo media preview (image or video)
    const preview = document.getElementById('promo-image-preview');
    if (preview && s.promo_image) {
        const isVideo = s.promo_media_type === 'video' || /\.(mp4|webm|mov)$/i.test(s.promo_image);
        if (isVideo) {
            preview.innerHTML = `<video src="${s.promo_image}" class="w-full h-full object-cover" muted autoplay loop playsinline></video>`;
        } else {
            preview.innerHTML = `<img src="${s.promo_image}" class="w-full h-full object-cover">`;
        }
    }
}

async function savePromoMonth() {
    const btn = document.getElementById('save-promo-month-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...'; }

    const bullets = [];
    for (let i = 1; i <= 3; i++) {
        const val = document.getElementById('promo-bullet-' + i)?.value?.trim();
        if (val) bullets.push(val);
    }

    const settings = {
        promo_active: document.getElementById('promo-active-toggle')?.checked ? '1' : '0',
        promo_title: document.getElementById('promo-month-title')?.value?.trim() || '',
        promo_subtitle: document.getElementById('promo-month-subtitle')?.value?.trim() || '',
        promo_link: document.getElementById('promo-month-link')?.value?.trim() || '',
        promo_bullets: JSON.stringify(bullets)
    };

    const res = await api('/settings', 'POST', settings);
    if (res?.success) {
        showNotification('Promocion del mes actualizada', 'success');
    } else {
        showNotification(res?.message || 'Error al guardar promocion', 'error');
    }

    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save mr-2"></i>Guardar Promocion'; }
}

async function uploadPromoImage() {
    const input = document.getElementById('promo-image-input');
    if (!input?.files[0]) return;

    const file = input.files[0];
    const isVideo = file.type.startsWith('video/');
    const maxSize = isVideo ? 25 * 1024 * 1024 : 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showNotification(`El archivo excede el tamano maximo (${isVideo ? '25MB' : '5MB'})`, 'error');
        input.value = '';
        return;
    }

    const form = new FormData();
    form.append('media', file);

    const preview = document.getElementById('promo-image-preview');
    if (preview) preview.innerHTML = '<i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>';

    const res = await api('/settings/promo-image', 'POST', form);
    if (res?.success) {
        showNotification('Medio de promocion actualizado', 'success');
        if (preview) {
            const type = res.data?.type || (isVideo ? 'video' : 'image');
            const url = res.data.url;
            if (type === 'video') {
                preview.innerHTML = `<video src="${url}" class="w-full h-full object-cover" muted autoplay loop playsinline></video>`;
            } else {
                preview.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
            }
        }
    } else {
        showNotification(res?.message || 'Error al subir el archivo', 'error');
        if (preview) preview.innerHTML = '<i class="fas fa-image text-3xl text-gray-500"></i>';
    }

    input.value = '';
}

async function loadOffersTable() {
    const tbody = document.getElementById('offers-table');
    if (!tbody) return;

    const res = await api('/products?offers=1&per_page=200');
    if (!res?.success || !res.data) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No se pudieron cargar los productos</td></tr>';
        return;
    }

    const offers = res.data;

    if (offers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">
            <i class="fas fa-tag text-3xl mb-3 block text-gray-600"></i>
            No hay productos en oferta. Usa "Agregar Oferta" para crear una.
        </td></tr>`;
        return;
    }

    tbody.innerHTML = offers.map(p => {
        const orig = parseFloat(p.original_price) || 0;
        const price = parseFloat(p.price) || 0;
        const discount = Math.round((1 - price / orig) * 100);
        const isFeatured = p.is_featured == 1;
        const brandName = p.brand_name || '';

        return `<tr class="hover:bg-gray-700/30 transition">
            <td class="px-4 py-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                        ${p.image_url ? `<img src="${p.image_url}" class="w-full h-full object-cover">` : '<i class="fas fa-wine-bottle text-gray-500 text-sm"></i>'}
                    </div>
                    <div>
                        <p class="font-medium text-sm">${p.name}</p>
                        <p class="text-xs text-gray-400">${brandName}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-right text-sm text-gray-400">${formatCurrency(orig)}</td>
            <td class="px-4 py-3 text-right text-sm font-semibold text-amber-400">${formatCurrency(price)}</td>
            <td class="px-4 py-3 text-center">
                <span class="bg-red-500/20 text-red-400 px-2.5 py-1 rounded-full text-xs font-bold">-${discount}%</span>
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="toggleOfferFeatured(${p.id}, ${isFeatured ? 0 : 1})" class="text-lg ${isFeatured ? 'text-amber-400' : 'text-gray-600 hover:text-amber-400'} transition" title="${isFeatured ? 'Quitar destacado' : 'Marcar como destacado'}">
                    <i class="fas fa-star"></i>
                </button>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
                <button onclick="editOffer(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${orig}, ${price})" class="text-amber-400 hover:text-amber-300 transition text-sm" title="Editar oferta">
                    <i class="fas fa-edit mr-1"></i>Editar
                </button>
                <button onclick="removeOffer(${p.id})" class="text-red-400 hover:text-red-300 transition text-sm" title="Quitar oferta">
                    <i class="fas fa-times-circle mr-1"></i>Quitar
                </button>
            </td>
        </tr>`;
    }).join('');
}

function editOffer(productId, productName, originalPrice, currentPrice) {
    const modal = document.getElementById('offerModal');
    if (!modal) return;

    // Pre-populate the modal with existing data
    document.getElementById('offer-product-search').value = '';
    document.getElementById('offer-product-results')?.classList.add('hidden');
    document.getElementById('offer-product-id').value = productId;
    document.getElementById('offer-product-name').textContent = productName;
    document.getElementById('offer-product-current-price').textContent = 'Precio actual: ' + formatCurrency(currentPrice);
    document.getElementById('offer-selected-product')?.classList.remove('hidden');
    document.getElementById('offer-original-price').value = originalPrice;

    // Update discount preview
    updateOfferDiscountPreview();

    modal.classList.remove('hidden');
}

async function removeOffer(productId) {
    const confirmed = await showConfirm('Quitar la oferta de este producto? Se eliminara el precio original.');
    if (!confirmed) return;

    const res = await api(`/products/${productId}`, 'PUT', { original_price: null });
    if (res?.success) {
        showNotification('Oferta eliminada', 'success');
        loadOffersTable();
    } else {
        showNotification(res?.message || 'Error al quitar oferta', 'error');
    }
}

async function toggleOfferFeatured(productId, newValue) {
    const res = await api(`/products/${productId}`, 'PUT', { is_featured: newValue });
    if (res?.success) {
        showNotification(newValue ? 'Producto marcado como destacado' : 'Producto ya no es destacado', 'success');
        loadOffersTable();
    } else {
        showNotification(res?.message || 'Error al actualizar', 'error');
    }
}

function openOfferModal() {
    const modal = document.getElementById('offerModal');
    if (!modal) return;
    document.getElementById('offer-product-search').value = '';
    document.getElementById('offer-product-id').value = '';
    document.getElementById('offer-original-price').value = '';
    document.getElementById('offer-product-results')?.classList.add('hidden');
    document.getElementById('offer-selected-product')?.classList.add('hidden');
    document.getElementById('offer-discount-preview')?.classList.add('hidden');
    modal.classList.remove('hidden');
    setTimeout(() => document.getElementById('offer-product-search')?.focus(), 100);
}

function closeOfferModal() {
    document.getElementById('offerModal')?.classList.add('hidden');
}

function clearOfferProduct() {
    document.getElementById('offer-product-id').value = '';
    document.getElementById('offer-selected-product')?.classList.add('hidden');
    document.getElementById('offer-original-price').value = '';
    document.getElementById('offer-discount-preview')?.classList.add('hidden');
    document.getElementById('offer-product-search').value = '';
    document.getElementById('offer-product-search')?.focus();
}

async function searchOfferProducts() {
    const query = document.getElementById('offer-product-search')?.value?.trim();
    const resultsDiv = document.getElementById('offer-product-results');
    if (!resultsDiv) return;

    if (!query || query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }

    const res = await api(`/products?search=${encodeURIComponent(query)}&per_page=10`);
    if (!res?.success || !res.data?.length) {
        resultsDiv.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">No se encontraron productos</div>';
        resultsDiv.classList.remove('hidden');
        return;
    }

    // Filter out products that already have an offer
    const available = res.data.filter(p => {
        const orig = parseFloat(p.original_price) || 0;
        const price = parseFloat(p.price) || 0;
        return !(orig > 0 && orig > price);
    });

    if (available.length === 0) {
        resultsDiv.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Todos los productos encontrados ya tienen oferta</div>';
        resultsDiv.classList.remove('hidden');
        return;
    }

    resultsDiv.innerHTML = available.map(p => `
        <div class="px-4 py-2.5 hover:bg-gray-700 cursor-pointer transition text-sm border-b border-gray-700/50 last:border-b-0"
             onclick="selectOfferProduct(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
            <p class="font-medium">${p.name}</p>
            <p class="text-xs text-gray-400">${p.brand_name || ''} - Precio actual: ${formatCurrency(p.price)}</p>
        </div>
    `).join('');
    resultsDiv.classList.remove('hidden');
}

function selectOfferProduct(id, name, price) {
    document.getElementById('offer-product-id').value = id;
    document.getElementById('offer-product-name').textContent = name;
    document.getElementById('offer-product-current-price').textContent = 'Precio actual: ' + formatCurrency(price);
    document.getElementById('offer-selected-product')?.classList.remove('hidden');
    document.getElementById('offer-product-results')?.classList.add('hidden');
    document.getElementById('offer-product-search').value = '';
    document.getElementById('offer-original-price')?.focus();
}

function updateOfferDiscountPreview() {
    const preview = document.getElementById('offer-discount-preview');
    const origPrice = parseFloat(document.getElementById('offer-original-price')?.value) || 0;
    const productId = document.getElementById('offer-product-id')?.value;
    const currentPriceText = document.getElementById('offer-product-current-price')?.textContent || '';

    if (!preview || !productId || origPrice <= 0) {
        if (preview) preview.classList.add('hidden');
        return;
    }

    // Extract current price from text
    const match = currentPriceText.match(/[\d,.]+/);
    const currentPrice = match ? parseFloat(match[0].replace(/,/g, '')) : 0;

    if (currentPrice > 0 && origPrice > currentPrice) {
        const discount = Math.round((1 - currentPrice / origPrice) * 100);
        preview.textContent = `Descuento: ${discount}% (de ${formatCurrency(origPrice)} a ${formatCurrency(currentPrice)})`;
        preview.classList.remove('hidden');
        preview.classList.remove('text-red-400');
        preview.classList.add('text-green-400');
    } else if (origPrice > 0 && origPrice <= currentPrice) {
        preview.textContent = 'El precio original debe ser mayor al precio actual del producto';
        preview.classList.remove('hidden');
        preview.classList.remove('text-green-400');
        preview.classList.add('text-red-400');
    } else {
        preview.classList.add('hidden');
    }
}

async function saveOffer() {
    const productId = document.getElementById('offer-product-id')?.value;
    const originalPrice = parseFloat(document.getElementById('offer-original-price')?.value) || 0;

    if (!productId) {
        showNotification('Selecciona un producto', 'error');
        return;
    }
    if (originalPrice <= 0) {
        showNotification('Ingresa un precio original valido', 'error');
        return;
    }

    const res = await api(`/products/${productId}`, 'PUT', { original_price: originalPrice });
    if (res?.success) {
        showNotification('Oferta aplicada exitosamente', 'success');
        closeOfferModal();
        loadOffersTable();
    } else {
        showNotification(res?.message || 'Error al aplicar oferta', 'error');
    }
}

// ==================== MARCAS ====================
async function loadBrands() {
    const tbody = document.getElementById('brands-table');
    if (!tbody) return;

    const res = await api('/brands/all');
    if (!res?.success || !res.data) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No se pudieron cargar las marcas</td></tr>';
        return;
    }

    const brands = res.data;
    if (brands.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">
            <i class="fas fa-tag text-3xl mb-3 block text-gray-600"></i>
            No hay marcas registradas. Crea una nueva marca para comenzar.
        </td></tr>`;
        return;
    }

    tbody.innerHTML = brands.map(b => {
        const active = b.is_active == 1;
        return `<tr class="hover:bg-gray-700/30 transition">
            <td class="px-4 py-3 font-medium">${b.name}</td>
            <td class="px-4 py-3 text-sm text-gray-400">${b.slug}</td>
            <td class="px-4 py-3 text-center">
                <span class="bg-gray-700 px-2.5 py-1 rounded-full text-xs font-semibold">${b.product_count || 0}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="toggleBrand(${b.id})" class="px-3 py-1 rounded-full text-xs font-bold transition cursor-pointer ${active ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30' : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'}">
                    ${active ? 'Activa' : 'Inactiva'}
                </button>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
                <button onclick="openBrandModal(${b.id}, '${b.name.replace(/'/g, "\\'")}', ${b.is_active})" class="text-amber-400 hover:text-amber-300 transition" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteBrand(${b.id}, '${b.name.replace(/'/g, "\\'")}')" class="text-red-400 hover:text-red-300 transition" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function openBrandModal(id = null, name = '', isActive = 1) {
    const modal = document.getElementById('brandModal');
    const title = document.getElementById('brand-modal-title');
    const idField = document.getElementById('brand-id');
    const nameField = document.getElementById('brand-name');
    const activeField = document.getElementById('brand-is-active');

    if (!modal) return;

    if (id) {
        title.textContent = 'Editar Marca';
        idField.value = id;
        nameField.value = name;
        activeField.checked = isActive == 1;
    } else {
        title.textContent = 'Nueva Marca';
        idField.value = '';
        nameField.value = '';
        activeField.checked = true;
    }

    modal.classList.remove('hidden');
    setTimeout(() => nameField.focus(), 100);
}

function closeBrandModal() {
    const modal = document.getElementById('brandModal');
    if (modal) modal.classList.add('hidden');
}

async function saveBrand(e) {
    if (e) e.preventDefault();

    const id = document.getElementById('brand-id')?.value;
    const name = document.getElementById('brand-name')?.value?.trim();
    const isActive = document.getElementById('brand-is-active')?.checked ? 1 : 0;

    if (!name) {
        showNotification('El nombre de la marca es requerido', 'error');
        return;
    }

    const body = { name, is_active: isActive };
    let res;

    if (id) {
        res = await api(`/brands/${id}`, 'PUT', body);
    } else {
        res = await api('/brands', 'POST', body);
    }

    if (res?.success) {
        showNotification(id ? 'Marca actualizada exitosamente' : 'Marca creada exitosamente', 'success');
        closeBrandModal();
        loadBrands();
    } else {
        showNotification(res?.message || 'Error al guardar la marca', 'error');
    }
}

async function deleteBrand(id, name) {
    const confirmed = await showConfirm(`Eliminar la marca "${name}"? Esta accion no se puede deshacer.`);
    if (!confirmed) return;

    const res = await api(`/brands/${id}`, 'DELETE');
    if (res?.success) {
        showNotification('Marca eliminada exitosamente', 'success');
        loadBrands();
    } else {
        showNotification(res?.message || 'Error al eliminar la marca', 'error');
    }
}

async function toggleBrand(id) {
    const res = await api(`/brands/${id}/toggle`, 'PUT');
    if (res?.success) {
        showNotification(res.message || 'Estado de marca actualizado', 'success');
        loadBrands();
    } else {
        showNotification(res?.message || 'Error al cambiar estado', 'error');
    }
}

// ==================== CONFIGURACION ====================
async function loadSettings() {
    const res = await api('/settings');
    if (!res?.success) return;
    const s = res.data;

    // Asegurar que el select de Cardnet tenga la opcion 'simulator' aunque
    // el HTML cacheado del admin sea viejo (evita que al guardar se pise
    // el valor con 'sandbox' por defecto).
    const envSelect = document.getElementById('setting-cardnet_environment');
    if (envSelect && !envSelect.querySelector('option[value="simulator"]')) {
        const opt = document.createElement('option');
        opt.value = 'simulator';
        opt.textContent = 'Simulador (Local, sin credenciales)';
        envSelect.insertBefore(opt, envSelect.firstChild);
    }

    ['store_name', 'address', 'contact_phone', 'contact_email', 'whatsapp_number', 'tax_name', 'tax_percent', 'min_free_shipping', 'store_hours', 'store_rnc', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email', 'min_order_amount', 'cardnet_environment', 'cardnet_currency_code', 'cardnet_merchant_number', 'cardnet_merchant_terminal', 'cardnet_merchant_name', 'cardnet_merchant_type', 'cardnet_acquiring_inst_code', 'cardnet_return_page', 'cardnet_cancel_page'].forEach(f => {
        const el = document.getElementById('setting-' + f);
        if (el && s[f]) el.value = s[f];
    });
    // Load bank accounts (multi-account)
    loadBankAccountsFromSettings(s);

    const taxEn = document.getElementById('setting-tax_enabled');
    if (taxEn) taxEn.checked = s.tax_enabled === '1';
    const ncfEn = document.getElementById('setting-ncf_enabled');
    if (ncfEn) ncfEn.checked = s.ncf_enabled === '1';
    ncfEnabled = s.ncf_enabled === '1';

    // Checkout payment method toggles
    ['checkout_pay_cash', 'checkout_pay_card', 'checkout_pay_transfer', 'checkout_pay_card_online'].forEach(f => {
        const el = document.getElementById('setting-' + f);
        if (el) el.checked = s[f] === '1';
    });

    // Cardnet enabled toggle
    const cardnetEn = document.getElementById('setting-cardnet_enabled');
    if (cardnetEn) cardnetEn.checked = s.cardnet_enabled === '1';

    // Show/hide NCF sequences section
    const ncfSeqSection = document.getElementById('ncf-sequences-section');
    if (ncfSeqSection) ncfSeqSection.style.display = ncfEnabled ? 'block' : 'none';
    if (ncfEnabled) loadNcfSequences();
}

async function saveSettings() {
    const settings = {};
    ['store_name', 'address', 'contact_phone', 'contact_email', 'whatsapp_number', 'tax_name', 'tax_percent', 'min_free_shipping', 'store_hours', 'store_rnc', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email', 'min_order_amount', 'cardnet_environment', 'cardnet_currency_code', 'cardnet_merchant_number', 'cardnet_merchant_terminal', 'cardnet_merchant_name', 'cardnet_merchant_type', 'cardnet_acquiring_inst_code', 'cardnet_return_page', 'cardnet_cancel_page'].forEach(f => {
        const el = document.getElementById('setting-' + f);
        if (el) settings[f] = el.value;
    });
    // Bank accounts are saved independently via the bank account modal

    settings.tax_enabled = document.getElementById('setting-tax_enabled')?.checked ? '1' : '0';
    settings.ncf_enabled = document.getElementById('setting-ncf_enabled')?.checked ? '1' : '0';
    settings.cardnet_enabled = document.getElementById('setting-cardnet_enabled')?.checked ? '1' : '0';

    // Checkout payment method toggles
    ['checkout_pay_cash', 'checkout_pay_card', 'checkout_pay_transfer', 'checkout_pay_card_online'].forEach(f => {
        settings[f] = document.getElementById('setting-' + f)?.checked ? '1' : '0';
    });

    // Secret key: solo enviar si el usuario escribio algo (no sobreescribir con vacio)
    const secretEl = document.getElementById('setting-cardnet_secret_key');
    if (secretEl && secretEl.value.trim() !== '') {
        settings.cardnet_secret_key = secretEl.value.trim();
    }

    const res = await api('/settings', 'POST', settings);
    if (res?.success) {
        showNotification('Configuracion guardada exitosamente', 'success');
        taxPercent = parseFloat(settings.tax_percent) || 18;
        taxEnabled = settings.tax_enabled === '1';
        ncfEnabled = settings.ncf_enabled === '1';
        const ncfSeqSection = document.getElementById('ncf-sequences-section');
        if (ncfSeqSection) ncfSeqSection.style.display = ncfEnabled ? 'block' : 'none';
        if (ncfEnabled) loadNcfSequences();
    } else showNotification(res?.message || 'Error al guardar configuracion', 'error');
}

// ==================== INDIVIDUAL SECTION SAVE HELPERS ====================
async function _saveSettingsGroup(btnId, fields, label) {
    const btn = document.getElementById(btnId);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i>'; }
    const res = await api('/settings', 'POST', fields);
    if (res?.success) {
        showNotification(`${label} guardado`, 'success');
    } else {
        showNotification(res?.message || 'Error al guardar', 'error');
    }
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save text-xs"></i>Guardar'; }
}

async function saveSettingsTienda() {
    await _saveSettingsGroup('save-settings-tienda-btn', {
        store_name: document.getElementById('setting-store_name')?.value || '',
        address: document.getElementById('setting-address')?.value || '',
        contact_phone: document.getElementById('setting-contact_phone')?.value || '',
        contact_email: document.getElementById('setting-contact_email')?.value || ''
    }, 'Informacion de la tienda');
}

async function saveSettingsWhatsApp() {
    await _saveSettingsGroup('save-settings-whatsapp-btn', {
        whatsapp_number: document.getElementById('setting-whatsapp_number')?.value || ''
    }, 'WhatsApp');
}

async function saveSettingsImpuestos() {
    const fields = {
        tax_enabled: document.getElementById('setting-tax_enabled')?.checked ? '1' : '0',
        tax_name: document.getElementById('setting-tax_name')?.value || '',
        tax_percent: document.getElementById('setting-tax_percent')?.value || ''
    };
    await _saveSettingsGroup('save-settings-impuestos-btn', fields, 'Impuestos');
    taxPercent = parseFloat(fields.tax_percent) || 18;
    taxEnabled = fields.tax_enabled === '1';
}

async function saveSettingsEnvios() {
    await _saveSettingsGroup('save-settings-envios-btn', {
        min_free_shipping: document.getElementById('setting-min_free_shipping')?.value || ''
    }, 'Envios');
}

async function saveSettingsHorario() {
    await _saveSettingsGroup('save-settings-horario-btn', {
        store_hours: document.getElementById('setting-store_hours')?.value || ''
    }, 'Horario');
}

async function saveSettingsNcf() {
    const fields = {
        ncf_enabled: document.getElementById('setting-ncf_enabled')?.checked ? '1' : '0',
        store_rnc: document.getElementById('setting-store_rnc')?.value || ''
    };
    await _saveSettingsGroup('save-settings-ncf-btn', fields, 'Comprobantes Fiscales');
    ncfEnabled = fields.ncf_enabled === '1';
    const ncfSeqSection = document.getElementById('ncf-sequences-section');
    if (ncfSeqSection) ncfSeqSection.style.display = ncfEnabled ? 'block' : 'none';
    if (ncfEnabled) loadNcfSequences();
}

async function saveSettingsSmtp() {
    await _saveSettingsGroup('save-settings-smtp-btn', {
        smtp_host: document.getElementById('setting-smtp_host')?.value || '',
        smtp_port: document.getElementById('setting-smtp_port')?.value || '',
        smtp_user: document.getElementById('setting-smtp_user')?.value || '',
        smtp_pass: document.getElementById('setting-smtp_pass')?.value || '',
        smtp_from_name: document.getElementById('setting-smtp_from_name')?.value || '',
        smtp_from_email: document.getElementById('setting-smtp_from_email')?.value || ''
    }, 'Configuracion SMTP');
}

// ==================== CUENTAS BANCARIAS (multi-cuenta) ====================
let bankAccounts = [];

function loadBankAccountsFromSettings(s) {
    bankAccounts = [];
    if (s.bank_accounts) {
        try { bankAccounts = JSON.parse(s.bank_accounts); } catch (e) {}
    }
    // Backwards compat: migrate old single-account fields if no bank_accounts key yet
    if (!bankAccounts.length && s.bank_name) {
        bankAccounts = [{
            bank_name: s.bank_name,
            bank_account_type: s.bank_account_type || 'Ahorros',
            bank_account_number: s.bank_account_number || '',
            bank_account_holder: s.bank_account_holder || ''
        }];
    }
    renderBankAccountsList();
}

function renderBankAccountsList() {
    const container = document.getElementById('bank-accounts-list');
    const empty = document.getElementById('bank-accounts-empty');
    if (!container) return;

    if (!bankAccounts.length) {
        container.innerHTML = '';
        if (empty) empty.classList.remove('hidden');
        return;
    }
    if (empty) empty.classList.add('hidden');

    container.innerHTML = bankAccounts.map((acc, i) => `
        <div class="bg-gray-900 rounded-lg px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-white text-sm">${acc.bank_name}</p>
                <p class="text-xs text-gray-400 mt-0.5">${acc.bank_account_type} &bull; <span class="font-mono">${acc.bank_account_number}</span></p>
                ${acc.bank_account_holder ? `<p class="text-xs text-gray-500">${acc.bank_account_holder}</p>` : ''}
            </div>
            <div class="flex gap-2 flex-shrink-0">
                <button onclick="openBankAccountModal(${i})" class="text-amber-400 hover:text-amber-300 transition p-1" title="Editar">
                    <i class="fas fa-edit text-sm"></i>
                </button>
                <button onclick="deleteBankAccount(${i})" class="text-red-400 hover:text-red-300 transition p-1" title="Eliminar">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function openBankAccountModal(index = null) {
    const modal = document.getElementById('bankAccountModal');
    if (!modal) return;

    document.getElementById('bank-account-edit-index').value = index !== null ? index : '';
    document.getElementById('bank-account-modal-title').textContent = index !== null ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria';

    if (index !== null && bankAccounts[index]) {
        const acc = bankAccounts[index];
        document.getElementById('ba-bank_name').value = acc.bank_name || '';
        document.getElementById('ba-bank_account_type').value = acc.bank_account_type || 'Ahorros';
        document.getElementById('ba-bank_account_number').value = acc.bank_account_number || '';
        document.getElementById('ba-bank_account_holder').value = acc.bank_account_holder || '';
    } else {
        document.getElementById('ba-bank_name').value = '';
        document.getElementById('ba-bank_account_type').value = 'Ahorros';
        document.getElementById('ba-bank_account_number').value = '';
        document.getElementById('ba-bank_account_holder').value = '';
    }

    modal.classList.remove('hidden');
    setTimeout(() => document.getElementById('ba-bank_name')?.focus(), 100);
}

async function saveBankAccount() {
    const indexStr = document.getElementById('bank-account-edit-index')?.value;
    const index = indexStr !== '' ? parseInt(indexStr) : null;

    const acc = {
        bank_name: document.getElementById('ba-bank_name')?.value?.trim() || '',
        bank_account_type: document.getElementById('ba-bank_account_type')?.value || 'Ahorros',
        bank_account_number: document.getElementById('ba-bank_account_number')?.value?.trim() || '',
        bank_account_holder: document.getElementById('ba-bank_account_holder')?.value?.trim() || ''
    };

    if (!acc.bank_name || !acc.bank_account_number) {
        showNotification('El nombre del banco y el numero de cuenta son requeridos', 'error');
        return;
    }

    const updated = [...bankAccounts];
    if (index !== null) {
        updated[index] = acc;
    } else {
        updated.push(acc);
    }

    const res = await api('/settings', 'POST', { bank_accounts: JSON.stringify(updated) });
    if (res?.success) {
        bankAccounts = updated;
        showNotification(index !== null ? 'Cuenta actualizada' : 'Cuenta agregada', 'success');
        document.getElementById('bankAccountModal')?.classList.add('hidden');
        renderBankAccountsList();
    } else {
        showNotification(res?.message || 'Error al guardar la cuenta', 'error');
    }
}

async function deleteBankAccount(index) {
    const acc = bankAccounts[index];
    const ok = await showConfirm(`Eliminar la cuenta de ${acc?.bank_name || 'este banco'}?`);
    if (!ok) return;

    const updated = bankAccounts.filter((_, i) => i !== index);
    const res = await api('/settings', 'POST', { bank_accounts: JSON.stringify(updated) });
    if (res?.success) {
        bankAccounts = updated;
        showNotification('Cuenta eliminada', 'success');
        renderBankAccountsList();
    } else {
        showNotification(res?.message || 'Error al eliminar la cuenta', 'error');
    }
}

async function saveSettingsPedidos() {
    const fields = {
        min_order_amount: document.getElementById('setting-min_order_amount')?.value || ''
    };
    ['checkout_pay_cash', 'checkout_pay_card', 'checkout_pay_transfer', 'checkout_pay_card_online'].forEach(f => {
        fields[f] = document.getElementById('setting-' + f)?.checked ? '1' : '0';
    });
    await _saveSettingsGroup('save-settings-pedidos-btn', fields, 'Pedidos Online');
}

async function saveSettingsCardnet() {
    const fields = {
        cardnet_enabled: document.getElementById('setting-cardnet_enabled')?.checked ? '1' : '0',
        cardnet_environment: document.getElementById('setting-cardnet_environment')?.value || '',
        cardnet_currency_code: document.getElementById('setting-cardnet_currency_code')?.value || '',
        cardnet_merchant_number: document.getElementById('setting-cardnet_merchant_number')?.value || '',
        cardnet_merchant_terminal: document.getElementById('setting-cardnet_merchant_terminal')?.value || '',
        cardnet_merchant_name: document.getElementById('setting-cardnet_merchant_name')?.value || '',
        cardnet_merchant_type: document.getElementById('setting-cardnet_merchant_type')?.value || '',
        cardnet_acquiring_inst_code: document.getElementById('setting-cardnet_acquiring_inst_code')?.value || '',
        cardnet_return_page: document.getElementById('setting-cardnet_return_page')?.value || '',
        cardnet_cancel_page: document.getElementById('setting-cardnet_cancel_page')?.value || ''
    };
    const secretEl = document.getElementById('setting-cardnet_secret_key');
    if (secretEl?.value.trim()) fields.cardnet_secret_key = secretEl.value.trim();
    await _saveSettingsGroup('save-settings-cardnet-btn', fields, 'Cardnet');
}

// ==================== NCF (Comprobantes Fiscales) ====================
async function loadNcfSequences() {
    const res = await api('/ncf-sequences');
    if (!res?.success) return;
    const tbody = document.getElementById('ncf-sequences-tbody');
    const empty = document.getElementById('ncf-sequences-empty');
    if (!tbody) return;
    const sequences = res.data || [];
    if (!sequences.length) {
        tbody.innerHTML = '';
        if (empty) empty.classList.remove('hidden');
        return;
    }
    if (empty) empty.classList.add('hidden');
    tbody.innerHTML = sequences.map(s => {
        const isExpired = s.is_expired == 1;
        const remaining = parseInt(s.remaining) || 0;
        const isLow = remaining > 0 && remaining <= 10;
        const statusBadge = !s.is_active ? '<span class="px-2 py-1 bg-gray-600 text-gray-300 text-xs rounded-full">Inactiva</span>'
            : isExpired ? '<span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full">Vencida</span>'
            : remaining === 0 ? '<span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-full">Agotada</span>'
            : isLow ? '<span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full">Baja</span>'
            : '<span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Activa</span>';
        return `<tr class="border-b border-gray-700/30 hover:bg-gray-700/20">
            <td class="px-4 py-3 font-medium">${s.ncf_type} <span class="text-gray-400 text-xs">${s.type_name}</span></td>
            <td class="px-4 py-3 text-center text-sm">${s.start_number} - ${s.end_number}</td>
            <td class="px-4 py-3 text-center text-sm">${s.current_number}</td>
            <td class="px-4 py-3 text-center font-medium ${isLow ? 'text-yellow-400' : remaining === 0 ? 'text-red-400' : ''}">${remaining}</td>
            <td class="px-4 py-3 text-center text-sm">${s.expiration_date}</td>
            <td class="px-4 py-3 text-center">${statusBadge}</td>
            <td class="px-4 py-3 text-center">
                <button onclick="toggleNcfActive(${s.id}, ${s.is_active ? 0 : 1})" class="text-gray-400 hover:text-white mr-2" title="${s.is_active ? 'Desactivar' : 'Activar'}">
                    <i class="fas fa-${s.is_active ? 'toggle-on text-green-400' : 'toggle-off'}"></i>
                </button>
                <button onclick="deleteNcfSequence(${s.id})" class="text-red-400 hover:text-red-300" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

async function loadNcfStatus() {
    const res = await api('/ncf-sequences/status');
    if (res?.success) {
        ncfSequenceStatus = res.data || {};
        validateNcfSelection();
    }
}

function openNcfModal() {
    document.getElementById('ncf-seq-id').value = '';
    document.getElementById('ncf-seq-type').value = '';
    document.getElementById('ncf-seq-start').value = '';
    document.getElementById('ncf-seq-end').value = '';
    document.getElementById('ncf-seq-expiration').value = '';
    document.getElementById('ncf-seq-type').disabled = false;
    document.getElementById('ncf-seq-start').disabled = false;
    document.getElementById('ncf-modal-title').textContent = 'Agregar Secuencia NCF';
    document.getElementById('ncf-modal')?.classList.remove('hidden');
}

async function saveNcfSequence() {
    const id = document.getElementById('ncf-seq-id')?.value;
    const body = {
        ncf_type: document.getElementById('ncf-seq-type')?.value,
        start_number: parseInt(document.getElementById('ncf-seq-start')?.value) || 0,
        end_number: parseInt(document.getElementById('ncf-seq-end')?.value) || 0,
        expiration_date: document.getElementById('ncf-seq-expiration')?.value
    };
    if (!body.ncf_type || !body.start_number || !body.end_number || !body.expiration_date) {
        showNotification('Complete todos los campos requeridos', 'error');
        return;
    }
    const res = id
        ? await api('/ncf-sequences/' + id, 'PUT', { end_number: body.end_number, expiration_date: body.expiration_date })
        : await api('/ncf-sequences', 'POST', body);
    if (res?.success) {
        showNotification(id ? 'Secuencia NCF actualizada' : 'Secuencia NCF creada', 'success');
        document.getElementById('ncf-modal')?.classList.add('hidden');
        loadNcfSequences();
    } else {
        showNotification(res?.message || 'Error al guardar secuencia NCF', 'error');
    }
}

async function toggleNcfActive(id, newState) {
    const res = await api('/ncf-sequences/' + id, 'PUT', { is_active: newState });
    if (res?.success) {
        showNotification(newState ? 'Secuencia activada' : 'Secuencia desactivada', 'success');
        loadNcfSequences();
    } else {
        showNotification(res?.message || 'Error al cambiar estado', 'error');
    }
}

async function deleteNcfSequence(id) {
    const ok = await showConfirm('Esta seguro de eliminar esta secuencia NCF? Solo se puede eliminar si no ha sido utilizada.');
    if (!ok) return;
    const res = await api('/ncf-sequences/' + id, 'DELETE');
    if (res?.success) {
        showNotification('Secuencia NCF eliminada', 'success');
        loadNcfSequences();
    } else {
        showNotification(res?.message || 'Error al eliminar secuencia', 'error');
    }
}

function validateNcfSelection() {
    const toggle = document.getElementById('pos-ncf-toggle');
    const typeSelect = document.getElementById('pos-ncf-type');
    const warningEl = document.getElementById('pos-ncf-warning');
    const warningText = document.getElementById('pos-ncf-warning-text');
    const errorEl = document.getElementById('pos-ncf-error');
    const errorText = document.getElementById('pos-ncf-error-text');
    if (!toggle || !typeSelect) return;

    warningEl?.classList.add('hidden');
    errorEl?.classList.add('hidden');

    if (!toggle.checked) return;

    const selectedType = typeSelect.value;
    const status = ncfSequenceStatus[selectedType];

    if (!status || !status.available) {
        errorEl?.classList.remove('hidden');
        if (errorText) errorText.textContent = `No hay secuencia disponible para ${selectedType}.`;
        return;
    }

    if (status.warning) {
        warningEl?.classList.remove('hidden');
        if (warningText) warningText.textContent = `Quedan solo ${status.remaining} comprobantes ${selectedType}.`;
    }

    if (selectedType === 'B01' && !posSelectedCustomerId) {
        warningEl?.classList.remove('hidden');
        if (warningText) warningText.textContent = 'B01 requiere un cliente con RNC seleccionado.';
    }
}

// ==================== USUARIOS ====================
async function loadUsers(page = 1) {
    currentUserPage = page;
    const search = document.getElementById('user-search')?.value || '';
    const role = document.getElementById('filter-user-role')?.value || '';
    let url = `/users?page=${page}&limit=15`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (role) url += `&role=${encodeURIComponent(role)}`;

    const res = await api(url);
    const tbody = document.getElementById('users-table');
    if (!tbody) return;

    if (!res?.success || !res.data?.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">No se encontraron usuarios</td></tr>';
        renderPagination('users-pagination-btns', null, 'loadUsers');
        const info = document.getElementById('users-pagination-info');
        if (info) info.textContent = 'Mostrando 0 usuarios';
        return;
    }

    tbody.innerHTML = res.data.map(u => `
        <tr class="hover:bg-gray-700/30 transition">
            <td class="px-4 py-3">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-amber-500/20 rounded-full flex items-center justify-center text-amber-400 font-bold text-sm">
                        ${(u.full_name || u.username).charAt(0).toUpperCase()}
                    </div>
                    <span class="font-medium">${u.username}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-sm">${u.full_name || '-'}</td>
            <td class="px-4 py-3 text-sm text-gray-400">${u.email || '-'}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">${roleLabels[u.role] || u.role}</span></td>
            <td class="px-4 py-3">${u.is_active ? '<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">Activo</span>' : '<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400">Inactivo</span>'}</td>
            <td class="px-4 py-3 text-sm text-gray-400">${u.last_login ? formatDate(u.last_login) : 'Nunca'}</td>
            <td class="px-4 py-3">
                <div class="flex justify-end space-x-2">
                    <button onclick="openUserModal(${u.id})" class="text-blue-400 hover:text-blue-300 transition" title="Editar"><i class="fas fa-edit"></i></button>
                    <button onclick="toggleUserActive(${u.id}, ${u.is_active ? 1 : 0})" class="text-yellow-400 hover:text-yellow-300 transition" title="${u.is_active ? 'Desactivar' : 'Activar'}"><i class="fas fa-${u.is_active ? 'ban' : 'check-circle'}"></i></button>
                    <button onclick="openResetPasswordPrompt(${u.id}, '${u.username.replace(/'/g, "\\'")}')" class="text-purple-400 hover:text-purple-300 transition" title="Resetear contrasena"><i class="fas fa-key"></i></button>
                </div>
            </td>
        </tr>
    `).join('');

    if (res.pagination) {
        renderPagination('users-pagination-btns', res.pagination, 'loadUsers');
        const info = document.getElementById('users-pagination-info');
        if (info) info.textContent = `Mostrando ${res.data.length} de ${res.pagination.total} usuarios`;
    }
}

async function openUserModal(id = null) {
    const modal = document.getElementById('user-modal');
    const title = document.getElementById('user-modal-title');
    if (!modal) return;

    // Reset form
    document.getElementById('user-id').value = '';
    document.getElementById('user-username').value = '';
    document.getElementById('user-password').value = '';
    document.getElementById('user-full_name').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-phone').value = '';
    document.getElementById('user-role').value = 'vendedor';
    document.getElementById('user-is_active').checked = true;

    const passField = document.getElementById('user-password');
    const passLabel = passField?.previousElementSibling;

    if (id) {
        title.textContent = 'Editar Usuario';
        if (passLabel) passLabel.textContent = 'Contrasena (dejar vacio para no cambiar)';
        const res = await api(`/users/${id}`);
        if (res?.success) {
            const u = res.data;
            document.getElementById('user-id').value = u.id;
            document.getElementById('user-username').value = u.username || '';
            document.getElementById('user-full_name').value = u.full_name || '';
            document.getElementById('user-email').value = u.email || '';
            document.getElementById('user-phone').value = u.phone || '';
            document.getElementById('user-role').value = u.role || 'vendedor';
            document.getElementById('user-is_active').checked = !!u.is_active;
        }
    } else {
        title.textContent = 'Agregar Usuario';
        if (passLabel) passLabel.textContent = 'Contrasena *';
    }
    modal.classList.remove('hidden');
}

async function saveUser() {
    const id = document.getElementById('user-id')?.value;
    const data = {
        username: document.getElementById('user-username')?.value?.trim(),
        full_name: document.getElementById('user-full_name')?.value?.trim(),
        email: document.getElementById('user-email')?.value?.trim() || null,
        phone: document.getElementById('user-phone')?.value?.trim() || null,
        role: document.getElementById('user-role')?.value,
        is_active: document.getElementById('user-is_active')?.checked ? 1 : 0
    };

    if (!data.username || !data.full_name || !data.role) {
        showNotification('Complete los campos obligatorios', 'error');
        return;
    }

    const password = document.getElementById('user-password')?.value;

    if (id) {
        // Update user data
        const res = await api(`/users/${id}`, 'PUT', data);
        if (res?.success) {
            // If password was provided, also reset it
            if (password && password.length >= 6) {
                await api(`/users/${id}/reset-password`, 'PUT', { password });
            }
            showNotification('Usuario actualizado exitosamente', 'success');
            document.getElementById('user-modal')?.classList.add('hidden');
            loadUsers(currentUserPage);
        } else {
            showNotification(res?.message || 'Error al actualizar usuario', 'error');
        }
    } else {
        // Create - password required
        if (!password || password.length < 6) {
            showNotification('La contrasena debe tener al menos 6 caracteres', 'error');
            return;
        }
        data.password = password;
        const res = await api('/users', 'POST', data);
        if (res?.success) {
            showNotification('Usuario creado exitosamente', 'success');
            document.getElementById('user-modal')?.classList.add('hidden');
            loadUsers(1);
        } else {
            showNotification(res?.message || 'Error al crear usuario', 'error');
        }
    }
}

async function toggleUserActive(id, currentStatus) {
    const action = currentStatus ? 'desactivar' : 'activar';
    const ok = await showConfirm(`Desea ${action} este usuario?`);
    if (!ok) return;
    const res = await api(`/users/${id}/toggle-active`, 'PUT');
    if (res?.success) {
        showNotification(res.message || `Usuario ${action === 'activar' ? 'activado' : 'desactivado'}`, 'success');
        loadUsers(currentUserPage);
    } else {
        showNotification(res?.message || 'Error al cambiar estado del usuario', 'error');
    }
}

async function openResetPasswordPrompt(id, username) {
    const ok = await showConfirm(`Restablecer la contrasena de "${username}"?`);
    if (!ok) return;
    const newPass = prompt('Ingrese la nueva contrasena (min 6 caracteres):');
    if (!newPass) return;
    if (newPass.length < 6) {
        showNotification('La contrasena debe tener al menos 6 caracteres', 'error');
        return;
    }
    const res = await api(`/users/${id}/reset-password`, 'PUT', { password: newPass });
    if (res?.success) {
        showNotification('Contrasena restablecida exitosamente', 'success');
    } else {
        showNotification(res?.message || 'Error al restablecer contrasena', 'error');
    }
}

// ==================== PERMISOS POR ROL ====================
async function loadRolePermissions(role = null) {
    if (role) currentRoleTab = role;
    const currentRole = currentRoleTab;

    // Update tab styles
    document.querySelectorAll('.role-perm-tab').forEach(tab => {
        if (tab.dataset.roleTab === currentRole) {
            tab.classList.add('bg-amber-500', 'text-black');
            tab.classList.remove('bg-gray-700', 'text-gray-300');
        } else {
            tab.classList.remove('bg-amber-500', 'text-black');
            tab.classList.add('bg-gray-700', 'text-gray-300');
        }
    });

    const container = document.getElementById('role-permissions-container');
    if (!container) return;
    container.innerHTML = '<div class="col-span-full text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-500"></i></div>';

    const res = await api(`/roles/${currentRole}/permissions`);
    if (!res?.success) {
        container.innerHTML = '<p class="col-span-full text-center text-red-400 py-4">Error al cargar permisos</p>';
        return;
    }

    const allPerms = res.data.all_permissions || [];
    const granted = res.data.granted || [];

    // Group by module
    const grouped = {};
    allPerms.forEach(p => {
        const mod = p.module;
        if (!grouped[mod]) grouped[mod] = [];
        grouped[mod].push(p);
    });

    const moduleLabels = {
        dashboard: 'Dashboard',
        pos: 'Punto de Venta',
        products: 'Productos',
        orders: 'Pedidos',
        expenses: 'Gastos',
        cash_register: 'Caja Registradora',
        reports: 'Reportes',
        customers: 'Clientes',
        credits: 'Creditos',
        settings: 'Configuracion',
        users: 'Usuarios',
        roles: 'Roles'
    };

    container.innerHTML = Object.keys(grouped).map(mod => `
        <div class="bg-gray-900 rounded-xl p-4 border border-gray-700">
            <h4 class="font-semibold text-amber-400 mb-3 flex items-center">
                <i class="fas fa-shield-alt mr-2 text-sm"></i>${moduleLabels[mod] || mod}
            </h4>
            <div class="space-y-2">
                ${grouped[mod].map(p => `
                    <label class="flex items-center space-x-3 cursor-pointer hover:bg-gray-800 p-2 rounded-lg transition">
                        <input type="checkbox" value="${p.permission_key}" class="role-perm-checkbox w-4 h-4 rounded border-gray-600 bg-gray-900 text-amber-500 focus:ring-amber-500"
                            ${granted.includes(p.permission_key) ? 'checked' : ''}>
                        <div>
                            <p class="text-sm font-medium">${p.name}</p>
                            <p class="text-xs text-gray-500">${p.description || ''}</p>
                        </div>
                    </label>
                `).join('')}
            </div>
        </div>
    `).join('');
}

async function saveRolePermissions() {
    const btn = document.getElementById('save-role-permissions-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...'; }

    const checkboxes = document.querySelectorAll('.role-perm-checkbox');
    const permissions = [];
    checkboxes.forEach(cb => {
        if (cb.checked) permissions.push(cb.value);
    });

    const res = await api(`/roles/${currentRoleTab}/permissions`, 'PUT', { permissions });
    if (res?.success) {
        showNotification(`Permisos de ${roleLabels[currentRoleTab] || currentRoleTab} actualizados`, 'success');
        loadRolePermissions();
    } else {
        showNotification(res?.message || 'Error al guardar permisos', 'error');
    }

    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-shield-alt mr-2"></i>Guardar Permisos'; }
}
