<?php
// Footer
?>
<footer class="main-footer-v2">
    <div class="footer-top-v2">
        <div class="container">
            <div class="footer-grid-v2">
                <div class="f-col-about">
                    <div class="f-logo">
                        <span class="f-logo-icon">📱</span>
                        <span class="f-logo-text">PhoneStore</span>
                    </div>
                    <p class="f-desc">Chuyên cung cấp điện thoại chính hãng từ các thương hiệu hàng đầu thế giới với mức giá tốt nhất và dịch vụ hậu mãi chu đáo.</p>
                    <div class="f-social">
                        <a href="#" class="s-link">Facebook</a>
                        <a href="#" class="s-link">Zalo</a>
                        <a href="#" class="s-link">Tiktok</a>
                    </div>
                </div>
                <div class="f-col-links">
                    <h4>Tổng đài hỗ trợ</h4>
                    <ul class="f-list">
                        <li>Gọi mua: <strong>1800.6789</strong> (7:30 - 22:00)</li>
                        <li>Khiếu nại: <strong>1800.1234</strong> (8:00 - 21:30)</li>
                        <li>Bảo hành: <strong>1800.5678</strong> (8:00 - 21:00)</li>
                    </ul>
                </div>
                <div class="f-col-links">
                    <h4>Giới thiệu</h4>
                    <ul class="f-list">
                        <li><a href="<?= BASE_URL ?>/gioi-thieu.php">Về PhoneStore</a></li>
                        <li><a href="<?= BASE_URL ?>/gioi-thieu.php#tam-nhin">Tầm nhìn & Sứ mệnh</a></li>
                        <li><a href="<?= BASE_URL ?>/gioi-thieu.php#cam-ket">Cam kết chất lượng</a></li>
                        <li><a href="<?= BASE_URL ?>/gioi-thieu.php#chinh-sach">Chính sách chung</a></li>
                    </ul>
                </div>
                <div class="f-col-payment">
                    <h4>Liên hệ</h4>
                    <ul class="f-list">
                        <li><a href="<?= BASE_URL ?>/lien-he.php">Thông tin liên hệ</a></li>
                        <li><a href="<?= BASE_URL ?>/lien-he.php#ban-do">Bản đồ cửa hàng</a></li>
                        <li><a href="<?= BASE_URL ?>/lien-he.php#gui-loi-nhan">Gửi lời nhắn</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom-v2">
        <div class="container">
            <div class="fb-wrapper">
                <p>© 2026 <strong>PhoneStore</strong>. Bản quyền thuộc về Công ty TNHH PhoneStore Việt Nam.</p>
                <div class="fb-links">
                    <a href="#">Điều khoản sử dụng</a>
                    <a href="#">Chính sách bảo mật</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chat.css?v=3.8">

<!-- Chat Widget V3 (Unique Mode) -->
<div class="ps-chat-widget">
    <div class="ps-chat-trigger" id="chatTrigger">
        <div class="ps-chat-sparkle-container">
            <svg class="ps-chat-sparkle s1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/>
            </svg>
            <svg class="ps-chat-sparkle s2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/>
            </svg>
            <svg class="ps-chat-sparkle s3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/>
            </svg>
        </div>
    </div>
    <div class="ps-chat-window" id="chatWindow">
        <!-- Resize handles -->
        <div class="ps-chat-resizer ps-chat-resizer-t"></div>
        <div class="ps-chat-resizer ps-chat-resizer-l"></div>
        <div class="ps-chat-resizer ps-chat-resizer-tl"></div>
        
        <div class="ps-chat-header">
            <div class="ps-chat-title">
                <img src="<?= BASE_URL ?>/assets/images/chat_robot_avatar.png" alt="Robot" class="ps-chat-avatar">
                <div class="ps-chat-header-info">
                    <span class="ps-chat-online-dot"></span> Trợ lý PhoneStore
                </div>
            </div>
            <div class="ps-chat-header-actions">
                <span class="ps-chat-action-btn" id="chatHistory" title="Lịch sử trò chuyện">🕒</span>
                <span class="ps-chat-action-btn ps-chat-expand-btn" id="chatExpand" title="Mở rộng">⤢</span>
                <span class="ps-chat-action-btn ps-chat-refresh-btn" id="chatRefresh" title="Làm mới cuộc trò chuyện">✨</span>
                <span class="ps-chat-close" id="chatClose">✕</span>
            </div>
        </div>
        
        <!-- History Drawer -->
        <div class="ps-chat-history-drawer" id="historyDrawer">
            <div class="ps-chat-drawer-header">
                <span class="ps-chat-drawer-title">Lịch sử trò chuyện</span>
                <span class="ps-chat-action-btn" id="closeHistory">✕</span>
            </div>
            <div class="ps-chat-history-list" id="historyList">
                <div class="ps-chat-history-item">
                    <p>Đang tải lịch sử...</p>
                </div>
            </div>
        </div>

        <div class="ps-chat-messages" id="chatMessages">
            <!-- JS will render Welcome View or Messages here -->
        </div>

        <div class="ps-chat-typing" id="typingIndicator">
            <div class="ps-chat-dot"></div>
            <div class="ps-chat-dot"></div>
            <div class="ps-chat-dot"></div>
            <span>Gemini đang suy nghĩ...</span>
        </div>

        <div class="ps-chat-input-area">
            <div class="ps-chat-input-wrapper">
                <input type="text" id="chatInput" placeholder="Hỏi tôi bất cứ điều gì...">
                <button class="ps-chat-icon-btn" id="voiceBtn" title="Nhập bằng giọng nói">🎤</button>
                <button class="ps-chat-send-btn" id="chatSend">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22L11 13L2 9L22 2Z" fill="white"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Nhận diện trạng thái đăng nhập để tự động làm mới chatbot
    const currentUserId = '<?= isLoggedIn() ? $_SESSION['user_site']['id'] : "" ?>';
    const lastUserId = sessionStorage.getItem('last_chat_user');
    
    if (lastUserId !== null && lastUserId !== currentUserId) {
        sessionStorage.setItem('chat_needs_refresh', 'true');
    }
    sessionStorage.setItem('last_chat_user', currentUserId);
</script>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/chat.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/address_selector.js"></script>
<script src="<?= BASE_URL ?>/assets/js/cart.js"></script>
</body>
</html>
