// Cart JS placeholder (cart page use inline scripts for simplicity)
// Global cart count refresh
async function refreshCartCount() {
    try {
        const res = await fetch(window.BASE_URL + '/api/cart.php');
        const d = await res.json();
        if (d.success) updateCartBadge(d.count || 0);
    } catch (e) { }
}
