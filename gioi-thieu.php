<?php
$pageTitle = 'Giới thiệu';
require_once __DIR__ . '/includes/header.php';
?>

<main class="about-page">
    <section class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1>Về PhoneStore</h1>
                <p>Nâng tầm trải nghiệm công nghệ của bạn với những sản phẩm chính hãng và dịch vụ tận tâm.</p>
            </div>
        </div>
    </section>

    <section class="about-intro section-padding">
        <div class="container">
            <div class="intro-grid">
                <div class="intro-text">
                    <h2>Chào mừng đến với PhoneStore</h2>
                    <p>Được thành lập với niềm đam mê công nghệ, PhoneStore đã nhanh chóng trở thành một trong những điểm đến tin cậy hàng đầu cho những ai yêu thích các thiết bị di động chính hãng tại Việt Nam.</p>
                    <p>Chúng tôi không chỉ bán điện thoại, chúng tôi mang đến những giải pháp công nghệ giúp kết nối và làm phong phú thêm cuộc sống của bạn.</p>
                </div>
                <div class="intro-stats">
                    <div class="stat-item">
                        <span class="stat-number">10+</span>
                        <span class="stat-label">Năm kinh nghiệm</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">1M+</span>
                        <span class="stat-label">Khách hàng tin dùng</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Cửa hàng toàn quốc</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="tam-nhin" class="about-mission section-padding bg-light">
        <div class="container">
            <div class="mission-grid">
                <div class="mission-card">
                    <div class="card-icon">🎯</div>
                    <h3>Tầm nhìn</h3>
                    <p>Trở thành hệ thống bán lẻ công nghệ số 1 tại Việt Nam, mang những đột phá công nghệ thế giới đến gần hơn với mọi người dân.</p>
                </div>
                <div class="mission-card">
                    <div class="card-icon">🚀</div>
                    <h3>Sứ mệnh</h3>
                    <p>Cung cấp sản phẩm chính hãng với giá trị thực, dịch vụ hậu mãi vượt trội và trải nghiệm mua sắm cá nhân hóa cho từng khách hàng.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="cam-ket" class="about-commitment section-padding">
        <div class="container">
            <h2 class="section-title">Cam kết của chúng tôi</h2>
            <div class="commitment-list">
                <div class="commitment-item">
                    <div class="item-header">
                        <span class="item-icon">🛡️</span>
                        <h4>100% Chính hãng</h4>
                    </div>
                    <p>Mọi sản phẩm tại PhoneStore đều có nguồn gốc rõ ràng, đầy đủ hóa đơn chứng từ từ các thương hiệu lớn như Apple, Samsung, Xiaomi...</p>
                </div>
                <div class="commitment-item">
                    <div class="item-header">
                        <span class="item-icon">💰</span>
                        <h4>Giá cả cạnh tranh</h4>
                    </div>
                    <p>Chúng tôi luôn nỗ lực tối ưu quy trình để mang đến mức giá tốt nhất cho khách hàng cùng nhiều chương trình ưu đãi hấp dẫn.</p>
                </div>
                <div class="commitment-item">
                    <div class="item-header">
                        <span class="item-icon">🤝</span>
                        <h4>Hậu mãi chu đáo</h4>
                    </div>
                    <p>Dịch vụ bảo hành nhanh chóng, chính sách đổi trả linh hoạt và đội ngũ kỹ thuật tận tâm luôn sẵn sàng hỗ trợ bạn.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.section-padding { padding: 80px 0; }
.bg-light { background: #f8f9fa; }
.about-hero {
    background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
    color: white;
    padding: 120px 0;
    text-align: center;
}
.about-hero h1 { font-size: 3.5rem; margin-bottom: 20px; font-weight: 800; }
.about-hero p { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto; }

.intro-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 50px; align-items: center; }
.intro-text h2 { font-size: 2.5rem; margin-bottom: 25px; color: #333; }
.intro-text p { margin-bottom: 20px; line-height: 1.8; color: #666; }

.intro-stats { display: grid; grid-template-columns: 1fr; gap: 20px; }
.stat-item { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; }
.stat-number { display: block; font-size: 2.5rem; font-weight: 800; color: #e41e26; }
.stat-label { color: #666; font-weight: 500; }

.mission-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.mission-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.03); }
.card-icon { font-size: 3rem; margin-bottom: 20px; }
.mission-card h3 { font-size: 1.8rem; margin-bottom: 15px; color: #333; }
.mission-card p { line-height: 1.7; color: #666; }

.section-title { text-align: center; font-size: 2.5rem; margin-bottom: 50px; color: #333; }
.commitment-list { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
.commitment-item { background: #fff; padding: 30px; border-radius: 15px; border: 1px solid #eee; transition: transform 0.3s; }
.commitment-item:hover { transform: translateY(-10px); }
.item-header { display: flex; align-items: center; margin-bottom: 15px; }
.item-icon { font-size: 1.5rem; margin-right: 15px; }
.commitment-item h4 { font-size: 1.2rem; color: #333; margin: 0; }
.commitment-item p { color: #666; font-size: 0.95rem; line-height: 1.6; }

@media (max-width: 768px) {
    .intro-grid, .mission-grid, .commitment-list { grid-template-columns: 1fr; }
    .about-hero h1 { font-size: 2.5rem; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
