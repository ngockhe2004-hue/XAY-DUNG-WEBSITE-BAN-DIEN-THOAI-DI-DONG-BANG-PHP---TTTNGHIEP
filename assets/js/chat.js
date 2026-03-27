document.addEventListener('DOMContentLoaded', function() {
    const chatTrigger = document.getElementById('chatTrigger');
    const chatWindow = document.getElementById('chatWindow');
    const chatClose = document.getElementById('chatClose');
    const chatRefresh = document.getElementById('chatRefresh');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatMessages = document.getElementById('chatMessages');
    const chatSuggestions = document.getElementById('chatSuggestions');
    const typingIndicator = document.getElementById('typingIndicator');
    const voiceBtn = document.getElementById('voiceBtn');
    
    let currentSessionId = '';

    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Cấu hình Marked.js
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true
        });
    }

    const chatHistory = document.getElementById('chatHistory');
    const chatExpand = document.getElementById('chatExpand');
    const historyDrawer = document.getElementById('historyDrawer');
    const historyList = document.getElementById('historyList');
    const closeHistory = document.getElementById('closeHistory');

    // Cấu hình marked để mở link trong tab mới
    if (typeof marked !== 'undefined') {
        const renderer = new marked.Renderer();
        // Hỗ trợ cả marked cũ và mới (v4+)
        renderer.link = function(arg1, arg2, arg3) {
            let href, title, text;
            if (typeof arg1 === 'object' && arg1 !== null) {
                href = arg1.href;
                title = arg1.title;
                text = arg1.text;
            } else {
                href = arg1;
                title = arg2;
                text = arg3;
            }
            return `<a href="${href}" title="${title || ''}" target="_blank" rel="noopener noreferrer">${text}</a>`;
        };
        marked.setOptions({ renderer: renderer });
    }

    // Luôn ưu tiên khôi phục Session ID cũ nếu có trong tab này
    currentSessionId = sessionStorage.getItem('ps_chat_session_id') || '';

    // Khôi phục trạng thái mở/đóng từ sessionStorage
    const isChatOpen = sessionStorage.getItem('ps_chat_open') === 'true';
    if (isChatOpen) {
        if (!currentSessionId) {
            currentSessionId = generateSessionId();
            sessionStorage.setItem('ps_chat_session_id', currentSessionId);
        }
        chatWindow.classList.add('active');
        document.querySelector('.ps-chat-widget').classList.add('chat-open');
        setTimeout(() => {
            loadMessages(); 
        }, 100);
    }

    // Mở/Đóng cửa sổ chat
    chatTrigger.addEventListener('click', () => {
        const isOpen = chatWindow.classList.contains('active');
        const widget = document.querySelector('.ps-chat-widget');
        
        if (!isOpen) {
            // Nếu chưa có session (mở lần đầu trong tab này)
            if (!currentSessionId) {
                currentSessionId = generateSessionId();
                sessionStorage.setItem('ps_chat_session_id', currentSessionId);
            }
            chatWindow.classList.add('active');
            widget.classList.add('chat-open');
            sessionStorage.setItem('ps_chat_open', 'true');
            loadMessages(); // Đảm bảo load tin nhắn hoặc màn hình chào mừng ngay lập tức
            chatInput.focus();
        } else {
            chatWindow.classList.remove('active');
            chatWindow.classList.remove('expanded');
            widget.classList.remove('chat-open');
            sessionStorage.setItem('ps_chat_open', 'false');
        }
    });

    chatClose.addEventListener('click', () => {
        chatWindow.classList.remove('active');
        chatWindow.classList.remove('expanded');
        document.querySelector('.ps-chat-widget').classList.remove('chat-open');
        sessionStorage.setItem('ps_chat_open', 'false');
    });

    // Mở rộng/Thu nhỏ cửa sổ
    chatExpand.addEventListener('click', () => {
        chatWindow.classList.toggle('expanded');
    });

    // Resize Logic
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
            // Lưu kích thước vào localStorage nếu muốn
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

    // Quản lý Lịch sử
    chatHistory.addEventListener('click', () => {
        historyDrawer.classList.add('active');
        renderHistoryList();
    });

    closeHistory.addEventListener('click', () => {
        historyDrawer.classList.remove('active');
    });

    // Làm mới cuộc trò chuyện (Refresh)
    if (chatRefresh) {
        chatRefresh.addEventListener('click', () => {
            if (confirm('Bạn muốn kết thúc cuộc trò chuyện này và bắt đầu một cuộc trò chuyện mới?')) {
                currentSessionId = generateSessionId();
                sessionStorage.setItem('ps_chat_session_id', currentSessionId);
                chatMessages.innerHTML = '';
                chatWindow.classList.remove('chat-started');
                renderWelcomeView();
                chatInput.focus();
            }
        });
    }

    // Gửi tin nhắn
    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Đảm bảo tất cả link trong chat mở tab mới
    chatMessages.addEventListener('click', (e) => {
        if (e.target.tagName === 'A') {
            e.target.target = '_blank';
            e.target.rel = 'noopener noreferrer';
        }
    });

    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        // Ẩn welcome view khi bắt đầu chat
        if (!chatWindow.classList.contains('chat-started')) {
            chatWindow.classList.add('chat-started');
            chatMessages.innerHTML = '';
        }

        appendMessage(text, 'user');
        chatInput.value = '';
        
        // Hiện typing indicator
        typingIndicator.classList.add('active');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Trích xuất Product ID (nếu đang ở trang chi tiết sản phẩm)
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id') || null;

        try {
            const response = await fetch('api/chat/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    message: text,
                    ma_phien: currentSessionId,
                    current_url: window.location.href,
                    page_title: document.title,
                    product_id: productId
                })
            });
            const result = await response.json();
            
            typingIndicator.classList.remove('active');
            
            if (result.success) {
                // Xử lý Action JSON và hiệu ứng gõ chữ
                processUserAiResponse(result.ai_message);
            } else {
                // Nếu API trả về success: false, hiển thị thông báo lỗi cụ thể từ máy chủ
                appendMessage(result.message || 'Có lỗi xảy ra, vui lòng thử lại sau.', 'ai');
            }
        } catch (error) {
            typingIndicator.classList.remove('active');
            console.error('Error sending message:', error);
            appendMessage('Không thể kết nối với máy chủ.', 'ai');
        }
    }

    function processUserAiResponse(rawText) {
        // Tách phần text và phần Action JSON
        const actionRegex = /\[\[ACTION:\s*([\s\S]*?)\s*\]\]/g;
        let match;
        let cleanText = rawText;
        const actions = [];

        while ((match = actionRegex.exec(rawText)) !== null) {
            cleanText = cleanText.replace(match[0], ''); // Luôn xóa chuỗi ACTION khỏi giao diện kể cả khi lỗi
            try {
                let jsonStr = match[1].replace(/```json/g, '').replace(/```/g, '').trim(); // Lọc markdown rác nếu AI lỡ chèn
                // Sử dụng new Function để parse lỏng lẻo (hỗ trợ key không ngoặc kép, nháy đơn) do AI hay nhầm lẫn
                let parsedAction = new Function("return " + jsonStr)();
                actions.push(parsedAction);
            } catch (e) { console.error("Lỗi parse Action JSON từ AI:", e, match[1]); }
        }

        // Add main text with typing effect
        appendMessage(cleanText.trim(), 'ai', true);

        // Process Action Blocks after a slight delay to let typing start
        setTimeout(() => {
            actions.forEach(action => {
                if (action.confirm) {
                    renderUserActionConfirmation(action);
                } else {
                    executeUserAction(action);
                }
            });
        }, 500);
    }

    function renderUserActionConfirmation(action) {
        let actionName = "Thực hiện tác vụ";
        let actionColor = "#ff1a35";
        
        switch (action.type) {
            case 'CANCEL_ORDER':
                actionName = `Hủy đơn hàng #${action.payload.order_id}`;
                break;
            case 'CANCEL_ALL_ORDERS':
                actionName = `Hủy TẤT CẢ đơn hàng chờ xác nhận`;
                break;
            case 'CLEAR_CART':
                actionName = "Xóa sạch giỏ hàng";
                break;
        }
        
        const div = document.createElement('div');
        div.className = 'ps-chat-msg ps-chat-ai';
        div.innerHTML = `
            <div class="ps-chat-avatar"><img src="assets/images/chat_robot_avatar.png" alt="AI"></div>
            <div class="ps-chat-msg-content" style="border: 2px solid ${actionColor}; background: #fff5f5; border-radius: 18px; padding: 15px;">
                <p style="margin-top:0; font-weight:bold; color:${actionColor}; display: flex; align-items: center; gap: 5px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
                    Xác nhận yêu cầu
                </p>
                <p style="margin-bottom: 12px; font-size: 14px;">Bạn có chắc chắn muốn thực hiện: <strong>${actionName}</strong>?</p>
                <div style="display: flex; gap: 10px;">
                    <button class="ps-chat-action-btn confirm" style="flex: 1; background:${actionColor}; color:white; border:none; padding:10px; border-radius:12px; cursor:pointer; font-weight:600; transition: opacity 0.2s;">Xác nhận</button>
                    <button class="ps-chat-action-btn cancel" style="flex: 1; background:#e5e7eb; color:#374151; border:none; padding:10px; border-radius:12px; cursor:pointer; font-weight:600;">Hủy yêu cầu</button>
                </div>
            </div>
        `;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        const confirmBtn = div.querySelector('.confirm');
        const cancelBtn = div.querySelector('.cancel');

        confirmBtn.onclick = () => {
            confirmBtn.disabled = true;
            cancelBtn.style.display = 'none';
            confirmBtn.textContent = 'Đang xử lý...';
            executeUserAction(action, div);
        };

        cancelBtn.onclick = () => {
            div.innerHTML = `<div class="ps-chat-msg-content"> Đã hủy tác vụ.</div>`;
        };
    }

    async function executeUserAction(action, containerDiv = null) {
        try {
            if (action.type === 'REDIRECT') {
                appendMessage('🔄 Đang chuyển hướng...', 'ai');
                setTimeout(() => window.location.href = action.payload.url, 1500);
            } 
            else if (action.type === 'ADD_CART') {
                const response = await fetch('api/cart.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ma_sanpham: action.payload.id,
                        so_luong: action.payload.qty || 1
                    }) 
                });
                const res = await response.json();
                
                if (res.success) {
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) cartCountElement.textContent = res.cart_count || '?';
                    appendMessage('✅ Đã thêm sản phẩm vào giỏ hàng thành công!', 'ai');
                } else {
                    appendMessage('❌ Lỗi thêm giỏ hàng: ' + (res.message || 'Thiếu thông tin biến thể.'), 'ai');
                }
                if (containerDiv) containerDiv.remove();
            }
            else if (action.type === 'CANCEL_ALL_ORDERS') {
                // Cờ theo dõi trạng thái đang hủy (local to this execution)
                let isCancelling = false; 
                if (isCancelling) return; // Prevent multiple calls if somehow triggered
                isCancelling = true;
                appendMessage(`Đang xử lý hủy toàn bộ đơn hàng...`, 'ai');
                fetch('api/orders.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'cancel_all', id: 0 })
                })
                .then(r => r.json())
                .then(res => {
                    isCancelling = false;
                    if (res.success) {
                        appendMessage(`✅ ${res.message}`, 'ai');
                        appendMessage(`[HỆ THỐNG] Vui lòng F5 tải lại trang để cập nhật danh sách đơn hàng.`, 'ai');
                    } else {
                        appendMessage(`❌ Lỗi hủy đơn hàng: ${res.message}`, 'ai');
                    }
                    if (containerDiv) containerDiv.remove();
                })
                .catch(err => {
                    isCancelling = false;
                    console.error('Error cancelling all orders:', err);
                    appendMessage(`❌ Lỗi hệ thống khi tải data.`, 'ai');
                    if (containerDiv) containerDiv.remove();
                });
            }
            else if (action.type === 'CLEAR_CART') {
                const response = await fetch('api/cart.php?clear=1', { method: 'DELETE' });
                const res = await response.json();
                if (res.success) {
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) cartCountElement.textContent = '0';
                    appendMessage('✅ Đã dọn sạch giỏ hàng của bạn thành công.', 'ai');
                } else {
                    appendMessage('❌ Lỗi khi dọn giỏ hàng: ' + res.message, 'ai');
                }
                if (containerDiv) containerDiv.remove();
            }
            else if (action.type === 'ADD_WISHLIST') {
                const response = await fetch('api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ma_sanpham: action.payload.id })
                });
                const res = await response.json();
                if (res.success) {
                    appendMessage(`✅ ${res.message}`, 'ai');
                } else {
                    appendMessage(`❌ Lỗi: ${res.message}`, 'ai');
                }
                if (containerDiv) containerDiv.remove();
            }
            else if (action.type === 'CANCEL_ORDER') {
                const response = await fetch('api/orders.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: action.payload.order_id, action: 'cancel' })
                });
                const res = await response.json();
                if (res.success) {
                    appendMessage(`✅ Đã hủy đơn hàng #${action.payload.order_id} thành công.`, 'ai');
                } else {
                    appendMessage(`❌ Không thể hủy đơn hàng #${action.payload.order_id}: ` + res.message, 'ai');
                }
                if (containerDiv) containerDiv.remove();
            }
        } catch(e) {
            console.error('Lỗi thực thi Action:', e);
            if (containerDiv) containerDiv.innerHTML = `<div class="ps-chat-msg-content text-danger"> Lỗi hệ thống khi thực thi tác vụ.</div>`;
        }
    }

    async function loadMessages() {
        if (!currentSessionId) return;
        try {
            const response = await fetch(`api/chat/get_messages.php?ma_phien=${currentSessionId}`);
            const result = await response.json();
            if (result.success) {
                chatMessages.innerHTML = '';
                if (result.messages && result.messages.length > 0) {
                    chatWindow.classList.add('chat-started');
                    result.messages.forEach(msg => {
                        appendMessage(msg.noi_dung, msg.nguoi_gui);
                    });
                } else {
                    chatWindow.classList.remove('chat-started');
                    renderWelcomeView();
                }
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            renderWelcomeView(); // Fallback
        }
    }

    function renderWelcomeView() {
        chatMessages.innerHTML = `
            <div class="ps-chat-welcome-view">
                <div class="ps-chat-welcome-avatar">
                    <img src="assets/images/chat_robot_avatar.png" alt="Robot">
                </div>
                <h2 class="ps-chat-welcome-title">Chào bạn! Mình là Trợ lý PhoneStore 👋</h2>
                <p class="ps-chat-welcome-desc" style="margin-bottom:20px;">Rất vui được gặp bạn! Mình có thể giúp gì cho bạn về thông tin Điện thoại, hay đơn hàng không? ✨</p>
                
                <!-- Gợi ý hành động nhanh -->
                <div class="ps-chat-suggestions" style="justify-content:center;">
                    <button class="ps-chat-chip" onclick="document.getElementById('chatInput').value='🎁 Khám phá Siêu ưu đãi hôm nay'; document.getElementById('chatSend').click();">🎁 Siêu ưu đãi hôm nay</button>
                    <button class="ps-chat-chip" onclick="document.getElementById('chatInput').value='📱 Sản phẩm nào mới nhất?'; document.getElementById('chatSend').click();">📱 Sản phẩm mới nhất</button>
                    <button class="ps-chat-chip" onclick="document.getElementById('chatInput').value='🔍 Tôi muốn tra cứu đơn hàng mới đặt'; document.getElementById('chatSend').click();">🔍 Tra cứu đơn hàng</button>
                    <button class="ps-chat-chip" onclick="document.getElementById('chatInput').value='📞 Tôi cần hỗ trợ tư vấn trực tiếp'; document.getElementById('chatSend').click();">📞 Liên hệ tư vấn</button>
                </div>

                <div class="ps-chat-history-link" id="viewHistoryLink" style="margin-top: 30px; display:flex; align-items:center; justify-content:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Xem lại lịch sử trò chuyện cũ
                </div>
            </div>
        `;

        const historyLink = document.getElementById('viewHistoryLink');
        if (historyLink) {
            historyLink.onclick = () => {
                historyDrawer.classList.add('active');
                renderHistoryList();
            };
        }
    }

    function appendMessage(text, type, shouldType = false) {
        const div = document.createElement('div');
        div.className = `ps-chat-msg ps-chat-${type}`;
        
        if (type === 'ai') {
            const avatarHtml = `
                <div class="ps-chat-avatar">
                    <img src="assets/images/chat_robot_avatar.png" alt="AI">
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
                        // Bỏ qua các thẻ HTML (in ra ngay lập tức) để không làm gãy cấu trúc DOM
                        if (finalHTML.charAt(i) === '<') {
                            while (i < finalHTML.length && finalHTML.charAt(i) !== '>') { i++; }
                            i++;
                        } else {
                            // Tự động tăng tốc độ gõ nếu văn bản quá dài
                            i += (finalHTML.length > 300 ? 5 : 2);
                        }
                        
                        if (i > finalHTML.length) i = finalHTML.length;
                        
                        contentDiv.innerHTML = finalHTML.substring(0, i);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        setTimeout(typeEffect, 15);
                    } else {
                        // Sau khi gõ xong mới hiện nút Copy
                        contentDiv.innerHTML = finalHTML + '<span class="ps-chat-copy-btn">📋 Sao chép</span>';
                        setupCopyBtn(contentDiv, text);
                    }
                }
                typeEffect();
            } else {
                contentDiv.innerHTML = (typeof marked !== 'undefined' ? marked.parse(text) : text) + 
                                     '<span class="ps-chat-copy-btn">📋 Sao chép</span>';
                setupCopyBtn(contentDiv, text);
            }
        } else {
            div.textContent = text;
            chatMessages.appendChild(div);
        }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function setupCopyBtn(container, text) {
        const copyBtn = container.querySelector('.ps-chat-copy-btn');
        if (copyBtn) {
            copyBtn.onclick = () => {
                navigator.clipboard.writeText(text);
                copyBtn.innerHTML = '✅ Đã chép';
                setTimeout(() => copyBtn.innerHTML = '📋 Sao chép', 2000);
            };
        }
    }

    async function renderHistoryList() {
        historyList.innerHTML = '<div class="ps-chat-history-item"><p>Đang tải lịch sử...</p></div>';
        
        try {
            const response = await fetch('api/chat/get_history.php');
            const result = await response.json();
            
            if (result.success && result.sessions.length > 0) {
                historyList.innerHTML = '';
                result.sessions.forEach(sess => {
                    const item = document.createElement('div');
                    item.className = 'ps-chat-history-item';
                    const time = new Date(sess.thoi_gian_bat_dau).toLocaleString('vi-VN', {
                        day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
                    });
                    
                    const title = sess.ten_phien || sess.tin_nhan_dau || 'Trò chuyện mới';
                    const truncatedTitle = title.length > 35 ? title.substring(0, 35) + '...' : title;

                    item.innerHTML = `
                        <span class="ps-chat-history-item-icon">💬</span>
                        <div class="ps-chat-history-item-content">
                            <p title="${title}">${truncatedTitle}</p>
                            <div class="ps-chat-history-item-time">${time}</div>
                        </div>
                        <div class="ps-chat-history-item-actions">
                            <span class="ps-chat-history-action-btn rename-btn" title="Đổi tên">✏️</span>
                            <span class="ps-chat-history-action-btn delete-btn" title="Xóa">🗑️</span>
                        </div>
                    `;

                    // Load session when clicking content
                    item.querySelector('.ps-chat-history-item-content').onclick = (e) => {
                        e.stopPropagation();
                        loadPastSession(sess.ma_phien);
                    };

                    // Rename action
                    item.querySelector('.rename-btn').onclick = (e) => {
                        e.stopPropagation();
                        renameSession(sess.ma_phien, title);
                    };

                    // Delete action
                    item.querySelector('.delete-btn').onclick = (e) => {
                        e.stopPropagation();
                        deleteSession(sess.ma_phien);
                    };

                    historyList.appendChild(item);
                });
            } else {
                historyList.innerHTML = '<div class="ps-chat-history-item"><div class="ps-chat-history-item-content"><p>Chưa có lịch sử trò chuyện.</p></div></div>';
            }
        } catch (error) {
            console.error('Error fetching history:', error);
            historyList.innerHTML = '<div class="ps-chat-history-item"><p>Lỗi khi tải lịch sử.</p></div>';
        }
    }

    async function loadPastSession(sessionId) {
        currentSessionId = sessionId;
        historyDrawer.classList.remove('active');
        chatMessages.innerHTML = '<div class="ps-chat-msg ps-chat-ai">Đang tải cuộc trò chuyện cũ...</div>';
        
        try {
            const response = await fetch(`api/chat/get_session_messages.php?ma_phien=${sessionId}`);
            const result = await response.json();
            
            if (result.success) {
                chatMessages.innerHTML = '<div class="ps-chat-msg ps-chat-ai">Đây là nội dung cuộc trò chuyện cũ:</div>';
                if (result.messages && result.messages.length > 0) {
                    result.messages.forEach(msg => {
                        appendMessage(msg.noi_dung, msg.nguoi_gui);
                    });
                } else {
                    chatMessages.innerHTML += '<p>Không có tin nhắn nào trong phiên này.</p>';
                }
            }
        } catch (error) {
            console.error('Error loading past session:', error);
            chatMessages.innerHTML = '<div class="ps-chat-msg ps-chat-ai">Lỗi khi tải cuộc trò chuyện.</div>';
        }
    }

    async function deleteSession(sessionId) {
        if (!confirm('Bạn có chắc muốn xóa cuộc trò chuyện này?')) return;
        
        try {
            const response = await fetch('api/chat/delete_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ma_phien: sessionId })
            });
            const result = await response.json();
            if (result.success) {
                renderHistoryList(); // Refresh list
                if (currentSessionId === sessionId) {
                    currentSessionId = generateSessionId();
                    chatMessages.innerHTML = '<div class="ps-chat-msg ps-chat-ai">Cuộc trò chuyện hiện tại đã bị xóa. Tôi có thể giúp gì mới cho bạn?</div>';
                }
            }
        } catch (error) {
            console.error('Error deleting session:', error);
        }
    }

    async function renameSession(sessionId, oldName) {
        const newName = prompt('Nhập tên mới cho cuộc trò chuyện:', oldName);
        if (newName === null || newName.trim() === '' || newName === oldName) return;
        
        try {
            const response = await fetch('api/chat/rename_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    ma_phien: sessionId,
                    ten_moi: newName.trim()
                })
            });
            const result = await response.json();
            if (result.success) {
                renderHistoryList(); // Refresh list
            }
        } catch (error) {
            console.error('Error renaming session:', error);
        }
    }

    // Nhận diện giọng nói (Voice to Text)
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        recognition.lang = 'vi-VN';
        
        voiceBtn.addEventListener('click', () => {
            recognition.start();
            voiceBtn.innerHTML = '🔴';
        });

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            chatInput.value = transcript;
            voiceBtn.innerHTML = '🎤';
            sendMessage(); // Tự động gửi khi nhận diện xong
        };

        recognition.onend = () => {
            voiceBtn.innerHTML = '🎤';
        };

        recognition.onerror = () => {
            voiceBtn.innerHTML = '🎤';
        };
    } else {
        voiceBtn.style.display = 'none';
    }
});
