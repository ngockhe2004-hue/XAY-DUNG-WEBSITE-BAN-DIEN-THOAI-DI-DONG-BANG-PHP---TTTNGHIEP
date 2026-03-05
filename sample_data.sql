-- ============================================================
-- SAMPLE DATA - PhoneStore (Fixed for schema v2)
-- Run này SAU khi chạy database_dienthoai.sql
-- ============================================================

USE bandienthoai;

-- ===== THƯƠNG HIỆU =====
INSERT IGNORE INTO thuonghieu (ten_thuonghieu, slug, quoc_gia, mo_ta, is_active) VALUES
('Apple',   'apple',   'Mỹ',       'Hãng điện thoại cao cấp số 1 thế giới', 1),
('Samsung', 'samsung', 'Hàn Quốc', 'Nhà sản xuất điện thoại Android hàng đầu', 1),
('Xiaomi',  'xiaomi',  'Trung Quốc','Điện thoại giá tốt, cấu hình mạnh', 1),
('OPPO',    'oppo',    'Trung Quốc','Chuyên về camera và thiết kế', 1),
('Vivo',    'vivo',    'Trung Quốc','Camera selfie hàng đầu', 1),
('Realme',  'realme',  'Trung Quốc','Điện thoại gaming tầm trung', 1),
('OnePlus', 'oneplus', 'Trung Quốc','Flagship Android tốc độ cao', 1),
('Google',  'google',  'Mỹ',       'Điện thoại Pixel AI hàng đầu', 1);

-- ===== DANH MỤC =====
INSERT IGNORE INTO danhmuc (ten_danhmuc, slug, mo_ta, thu_tu, is_active) VALUES
('iPhone', 'iphone', 'Điện thoại Apple iPhone', 1, 1),
('Samsung Galaxy', 'samsung-galaxy', 'Điện thoại Samsung Galaxy', 2, 1),
('Điện thoại Xiaomi', 'dien-thoai-xiaomi', 'Điện thoại Xiaomi', 3, 1),
('OPPO', 'dien-thoai-oppo', 'Điện thoại OPPO', 4, 1),
('Flagship', 'flagship', 'Flagship cao cấp trên 15 triệu', 5, 1),
('Tầm trung', 'tam-trung', 'Điện thoại tầm trung 5-15 triệu', 6, 1),
('Phổ thông', 'pho-thong', 'Điện thoại phổ thông dưới 5 triệu', 7, 1),
('Gaming Phone', 'gaming-phone', 'Điện thoại chơi game', 8, 1);

-- ===== SẢN PHẨM MẪU =====
INSERT IGNORE INTO sanpham (ten_sanpham, slug, ma_danhmuc, ma_thuonghieu, mo_ta_ngan, gia_goc, he_dieu_hanh, chip, man_hinh_size, man_hinh_loai, pin_dung_luong, sac_nhanh, camera_sau, camera_truoc, is_noi_bat, is_hang_moi, tong_da_ban, diem_danh_gia) VALUES
('iPhone 16 Pro Max', 'iphone-16-pro-max',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='iphone' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='apple' LIMIT 1),
 'iPhone 16 Pro Max - Titan cao cấp, Camera 48MP, Chip A18 Pro siêu mạnh',
 33990000, 'iOS', 'Apple A18 Pro', 6.9, 'Super Retina XDR ProMotion OLED', 4685, 27,
 '48MP Fusion + 48MP Ultra Wide + 12MP 5x Telephoto', '12MP TrueDepth',
 1, 1, 1250, 4.80),

('iPhone 16', 'iphone-16',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='iphone' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='apple' LIMIT 1),
 'iPhone 16 - Chip A18, Camera Control, Action Button',
 22990000, 'iOS', 'Apple A18', 6.1, 'Super Retina XDR OLED', 3561, 25,
 '48MP Fusion + 12MP Ultra Wide', '12MP TrueDepth',
 1, 1, 890, 4.70),

('Samsung Galaxy S25 Ultra', 'samsung-galaxy-s25-ultra',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='samsung-galaxy' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='samsung' LIMIT 1),
 'Galaxy S25 Ultra - Galaxy AI, S Pen tích hợp, Camera 200MP',
 31990000, 'Android', 'Snapdragon 8 Elite', 6.9, 'Dynamic AMOLED 2X 120Hz', 5000, 45,
 '200MP + 50MP + 10MP + 10MP', '12MP',
 1, 1, 680, 4.70),

