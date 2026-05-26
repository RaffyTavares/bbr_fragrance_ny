// BBR Fragrance - Main: Products (Loading, Rendering, Pagination, Promo, Featured, Animation)

// ===================================
// Product Loading from API
// ===================================
let productsCache = null;

async function loadProducts(params = {}) {
    const queryParams = new URLSearchParams();
    if (params.category) queryParams.set('category', params.category);
    if (params.family) queryParams.set('family', params.family);
    if (params.brand) queryParams.set('brand', params.brand);
    if (params.search) queryParams.set('search', params.search);
    if (params.sort) queryParams.set('sort', params.sort);
    if (params.min_price) queryParams.set('min_price', params.min_price);
    if (params.max_price) queryParams.set('max_price', params.max_price);
    if (params.page) queryParams.set('page', params.page);
    if (params.per_page) queryParams.set('per_page', params.per_page);
    if (params.featured) queryParams.set('featured', params.featured);
    if (params.offers) queryParams.set('offers', params.offers);

    const qs = queryParams.toString();
    const url = `/products${qs ? '?' + qs : ''}`;
    const result = await apiGet(url);
    return result;
}

async function loadProductById(id) {
    const result = await apiGet(`/products/${id}`);
    return result;
}

// ===================================
// Product Card Rendering
// ===================================
function createProductCard(product, isFeatured = false) {
    const image = getProductImage(product);
    const gradient = getProductGradient(product.family_name || product.family);
    const familySlug = (product.family_slug || product.family_name || '').toLowerCase();
    const brandName = product.brand_name || product.brand || '';
    const categorySlug = product.category_slug || product.category || '';
    const stock = parseInt(product.stock) || 0;
    const price = parseFloat(product.price) || 0;
    const originalPrice = parseFloat(product.original_price) || 0;
    const hasDiscount = originalPrice > 0 && originalPrice > price;
    const discountPct = hasDiscount ? Math.round((1 - price / originalPrice) * 100) : 0;

    const imageHtml = image
        ? `<img src="${image}" alt="${product.name}" class="w-full h-full object-cover">`
        : `<i class="fas fa-wine-bottle text-${isFeatured ? '8' : '7'}xl text-white/30"></i>`;

    let badge = '';
    if (stock === 0) {
        badge = '<div class="absolute top-4 right-4 bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold">Agotado</div>';
    } else if (stock > 0 && stock < 5) {
        badge = `<div class="absolute top-4 right-4 bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold">&Uacute;ltimas ${stock}</div>`;
    }

    const discountBadge = hasDiscount ? `<div class="absolute top-4 left-14 bg-red-500 text-white px-2.5 py-1 rounded-full text-xs font-bold">-${discountPct}%</div>` : '';

    const detailUrl = getDetailUrl(product.id);
    const heightClass = isFeatured ? 'h-72' : 'h-64';
    const containerClass = isFeatured ? 'product-card' : 'product-item';

    return `
        <div class="${containerClass} bg-white rounded-lg overflow-hidden hover:transform hover:scale-105 transition duration-300 border border-gray-200 hover:border-amber-500"
             data-category="${categorySlug}"
             data-family="${familySlug}"
             data-brand="${brandName.toLowerCase()}"
             data-price="${price}"
             data-product-id="${product.id}">
            <div class="relative overflow-hidden group">
                <div class="${heightClass} bg-gradient-to-br ${gradient} flex items-center justify-center">
                    ${imageHtml}
                </div>
                ${badge}
                ${discountBadge}
                <button class="absolute top-4 left-4 w-10 h-10 bg-black/10 backdrop-blur rounded-full flex items-center justify-center hover:bg-amber-500 hover:text-black transition text-gray-700">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="p-6">
                <span class="text-xs text-amber-400 uppercase tracking-wider">${brandName}</span>
                <h3 class="text-xl font-semibold mt-2 mb-1 text-gray-700">${product.name || ''}</h3>
                ${product.volume_ml ? `<p class="text-xs text-gray-700 mb-3"><i class="fas fa-flask mr-1 text-amber-400/70"></i>${product.volume_ml} ml</p>` : '<div class="mb-3"></div>'}
                <div class="flex items-center mb-4">
                    <div class="flex text-amber-400 text-sm">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        ${hasDiscount ? `<del class="text-gray-400 text-sm mr-2">${formatPrice(originalPrice)}</del>` : ''}
                        <span class="text-2xl font-bold text-amber-500">${formatPrice(price)}</span>
                    </div>
                </div>
                ${!isFeatured && product.description ? `<p class="text-gray-600 text-sm mb-4 line-clamp-2">${product.description}</p>` : ''}
                <div class="flex gap-2">
                    <button class="flex-1 bg-amber-500 text-white py-3 rounded-lg font-semibold hover:bg-amber-400 transition add-to-cart" ${stock === 0 ? 'disabled' : ''}>
                        <i class="fas fa-shopping-cart mr-2"></i>${stock === 0 ? 'Agotado' : 'Agregar'}
                    </button>
                    <a href="${detailUrl}" class="bg-gray-100 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-200 transition flex items-center">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
    `;
}

