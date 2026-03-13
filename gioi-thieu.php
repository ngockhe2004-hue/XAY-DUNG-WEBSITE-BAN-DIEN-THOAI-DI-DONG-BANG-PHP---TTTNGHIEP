<?php
$pageTitle = 'Giới thiệu';
require_once __DIR__ . '/includes/header.php';
?>

<main class="about-page">
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <h1 class="fade-in-up">Về PhoneStore</h1>
                <p class="fade-in-up delay-1">Nâng tầm trải nghiệm công nghệ của bạn với những sản phẩm chính hãng và dịch vụ tận tâm từ trái tim.</p>
            </div>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="about-intro section-padding">
        <div class="container">
            <div class="intro-grid">
                <div class="intro-image-wrap fade-in-left">
                    <img src="<?= BASE_URL ?>/assets/images/store-interior.png" alt="PhoneStore Interior" class="rounded-lg shadow-2xl">
                    <div class="image-accent"></div>
                </div>
                <div class="intro-text fade-in-right">
                    <span class="section-tag">Câu chuyện của chúng tôi</span>
                    <h2>Chào mừng đến với <span class="text-gradient">PhoneStore</span></h2>
                    <p>Được thành lập với niềm đam mê công nghệ mãnh liệt, PhoneStore đã nhanh chóng trở thành một trong những điểm đến tin cậy hàng đầu cho những ai yêu thích các thiết bị di động chính hãng tại Việt Nam.</p>
                    <p>Chúng tôi không chỉ bán điện thoại, chúng tôi mang đến những giải pháp công nghệ giúp kết nối và làm phong phú thêm cuộc sống của bạn trong kỷ nguyên số.</p>
                    
                    <div class="intro-stats-v2">
                        <div class="stat-card">
                            <span class="stat-num">10+</span>
                            <span class="stat-txt">Năm kinh nghiệm</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-num">1M+</span>
                            <span class="stat-txt">Khách hàng</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-num">50+</span>
                            <span class="stat-txt">Cửa hàng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision & Mission Section -->
    <section id="tam-nhin" class="about-vision section-padding bg-dark text-white">
        <div class="container">
            <div class="vision-mission-grid">
                <div class="vision-card fade-in-up">
                    <div class="card-glass">
                        <div class="card-icon-v2">🎯</div>
                        <h3>Tầm nhìn</h3>
                        <p>Trở thành hệ thống bán lẻ công nghệ số 1 tại Việt Nam, mang những đột phá công nghệ thế giới đến gần hơn với mọi người dân, kiến tạo một cuộc sống thông minh hơn.</p>
                    </div>
                </div>
                
                <div class="mission-image-center fade-in-up delay-1">
                    <img src="<?= BASE_URL ?>/assets/images/vision-mission.png" alt="Vision and Mission" class="floating-img">
                </div>

                <div class="mission-card fade-in-up delay-2">
                    <div class="card-glass">
                        <div class="card-icon-v2">🚀</div>
                        <h3>Sứ mệnh</h3>
                        <p>Cung cấp sản phẩm chính hãng với giá trị thực, dịch vụ hậu mãi vượt trội và trải nghiệm mua sắm cá nhân hóa cho từng khách hàng, vượt trên mọi mong đợi.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Commitment Section -->
    <section id="cam-ket" class="about-commitment section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-tag">Giá trị cốt lõi</span>
                <h2 class="section-title">Cam kết của chúng tôi</h2>
            </div>
            <div class="commitment-grid">
                <div class="commitment-item-v2 fade-in-up">
                    <div class="item-inner">
                        <div class="item-icon-wrap">🛡️</div>
                        <h4>100% Chính hãng</h4>
                        <p>Mọi sản phẩm tại PhoneStore đều có nguồn gốc rõ ràng, đầy đủ hóa đơn chứng từ từ các thương hiệu lớn toàn cầu.</p>
                    </div>
                </div>
                <div class="commitment-item-v2 fade-in-up delay-1">
                    <div class="item-inner">
                        <div class="item-icon-wrap">💰</div>
                        <h4>Giá cả cạnh tranh</h4>
                        <p>Chúng tôi luôn nỗ lực tối ưu quy trình để mang đến mức giá tốt nhất cùng nhiều chương trình ưu đãi độc quyền.</p>
                    </div>
                </div>
                <div class="commitment-item-v2 fade-in-up delay-2">
                    <div class="item-inner">
                        <div class="item-icon-wrap">🤝</div>
                        <h4>Hậu mãi chu đáo</h4>
                        <p>Dịch vụ bảo hành 24/7, chính sách đổi trả nhanh chóng và đội ngũ kỹ thuật tận tâm luôn sẵn sàng đồng hành cùng bạn.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