('Samsung Galaxy A55', 'samsung-galaxy-a55',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='samsung-galaxy' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='samsung' LIMIT 1),
 'Galaxy A55 5G - Thiết kế cao cấp, Chống nước IP67',
 9990000, 'Android', 'Exynos 1480', 6.6, 'Super AMOLED 120Hz', 5000, 25,
 '50MP + 12MP + 5MP', '32MP',
 0, 0, 450, 4.50),

('Xiaomi 14 Ultra', 'xiaomi-14-ultra',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='dien-thoai-xiaomi' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='xiaomi' LIMIT 1),
 'Xiaomi 14 Ultra - Camera Leica Summilux, Snapdragon 8 Gen 3',
 27990000, 'Android', 'Snapdragon 8 Gen 3', 6.73, 'LTPO AMOLED 120Hz', 5000, 90,
 '50MP Leica + 50MP + 50MP + 50MP', '32MP',
 1, 0, 320, 4.80),

('OPPO Find X8 Pro', 'oppo-find-x8-pro',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='dien-thoai-oppo' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='oppo' LIMIT 1),
 'OPPO Find X8 Pro - Hasselblad Camera, Dimensity 9400',
 22990000, 'Android', 'MediaTek Dimensity 9400', 6.78, 'LTPO AMOLED 120Hz', 5910, 80,
 '50MP LYT-900 + 50MP 6x + 50MP ultra wide', '32MP',
 1, 1, 180, 4.60),

('Realme GT 6', 'realme-gt-6',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='gaming-phone' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='realme' LIMIT 1),
 'Realme GT 6 - Gaming phone Snapdragon 8s Gen 3, Sạc 120W',
 12990000, 'Android', 'Snapdragon 8s Gen 3', 6.78, 'AMOLED 144Hz', 5500, 120,
 '50MP + 8MP', '32MP',
 0, 0, 230, 4.40),

('Google Pixel 9 Pro', 'google-pixel-9-pro',
 (SELECT ma_danhmuc FROM danhmuc WHERE slug='flagship' LIMIT 1),
 (SELECT ma_thuonghieu FROM thuonghieu WHERE slug='google' LIMIT 1),
 'Google Pixel 9 Pro - AI Camera số 1, Tensor G4, 7 năm update',
 24990000, 'Android', 'Google Tensor G4', 6.3, 'LTPO OLED 120Hz', 4700, 30,
 '50MP + 48MP ultra wide + 48MP 5x telephoto', '42MP',
 1, 1, 150, 4.70);

-- ===== BIẾN THỂ SẢN PHẨM =====
-- iPhone 16 Pro Max
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, gia_goc, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16-pro-max'), 'ip16pm-8-256-tden', 8, 256, 'Titan Đen', '#2C2C2C', 33990000, 35990000, 15),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16-pro-max'), 'ip16pm-8-512-tden', 8, 512, 'Titan Đen', '#2C2C2C', 38990000, 40990000, 10),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16-pro-max'), 'ip16pm-8-256-ttn', 8, 256, 'Titan Tự Nhiên', '#D4B890', 33990000, 35990000, 12),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16-pro-max'), 'ip16pm-8-1024-ttrang', 8, 1024, 'Titan Trắng', '#F5F5F0', 49990000, NULL, 5);

-- iPhone 16
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16'), 'ip16-8-128-den', 8, 128, 'Đen Soot', '#2C2C2C', 22990000, 20),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16'), 'ip16-8-256-den', 8, 256, 'Đen Soot', '#2C2C2C', 26990000, 15),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16'), 'ip16-8-128-trang', 8, 128, 'Trắng', '#FFFFFF', 22990000, 18),
((SELECT ma_sanpham FROM sanpham WHERE slug='iphone-16'), 'ip16-8-256-hong', 8, 256, 'Hồng', '#FFB6B9', 26990000, 8);