// ===================================
// Skeleton Loading Cards
// ===================================
function renderSkeletonCards(container, count = 6) {
    let html = '';
    for (let i = 0; i < count; i++) {
        html += `
            <div class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                <div class="h-64 skeleton"></div>
                <div class="p-6 space-y-3">
                    <div class="h-3 skeleton rounded w-1/4"></div>
                    <div class="h-5 skeleton rounded w-3/4"></div>
                    <div class="h-3 skeleton rounded w-1/2"></div>
                    <div class="h-8 skeleton rounded w-1/3"></div>
                    <div class="h-10 skeleton rounded"></div>
                </div>
            </div>
        `;
    }
    container.innerHTML = html;
}

// ===================================
// Attach Add-to-Cart Listeners
// ===================================
function attachAddToCartListeners() {
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.replaceWith(btn.cloneNode(true));
    });

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const productCard = this.closest('.product-item') || this.closest('.product-card');
            if (!productCard) return;

            const productId = productCard.dataset.productId;
            const name = productCard.querySelector('h3')?.textContent || '';
            const brand = productCard.querySelector('.text-amber-400.uppercase')?.textContent?.trim() || '';
            const priceText = productCard.querySelector('.text-2xl.font-bold')?.textContent || '0';
            const price = parseFloat(priceText.replace(/[^0-9.,]/g, '').replace(/,/g, ''));

            if (productCard.querySelector('.add-to-cart[disabled]')) return;

            cart.addItem({
                id: parseInt(productId),
                name: name,
                brand: brand,
                price: price
            });
        });
    });
}

// ===================================
// Render Products on productos.html
// ===================================
async function renderProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;

    renderSkeletonCards(productsGrid, 6);

    // Get filter params from current state
    const params = getCurrentFilterParams();
    const result = await loadProducts(params);

    if (!result.success || !result.data || result.data.length === 0) {
        productsGrid.innerHTML = `
            <div class="col-span-full text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-xl">No hay productos disponibles</p>
                <p class="text-gray-500 text-sm mt-2">Intenta con otros filtros</p>
            </div>
        `;
        const resultsCount = document.getElementById('results-count');
        if (resultsCount) resultsCount.textContent = '0';

        // Hide pagination
        const paginationContainer = document.getElementById('pagination-container');
        if (paginationContainer) paginationContainer.classList.add('hidden');
        return;
    }

    const products = result.data;
    productsGrid.innerHTML = products.map(p => createProductCard(p, false)).join('');
    attachAddToCartListeners();

    // Update results count
    const resultsCount = document.getElementById('results-count');
    if (resultsCount) {
        resultsCount.textContent = result.pagination ? result.pagination.total : products.length;
    }

    // Update pagination
    if (result.pagination && result.pagination.total_pages > 1) {
        renderAPIPagination(result.pagination);
    } else {
        const paginationContainer = document.getElementById('pagination-container');
        if (paginationContainer) paginationContainer.classList.add('hidden');
    }

    // Animate cards in
    animateProductCards();
}

function getCurrentFilterParams() {
    const params = {};

    // Category from checkboxes
    const selectedCategories = Array.from(document.querySelectorAll('.filter-category:checked')).map(cb => cb.value);
    if (selectedCategories.length === 1) {
        params.category = selectedCategories[0];
    }

    // Family from checkboxes
    const selectedFamilies = Array.from(document.querySelectorAll('.filter-family:checked')).map(cb => cb.value);
    if (selectedFamilies.length === 1) {
        params.family = selectedFamilies[0];
    }

    // Brand from checkboxes
    const selectedBrands = Array.from(document.querySelectorAll('.filter-brand:checked')).map(cb => cb.value);
    if (selectedBrands.length === 1) {
        params.brand = selectedBrands[0];
    }

    // Price range
    const selectedPrice = document.querySelector('.filter-price:checked')?.value;
    if (selectedPrice) {
        const parts = selectedPrice.split('-');
        if (parts[0] && parts[0] !== '+') params.min_price = parts[0];
        if (parts[1] && parts[1] !== '+') params.max_price = parts[1];
        if (parts.length === 1 && parts[0].endsWith('+')) {
            params.min_price = parts[0].replace('+', '');
        }
    }

    // Sort
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect && sortSelect.value && sortSelect.value !== 'default') {
        switch (sortSelect.value) {
            case 'price-asc':
                params.sort = 'price_asc';
                break;
            case 'price-desc':
                params.sort = 'price_desc';
                break;
            case 'name-asc':
                params.sort = 'name_asc';
                break;
            case 'name-desc':
                params.sort = 'name_desc';
                break;
        }
    }

    // Offers filter
    const offersCheckbox = document.getElementById('filter-offers');
    if (offersCheckbox?.checked) {
        params.offers = '1';
    }

    // Page
    if (window.currentPage && window.currentPage > 1) {
        params.page = window.currentPage;
    }

    params.per_page = 12;
    return params;
}

