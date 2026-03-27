document.addEventListener('DOMContentLoaded', function () {
    const chatTrigger = document.getElementById('chatTrigger');
    const chatWindow = document.getElementById('chatWindow');
    const chatClose = document.getElementById('chatClose');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatMessages = document.getElementById('chatMessages');
    const chatRefresh = document.getElementById('chatRefresh');
    const typingIndicator = document.getElementById('typingIndicator');
    const chatExpand = document.getElementById('chatExpand');

    let isExpanded = false;
    let maPhien = sessionStorage.getItem('admin_chat_phien') || 'adm_' + Math.random().toString(36).substr(2, 9);
    sessionStorage.setItem('admin_chat_phien', maPhien);

    // Toggle Chat
    chatTrigger.addEventListener('click', () => {
        chatWindow.classList.add('active');
        chatTrigger.style.display = 'none';
        if (!chatWindow.classList.contains('chat-started') && chatMessages.children.length === 0) {
            renderWelcomeView();
        }
        chatInput.focus();
    });

    chatClose.addEventListener('click', () => {
        chatWindow.classList.remove('active');
        chatTrigger.style.display = 'flex';
    });

    // Expand
    chatExpand.addEventListener('click', () => {
        chatWindow.classList.toggle('expanded');
    });

    // Resize Logic (Copy from user chat for consistency)
    const resizers = document.querySelectorAll('.ps-chat-resizer');
    let isResizing = false;

    resizers.forEach(resizer => {
        resizer.addEventListener('mousedown', initResize);
    });

    function initResize(e) {
        e.preventDefault();
        isResizing = true;
        
        const initialWidth = chatWindow.offsetWidth;
        const initialHeight = chatWindow.offsetHeight;
        const initialMouseX = e.clientX;
        const initialMouseY = e.clientY;
        
        const isLeft = e.target.classList.contains('ps-chat-resizer-l') || e.target.classList.contains('ps-chat-resizer-tl');
        const isTop = e.target.classList.contains('ps-chat-resizer-t') || e.target.classList.contains('ps-chat-resizer-tl');

        function resize(e) {
            if (!isResizing) return;
            
            if (isLeft) {
                const newWidth = initialWidth + (initialMouseX - e.clientX);
                chatWindow.style.setProperty('width', `${newWidth}px`, 'important');
            }
            
            if (isTop) {
                const newHeight = initialHeight + (initialMouseY - e.clientY);
                chatWindow.style.setProperty('height', `${newHeight}px`, 'important');
            }
            
            // Đảm bảo tin nhắn vẫn cuộn xuống dưới
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function stopResize() {
            isResizing = false;
            window.removeEventListener('mousemove', resize);
            window.removeEventListener('mouseup', stopResize);
            // Lưu kích thước vào localStorage
            localStorage.setItem('ps_chat_width', chatWindow.style.width);
            localStorage.setItem('ps_chat_height', chatWindow.style.height);
        }

        window.addEventListener('mousemove', resize);
        window.addEventListener('mouseup', stopResize);
    }

    // Khôi phục kích thước đã lưu
    const savedWidth = localStorage.getItem('ps_chat_width');
    const savedHeight = localStorage.getItem('ps_chat_height');
    if (savedWidth) chatWindow.style.setProperty('width', savedWidth, 'important');
    if (savedHeight) chatWindow.style.setProperty('height', savedHeight, 'important');

    // Send Message
    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Ẩn welcome view khi bắt đầu chat
        if (!chatWindow.classList.contains('chat-started')) {
            chatWindow.classList.add('chat-started');
            chatMessages.innerHTML = '';
        }

        addMessage(text, 'user');
        chatInput.value = '';
        typingIndicator.style.display = 'flex';
        scrollToBottom();

        try {
            const response = await fetch('/website%20bandienthoai/api/admin/chat/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, ma_phien: maPhien })
            });

            const data = await response.json();
            typingIndicator.style.display = 'none';

            if (data.success) {
                processAiResponse(data.message, true); // Thêm true để kích hoạt hiệu ứng gõ
            } else {
                addMessage('Lỗi: ' + data.message, 'ai');
            }
        } catch (error) {
            typingIndicator.style.display = 'none';
            addMessage('Lỗi kết nối máy chủ.', 'ai');
        }
    }

    function processAiResponse(rawText, shouldType = false) {
        // Tách phần text và phần Action JSON
        const actionRegex = /\[\[ACTION:\s*([\s\S]*?)\s*\]\]/g;
        let match;
        let cleanText = rawText;
        const actions = [];

        while ((match = actionRegex.exec(rawText)) !== null) {
            cleanText = cleanText.replace(match[0], ''); // Luôn xóa chuỗi ACTION khỏi giao diện kể cả khi lỗi
            try {
                let jsonStr = match[1].replace(/```json/g, '').replace(/```/g, '').trim(); // Lọc markdown rác nếu AI lỡ chèn
                let parsedAction = new Function("return " + jsonStr)();
                actions.push(parsedAction);
            } catch (e) { console.error("Lỗi parse Action JSON từ Admin AI:", e, match[1]); }
        }

        // Add main text with typing effect if requested
        addMessage(cleanText.trim(), 'ai', true, shouldType);

        // Add action blocks
        actions.forEach(action => {
            renderActionBlock(action);
        });
    }

    function renderActionBlock(action) {
        const div = document.createElement('div');
        div.className = 'ps-chat-msg ps-chat-ai';

        let actionDesc = "";
        let actionBtnText = "Thực hiện";

        switch (action.type) {
            case 'UPDATE_ORDER_STATUS':
                actionDesc = `📝 Đề xuất cập nhật đơn hàng #${action.id} sang trạng thái [${action.status}]`;
                actionBtnText = "Xác nhận cập nhật";
                break;
            case 'BAN_USER':
                actionDesc = `🚫 Đề xuất KHÓA tài khoản người dùng ID: ${action.id}`;
                actionBtnText = "Xác nhận KHÓA";
                break;
            default:
                actionDesc = `⚙️ Tác vụ hệ thống: ${action.type}`;
        }

        div.innerHTML = `
            <div class="ps-chat-action-block">
                <div style="font-size: 13px; margin-bottom: 8px;">${actionDesc}</div>
                ${action.important ? `<button class="btn-action-confirm" onclick="executeAdminAction(${JSON.stringify(action).replace(/"/g, '&quot;')}, this)">${actionBtnText}</button>` : ''}
            </div>
        `;
        chatMessages.appendChild(div);
        scrollToBottom();
    }

    window.executeAdminAction = async function (action, btn) {
        btn.disabled = true;
        btn.textContent = "Đang xử lý...";

        try {
            // Demo logic: Trong thực tế sẽ gọi api/admin/chat/execute_action.php
            const response = await fetch('/website%20bandienthoai/api/admin/chat/execute_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(action)
            });
            const data = await response.json();

            if (data.success) {
                btn.style.background = '#22c55e';
                btn.textContent = "✅ Thành công";
                // Khi xác nhận thành công, thêm tin nhắn nhưng không cuộn để người dùng vẫn thấy trạng thái nút
                addMessage(`Hệ thống: ${data.message}`, 'ai', false);
            } else {
                btn.style.background = '#ef4444';
                btn.textContent = "❌ Thất bại";
                alert(data.message);
            }
        } catch (e) {
            alert("Lỗi thực thi tác vụ.");
            btn.disabled = false;
        }
    };

    function addMessage(text, side, shouldScroll = true, shouldType = false) {
        const div = document.createElement('div');
        div.className = `ps-chat-msg ps-chat-${side}`;
        
        if (side === 'ai') {
            const avatarHtml = `
                <div class="ps-chat-avatar">
                    <img src="../assets/images/chat_robot_avatar.png" alt="AI">
                </div>
            `;
            const contentDiv = document.createElement('div');
            contentDiv.className = 'ps-chat-msg-content';
            
            div.innerHTML = avatarHtml;
            div.appendChild(contentDiv);
            chatMessages.appendChild(div);

            if (shouldType) {
                const finalHTML = typeof marked !== 'undefined' ? marked.parse(text) : text;
                let i = 0;
                
                function typeEffect() {
                    if (i < finalHTML.length) {
                        if (finalHTML.charAt(i) === '<') {
                            while (i < finalHTML.length && finalHTML.charAt(i) !== '>') { i++; }
                            i++;
                        } else {
                            i += (finalHTML.length > 300 ? 5 : 2);
                        }
                        
                        if (i > finalHTML.length) i = finalHTML.length;
                        
                        contentDiv.innerHTML = finalHTML.substring(0, i);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        setTimeout(typeEffect, 15);
                    } else {
                        contentDiv.innerHTML = finalHTML;
                    }
                }
                typeEffect();
            } else {
                contentDiv.innerHTML = typeof marked !== 'undefined' ? marked.parse(text) : text;
            }
        } else {
            div.textContent = text;
            chatMessages.appendChild(div);
        }
        
        if (shouldScroll && !shouldType) {
            scrollToBottom();
        }
    }

    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
    chatRefresh.addEventListener('click', () => {
        if (confirm('Bạn muốn làm mới cuộc hội thoại này?')) {
            maPhien = 'adm_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('admin_chat_phien', maPhien);
            chatMessages.innerHTML = '';
            chatWindow.classList.remove('chat-started');
            renderWelcomeView();
        }
    });

    function renderWelcomeView() {
        chatMessages.innerHTML = `
            <div class="ps-chat-welcome-view">
                <div class="ps-chat-welcome-avatar">
                    <img src="../assets/images/chat_robot_avatar.png" alt="Robot">
                </div>
                <h2 class="ps-chat-welcome-title">Chào Sếp! Trợ lý Admin AI đã sẵn sàng 🤖💼</h2>
                <p class="ps-chat-welcome-desc">Tôi có thể giúp ngài kiểm tra doanh thu, xử lý đơn hàng hoặc quản lý người dùng ngay lập tức. Ngài cần gì ạ?</p>
                
                <div class="ps-chat-welcome-grid">
                    <div class="ps-chat-welcome-chip" data-msg="Báo cáo doanh thu hôm nay">
                        <span class="emoji">📊</span>
                        <span>Doanh thu</span>
                    </div>
                    <div class="ps-chat-welcome-chip" data-msg="Có đơn hàng nào đang chờ không?">
                        <span class="emoji">🛒</span>
                        <span>Đơn hàng</span>
                    </div>
                    <div class="ps-chat-welcome-chip" data-msg="Thống kê người dùng mới">
                        <span class="emoji">👥</span>
                        <span>Người dùng</span>
                    </div>
                    <div class="ps-chat-welcome-chip" data-msg="Sản phẩm nào sắp hết hàng?">
                        <span class="emoji">📈</span>
                        <span>Tồn kho</span>
                    </div>
                </div>
            </div>
        `;

        // Gán sự kiện cho các chips
        document.querySelectorAll('.ps-chat-welcome-chip').forEach(chip => {
            chip.onclick = () => {
                chatInput.value = chip.getAttribute('data-msg');
                sendMessage();
            };
        });
    }

    // Kiểm tra và hiển thị Welcome View khi khởi tạo
    if (chatMessages.children.length === 0) {
        renderWelcomeView();
    }
});
