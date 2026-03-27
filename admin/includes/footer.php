</div><!-- /admin-content -->
</div><!-- /admin-main -->

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/chat.css?v=3.9">
<style>
    /* Custom Admin Chat Style - Override to match User AI Theme but keep Admin Actions */
    .ps-chat-trigger { 
        background: radial-gradient(circle at 30% 30%, #ff1a35, #d70018) !important;
        box-shadow: 0 10px 30px rgba(215, 0, 24, 0.5), inset 0 4px 10px rgba(255, 255, 255, 0.3) !important;
    }
    .ps-chat-header { 
        background: rgba(255, 255, 255, 0.3) !important; 
        color: #1c1c1c !important;
    }
    .ps-chat-user {
        background: #d70018 !important;
        box-shadow: 0 8px 20px rgba(215, 0, 24, 0.2) !important;
    }
    .ps-chat-window {
        overscroll-behavior: contain !important;
    }
    .ps-chat-messages {
        overscroll-behavior: contain !important;
    }
    .ps-chat-action-block {
        background: rgba(215, 0, 24, 0.05);
        border: 1px solid rgba(215, 0, 24, 0.1);
        border-radius: 12px;
        padding: 12px;
        margin: 10px 0;
    }
    .btn-action-confirm {
        background: #d70018;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(215, 0, 24, 0.2);
    }
    .btn-action-confirm:hover {
        background: #b50014;
        transform: translateY(-2px);
    }
</style>

<!-- Admin Chat Widget (Unified Look) -->
<div class="ps-chat-widget">
    <div class="ps-chat-trigger" id="chatTrigger">
        <div class="ps-chat-sparkle-container">
            <svg class="ps-chat-sparkle s1" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/></svg>
            <svg class="ps-chat-sparkle s2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/></svg>
            <svg class="ps-chat-sparkle s3" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#FFD700"/></svg>
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
                    <span class="ps-chat-online-dot"></span> Trợ lý Admin AI
                </div>
            </div>
            <div class="ps-chat-header-actions">
                <span class="ps-chat-action-btn ps-chat-expand-btn" id="chatExpand" title="Mở rộng">⤢</span>
                <span class="ps-chat-action-btn ps-chat-refresh-btn" id="chatRefresh" title="Làm mới">✨</span>
                <span class="ps-chat-close" id="chatClose">✕</span>
            </div>
        </div>
        
        <div class="ps-chat-messages" id="chatMessages">
            <!-- JS will render Welcome View or Messages here -->
        </div>

        <div class="ps-chat-typing" id="typingIndicator">
            <div class="ps-chat-dot"></div><div class="ps-chat-dot"></div><div class="ps-chat-dot"></div>
            <span>AI đang truy xuất dữ liệu...</span>
        </div>

        <div class="ps-chat-input-area">
            <div class="ps-chat-input-wrapper">
                <input type="text" id="chatInput" placeholder="VD: Hôm nay có bao nhiêu đơn hàng?">
                <button class="ps-chat-send-btn" id="chatSend">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2L15 22L11 13L2 9L22 2Z" fill="white"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/chat_admin.js?v=<?= time() ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