-- Samsung S25 Ultra
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, gia_goc, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-s25-ultra'), 's25u-12-256-den', 12, 256, 'Đen', '#1A1A1A', 31990000, 33990000, 8),
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-s25-ultra'), 's25u-12-512-xanh', 12, 512, 'Xanh Titanium', '#4A6B8A', 36990000, 38990000, 6),
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-s25-ultra'), 's25u-12-1024-bac', 12, 1024, 'Bạc', '#C0C0C0', 46990000, NULL, 4);

-- Samsung A55
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-a55'), 'a55-8-128-xanh', 8, 128, 'Xanh', '#4A90D9', 9990000, 25),
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-a55'), 'a55-8-256-den', 8, 256, 'Đen', '#1A1A1A', 11490000, 20),
((SELECT ma_sanpham FROM sanpham WHERE slug='samsung-galaxy-a55'), 'a55-12-256-trang', 12, 256, 'Trắng', '#FFFFFF', 12990000, 15);

-- Xiaomi 14 Ultra
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='xiaomi-14-ultra'), 'mi14u-16-512-den', 16, 512, 'Đen', '#1A1A1A', 27990000, 10),
((SELECT ma_sanpham FROM sanpham WHERE slug='xiaomi-14-ultra'), 'mi14u-16-512-trang', 16, 512, 'Trắng', '#FFFFFF', 27990000, 8);

-- OPPO Find X8 Pro
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='oppo-find-x8-pro'), 'opx8p-16-512-den', 16, 512, 'Đen Vũ Trụ', '#1A1A1A', 25990000, 8),
((SELECT ma_sanpham FROM sanpham WHERE slug='oppo-find-x8-pro'), 'opx8p-12-256-bac', 12, 256, 'Bạc Ngân Hà', '#C0C0C0', 22990000, 10);

-- Realme GT 6
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='realme-gt-6'), 'rgt6-12-256-den', 12, 256, 'Đen', '#1A1A1A', 12990000, 15),
((SELECT ma_sanpham FROM sanpham WHERE slug='realme-gt-6'), 'rgt6-12-512-xanh', 12, 512, 'Xanh', '#4A90D9', 15490000, 10);

-- Google Pixel 9 Pro
INSERT IGNORE INTO bienthe_sanpham (ma_sanpham, ma_sku, ram_gb, rom_gb, mau_sac, ma_hex_mau, gia, ton_kho) VALUES
((SELECT ma_sanpham FROM sanpham WHERE slug='google-pixel-9-pro'), 'px9p-16-256-den', 16, 256, 'Obsidian', '#1A1A1A', 24990000, 8),
((SELECT ma_sanpham FROM sanpham WHERE slug='google-pixel-9-pro'), 'px9p-16-512-trang', 16, 512, 'Porcelain', '#F5F5F0', 28990000, 6);

-- ===== MÃ KHUYẾN MÃI MẪU =====
INSERT IGNORE INTO ma_khuyenmai (ma_code, ten_km, kieu_giam, gia_tri_giam, giam_toi_da, don_toi_thieu, ngay_bat_dau, ngay_ket_thuc, so_lan_toi_da, is_active) VALUES
('WELCOME10', 'Chào mừng thành viên mới', 'phan_tram', 10, 500000, 1000000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 1000, 1),
('SALE50K', 'Giảm 50k mọi đơn', 'so_tien', 50000, NULL, 500000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 5000, 1),
('PHONE20', 'Giảm 20% tối đa 2 triệu', 'phan_tram', 20, 2000000, 5000000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 500, 1);

-- ===== USERS MẪU =====
-- Password hash cho 'Admin@1234' - dùng bcrypt
-- Thực tế nên đăng ký qua register.php, sau đó UPDATE quyen='admin'
-- Đây là hash mẫu (sẽ không hoạt động nếu thuật toán khác)
-- HƯỚNG DẪN: Đăng ký qua /register.php sau đó chạy lệnh dưới để set admin:
-- UPDATE users SET quyen='admin' WHERE ten_user='your_username';

SELECT '===== IMPORT HOÀN TẤT =====' AS thong_bao;
SELECT COUNT(*) AS so_thuonghieu FROM thuonghieu;
SELECT COUNT(*) AS so_danhmuc FROM danhmuc;
SELECT COUNT(*) AS so_sanpham FROM sanpham;
SELECT COUNT(*) AS so_bienthe FROM bienthe_sanpham;
SELECT COUNT(*) AS so_makhuyenmai FROM ma_khuyenmai;
