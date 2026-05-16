// BBR Fragrance - Main: UI (Mobile Menu, Cart Modal, Search, Filters, Grid/List, Tabs, Smooth Scroll, Contact Form)

// ===================================
// Mobile Menu
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
    }
});

// ===================================
// Cart Modal
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const cartBtn = document.getElementById('cart-btn');
    const cartModal = document.getElementById('cart-modal');
    const closeCartBtn = document.getElementById('close-cart');
    const whatsappOrderBtn = document.getElementById('whatsapp-order');

    if (cartBtn && cartModal) {
        cartBtn.addEventListener('click', () => cartModal.classList.remove('hidden'));
    }

    if (closeCartBtn && cartModal) {
        closeCartBtn.addEventListener('click', () => cartModal.classList.add('hidden'));
        cartModal.addEventListener('click', (e) => {
            if (e.target === cartModal) cartModal.classList.add('hidden');
        });
    }

    if (whatsappOrderBtn) {
        whatsappOrderBtn.addEventListener('click', () => cart.sendToWhatsApp());
    }

    // Checkout button creates order via API
    const checkoutBtn = cartModal?.querySelector('.bg-amber-500.text-black');
    if (checkoutBtn && !checkoutBtn.classList.contains('add-to-cart')) {
        checkoutBtn.addEventListener('click', () => cart.createOrder());
    }
});

