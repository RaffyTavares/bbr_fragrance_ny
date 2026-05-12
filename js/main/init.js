// BBR Fragance - Main: Page Initialization

// ===================================
// Page Initialization
// ===================================
window.currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    // Products page
    const productsGrid = document.getElementById('products-grid');
    if (productsGrid) {
        renderProducts();
    }

    // Home page featured products
    const featuredGrid = document.getElementById('featured-products');
    if (featuredGrid) {
        renderFeaturedProducts();
    }

    // Home page promo month
    loadPromoMonth();

    // Product detail page - handled by inline script in producto-detalle.html
});

console.log('Productos en carrito:', cart.getItemCount());
