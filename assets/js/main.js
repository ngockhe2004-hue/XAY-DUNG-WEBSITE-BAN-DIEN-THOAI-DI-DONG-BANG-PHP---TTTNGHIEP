// ============================================================
// MAIN JS - PhoneStore
// ============================================================

var BASE_URL = window.BASE_URL || document.currentScript?.src.split('/assets')[0] || '';

// ===== Scroll to top =====
window.addEventListener('scroll', () => {
    const btn = document.getElementById('scrollTop');
    if (btn) btn.classList.toggle('visible', window.scrollY > 400);
});

// ===== Auto-hide flash =====
setTimeout(() => {
    const flash = document.getElementById('flashMsg');
    if (flash) flash.style.opacity = '0', setTimeout(() => flash.remove(), 300);
}, 4000);

// ===== Live Search =====
let searchTimer;
const searchInput = document.getElementById('searchInput');
const searchDrop = document.getElementById('searchDropdown');
if (searchInput && searchDrop) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) { searchDrop.classList.remove('show'); return; }
        searchTimer = setTimeout(async () => {
            try {
                const res = await fetch(`${BASE_URL}/api/search.php?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                if (!data.products?.length) { searchDrop.classList.remove('show'); return; }
                searchDrop.innerHTML = data.products.map(p => `
                    <a class="search-item" href="${BASE_URL}/product_detail.php?id=${p.ma_sanpham}">
                        <img src="${p.anh || 'https://placehold.co/44x44/1a1a26/6c63ff?text=SP'}" alt="">
                        <div class="search-item-info">
                            <div class="name">${p.ten_sanpham}</div>
                            <div class="price">${formatVND(p.gia_thap)}</div>
                        </div>
                    </a>
                `).join('');
                searchDrop.classList.add('show');
            } catch (e) { }
        }, 300);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.search-bar')) searchDrop.classList.remove('show');
    });
}

// ===== Cart count update =====
function updateCartBadge(count) {
    const el = document.getElementById('cartCount');
    if (el) {
        el.textContent = count;
        el.style.transform = 'scale(1.4)';
        setTimeout(() => el.style.transform = '', 200);
    }
}

// ===== Format price =====
function formatVND(n) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(n)) + ' ₫';
}

// ===== Wishlist toggle (global) =====
async function toggleWishlist(btn, productId) {
    const bUrl = btn.closest('[data-base-url]')?.dataset.baseUrl || BASE_URL || window.BASE_URL || '';
    try {
        const res = await fetch(`${BASE_URL}/api/wishlist.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ma_sanpham: productId })
        });
        const data = await res.json();
        if (data.success) {
            if (data.action === 'added') {
                btn.textContent = '❤️';
                btn.classList.add('wished');
                showToast('Đã thêm vào yêu thích!', 'success');
            } else {
                btn.textContent = '♡';
                btn.classList.remove('wished');
                showToast('Đã xóa khỏi yêu thích', 'info');
            }
        } else {
            if (data.message?.includes('đăng nhập')) {
                window.location.href = BASE_URL + '/login.php';
            } else {
                showToast(data.message || 'Thất bại', 'error');
            }
        }
    } catch (e) { showToast('Lỗi kết nối!', 'error'); }
}

// ===== Quick Add to Cart (product card) =====
async function quickAddToCart(productId, btn) {
    // Get first available variant
    try {
        const res = await fetch(`${BASE_URL}/api/variants.php?id=${productId}`);
        const data = await res.json();
        if (!data.success || !data.variant) {
            // Redirect to detail page if multiple variants
            window.location.href = `${BASE_URL}/product_detail.php?id=${productId}`;
            return;
        }
        await addToCart(data.variant.ma_bienthe, 1, btn);
    } catch (e) {
        window.location.href = `${BASE_URL}/product_detail.php?id=${productId}`;
    }
}

// ===== Add to cart =====
async function addToCart(maBienthe, soLuong = 1, btn = null) {
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Đang thêm...'; }
    try {
        const res = await fetch(`${BASE_URL}/api/cart.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ma_bienthe: maBienthe, so_luong: soLuong })
        });
        const data = await res.json();
        if (data.success) {
            updateCartBadge(data.cart_count);
            showToast('✅ Đã thêm vào giỏ hàng!', 'success');
            if (btn) { btn.textContent = '✅ Đã thêm!'; setTimeout(() => { btn.disabled = false; btn.textContent = '🛒 Thêm vào giỏ'; }, 2000); }
        } else {
            if (data.message?.includes('đăng nhập')) {
                window.location.href = BASE_URL + '/login.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            }
            showToast('❌ ' + (data.message || 'Thêm thất bại'), 'error');
            if (btn) { btn.disabled = false; btn.textContent = '🛒 Thêm vào giỏ'; }
        }
    } catch (e) {
        showToast('Lỗi kết nối!', 'error');
        if (btn) { btn.disabled = false; btn.textContent = '🛒 Thêm vào giỏ'; }
    }
}

// ===== Toast notification =====
function showToast(message, type = 'success') {
    const existing = document.querySelectorAll('.toast-msg');
    existing.forEach(t => t.remove());
    const colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const toast = document.createElement('div');
    toast.className = 'toast-msg';
    toast.innerHTML = message;
    toast.style.cssText = `
        position:fixed; bottom:28px; left:50%; transform:translateX(-50%) translateY(80px);
        background:${colors[type] || colors.success}; color:#fff;
        padding:12px 24px; border-radius:50px; font-size:14px; font-weight:600;
        box-shadow:0 8px 32px rgba(0,0,0,0.3); z-index:9999;
        transition:transform 0.3s ease; white-space:nowrap;
    `;
    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.transform = 'translateX(-50%) translateY(0)'; });
    setTimeout(() => {
        toast.style.transform = 'translateX(-50%) translateY(80px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== Image lazy load error fallback =====
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', () => {
            img.src = 'https://placehold.co/400x400/1a1a26/6c63ff?text=No+Image';
        });
    });
});
