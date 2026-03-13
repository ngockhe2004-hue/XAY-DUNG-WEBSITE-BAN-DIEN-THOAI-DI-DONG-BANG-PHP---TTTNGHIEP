document.addEventListener('DOMContentLoaded', function() {
    const chatTrigger = document.getElementById('chatTrigger');
    const chatWindow = document.getElementById('chatWindow');
    const chatClose = document.getElementById('chatClose');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatMessages = document.getElementById('chatMessages');

    // Mở/Đóng cửa sổ chat
    chatTrigger.addEventListener('click', () => {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            loadMessages();
            chatInput.focus();
        }
    });

    chatClose.addEventListener('click', () => {
        chatWindow.classList.remove('active');
    });

    // Gửi tin nhắn
    chatSend.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        chatInput.value = '';

        try {
            const response = await fetch('api/chat/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const result = await response.json();
            if (result.success) {
                appendMessage(result.ai_message, 'ai');
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    async function loadMessages() {
        try {
            const response = await fetch('api/chat/get_messages.php');
            const result = await response.json();
            if (result.success) {
                chatMessages.innerHTML = '';
                result.messages.forEach(msg => {
                    appendMessage(msg.noi_dung, msg.nguoi_gui);
                });
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    function appendMessage(text, type) {
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.textContent = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
});