/* Reset & Base */
.about-page { overflow-x: hidden; font-family: 'Inter', sans-serif; }
.section-padding { padding: 100px 0; }
.text-gradient { background: linear-gradient(135deg, #e41e26 0%, #ff5e62 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.bg-dark { background: #0f0f0f; }

/* Hero Section */
.about-hero {
    position: relative;
    background: url('<?= BASE_URL ?>/assets/images/about-hero.png') no-repeat center center;
    background-size: cover;
    height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
}
.hero-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.4));
}
.hero-content { position: relative; z-index: 2; max-width: 800px; padding: 0 20px; }
.about-hero h1 { font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 800; margin-bottom: 20px; letter-spacing: -1px; }
.about-hero p { font-size: 1.25rem; font-weight: 300; opacity: 0.9; line-height: 1.6; }

/* Intro Section */
.section-tag { display: inline-block; padding: 6px 15px; background: rgba(228, 30, 38, 0.1); color: #e41e26; border-radius: 50px; font-weight: 600; font-size: 0.85rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
.intro-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
.intro-image-wrap { position: relative; }
.intro-image-wrap img { width: 100%; border-radius: 30px; transform: rotate(-2deg); transition: transform 0.5s ease; position: relative; z-index: 2; }
.intro-image-wrap:hover img { transform: rotate(0deg) scale(1.02); }
.image-accent { position: absolute; top: -20px; left: -20px; width: 100%; height: 100%; border: 3px solid #e41e26; border-radius: 30px; z-index: 1; }

.intro-text h2 { font-size: 2.8rem; font-weight: 800; color: #1a1a1a; margin-bottom: 30px; line-height: 1.2; }
.intro-text p { font-size: 1.1rem; line-height: 1.8; color: #555; margin-bottom: 25px; }

.intro-stats-v2 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 40px; }
.stat-card { background: #fff; padding: 25px 15px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.06); text-align: center; transition: transform 0.3s ease; }
.stat-card:hover { transform: translateY(-5px); }
.stat-num { display: block; font-size: 2.2rem; font-weight: 800; color: #e41e26; margin-bottom: 5px; }
.stat-txt { font-size: 0.85rem; font-weight: 600; color: #777; text-transform: uppercase; }

/* Vision & Mission */
.vision-mission-grid { display: grid; grid-template-columns: 1fr auto 1fr; gap: 40px; align-items: center; }
.card-glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); padding: 50px 40px; border-radius: 30px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column; justify-content: center; }
.card-glass:hover { background: rgba(255,255,255,0.07); border-color: rgba(228, 30, 38, 0.5); transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
.card-icon-v2 { font-size: 3.5rem; margin-bottom: 20px; filter: drop-shadow(0 0 15px rgba(255,255,255,0.2)); }
.card-glass h3 { font-size: 2.2rem; font-weight: 800; margin-bottom: 20px; color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
.card-glass p { font-size: 1.1rem; line-height: 1.8; color: #e0e0e0; opacity: 0.95; font-weight: 400; }
.mission-image-center { max-width: 400px; }
.floating-img { width: 100%; animation: floating 6s ease-in-out infinite; }

@keyframes floating {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(2deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

/* Commitment Section */
.commitment-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 60px; }
.commitment-item-v2 { perspective: 1000px; }
.item-inner { background: #fff; padding: 45px 35px; border-radius: 25px; border: 1px solid #f0f0f0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); text-align: center; }
.commitment-item-v2:hover .item-inner { background: #fafafa; border-color: #e41e26; transform: translateY(-15px) scale(1.05); box-shadow: 0 25px 50px rgba(0,0,0,0.1); }
.item-icon-wrap { width: 70px; height: 70px; background: rgba(228, 30, 38, 0.05); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 25px; color: #e41e26; }
.commitment-item-v2 h4 { font-size: 1.35rem; font-weight: 700; color: #1a1a1a; margin-bottom: 15px; }
.commitment-item-v2 p { color: #666; line-height: 1.6; }

/* Animations */
.fade-in-up { animation: fadeInUp 0.8s ease forwards; opacity: 0; }
.fade-in-left { animation: fadeInLeft 0.8s ease forwards; opacity: 0; }
.fade-in-right { animation: fadeInRight 0.8s ease forwards; opacity: 0; }
.delay-1 { animation-delay: 0.2s; }
.delay-2 { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes fadeInLeft {
    from { transform: translateX(-50px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes fadeInRight {
    from { transform: translateX(50px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 1200px) {
    .vision-mission-grid { grid-template-columns: 1fr 1fr; }
    .mission-image-center { grid-column: span 2; order: -1; margin: 0 auto 40px; }
}
@media (max-width: 992px) {
    .intro-grid { grid-template-columns: 1fr; gap: 50px; }
    .commitment-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .section-padding { padding: 60px 0; }
    .intro-stats-v2 { grid-template-columns: 1fr; }
    .card-glass { padding: 30px; }
    .vision-mission-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

