// Admin JS
function confirmDelete(msg) {
    return confirm(msg || 'Bạn có chắc chắn muốn xóa không?');
}

// Modal helpers
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});

// Flash message auto-dismiss
setTimeout(() => {
    document.querySelectorAll('[data-flash]').forEach(el => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    });
}, 4000);

// Toast for admin
function adminToast(msg, type = 'success') {
    const colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6' };
    const el = document.createElement('div');
    el.innerText = msg;
    el.style.cssText = `position:fixed;bottom:24px;right:24px;padding:12px 22px;background:${colors[type] || colors.success};color:#fff;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,0.4);transform:translateY(40px);transition:transform 0.3s;`;
    document.body.appendChild(el);
    requestAnimationFrame(() => el.style.transform = 'translateY(0)');
    setTimeout(() => { el.style.transform = 'translateY(40px)'; setTimeout(() => el.remove(), 300); }, 3500);
}