// ===================================
// Search Modal (API-based)
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('search-btn');
    const searchModal = document.getElementById('search-modal');
    const closeSearch = document.getElementById('close-search');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    let searchTimeout;

    if (searchBtn && searchModal) {
        searchBtn.addEventListener('click', () => {
            searchModal.classList.remove('hidden');
            if (searchInput) setTimeout(() => searchInput.focus(), 100);
        });
    }

    if (closeSearch && searchModal) {
        closeSearch.addEventListener('click', () => {
            searchModal.classList.add('hidden');
            if (searchInput) searchInput.value = '';
            if (searchResults) searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe para buscar productos...</p>';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !searchModal.classList.contains('hidden')) {
                searchModal.classList.add('hidden');
                if (searchInput) searchInput.value = '';
            }
        });

        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.add('hidden');
                if (searchInput) searchInput.value = '';
            }
        });
    }

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();

            if (query.length === 0) {
                searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe para buscar productos...</p>';
                return;
            }

            if (query.length < 2) {
                searchResults.innerHTML = '<p class="text-gray-400 text-center py-8">Escribe al menos 2 caracteres...</p>';
                return;
            }

            // Show loading
            searchResults.innerHTML = '<div class="text-center py-8"><div class="spinner mx-auto"></div></div>';

            // Debounce search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                const result = await apiGet(`/products/search?q=${encodeURIComponent(query)}`);

                if (!result.success || !result.data || result.data.length === 0) {
                    searchResults.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-search text-4xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400">No se encontraron productos para "${query}"</p>
                        </div>
                    `;
                    return;
                }

                const detailBase = getDetailUrl(0).replace('?id=0', '');
                searchResults.innerHTML = result.data.map(product => `
                    <a href="${detailBase}?id=${product.id}" class="flex items-center gap-4 p-4 hover:bg-gray-800 rounded-lg transition border-b border-gray-800 last:border-0">
                        <div class="w-16 h-16 bg-gradient-to-br ${getProductGradient(product.family_name)} rounded flex items-center justify-center flex-shrink-0 overflow-hidden">
                            ${getProductImage(product) ? `<img src="${getProductImage(product)}" class="w-full h-full object-cover">` : '<i class="fas fa-wine-bottle text-2xl text-white/30"></i>'}
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-white">${product.name}</h4>
                            <p class="text-sm text-amber-400">${product.brand_name || ''}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-amber-400">${formatPrice(product.price)}</p>
                            <p class="text-xs text-gray-400 capitalize">${product.category_name || ''}</p>
                        </div>
                    </a>
                `).join('');
            }, 300);
        });
    }
});

// ===================================
// Filters (productos.html)
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const filterCheckboxes = document.querySelectorAll('.filter-category, .filter-family, .filter-brand');
    const filterPrice = document.querySelectorAll('.filter-price');
    const filterOffers = document.getElementById('filter-offers');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const sortSelect = document.getElementById('sort-select');

    // Apply filters from URL on page load
    function applyFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        const family = urlParams.get('family');
        const offers = urlParams.get('offers');

        if (category) {
            const cb = document.querySelector(`.filter-category[value="${category}"]`);
            if (cb) cb.checked = true;
        }

        if (family) {
            const cb = document.querySelector(`.filter-family[value="${family}"]`);
            if (cb) cb.checked = true;
        }

        if (offers === '1') {
            const offersCheckbox = document.getElementById('filter-offers');
            if (offersCheckbox) offersCheckbox.checked = true;
        }
    }

    if (filterCheckboxes.length > 0 || filterPrice.length > 0 || filterOffers) {
        applyFiltersFromURL();

        filterCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                window.currentPage = 1;
                renderProducts();
            });
        });

        filterPrice.forEach(rb => {
            rb.addEventListener('change', () => {
                window.currentPage = 1;
                renderProducts();
            });
        });

        if (filterOffers) {
            filterOffers.addEventListener('change', () => {
                window.currentPage = 1;
                renderProducts();
            });
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                filterCheckboxes.forEach(cb => cb.checked = false);
                filterPrice.forEach(rb => rb.checked = false);
                if (filterOffers) filterOffers.checked = false;
                window.currentPage = 1;
                renderProducts();
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                window.currentPage = 1;
                renderProducts();
            });
        }
    }
});

// ===================================
// Grid/List View Toggle
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const gridViewBtn = document.getElementById('grid-view');
    const listViewBtn = document.getElementById('list-view');
    const productsGrid = document.getElementById('products-grid');

    if (gridViewBtn && listViewBtn && productsGrid) {
        gridViewBtn.addEventListener('click', () => {
            productsGrid.classList.remove('list-view');
            gridViewBtn.classList.add('bg-amber-500', 'text-black');
            gridViewBtn.classList.remove('bg-gray-800', 'text-white');
            listViewBtn.classList.remove('bg-amber-500', 'text-black');
            listViewBtn.classList.add('bg-gray-800', 'text-white');
        });

        listViewBtn.addEventListener('click', () => {
            productsGrid.classList.add('list-view');
            listViewBtn.classList.add('bg-amber-500', 'text-black');
            listViewBtn.classList.remove('bg-gray-800', 'text-white');
            gridViewBtn.classList.remove('bg-amber-500', 'text-black');
            gridViewBtn.classList.add('bg-gray-800', 'text-white');
        });
    }
});

// ===================================
// Tabs on Detail Page
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.dataset.tab;
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            tabContents.forEach(content => content.classList.add('hidden'));
            const targetContent = document.getElementById(targetTab);
            if (targetContent) targetContent.classList.remove('hidden');
        });
    });
});

// ===================================
// Smooth Scroll
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href === '#' || href.length <= 1) return;
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                window.scrollTo({ top: target.offsetTop - 80, behavior: 'smooth' });
            }
        });
    });
});

// ===================================
// Contact Form (WhatsApp-based)
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.querySelector('#contacto form');
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);
            const name = contactForm.querySelector('input[type="text"]')?.value || '';
            const email = contactForm.querySelector('input[type="email"]')?.value || '';
            const phone = contactForm.querySelector('input[type="tel"]')?.value || '';
            const message = contactForm.querySelector('textarea')?.value || '';

            const waMessage = `*Mensaje desde la web BBR Fragrance*%0A%0A` +
                `*Nombre:* ${name}%0A` +
                `*Email:* ${email}%0A` +
                `*Telefono:* ${phone}%0A` +
                `*Mensaje:* ${message}`;

            window.open(`https://wa.me/${CONFIG.whatsappNumber}?text=${waMessage}`, '_blank');
            contactForm.reset();
        });
    }
});

// ===================================
// Checkout Modal Logic
// ===================================
let checkoutStep = 1;
let checkoutPaymentMethod = null;
let checkoutBankData = null;

