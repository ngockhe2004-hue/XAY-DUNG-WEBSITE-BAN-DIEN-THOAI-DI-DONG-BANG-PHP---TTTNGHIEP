<?php
$pageTitle = 'Liên hệ';
require_once __DIR__ . '/includes/header.php';
?>

<main class="contact-page">
    <section class="contact-hero">
        <div class="container">
            <h1>Kết nối với chúng tôi</h1>
            <p>Đội ngũ PhoneStore luôn sẵn sàng lắng nghe và hỗ trợ bạn 24/7.</p>
        </div>
    </section>

    <section class="contact-content section-padding">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Info -->
                <div class="contact-info">
                    <div class="info-card">
                        <h3>Thông tin liên hệ</h3>
                        <div class="info-item">
                            <span class="info-icon">📞</span>
                            <div class="info-text">
                                <strong>Tổng đài hỗ trợ</strong>
                                <p>Gọi mua: 1800.6789 (7:30 - 22:00)</p>
                                <p>Khiếu nại: 1800.1234 (8:00 - 21:30)</p>
                                <p>Bảo hành: 1800.5678 (8:00 - 21:00)</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📧</span>
                            <div class="info-text">
                                <strong>Email</strong>
                                <p>support@phonestore.com.vn</p>
                                <p>business@phonestore.com.vn</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <span class="info-icon">📍</span>
                            <div class="info-text">
                                <strong>Văn phòng chính</strong>
                                <p>Số 123 Đường Công Nghệ, Quận 1, TP. Hồ Chí Minh</p>
                            </div>
                        </div>
                    </div>

                    <div class="social-card">
                        <h4>Theo dõi chúng tôi</h4>
                        <div class="social-links">
                            <a href="#" class="social-btn fb">Facebook</a>
                            <a href="#" class="social-btn zl">Zalo</a>
                            <a href="#" class="social-btn tt">Tiktok</a>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div id="gui-loi-nhan" class="contact-form-wrap">
                    <div class="form-container">
                        <h3>Gửi lời nhắn cho chúng tôi</h3>
                        <p>Nếu bạn có thắc mắc hay góp ý, đừng ngần ngại gửi tin nhắn cho PhoneStore.</p>
                        
                        <form action="#" method="POST" class="main-contact-form">
                            <div class="form-group">
                                <label for="name">Họ và tên</label>
                                <input type="text" id="name" name="name" placeholder="Nhập họ tên của bạn" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" placeholder="Địa chỉ email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Số điện thoại</label>
                                    <input type="tel" id="phone" name="phone" placeholder="Số điện thoại của bạn" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subject">Chủ đề</label>
                                <select id="subject" name="subject">
                                    <option value="tu-van">Tư vấn mua hàng</option>
                                    <option value="bao-hanh">Hỗ trợ bảo hành</option>
                                    <option value="khieu-nai">Phản hồi/Khiếu nại</option>
                                    <option value="khac">Vấn đề khác</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="message">Nội dung</label>
                                <textarea id="message" name="message" rows="5" placeholder="Bạn muốn nhắn nhủ điều gì với PhoneStore?" required></textarea>
                            </div>
                            <button type="submit" class="btn-submit">Gửi tin nhắn ngay</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="ban-do" class="contact-map">
        <div class="map-placeholder">
            <div class="map-overlay">
                <span class="map-icon">🗺️</span>
                <h3>Tìm cửa hàng gần bạn nhất</h3>
                <p>Chúng tôi có hơn 50 cửa hàng trên toàn quốc để phục vụ bạn tốt hơn.</p>
                <a href="<?= BASE_URL ?>/products.php" class="btn-outline">Xem danh sách cửa hàng</a>
            </div>
        </div>
    </section>
</main>

<style>
.section-padding { padding: 80px 0; }
.contact-hero {
    background: linear-gradient(135deg, #e41e26 0%, #ff4b5c 100%);
    color: white;
    padding: 100px 0;
    text-align: center;
}
.contact-hero h1 { font-size: 3rem; margin-bottom: 15px; font-weight: 800; }
.contact-hero p { font-size: 1.1rem; opacity: 0.9; }

.contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px; }

.info-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.05); margin-bottom: 30px; }
.info-card h3 { font-size: 1.8rem; margin-bottom: 30px; color: #333; }
.info-item { display: flex; gap: 20px; margin-bottom: 25px; }
.info-icon { font-size: 1.5rem; background: #fff1f2; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
.info-text strong { display: block; font-size: 1.1rem; color: #333; margin-bottom: 5px; }
.info-text p { color: #666; font-size: 0.95rem; margin-bottom: 3px; }

.social-card { background: #f8f9fa; padding: 30px; border-radius: 20px; text-align: center; }
.social-card h4 { margin-bottom: 20px; color: #333; }
.social-links { display: flex; justify-content: center; gap: 10px; }
.social-btn { padding: 10px 20px; border-radius: 30px; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.3s; }
.social-btn.fb { background: #1877f2; color: white; }
.social-btn.zl { background: #0068ff; color: white; }
.social-btn.tt { background: #000; color: white; }
.social-btn:hover { opacity: 0.8; transform: translateY(-3px); }

.form-container { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.05); border: 1px solid #eee; }
.form-container h3 { font-size: 1.8rem; margin-bottom: 15px; color: #333; }
.form-container p { color: #666; margin-bottom: 30px; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 12px 15px; border: 1.5px solid #eee; border-radius: 10px; font-size: 1rem; transition: 0.3s; outline: none;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #e41e26; box-shadow: 0 0 0 3px rgba(228, 30, 38, 0.1); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.btn-submit {
    width: 100%; padding: 15px; background: #e41e26; color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s;
}
.btn-submit:hover { background: #c81a21; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(228, 30, 38, 0.2); }

.contact-map { height: 400px; background: #eee; position: relative; overflow: hidden; }
.map-placeholder {
    width: 100%; height: 100%; background: url('https://img.freepik.com/free-vector/city-map-with-pin-pointers_23-2148281358.jpg') no-repeat center center;
    background-size: cover; display: flex; align-items: center; justify-content: center;
}
.map-overlay { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 20px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.1); max-width: 400px; }
.map-icon { font-size: 3rem; margin-bottom: 15px; display: block; }
.map-overlay h3 { margin-bottom: 10px; color: #333; }
.map-overlay p { color: #666; font-size: 0.9rem; margin-bottom: 20px; }
.btn-outline {
    display: inline-block; padding: 10px 25px; border: 2px solid #e41e26; color: #e41e26; text-decoration: none; border-radius: 30px; font-weight: 700; transition: 0.3s;
}
.btn-outline:hover { background: #e41e26; color: white; }

@media (max-width: 992px) {
    .contact-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; gap: 0; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
