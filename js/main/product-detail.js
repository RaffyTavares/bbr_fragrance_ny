// BBR Fragance - Main: Product Detail Page

// ===================================
// Product Detail Page
// ===================================
async function loadProductDetail() {
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId) return;

    // Show loading state
    const productDetailSection = document.querySelector('.py-8.bg-gradient-to-b');
    if (productDetailSection) {
        productDetailSection.innerHTML = `
            <div class="container mx-auto px-4 text-center py-20">
                <div class="spinner mx-auto mb-4"></div>
                <p class="text-gray-400">Cargando producto...</p>
            </div>
        `;
    }

    const result = await loadProductById(productId);

    if (!result.success || !result.data) {
        if (productDetailSection) {
            productDetailSection.innerHTML = `
                <div class="container mx-auto px-4 text-center py-20">
                    <i class="fas fa-exclamation-triangle text-6xl text-amber-400 mb-4"></i>
                    <h2 class="text-2xl font-semibold mb-2">Producto no encontrado</h2>
                    <p class="text-gray-400 mb-6">El producto que buscas no existe o fue eliminado.</p>
                    <a href="productos.html" class="inline-block px-6 py-3 bg-amber-500 text-black font-semibold rounded-lg hover:bg-amber-400 transition">
                        Ver todos los productos
                    </a>
                </div>
            `;
        }
        return;
    }

    const product = result.data;
    const image = getProductImage(product);
    const gradient = getProductGradient(product.family_name);
    const stock = parseInt(product.stock) || 0;
    const price = parseFloat(product.price) || 0;

    // Update page title
    document.title = `${product.name} - ${product.brand_name || ''} | Bbr_Fragance`;

    // Update breadcrumb
    const breadcrumb = document.querySelector('.pt-32.pb-8 .container');
    if (breadcrumb) {
        breadcrumb.innerHTML = `
            <div class="flex items-center space-x-2 text-sm text-gray-400">
                <a href="../index.html" class="hover:text-amber-400">Inicio</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <a href="productos.html" class="hover:text-amber-400">Productos</a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white">${product.brand_name || ''} ${product.name}</span>
            </div>
        `;
    }

    // Build images HTML
    let mainImageHtml = '';
    let thumbnailsHtml = '';

    if (product.images && product.images.length > 0) {
        const mainImg = product.images[0];
        const mainImgUrl = mainImg.url || mainImg.image_url;
        mainImageHtml = `<img src="${mainImgUrl}" alt="${product.name}" class="w-full h-full object-cover" id="main-product-image">`;
        thumbnailsHtml = product.images.map((img, index) => {
            const imgUrl = img.url || img.image_url;
            return `
            <div class="bg-gray-800 rounded-lg h-24 flex items-center justify-center cursor-pointer border-2 ${index === 0 ? 'border-amber-500' : 'border-gray-700 hover:border-amber-500'} transition overflow-hidden"
                 onclick="document.getElementById('main-product-image').src='${imgUrl}'; this.parentElement.querySelectorAll('div').forEach(d => d.classList.replace('border-amber-500','border-gray-700')); this.classList.replace('border-gray-700','border-amber-500');">
                <img src="${imgUrl}" alt="" class="w-full h-full object-cover">
            </div>
        `}).join('');
    } else if (image) {
        mainImageHtml = `<img src="${image}" alt="${product.name}" class="w-full h-full object-cover">`;
    } else {
        mainImageHtml = `<i class="fas fa-wine-bottle text-9xl text-white/30"></i>`;
    }

    // Render complete product detail
    if (productDetailSection) {
        productDetailSection.innerHTML = `
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Gallery -->
                <div>
                    <div class="sticky top-24">
                        <div class="bg-gradient-to-br ${gradient} rounded-lg h-96 md:h-[500px] flex items-center justify-center mb-4 relative overflow-hidden">
                            ${mainImageHtml}
                            <button class="absolute top-4 left-4 w-12 h-12 bg-white/10 backdrop-blur rounded-full flex items-center justify-center hover:bg-amber-500 hover:text-black transition">
                                <i class="fas fa-heart text-xl"></i>
                            </button>
                        </div>
                        ${thumbnailsHtml ? `<div class="grid grid-cols-4 gap-4">${thumbnailsHtml}</div>` : ''}
                    </div>
                </div>

                <!-- Product Info -->
                <div>
                    <div class="mb-6">
                        <span class="text-sm text-amber-400 uppercase tracking-wider font-semibold">${product.brand_name || ''}</span>
                        <h1 class="text-4xl md:text-5xl font-serif mt-2 mb-4">${product.name}</h1>

                        <div class="flex items-center gap-4 mb-6">
                            <span class="text-4xl font-bold text-amber-400">${formatPrice(price)}</span>
                        </div>

                        <div class="flex items-center gap-2 mb-6">
                            ${stock > 0
                                ? `<i class="fas fa-check-circle text-green-400"></i>
                                   <span class="text-green-400 font-semibold">En stock</span>
                                   ${stock < 10 ? `<span class="text-gray-400 ml-2">• Ultimas ${stock} unidades</span>` : ''}`
                                : `<i class="fas fa-times-circle text-red-400"></i>
                                   <span class="text-red-400 font-semibold">Agotado</span>`
                            }
                        </div>
                    </div>

                    <div class="mb-8 pb-8 border-b border-gray-800">
                        <p class="text-gray-300 leading-relaxed">${product.description || 'Sin descripcion disponible.'}</p>
                    </div>

                    <!-- Info pills -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        ${product.category_name ? `<span class="bg-gray-800 px-4 py-2 rounded-full text-sm"><i class="fas fa-tag text-amber-400 mr-2"></i>${product.category_name}</span>` : ''}
                        ${product.family_name ? `<span class="bg-gray-800 px-4 py-2 rounded-full text-sm"><i class="fas fa-leaf text-amber-400 mr-2"></i>${product.family_name}</span>` : ''}
                        ${product.sku ? `<span class="bg-gray-800 px-4 py-2 rounded-full text-sm"><i class="fas fa-barcode text-amber-400 mr-2"></i>${product.sku}</span>` : ''}
                    </div>

                    <!-- Quantity -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Cantidad</h3>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border-2 border-gray-700 rounded-lg overflow-hidden">
                                <button class="px-4 py-3 hover:bg-gray-800 transition" id="qty-minus"><i class="fas fa-minus"></i></button>
                                <input type="number" value="1" min="1" max="${stock}" class="w-16 text-center bg-transparent py-3 focus:outline-none" id="qty-input">
                                <button class="px-4 py-3 hover:bg-gray-800 transition" id="qty-plus"><i class="fas fa-plus"></i></button>
                            </div>
                            ${stock > 0 ? `<span class="text-gray-400">Disponible: ${stock} unidades</span>` : ''}
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-col md:flex-row gap-4 mb-8">
                        <button id="add-to-cart-detail" class="flex-1 bg-amber-500 text-black py-4 rounded-lg font-bold text-lg hover:bg-amber-400 transition transform hover:scale-105 flex items-center justify-center" ${stock === 0 ? 'disabled' : ''}
                                data-product-id="${product.id}"
                                data-product-name="${product.name}"
                                data-product-brand="${product.brand_name || ''}"
                                data-product-price="${price}">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            ${stock === 0 ? 'Producto Agotado' : 'Agregar al carrito'}
                        </button>
                        <button id="whatsapp-buy" class="flex-1 bg-green-500 text-white py-4 rounded-lg font-bold text-lg hover:bg-green-600 transition transform hover:scale-105 flex items-center justify-center"
                                data-product-name="${product.name}"
                                data-product-brand="${product.brand_name || ''}"
                                data-product-price="${price}">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Comprar por WhatsApp
                        </button>
                    </div>

                    <!-- Benefits -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-shipping-fast text-2xl text-amber-400"></i>
                            <div><p class="font-semibold text-sm">Envio Rapido</p><p class="text-xs text-gray-400">24-48 horas</p></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-certificate text-2xl text-amber-400"></i>
                            <div><p class="font-semibold text-sm">100% Original</p><p class="text-xs text-gray-400">Garantizado</p></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-lock text-2xl text-amber-400"></i>
                            <div><p class="font-semibold text-sm">Pago Seguro</p><p class="text-xs text-gray-400">SSL protegido</p></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-undo text-2xl text-amber-400"></i>
                            <div><p class="font-semibold text-sm">Devoluciones</p><p class="text-xs text-gray-400">30 dias</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;

        // Re-attach event listeners for detail page
        setupDetailPageListeners(product);
    }

    // Load related products
    loadRelatedProducts(product);
}

function setupDetailPageListeners(product) {
    // Quantity buttons
    const qtyInput = document.getElementById('qty-input');
    const qtyMinus = document.getElementById('qty-minus');
    const qtyPlus = document.getElementById('qty-plus');

    if (qtyMinus && qtyInput) {
        qtyMinus.addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            if (val > 1) qtyInput.value = val - 1;
        });
    }
    if (qtyPlus && qtyInput) {
        qtyPlus.addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            const max = parseInt(qtyInput.max) || 99;
            if (val < max) qtyInput.value = val + 1;
        });
    }

    // Add to cart
    const addToCartBtn = document.getElementById('add-to-cart-detail');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const quantity = parseInt(document.getElementById('qty-input')?.value || 1);
            for (let i = 0; i < quantity; i++) {
                cart.addItem({
                    id: product.id,
                    name: product.name,
                    brand: product.brand_name || '',
                    price: parseFloat(product.price)
                });
            }
        });
    }

    // WhatsApp buy
    const whatsappBuy = document.getElementById('whatsapp-buy');
    if (whatsappBuy) {
        whatsappBuy.addEventListener('click', () => {
            const quantity = document.getElementById('qty-input')?.value || 1;
            const message = `*Hola! Me interesa este producto:*%0A%0A` +
                `*${product.name}*%0A` +
                `Marca: ${product.brand_name || ''}%0A` +
                `Precio: ${formatPrice(product.price)}%0A` +
                `Cantidad: ${quantity}`;
            const url = `https://wa.me/${CONFIG.whatsappNumber}?text=${message}`;
            window.open(url, '_blank');
        });
    }
}