function openCheckout() {
    const modal = document.getElementById('checkout-modal');
    if (!modal) return;

    // Reset state
    checkoutStep = 1;
    checkoutPaymentMethod = null;
    document.getElementById('ck-name').value = '';
    document.getElementById('ck-phone').value = '';
    document.getElementById('ck-email').value = '';
    document.getElementById('ck-address').value = '';

    // Reset payment selection
    document.querySelectorAll('.ck-pay-btn').forEach(btn => {
        btn.classList.remove('border-amber-500');
        btn.classList.add('border-gray-700');
        btn.querySelector('.ck-pay-radio').className = 'fas fa-circle text-gray-600 ck-pay-radio';
    });
    const bankDetails = document.getElementById('ck-bank-details');
    if (bankDetails) bankDetails.classList.add('hidden');

    // Load bank details from settings
    loadBankSettings();

    // Show step 1
    showCheckoutStep(1);
    modal.classList.remove('hidden');
}

function closeCheckout() {
    const modal = document.getElementById('checkout-modal');
    if (modal) modal.classList.add('hidden');
}

async function loadBankSettings() {
    try {
        const res = await apiGet('/settings');
        if (res.success && res.data) {
            const s = res.data;

            // Parse multi-account bank data
            let accounts = [];
            if (s.bank_accounts) {
                try { accounts = JSON.parse(s.bank_accounts); } catch (e) {}
            }
            // Backwards compat: single-account fields
            if (!accounts.length && s.bank_name) {
                accounts = [{
                    bank_name: s.bank_name,
                    bank_account_type: s.bank_account_type || 'Ahorros',
                    bank_account_number: s.bank_account_number || '',
                    bank_account_holder: s.bank_account_holder || ''
                }];
            }

            // Render accounts list in checkout
            const list = document.getElementById('ck-bank-accounts-list');
            if (list) {
                if (accounts.length) {
                    list.innerHTML = accounts.map(acc => `
                        <div class="bg-gray-700/60 rounded-lg px-3 py-2.5 space-y-1">
                            <p class="font-semibold text-white">${acc.bank_name}</p>
                            <div class="flex justify-between"><span class="text-gray-400">Tipo:</span><span class="text-white">${acc.bank_account_type}</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">Cuenta:</span><span class="text-white font-mono">${acc.bank_account_number}</span></div>
                            ${acc.bank_account_holder ? `<div class="flex justify-between"><span class="text-gray-400">Titular:</span><span class="text-white">${acc.bank_account_holder}</span></div>` : ''}
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p class="text-gray-500 text-xs">No hay datos bancarios configurados aun.</p>';
                }
            }

            // Show/hide payment methods based on settings
            const payMethods = {
                cash: s.checkout_pay_cash,
                card: s.checkout_pay_card,
                transfer: s.checkout_pay_transfer,
                card_online: (s.checkout_pay_card_online === '1' && s.cardnet_enabled === '1') ? '1' : '0'
            };
            document.querySelectorAll('.ck-pay-btn').forEach(btn => {
                const method = btn.dataset.method;
                btn.style.display = (payMethods[method] !== '0') ? '' : 'none';
            });

            // If selected method was disabled, reset selection
            if (checkoutPaymentMethod && payMethods[checkoutPaymentMethod] === '0') {
                checkoutPaymentMethod = null;
            }
        }
    } catch (e) {
        console.error('Error loading bank settings:', e);
    }
}

function showCheckoutStep(step) {
    checkoutStep = step;
    const errorMsg = document.getElementById('ck-error-msg');
    if (errorMsg) { errorMsg.classList.add('hidden'); errorMsg.textContent = ''; }

    // Show/hide step content
    document.querySelectorAll('#checkout-content .checkout-step').forEach(el => {
        el.classList.add('hidden');
    });
    const activeStep = document.querySelector(`#checkout-content .checkout-step[data-step="${step}"]`);
    if (activeStep) activeStep.classList.remove('hidden');

    // Update step indicators
    document.querySelectorAll('#checkout-steps-bar .checkout-step-indicator').forEach(ind => {
        const s = parseInt(ind.dataset.step);
        const circle = ind.querySelector('span:first-child');
        const label = ind.querySelector('span:last-child');
        if (s <= step && step !== 'success') {
            circle.className = 'w-7 h-7 rounded-full bg-amber-500 text-black flex items-center justify-center text-sm font-bold';
            label.className = 'text-xs mt-1 text-amber-400';
        } else {
            circle.className = 'w-7 h-7 rounded-full bg-gray-700 text-gray-400 flex items-center justify-center text-sm font-bold';
            label.className = 'text-xs mt-1 text-gray-500';
        }
    });

    // Update buttons
    const btnBack = document.getElementById('ck-btn-back');
    const btnNext = document.getElementById('ck-btn-next');
    const footer = document.getElementById('checkout-footer');
    const stepsBar = document.getElementById('checkout-steps-bar');

    if (step === 'success') {
        footer.classList.add('hidden');
        stepsBar.classList.add('hidden');
        return;
    }

    footer.classList.remove('hidden');
    stepsBar.classList.remove('hidden');

    if (step === 1) {
        btnBack.classList.add('hidden');
        btnNext.innerHTML = 'Siguiente <i class="fas fa-arrow-right ml-2"></i>';
        btnNext.className = 'flex-1 bg-amber-500 text-black font-bold py-3 rounded-lg hover:bg-amber-400 transition';
    } else if (step === 2) {
        btnBack.classList.remove('hidden');
        btnNext.innerHTML = 'Siguiente <i class="fas fa-arrow-right ml-2"></i>';
        btnNext.className = 'flex-1 bg-amber-500 text-black font-bold py-3 rounded-lg hover:bg-amber-400 transition';
    } else if (step === 3) {
        btnBack.classList.remove('hidden');
        btnNext.innerHTML = '<i class="fas fa-check mr-2"></i>Confirmar Pedido';
        btnNext.className = 'flex-1 bg-green-500 text-white font-bold py-3 rounded-lg hover:bg-green-400 transition';
        populateSummary();
    }
}

function showCheckoutError(msg) {
    const errorMsg = document.getElementById('ck-error-msg');
    if (errorMsg) {
        errorMsg.textContent = msg;
        errorMsg.classList.remove('hidden');
    }
}

function validateStep(step) {
    if (step === 1) {
        const name = document.getElementById('ck-name').value.trim();
        const phone = document.getElementById('ck-phone').value.trim();
        const email = document.getElementById('ck-email').value.trim();

        if (!name) { showCheckoutError('El nombre es obligatorio.'); return false; }
        if (!phone) { showCheckoutError('El telefono es obligatorio.'); return false; }
        if (email && !isValidEmail(email)) { showCheckoutError('El correo electronico no es valido.'); return false; }
        return true;
    }

    if (step === 2) {
        if (!checkoutPaymentMethod) {
            showCheckoutError('Selecciona un metodo de pago.');
            return false;
        }
        return true;
    }

    return true;
}

function selectPaymentMethod(method) {
    checkoutPaymentMethod = method;

    document.querySelectorAll('.ck-pay-btn').forEach(btn => {
        const isSelected = btn.dataset.method === method;
        btn.classList.toggle('border-amber-500', isSelected);
        btn.classList.toggle('border-gray-700', !isSelected);
        const radio = btn.querySelector('.ck-pay-radio');
        radio.className = isSelected
            ? 'fas fa-check-circle text-amber-500 ck-pay-radio'
            : 'fas fa-circle text-gray-600 ck-pay-radio';
    });

    // Show/hide bank details
    const bankDetails = document.getElementById('ck-bank-details');
    if (bankDetails) {
        bankDetails.classList.toggle('hidden', method !== 'transfer');
    }
    // Show/hide cardnet info
    const cardnetInfo = document.getElementById('ck-cardnet-info');
    if (cardnetInfo) {
        cardnetInfo.classList.toggle('hidden', method !== 'card_online');
    }

    // Clear error if there was one
    const errorMsg = document.getElementById('ck-error-msg');
    if (errorMsg) errorMsg.classList.add('hidden');
}

function populateSummary() {
    const paymentLabels = {
        cash: 'Efectivo (Contra Entrega)',
        card: 'Tarjeta (Al Entregar)',
        transfer: 'Transferencia Bancaria',
        card_online: 'Tarjeta en Linea (Cardnet)'
    };

    // Customer data
    const name = document.getElementById('ck-name').value.trim();
    const phone = document.getElementById('ck-phone').value.trim();
    const email = document.getElementById('ck-email').value.trim();
    const address = document.getElementById('ck-address').value.trim();

    const custEl = document.getElementById('ck-summary-customer');
    if (custEl) {
        custEl.innerHTML = `
            <p><strong class="text-white">${name}</strong></p>
            <p><i class="fas fa-phone text-xs mr-1"></i>${phone}</p>
            ${email ? `<p><i class="fas fa-envelope text-xs mr-1"></i>${email}</p>` : ''}
            ${address ? `<p><i class="fas fa-map-marker-alt text-xs mr-1"></i>${address}</p>` : ''}
        `;
    }

    // Payment
    const payEl = document.getElementById('ck-summary-payment');
    if (payEl) payEl.textContent = paymentLabels[checkoutPaymentMethod] || checkoutPaymentMethod;

    // Items
    const itemsEl = document.getElementById('ck-summary-items');
    if (itemsEl && cart) {
        itemsEl.innerHTML = cart.items.map(item => `
            <div class="flex justify-between items-center py-1 border-b border-gray-700 last:border-0">
                <div class="flex-1">
                    <span class="text-white">${item.name}</span>
                    <span class="text-gray-500 text-xs ml-1">x${item.quantity}</span>
                </div>
                <span class="text-amber-400 font-medium ml-2">${formatPrice(item.price * item.quantity)}</span>
            </div>
        `).join('');
    }

    // Totals
    const totalsEl = document.getElementById('ck-summary-totals');
    const totalEl = document.getElementById('ck-summary-total');
    if (totalsEl && cart) {
        const subtotal = cart.getTotal();
        totalsEl.innerHTML = `
            <div class="flex justify-between"><span class="text-gray-400">Subtotal:</span><span class="text-white">${formatPrice(subtotal)}</span></div>
        `;
    }
    if (totalEl && cart) {
        totalEl.textContent = formatPrice(cart.getTotal());
    }
}

async function submitCheckoutOrder() {
    const btnNext = document.getElementById('ck-btn-next');
    const originalHTML = btnNext.innerHTML;
    btnNext.disabled = true;
    btnNext.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';

    const customerData = {
        name: document.getElementById('ck-name').value.trim(),
        phone: document.getElementById('ck-phone').value.trim(),
        email: document.getElementById('ck-email').value.trim(),
        address: document.getElementById('ck-address').value.trim(),
        paymentMethod: checkoutPaymentMethod
    };

    try {
        const result = await cart.submitOrder(customerData);

        if (!result.success) {
            showCheckoutError(result.message || 'Error al crear el pedido.');
            btnNext.disabled = false;
            btnNext.innerHTML = originalHTML;
            return;
        }

        // Si es pago online -> iniciar sesion Cardnet y redirigir
        if (checkoutPaymentMethod === 'card_online') {
            btnNext.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Conectando con Cardnet...';
            try {
                const sessRes = await fetch(`${API_BASE}/payments/cardnet/session`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: result.orderId })
                });
                const sessData = await sessRes.json();
                if (!sessData.success) {
                    showCheckoutError(sessData.message || 'No se pudo iniciar el pago en linea.');
                    btnNext.disabled = false;
                    btnNext.innerHTML = originalHTML;
                    return;
                }
                redirectToCardnet(sessData.data);
            } catch (e) {
                showCheckoutError('Error al conectar con Cardnet.');
                btnNext.disabled = false;
                btnNext.innerHTML = originalHTML;
            }
            return;
        }

        // Flujo normal: mostrar pantalla de exito
        const orderNumEl = document.getElementById('ck-order-number');
        if (orderNumEl) orderNumEl.textContent = result.orderNumber;
        showCheckoutStep('success');
    } catch (error) {
        showCheckoutError('Error de conexion. Verifica que el servidor este activo.');
        btnNext.disabled = false;
        btnNext.innerHTML = originalHTML;
    }
}

// Auto-submit a Cardnet con la SESSION recibida
function redirectToCardnet(sessionData) {
    // Modo simulador: el "redirect_url" ya contiene todos los parametros en la
    // query string. Hacemos una navegacion GET simple para que el usuario vea
    // la pagina del simulador (Aprobar/Rechazar/Cancelar).
    if (sessionData.simulator) {
        window.location.href = sessionData.redirect_url;
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = sessionData.redirect_url;
    form.style.display = 'none';

    const fields = {
        SESSION: sessionData.session,
        'SESSION-KEY': sessionData.session_key || '',
        ChannelId: 'web'
    };
    Object.entries(fields).forEach(([k, v]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = k;
        input.value = v;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

// Mostrar el modal de resultado de pago (al volver de Cardnet)
function handlePaymentReturn() {
    const params = new URLSearchParams(window.location.search);
    const status = params.get('payment');
    if (!status) return;

    const orderNumber = params.get('order') || '';
    const message = params.get('msg') || '';

    const modal = document.getElementById('payment-result-modal');
    const iconWrap = document.getElementById('pr-icon-wrap');
    const icon = document.getElementById('pr-icon');
    const title = document.getElementById('pr-title');
    const msg = document.getElementById('pr-message');
    const orderWrap = document.getElementById('pr-order-wrap');
    const orderEl = document.getElementById('pr-order-number');

    if (!modal) return;

    if (status === 'success') {
        iconWrap.className = 'w-20 h-20 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-6';
        icon.className = 'fas fa-check-circle text-green-400 text-4xl';
        title.textContent = 'Pago Aprobado!';
        msg.textContent = 'Tu pago se proceso exitosamente. Te enviaremos los detalles por correo.';
        // Limpiar carrito
        try { cart.items = []; cart.saveCart(); cart.updateCartUI(); } catch (e) {}
    } else if (status === 'cancelled') {
        iconWrap.className = 'w-20 h-20 rounded-full bg-yellow-500/20 flex items-center justify-center mx-auto mb-6';
        icon.className = 'fas fa-exclamation-triangle text-yellow-400 text-4xl';
        title.textContent = 'Pago Cancelado';
        msg.textContent = message || 'Cancelaste el pago. Tu pedido no fue procesado.';
    } else {
        iconWrap.className = 'w-20 h-20 rounded-full bg-red-500/20 flex items-center justify-center mx-auto mb-6';
        icon.className = 'fas fa-times-circle text-red-400 text-4xl';
        title.textContent = 'Pago Rechazado';
        msg.textContent = message || 'No se pudo procesar tu pago. Intenta nuevamente o usa otro metodo.';
    }

    if (orderNumber) {
        orderWrap.classList.remove('hidden');
        orderEl.textContent = orderNumber;
    } else {
        orderWrap.classList.add('hidden');
    }

    modal.classList.remove('hidden');

    document.getElementById('pr-close-btn')?.addEventListener('click', () => {
        modal.classList.add('hidden');
        // Limpiar query string
        const url = new URL(window.location.href);
        ['payment', 'order', 'msg'].forEach(p => url.searchParams.delete(p));
        window.history.replaceState({}, '', url.pathname + url.hash);
    }, { once: true });
}

// Checkout event listeners
document.addEventListener('DOMContentLoaded', () => {
    const checkoutModal = document.getElementById('checkout-modal');
    if (!checkoutModal) return;

    // Mostrar modal de resultado de pago si volvimos de Cardnet
    handlePaymentReturn();

    // Close checkout
    document.getElementById('close-checkout')?.addEventListener('click', closeCheckout);
    document.getElementById('checkout-overlay')?.addEventListener('click', closeCheckout);

    // Payment method selection
    document.querySelectorAll('.ck-pay-btn').forEach(btn => {
        btn.addEventListener('click', () => selectPaymentMethod(btn.dataset.method));
    });

    // Next button
    document.getElementById('ck-btn-next')?.addEventListener('click', () => {
        if (checkoutStep === 1) {
            if (validateStep(1)) showCheckoutStep(2);
        } else if (checkoutStep === 2) {
            if (validateStep(2)) showCheckoutStep(3);
        } else if (checkoutStep === 3) {
            submitCheckoutOrder();
        }
    });

    // Back button
    document.getElementById('ck-btn-back')?.addEventListener('click', () => {
        if (checkoutStep === 2) showCheckoutStep(1);
        else if (checkoutStep === 3) showCheckoutStep(2);
    });
});