// ===================================
// API-Based Pagination
// ===================================
function renderAPIPagination(pagination) {
    const paginationContainer = document.getElementById('pagination-container');
    const buttonsContainer = document.getElementById('pagination-buttons');
    if (!paginationContainer || !buttonsContainer) return;

    paginationContainer.classList.remove('hidden');

    const { current_page, total_pages } = pagination;
    let buttonsHTML = '';

    // Previous button
    buttonsHTML += `
        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition ${current_page === 1 ? 'opacity-50 cursor-not-allowed' : ''}"
                onclick="goToAPIPage(${current_page - 1})" ${current_page === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    // Page buttons
    const maxVisible = 5;
    let startPage = Math.max(1, current_page - Math.floor(maxVisible / 2));
    let endPage = Math.min(total_pages, startPage + maxVisible - 1);
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        buttonsHTML += `<button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" onclick="goToAPIPage(1)">1</button>`;
        if (startPage > 2) buttonsHTML += `<span class="px-2 py-2 text-gray-400">...</span>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === current_page;
        buttonsHTML += `
            <button class="px-4 py-2 rounded-lg font-semibold transition ${isActive ? 'bg-amber-500 text-black' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}"
                    onclick="goToAPIPage(${i})" ${isActive ? 'disabled' : ''}>
                ${i}
            </button>
        `;
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) buttonsHTML += `<span class="px-2 py-2 text-gray-400">...</span>`;
        buttonsHTML += `<button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" onclick="goToAPIPage(${total_pages})">${total_pages}</button>`;
    }

    // Next button
    buttonsHTML += `
        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition ${current_page === total_pages ? 'opacity-50 cursor-not-allowed' : ''}"
                onclick="goToAPIPage(${current_page + 1})" ${current_page === total_pages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    buttonsContainer.innerHTML = buttonsHTML;
}

function goToAPIPage(page) {
    window.currentPage = page;
    renderProducts();
    const productsSection = document.querySelector('#productos-section');
    if (productsSection) {
        productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// ===================================
// Promo del Mes (dynamic from settings)
// ===================================
async function loadPromoMonth() {
    const section = document.getElementById('promo-month-section');
    if (!section) return;

    try {
        const res = await apiGet('/settings');
        if (!res || !res.success) return;
        const s = res.data || {};

        // If promo is explicitly disabled, hide the section
        if (s.promo_active === '0') {
            section.style.display = 'none';
            return;
        }

        // Populate fields if settings exist
        if (s.promo_title) {
            const titleEl = document.getElementById('promo-title');
            if (titleEl) titleEl.textContent = s.promo_title;
        }
        if (s.promo_subtitle) {
            const subEl = document.getElementById('promo-subtitle');
            if (subEl) subEl.textContent = s.promo_subtitle;
        }
        if (s.promo_bullets) {
            try {
                // Fix HTML-escaped quotes from legacy data
                const cleanBullets = s.promo_bullets.replace(/&quot;/g, '"').replace(/&amp;/g, '&');
                const bullets = JSON.parse(cleanBullets);
                const bulletsEl = document.getElementById('promo-bullets');
                if (bulletsEl && Array.isArray(bullets) && bullets.length > 0) {
                    bulletsEl.innerHTML = bullets
                        .filter(b => b && b.trim())
                        .map(b => `<li class="flex items-center text-white"><i class="fas fa-check-circle text-amber-400 mr-3"></i>${b}</li>`)
                        .join('');
                }
            } catch (e) { /* keep default bullets */ }
        }
        if (s.promo_link) {
            const linkEl = document.getElementById('promo-link');
            if (linkEl) linkEl.href = s.promo_link;
        }

        // Promo media (image or video)
        if (s.promo_image) {
            const container = document.getElementById('promo-image-container');
            const placeholder = document.getElementById('promo-image-placeholder');
            if (container) {
                if (placeholder) placeholder.style.display = 'none';
                const isVideo = s.promo_media_type === 'video' || /\.(mp4|webm|mov)$/i.test(s.promo_image);

                if (isVideo) {
                    // Restyle container for cinematic video presentation
                    container.className = 'relative rounded-3xl overflow-hidden border-2 group';
                    container.style.width = 'min(100%, 32rem)';
                    container.style.aspectRatio = '4 / 5';
                    container.style.height = 'auto';
                    container.style.borderColor = 'rgba(201,169,110,0.45)';
                    container.style.background = 'rgba(0,0,0,0.4)';
                    container.style.boxShadow = '0 30px 80px -20px rgba(0,0,0,0.8), 0 0 80px rgba(201,169,110,0.25), inset 0 0 60px rgba(201,169,110,0.06)';
                    container.style.backdropFilter = 'blur(12px)';

                    // Video element
                    const video = document.createElement('video');
                    video.src = s.promo_image;
                    video.autoplay = true;
                    video.muted = true; // required for autoplay
                    video.loop = true;
                    video.playsInline = true;
                    video.setAttribute('playsinline', '');
                    video.className = 'w-full h-full object-cover';
                    container.appendChild(video);

                    // Soft gold gradient overlay (top + bottom) for elegance
                    const overlay = document.createElement('div');
                    overlay.className = 'absolute inset-0 pointer-events-none';
                    overlay.style.background = 'linear-gradient(180deg, rgba(0,0,0,0.25) 0%, transparent 25%, transparent 70%, rgba(0,0,0,0.55) 100%)';
                    container.appendChild(overlay);

                    // Inner gold ring
                    const ring = document.createElement('div');
                    ring.className = 'absolute inset-0 rounded-3xl pointer-events-none';
                    ring.style.boxShadow = 'inset 0 0 0 1px rgba(212,186,133,0.25)';
                    container.appendChild(ring);

                    // Mute / Unmute button
                    const audioBtn = document.createElement('button');
                    audioBtn.type = 'button';
                    audioBtn.setAttribute('aria-label', 'Activar sonido');
                    audioBtn.className = 'absolute bottom-4 right-4 w-12 h-12 rounded-full flex items-center justify-center transition transform hover:scale-110 backdrop-blur-md';
                    audioBtn.style.background = 'rgba(0,0,0,0.55)';
                    audioBtn.style.border = '1px solid rgba(201,169,110,0.5)';
                    audioBtn.style.color = '#D4BA85';
                    audioBtn.style.boxShadow = '0 4px 20px rgba(0,0,0,0.5)';
                    audioBtn.innerHTML = '<i class="fas fa-volume-mute text-lg"></i>';
                    audioBtn.addEventListener('click', () => {
                        video.muted = !video.muted;
                        if (!video.muted) {
                            video.volume = 1;
                            // Ensure playback when user gesture unlocks audio
                            video.play().catch(() => {});
                            audioBtn.innerHTML = '<i class="fas fa-volume-up text-lg"></i>';
                            audioBtn.setAttribute('aria-label', 'Silenciar');
                        } else {
                            audioBtn.innerHTML = '<i class="fas fa-volume-mute text-lg"></i>';
                            audioBtn.setAttribute('aria-label', 'Activar sonido');
                        }
                    });
                    container.appendChild(audioBtn);

                    // "Oferta Especial" floating chip top-left for editorial feel
                    const chip = document.createElement('div');
                    chip.className = 'absolute top-4 left-4 px-3 py-1.5 rounded-full text-xs uppercase tracking-widest font-semibold backdrop-blur-md';
                    chip.style.background = 'rgba(0,0,0,0.55)';
                    chip.style.border = '1px solid rgba(201,169,110,0.5)';
                    chip.style.color = '#D4BA85';
                    chip.innerHTML = '<i class="fas fa-circle text-[6px] mr-2 align-middle" style="color:#C9A96E;"></i>En vivo';
                    container.appendChild(chip);
                } else {
                    const img = document.createElement('img');
                    img.src = s.promo_image;
                    img.alt = s.promo_title || 'Promocion del Mes';
                    img.className = 'w-full h-full object-cover';
                    container.appendChild(img);
                }
            }
        }
    } catch (e) {
        // Settings not available, keep defaults
    }
}

// ===================================
// Render Featured Products (index.html)
// ===================================
async function renderFeaturedProducts() {
    const featuredGrid = document.getElementById('featured-products');
    if (!featuredGrid) return;

    renderSkeletonCards(featuredGrid, 4);

    const result = await loadProducts({ featured: 1, per_page: 8 });

    if (!result.success || !result.data || result.data.length === 0) {
        featuredGrid.innerHTML = `
            <div class="col-span-full text-center py-20">
                <i class="fas fa-box-open text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-xl">No hay productos disponibles</p>
            </div>
        `;
        return;
    }

    featuredGrid.innerHTML = result.data.slice(0, 8).map(p => createProductCard(p, true)).join('');
    attachAddToCartListeners();
    animateProductCards();
}

// ===================================
// Animate Product Cards
// ===================================
function animateProductCards() {
    const cards = document.querySelectorAll('.product-card, .product-item');
    if (!cards.length) return;

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.05 });

    cards.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });
}
