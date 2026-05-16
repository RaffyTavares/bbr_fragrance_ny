// BBR Fragrance - Main: Shopping Cart (localStorage)

// ===================================
// Shopping Cart (localStorage - kept client-side)
// ===================================
class ShoppingCart {
    constructor() {
        this.items = this.loadCart();
        this.updateCartUI();
    }

    loadCart() {
        const stored = localStorage.getItem(CONFIG.cartStorageKey);
        if (!stored) return [];
        try {
            return JSON.parse(stored);
        } catch (e) {
            return [];
        }
    }

    saveCart() {
        localStorage.setItem(CONFIG.cartStorageKey, JSON.stringify(this.items));
    }

    addItem(product) {
        const productId = typeof product.id === 'string' ? parseInt(product.id) : product.id;
        const existingItem = this.items.find(item => item.id === productId);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: productId,
                name: product.name,
                brand: product.brand,
                price: product.price,
                quantity: 1
            });
        }

        this.saveCart();
        this.updateCartUI();
        this.showNotification('Producto agregado al carrito');
    }

    removeItem(productId) {
        const id = typeof productId === 'string' ? parseInt(productId) : productId;
        this.items = this.items.filter(item => item.id !== id);
        this.saveCart();
        this.updateCartUI();
    }

    updateQuantity(productId, quantity) {
        const id = typeof productId === 'string' ? parseInt(productId) : productId;
        const item = this.items.find(item => item.id === id);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(id);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartUI();
            }
        }
    }

    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getItemCount() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    }

    updateCartUI() {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            const count = this.getItemCount();
            cartCount.textContent = count;
            cartCount.classList.toggle('hidden', count === 0);
        }
        this.updateCartModal();
    }

    updateCartModal() {
        const cartItems = document.getElementById('cart-items');
        const cartTotal = document.getElementById('cart-total');
        if (!cartItems || !cartTotal) return;

        if (this.items.length === 0) {
            cartItems.innerHTML = '<p class="text-gray-400 text-center py-12">Tu carrito esta vacio</p>';
            cartTotal.textContent = formatPrice(0);
            return;
        }

        cartItems.innerHTML = this.items.map(item => `
            <div class="flex gap-4 mb-4 pb-4 border-b border-gray-800">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-900 to-pink-900 rounded flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-wine-bottle text-2xl text-white/30"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold mb-1">${item.name}</h4>
                    <p class="text-sm text-gray-400">${item.brand}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <button onclick="cart.updateQuantity(${item.id}, ${item.quantity - 1})" class="w-6 h-6 bg-gray-700 rounded flex items-center justify-center hover:bg-gray-600">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="w-8 text-center">${item.quantity}</span>
                        <button onclick="cart.updateQuantity(${item.id}, ${item.quantity + 1})" class="w-6 h-6 bg-gray-700 rounded flex items-center justify-center hover:bg-gray-600">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-amber-400">${formatPrice(item.price * item.quantity)}</p>
                    <button onclick="cart.removeItem(${item.id})" class="text-red-400 hover:text-red-300 text-sm mt-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');

        cartTotal.textContent = formatPrice(this.getTotal());
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-20 right-6 bg-green-500 text-white px-6 py-4 rounded-lg shadow-2xl z-50 animate-fade-in-up';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-2xl"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    generateWhatsAppMessage() {
        let message = '*Pedido desde BBR Fragrance*%0A%0A';
        this.items.forEach(item => {
            message += `*${item.name}*%0A`;
            message += `Marca: ${item.brand}%0A`;
            message += `Cantidad: ${item.quantity}%0A`;
            message += `Precio: ${formatPrice(item.price * item.quantity)}%0A%0A`;
        });
        message += `*Total: ${formatPrice(this.getTotal())}*`;
        return message;
    }

    sendToWhatsApp() {
        if (this.items.length === 0) {
            alert('Tu carrito esta vacio');
            return;
        }
        const message = this.generateWhatsAppMessage();
        const url = `https://wa.me/${CONFIG.whatsappNumber}?text=${message}`;
        window.open(url, '_blank');
    }

    // Open checkout modal instead of prompt()
    createOrder() {
        if (this.items.length === 0) {
            alert('Tu carrito esta vacio');
            return;
        }
        // Close cart modal and open checkout
        const cartModal = document.getElementById('cart-modal');
        if (cartModal) cartModal.classList.add('hidden');
        openCheckout();
    }

    // Actually submit the order to the API (called from checkout modal)
    async submitOrder(customerData) {
        const orderData = {
            customer_name: customerData.name,
            customer_phone: customerData.phone,
            customer_email: customerData.email || '',
            customer_address: customerData.address || '',
            payment_method: customerData.paymentMethod,
            notes: '',
            items: this.items.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                unit_price: item.price
            }))
        };

        const response = await fetch(`${API_BASE}/orders`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const result = await response.json();

        if (result.success) {
            const orderId = result.data?.id;
            const orderNumber = result.data?.order_number;
            // Para pago en linea NO limpiamos el carrito hasta confirmar pago
            if (customerData.paymentMethod !== 'card_online') {
                this.items = [];
                this.saveCart();
                this.updateCartUI();
            }
            return { success: true, orderId, orderNumber };
        } else {
            return { success: false, message: result.message || 'Error desconocido' };
        }
    }
}

// Initialize cart
const cart = new ShoppingCart();