async function loadRelatedProducts(product) {
    const relatedSection = document.querySelector('.py-20.bg-black .grid.grid-cols-1');
    if (!relatedSection) return;

    const params = { per_page: 4 };
    if (product.category_slug) params.category = product.category_slug;

    const result = await loadProducts(params);
    if (!result.success || !result.data) return;

    const related = result.data.filter(p => p.id !== product.id).slice(0, 4);

    if (related.length === 0) {
        relatedSection.parentElement.style.display = 'none';
        return;
    }

    relatedSection.innerHTML = related.map(p => {
        const img = getProductImage(p);
        const grad = getProductGradient(p.family_name);
        return `
            <div class="bg-gray-800/50 backdrop-blur rounded-lg overflow-hidden hover:transform hover:scale-105 transition duration-300 border border-gray-700 hover:border-amber-500">
                <div class="h-48 bg-gradient-to-br ${grad} flex items-center justify-center">
                    ${img ? `<img src="${img}" alt="${p.name}" class="w-full h-full object-cover">` : '<i class="fas fa-wine-bottle text-6xl text-white/30"></i>'}
                </div>
                <div class="p-4">
                    <span class="text-xs text-amber-400">${p.brand_name || ''}</span>
                    <h3 class="font-semibold mb-2">${p.name}</h3>
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-amber-400">${formatPrice(p.price)}</span>
                        <a href="producto-detalle.html?id=${p.id}" class="bg-amber-500 text-black px-3 py-1 rounded text-sm hover:bg-amber-400 transition">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}
