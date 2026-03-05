-- ============================================================
-- DATABASE: WEBSITE BÁN ĐIỆN THOẠI DI ĐỘNG
-- Database: MySQL 8.0+
-- Tác giả: Auto-generated
-- Ngày: 2026-03-02
-- ============================================================

CREATE DATABASE IF NOT EXISTS bandienthoai
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bandienthoai;

-- ============================================================
-- BẢNG 1: USERS - Người dùng
-- ============================================================
CREATE TABLE users (
    ma_user        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten_user       VARCHAR(50)  NOT NULL UNIQUE,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    hovaten        VARCHAR(100),
    SDT            VARCHAR(20),
    quyen          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    trang_thai     ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    ngay_lap       DATETIME DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_status (trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 2: DIACHI_USER - Địa chỉ giao hàng của người dùng (nhiều địa chỉ)
-- ============================================================
CREATE TABLE diachi_user (
    ma_diachi      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_user        INT UNSIGNED NOT NULL,
    ho_ten_nguoinhan VARCHAR(100) NOT NULL,
    SDT_nguoinhan    VARCHAR(20)  NOT NULL,
    tinh_thanh     VARCHAR(100) NOT NULL,
    quan_huyen     VARCHAR(100) NOT NULL,
    phuong_xa      VARCHAR(100) NOT NULL,
    dia_chi_cu_the VARCHAR(255) NOT NULL,
    la_macdinh     TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_diachi_user FOREIGN KEY (ma_user)
        REFERENCES users(ma_user) ON DELETE CASCADE,
    INDEX idx_user (ma_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 3: DANHMUC - Danh mục sản phẩm
-- ============================================================
CREATE TABLE danhmuc (
    ma_danhmuc     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten_danhmuc    VARCHAR(100) NOT NULL UNIQUE,
    slug           VARCHAR(120) NOT NULL UNIQUE COMMENT 'URL thân thiện',
    mo_ta          TEXT,
    hinh_anh       VARCHAR(255),
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    thu_tu         INT UNSIGNED DEFAULT 0 COMMENT 'Thứ tự hiển thị'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 4: THUONGHIEU - Thương hiệu
-- ============================================================
CREATE TABLE thuonghieu (
    ma_thuonghieu  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten_thuonghieu VARCHAR(100) NOT NULL UNIQUE,
    slug           VARCHAR(120) NOT NULL UNIQUE,
    logo_url       VARCHAR(255),
    quoc_gia       VARCHAR(100) COMMENT 'Quốc gia xuất xứ',
    mo_ta          TEXT,
    is_active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 5: SANPHAM - Sản phẩm điện thoại (thông tin chung)
-- ============================================================
CREATE TABLE sanpham (
    ma_sanpham     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ten_sanpham    VARCHAR(200) NOT NULL,
    slug           VARCHAR(220) NOT NULL UNIQUE,
    ma_sanpham_code VARCHAR(50) UNIQUE COMMENT 'Mã SKU gốc',
    ma_danhmuc     INT UNSIGNED NOT NULL,
    ma_thuonghieu  INT UNSIGNED NOT NULL,
    mo_ta_ngan     VARCHAR(500),
    mo_ta_day_du   LONGTEXT,
    gia_goc        DECIMAL(15,2) NOT NULL COMMENT 'Giá hiển thị gốc (sp đơn giản)',
    gia_khuyen_mai DECIMAL(15,2) DEFAULT NULL COMMENT 'Giá sau giảm (sp đơn giản)',
    -- Thông số kỹ thuật chung
    he_dieu_hanh   VARCHAR(50)  COMMENT 'Android / iOS',
    phien_ban_os   VARCHAR(50)  COMMENT 'Android 14 / iOS 17',
    chip           VARCHAR(100) COMMENT 'Snapdragon 8 Gen 3',
    man_hinh_size  DECIMAL(4,1) COMMENT 'Kích thước màn hình (inch)',
    man_hinh_loai  VARCHAR(100) COMMENT 'OLED, AMOLED, LCD,…',
    man_hinh_dophangiai VARCHAR(50) COMMENT '1080x2400',
    man_hinh_tanso  SMALLINT UNSIGNED COMMENT 'Hz - Tần số quét',
    pin_dung_luong  SMALLINT UNSIGNED COMMENT 'mAh',
    sac_nhanh       SMALLINT UNSIGNED COMMENT 'W - Công suất sạc nhanh',
    camera_sau      VARCHAR(200) COMMENT 'Mô tả camera sau',
    camera_truoc    VARCHAR(100) COMMENT 'Mô tả camera trước',
    ket_noi         VARCHAR(200) COMMENT '5G, 4G, WiFi 6, Bluetooth 5.3',
    khang_nuoc      VARCHAR(50)  COMMENT 'IP68, IP67, Không',
    kich_thuoc      VARCHAR(100) COMMENT 'Dài x Rộng x Dày (mm)',
    trong_luong     SMALLINT UNSIGNED COMMENT 'GRAM',
    -- Điểm đánh giá & bán hàng
    diem_danh_gia  DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    tong_luot_xem  INT UNSIGNED NOT NULL DEFAULT 0,
    tong_da_ban    INT UNSIGNED NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    is_noi_bat     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
    is_hang_moi    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Hàng mới về',
    ngay_lap       DATETIME DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sp_danhmuc    FOREIGN KEY (ma_danhmuc)    REFERENCES danhmuc(ma_danhmuc),
    CONSTRAINT fk_sp_thuonghieu FOREIGN KEY (ma_thuonghieu) REFERENCES thuonghieu(ma_thuonghieu),
    INDEX idx_danhmuc   (ma_danhmuc),
    INDEX idx_thuonghieu(ma_thuonghieu),
    INDEX idx_gia       (gia_goc),
    INDEX idx_danhgia   (diem_danh_gia DESC),
    INDEX idx_active    (is_active),
    INDEX idx_nobat     (is_noi_bat),
    FULLTEXT INDEX ft_sanpham (ten_sanpham, mo_ta_ngan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 6: BIENTHE_SANPHAM - Biến thể (RAM/ROM/Màu)
-- Mỗi biến thể = 1 tổ hợp cụ thể (VD: 8GB RAM + 256GB + Đen)
-- ============================================================
CREATE TABLE bienthe_sanpham (
    ma_bienthe     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_sanpham     INT UNSIGNED NOT NULL,
    ma_sku         VARCHAR(80)  NOT NULL UNIQUE COMMENT 'SKU của biến thể',
    ram_gb         TINYINT UNSIGNED NOT NULL COMMENT 'Dung lượng RAM (GB)',
    rom_gb         SMALLINT UNSIGNED NOT NULL COMMENT 'Bộ nhớ trong (GB)',
    mau_sac        VARCHAR(80)  NOT NULL COMMENT 'Màu sắc',
    ma_hex_mau     VARCHAR(7)   DEFAULT NULL COMMENT 'Mã màu HEX #FFFFFF',
    gia            DECIMAL(15,2) NOT NULL COMMENT 'Giá biến thể này',
    gia_goc        DECIMAL(15,2) DEFAULT NULL COMMENT 'Giá gốc trước giảm',
    ton_kho        INT UNSIGNED NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_bienthe_sp FOREIGN KEY (ma_sanpham)
        REFERENCES sanpham(ma_sanpham) ON DELETE CASCADE,
    INDEX idx_sp (ma_sanpham),
    UNIQUE KEY uq_bienthe (ma_sanpham, ram_gb, rom_gb, mau_sac)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 7: HINHANH_SANPHAM - Hình ảnh sản phẩm
-- ============================================================
CREATE TABLE hinhanh_sanpham (
    ma_anh         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_sanpham     INT UNSIGNED NOT NULL,
    ma_bienthe     INT UNSIGNED DEFAULT NULL COMMENT 'NULL = ảnh chung, có giá trị = ảnh của biến thể',
    image_url      VARCHAR(500) NOT NULL,
    alt_text       VARCHAR(200) DEFAULT NULL,
    la_anh_chinh   TINYINT(1) NOT NULL DEFAULT 0,
    thu_tu         TINYINT UNSIGNED DEFAULT 0,
    CONSTRAINT fk_anh_sp FOREIGN KEY (ma_sanpham)
        REFERENCES sanpham(ma_sanpham) ON DELETE CASCADE,
    CONSTRAINT fk_anh_bienthe FOREIGN KEY (ma_bienthe)
        REFERENCES bienthe_sanpham(ma_bienthe) ON DELETE SET NULL,
    INDEX idx_sp (ma_sanpham),
    INDEX idx_bienthe (ma_bienthe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 8: GIOHANG - Giỏ hàng (mỗi user 1 giỏ)
-- ============================================================
CREATE TABLE giohang (
    ma_gio         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_user        INT UNSIGNED NOT NULL UNIQUE,
    ngay_tao       DATETIME DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gio_user FOREIGN KEY (ma_user)
        REFERENCES users(ma_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 9: CHITIET_GIOHANG - Chi tiết giỏ hàng
-- ============================================================
CREATE TABLE chitiet_giohang (
    ma_ctgh        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_gio         INT UNSIGNED NOT NULL,
    ma_bienthe     INT UNSIGNED NOT NULL,
    so_luong       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    gia_tai_luc_them DECIMAL(15,2) NOT NULL COMMENT 'Giá tại thời điểm thêm vào giỏ',
    ngay_them      DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ctgh_gio     FOREIGN KEY (ma_gio)     REFERENCES giohang(ma_gio) ON DELETE CASCADE,
    CONSTRAINT fk_ctgh_bienthe FOREIGN KEY (ma_bienthe) REFERENCES bienthe_sanpham(ma_bienthe),
    UNIQUE KEY uq_gio_bienthe (ma_gio, ma_bienthe),
    INDEX idx_gio (ma_gio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 10: MA_KHUYENMAI - Coupon / Voucher
-- ============================================================
CREATE TABLE ma_khuyenmai (
    ma_km          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_code        VARCHAR(50)  NOT NULL UNIQUE,
    ten_km         VARCHAR(200),
    kieu_giam      ENUM('phan_tram','so_tien') NOT NULL DEFAULT 'phan_tram',
    gia_tri_giam   DECIMAL(15,2) NOT NULL,
    giam_toi_da    DECIMAL(15,2) DEFAULT NULL COMMENT 'Giới hạn số tiền giảm tối đa (cho loại %)',
    don_toi_thieu  DECIMAL(15,2) NOT NULL DEFAULT 0,
    so_lan_toi_da  INT UNSIGNED  DEFAULT NULL COMMENT 'NULL = không giới hạn',
    so_lan_da_dung INT UNSIGNED  NOT NULL DEFAULT 0,
    chi_1_lan_per_user TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = mỗi user chỉ dùng 1 lần',
    ngay_bat_dau   DATETIME NOT NULL,
    ngay_ket_thuc  DATETIME NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    ngay_tao       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (ma_code),
    INDEX idx_active (is_active),
    INDEX idx_ngay  (ngay_bat_dau, ngay_ket_thuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 11: LICH_SU_DUNG_KM - Lịch sử dùng mã khuyến mãi
-- ============================================================
CREATE TABLE lichsu_dung_km (
    ma_ls          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_km          INT UNSIGNED NOT NULL,
    ma_user        INT UNSIGNED NOT NULL,
    ma_donhang     INT UNSIGNED DEFAULT NULL,
    so_tien_giam   DECIMAL(15,2) NOT NULL,
    ngay_su_dung   DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ls_km   FOREIGN KEY (ma_km)   REFERENCES ma_khuyenmai(ma_km),
    CONSTRAINT fk_ls_user FOREIGN KEY (ma_user) REFERENCES users(ma_user),
    INDEX idx_km   (ma_km),
    INDEX idx_user (ma_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 12: DONHANG - Đơn hàng
-- ============================================================
CREATE TABLE donhang (
    ma_donhang     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_donhang_code VARCHAR(30) NOT NULL UNIQUE COMMENT 'Mã đơn hiển thị: DH20260302001',
    ma_user        INT UNSIGNED NOT NULL,
    -- Thông tin người nhận (snapshot tại thời điểm đặt)
    ten_nguoi_nhan VARCHAR(100) NOT NULL,
    SDT_nguoi_nhan VARCHAR(20)  NOT NULL,
    tinh_thanh     VARCHAR(100) NOT NULL,
    quan_huyen     VARCHAR(100) NOT NULL,
    phuong_xa      VARCHAR(100) NOT NULL,
    dia_chi_cu_the VARCHAR(255) NOT NULL,
    ghi_chu        VARCHAR(500) DEFAULT NULL,
    -- Tiền
    tong_tien_hang DECIMAL(15,2) NOT NULL COMMENT 'Tổng tiền sản phẩm',
    phi_giao_hang  DECIMAL(15,2) NOT NULL DEFAULT 0,
    so_tien_giam   DECIMAL(15,2) NOT NULL DEFAULT 0,
    tong_thanh_toan DECIMAL(15,2) NOT NULL COMMENT 'Số tiền thực tế thanh toán',
    -- Mã khuyến mãi
    ma_km          INT UNSIGNED DEFAULT NULL,
    -- Trạng thái
    trang_thai     ENUM('cho_xac_nhan','da_xac_nhan','dang_dong_goi','dang_giao','da_giao','da_huy','cho_hoan_tien','da_hoan_tien') NOT NULL DEFAULT 'cho_xac_nhan',
    -- Thanh toán
    phuong_thuc_TT ENUM('cod','chuyen_khoan','momo','vnpay','zalopay','visa_mastercard') NOT NULL,
    trang_thai_TT  ENUM('chua_thanh_toan','da_thanh_toan','that_bai','da_hoan_tien') NOT NULL DEFAULT 'chua_thanh_toan',
    -- Giao hàng
    ma_van_don     VARCHAR(100) DEFAULT NULL COMMENT 'Mã vận đơn giao hàng',
    don_vi_vc      VARCHAR(100) DEFAULT NULL COMMENT 'Đơn vị vận chuyển',
    ngay_du_kien   DATE         DEFAULT NULL,
    ngay_giao_thuc DATE         DEFAULT NULL,
    -- Thời gian
    ngay_dat       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dh_user FOREIGN KEY (ma_user) REFERENCES users(ma_user),
    CONSTRAINT fk_dh_km   FOREIGN KEY (ma_km)   REFERENCES ma_khuyenmai(ma_km),
    INDEX idx_user  (ma_user),
    INDEX idx_tt    (trang_thai),
    INDEX idx_ngay  (ngay_dat DESC),
    INDEX idx_tttt  (trang_thai_TT)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 13: CHITIET_DONHANG - Chi tiết đơn hàng
-- ============================================================
CREATE TABLE chitiet_donhang (
    ma_ctdh        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_donhang     INT UNSIGNED NOT NULL,
    ma_bienthe     INT UNSIGNED NOT NULL COMMENT 'Tham chiếu để có thể tra cứu',
    -- Snapshot thông tin tại thời điểm đặt hàng
    ten_sanpham    VARCHAR(200) NOT NULL,
    ram_gb         TINYINT UNSIGNED NOT NULL,
    rom_gb         SMALLINT UNSIGNED NOT NULL,
    mau_sac        VARCHAR(80) NOT NULL,
    hinh_anh_url   VARCHAR(500),
    so_luong       SMALLINT UNSIGNED NOT NULL,
    don_gia        DECIMAL(15,2) NOT NULL,
    thanh_tien     DECIMAL(15,2) NOT NULL,
    CONSTRAINT fk_ctdh_dh     FOREIGN KEY (ma_donhang) REFERENCES donhang(ma_donhang) ON DELETE CASCADE,
    CONSTRAINT fk_ctdh_bienthe FOREIGN KEY (ma_bienthe) REFERENCES bienthe_sanpham(ma_bienthe),
    INDEX idx_donhang (ma_donhang),
    INDEX idx_bienthe (ma_bienthe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 14: THANHTOAN - Giao dịch thanh toán
-- ============================================================
CREATE TABLE thanhtoan (
    ma_thanhtoan   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_donhang     INT UNSIGNED NOT NULL,
    so_tien        DECIMAL(15,2) NOT NULL,
    phuong_thuc    ENUM('cod','chuyen_khoan','momo','vnpay','zalopay','visa_mastercard') NOT NULL,
    trang_thai     ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
    ma_giao_dich   VARCHAR(100)  DEFAULT NULL COMMENT 'Transaction ID từ cổng thanh toán',
    thong_tin_them JSON          DEFAULT NULL COMMENT 'Dữ liệu raw từ payment gateway',
    ngay_thanhtoan DATETIME DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tt_dh FOREIGN KEY (ma_donhang)
        REFERENCES donhang(ma_donhang) ON DELETE CASCADE,
    INDEX idx_donhang  (ma_donhang),
    INDEX idx_trangThai(trang_thai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 15: DANHGIA - Đánh giá sản phẩm
-- ============================================================
CREATE TABLE danhgia (
    ma_danhgia     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_sanpham     INT UNSIGNED NOT NULL,
    ma_user        INT UNSIGNED NOT NULL,
    ma_ctdh        INT UNSIGNED DEFAULT NULL COMMENT 'Chỉ đánh giá sau khi mua',
    diem           TINYINT UNSIGNED NOT NULL COMMENT '1-5 sao',
    tieu_de        VARCHAR(200),
    noi_dung       TEXT,
    trang_thai     ENUM('cho_duyet','da_duyet','da_tu_choi') NOT NULL DEFAULT 'cho_duyet',
    luot_thich     INT UNSIGNED NOT NULL DEFAULT 0,
    ngay_lap       DATETIME DEFAULT CURRENT_TIMESTAMP,
    cap_nhat_ngay  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dg_sp   FOREIGN KEY (ma_sanpham) REFERENCES sanpham(ma_sanpham) ON DELETE CASCADE,
    CONSTRAINT fk_dg_user FOREIGN KEY (ma_user)    REFERENCES users(ma_user) ON DELETE CASCADE,
    CONSTRAINT fk_dg_ctdh FOREIGN KEY (ma_ctdh)   REFERENCES chitiet_donhang(ma_ctdh),
    CONSTRAINT ck_diem CHECK (diem BETWEEN 1 AND 5),
    UNIQUE KEY uq_user_bienthe_dg (ma_user, ma_ctdh),
    INDEX idx_sp     (ma_sanpham),
    INDEX idx_tt     (trang_thai),
    INDEX idx_user   (ma_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 16: HINHANH_DANHGIA - Hình ảnh trong đánh giá
-- ============================================================
CREATE TABLE hinhanh_danhgia (
    ma_anh         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_danhgia     INT UNSIGNED NOT NULL,
    image_url      VARCHAR(500) NOT NULL,
    thu_tu         TINYINT UNSIGNED DEFAULT 0,
    CONSTRAINT fk_anh_dg FOREIGN KEY (ma_danhgia)
        REFERENCES danhgia(ma_danhgia) ON DELETE CASCADE,
    INDEX idx_dg (ma_danhgia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 17: PHAN_HOI_DANHGIA - Admin phản hồi đánh giá
-- ============================================================
CREATE TABLE phanhoi_danhgia (
    ma_phanhoi     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_danhgia     INT UNSIGNED NOT NULL UNIQUE,
    ma_admin       INT UNSIGNED NOT NULL,
    noi_dung       TEXT NOT NULL,
    ngay_phanhoi   DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_phanhoi_dg    FOREIGN KEY (ma_danhgia) REFERENCES danhgia(ma_danhgia) ON DELETE CASCADE,
    CONSTRAINT fk_phanhoi_admin FOREIGN KEY (ma_admin)   REFERENCES users(ma_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 18: DSYEUTHICH - Danh sách yêu thích (Wishlist)
-- ============================================================
CREATE TABLE dsyeuthich (
    ma_dsyt        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_user        INT UNSIGNED NOT NULL,
    ma_sanpham     INT UNSIGNED NOT NULL,
    ngay_them      DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dsyt_user FOREIGN KEY (ma_user)    REFERENCES users(ma_user) ON DELETE CASCADE,
    CONSTRAINT fk_dsyt_sp   FOREIGN KEY (ma_sanpham) REFERENCES sanpham(ma_sanpham) ON DELETE CASCADE,
    UNIQUE KEY uq_user_sp (ma_user, ma_sanpham),
    INDEX idx_user (ma_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 19: LICH_SU_XEMSP - Lịch sử xem sản phẩm
-- ============================================================
CREATE TABLE lichsu_xem_sp (
    ma_ls          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_user        INT UNSIGNED NOT NULL,
    ma_sanpham     INT UNSIGNED NOT NULL,
    ngay_xem       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ls_xem_user FOREIGN KEY (ma_user)    REFERENCES users(ma_user) ON DELETE CASCADE,
    CONSTRAINT fk_ls_xem_sp   FOREIGN KEY (ma_sanpham) REFERENCES sanpham(ma_sanpham) ON DELETE CASCADE,
    UNIQUE KEY uq_user_sp_xem (ma_user, ma_sanpham),
    INDEX idx_user (ma_user),
    INDEX idx_sp   (ma_sanpham)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 20: BANNER - Banner quảng cáo trang chủ
-- ============================================================
CREATE TABLE banner (
    ma_banner      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tieu_de        VARCHAR(200),
    hinh_anh_url   VARCHAR(500) NOT NULL,
    link_url       VARCHAR(500),
    vi_tri         ENUM('trang_chu_chinh','trang_chu_phu','danh_muc') NOT NULL DEFAULT 'trang_chu_chinh',
    thu_tu         TINYINT UNSIGNED DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    ngay_bat_dau   DATETIME DEFAULT NULL,
    ngay_ket_thuc  DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BẢNG 21: AUDIT_LOG - Nhật ký hành động admin
-- ============================================================
CREATE TABLE audit_log (
    ma_log         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ma_admin       INT UNSIGNED NOT NULL,
    hanh_dong      VARCHAR(100) NOT NULL COMMENT 'Vd: UPDATE_ORDER_STATUS, DELETE_PRODUCT',
    bang_lien_quan VARCHAR(100) COMMENT 'Tên bảng bị tác động',
    ma_ban_ghi     INT UNSIGNED COMMENT 'ID bản ghi bị tác động',
    du_lieu_cu     JSON DEFAULT NULL COMMENT 'Dữ liệu trước khi thay đổi',
    du_lieu_moi    JSON DEFAULT NULL COMMENT 'Dữ liệu sau khi thay đổi',
    dia_chi_ip     VARCHAR(50),
    ngay_hanh_dong DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_admin FOREIGN KEY (ma_admin) REFERENCES users(ma_user),
    INDEX idx_admin   (ma_admin),
    INDEX idx_hanh_dong (hanh_dong),
    INDEX idx_ngay    (ngay_hanh_dong DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$

-- Trigger: Tự động cập nhật điểm đánh giá trung bình của sản phẩm
CREATE TRIGGER trg_update_diem_danhgia_after_insert
AFTER INSERT ON danhgia FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'da_duyet' THEN
        UPDATE sanpham
        SET diem_danh_gia = (
            SELECT COALESCE(AVG(diem), 0)
            FROM danhgia
            WHERE ma_sanpham = NEW.ma_sanpham AND trang_thai = 'da_duyet'
        )
        WHERE ma_sanpham = NEW.ma_sanpham;
    END IF;
END$$

CREATE TRIGGER trg_update_diem_danhgia_after_update
AFTER UPDATE ON danhgia FOR EACH ROW
BEGIN
    UPDATE sanpham
    SET diem_danh_gia = (
        SELECT COALESCE(AVG(diem), 0)
        FROM danhgia
        WHERE ma_sanpham = NEW.ma_sanpham AND trang_thai = 'da_duyet'
    )
    WHERE ma_sanpham = NEW.ma_sanpham;
END$$

CREATE TRIGGER trg_update_diem_danhgia_after_delete
AFTER DELETE ON danhgia FOR EACH ROW
BEGIN
    UPDATE sanpham
    SET diem_danh_gia = (
        SELECT COALESCE(AVG(diem), 0)
        FROM danhgia
        WHERE ma_sanpham = OLD.ma_sanpham AND trang_thai = 'da_duyet'
    )
    WHERE ma_sanpham = OLD.ma_sanpham;
END$$

-- Trigger: Tự động trừ tồn kho khi đặt hàng thành công
-- (Gọi sau khi insert chitiet_donhang; tồn kho sẽ bị đặt ở application layer khi confirm)
-- Trigger: Tăng tổng_da_ban khi đơn hàng chuyển sang da_giao
CREATE TRIGGER trg_tang_dabban_khi_dagiao
AFTER UPDATE ON donhang FOR EACH ROW
BEGIN
    IF NEW.trang_thai = 'da_giao' AND OLD.trang_thai <> 'da_giao' THEN
        UPDATE sanpham sp
        JOIN (
            SELECT b.ma_sanpham, SUM(ctdh.so_luong) AS tong_sl
            FROM chitiet_donhang ctdh
            JOIN bienthe_sanpham b ON ctdh.ma_bienthe = b.ma_bienthe
            WHERE ctdh.ma_donhang = NEW.ma_donhang
            GROUP BY b.ma_sanpham
        ) AS tong ON sp.ma_sanpham = tong.ma_sanpham
        SET sp.tong_da_ban = sp.tong_da_ban + tong.tong_sl;
    END IF;
END$$

-- Trigger: Tăng số lần đã dùng mã khuyến mãi
CREATE TRIGGER trg_tang_solandung_km
AFTER INSERT ON lichsu_dung_km FOR EACH ROW
BEGIN
    UPDATE ma_khuyenmai
    SET so_lan_da_dung = so_lan_da_dung + 1
    WHERE ma_km = NEW.ma_km;
END$$

DELIMITER ;

-- ============================================================
-- VIEWS HỮU ÍCH
-- ============================================================

-- View: Sản phẩm kèm thông tin đầy đủ (giá thấp nhất của biến thể)
CREATE OR REPLACE VIEW v_sanpham_tongquan AS
SELECT
    sp.ma_sanpham,
    sp.ten_sanpham,
    sp.slug,
    th.ten_thuonghieu,
    th.logo_url as logo_thuonghieu,
    dm.ten_danhmuc,
    sp.mo_ta_ngan,
    sp.diem_danh_gia,
    sp.tong_da_ban,
    sp.tong_luot_xem,
    sp.is_noi_bat,
    sp.is_hang_moi,
    sp.is_active,
    -- Phạm vi giá từ biến thể
    MIN(b.gia) AS gia_thap_nhat,
    MAX(b.gia) AS gia_cao_nhat,
    MIN(b.gia_goc) AS gia_goc_thap_nhat,
    -- Ảnh chính
    (SELECT image_url FROM hinhanh_sanpham h
     WHERE h.ma_sanpham = sp.ma_sanpham AND h.la_anh_chinh = 1
     LIMIT 1) AS anh_chinh,
    -- Đếm biến thể còn hàng
    SUM(CASE WHEN b.ton_kho > 0 THEN 1 ELSE 0 END) AS so_bienthe_conhang,
    COUNT(DISTINCT b.mau_sac) AS so_mau,
    sp.ngay_lap
FROM sanpham sp
JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
JOIN danhmuc dm    ON sp.ma_danhmuc    = dm.ma_danhmuc
LEFT JOIN bienthe_sanpham b ON sp.ma_sanpham = b.ma_sanpham AND b.is_active = 1
GROUP BY sp.ma_sanpham;

-- View: Thống kê đơn hàng theo ngày
CREATE OR REPLACE VIEW v_thongke_donhang AS
SELECT
    DATE(ngay_dat)               AS ngay,
    COUNT(*)                     AS tong_don,
    SUM(CASE WHEN trang_thai NOT IN ('da_huy','cho_hoan_tien','da_hoan_tien') THEN 1 ELSE 0 END) AS don_hop_le,
    SUM(CASE WHEN trang_thai = 'da_huy' THEN 1 ELSE 0 END)                                       AS don_huy,
    SUM(CASE WHEN trang_thai NOT IN ('da_huy','cho_hoan_tien','da_hoan_tien') THEN tong_thanh_toan ELSE 0 END) AS doanh_thu
FROM donhang
GROUP BY DATE(ngay_dat);

-- ============================================================
-- DỮ LIỆU MẪU - THƯƠNG HIỆU
-- ============================================================
INSERT INTO thuonghieu (ten_thuonghieu, slug, quoc_gia, mo_ta) VALUES
('Apple',     'apple',     'Mỹ',         'Thương hiệu iPhone cao cấp đến từ Mỹ'),
('Samsung',   'samsung',   'Hàn Quốc',   'Tập đoàn điện tử hàng đầu Hàn Quốc'),
('Xiaomi',    'xiaomi',    'Trung Quốc', 'Điện thoại giá tốt - hiệu năng cao'),
('OPPO',      'oppo',      'Trung Quốc', 'Chuyên về camera và sạc nhanh'),
('Vivo',      'vivo',      'Trung Quốc', 'Điện thoại phổ thông và tầm trung'),
('Realme',    'realme',    'Trung Quốc', 'Hiệu năng cao, giá phải chăng'),
('OnePlus',   'oneplus',   'Trung Quốc', 'Flagship killer nổi tiếng'),
('Nokia',     'nokia',     'Phần Lan',   'Thương hiệu lâu đời, bền bỉ'),
('Motorola',  'motorola',  'Mỹ',         'Điện thoại đa dạng phân khúc'),
('Google',    'google',    'Mỹ',         'Điện thoại Pixel hỗ trợ AI');

-- ============================================================
-- DỮ LIỆU MẪU - DANH MỤC
-- ============================================================
INSERT INTO danhmuc (ten_danhmuc, slug, mo_ta, thu_tu) VALUES
('iPhone',              'iphone',           'Dòng điện thoại Apple iPhone', 1),
('Samsung Galaxy',      'samsung-galaxy',   'Dòng điện thoại Samsung Galaxy', 2),
('Điện thoại Xiaomi',   'dienthoai-xiaomi', 'Các dòng Xiaomi, Redmi, POCO', 3),
('OPPO',                'oppo',             'Điện thoại OPPO các dòng', 4),
('Điện thoại phổ thông','dienthoai-photong','Điện thoại cơ bản, pin khủng', 5),
('Flagship',            'flagship',         'Điện thoại cao cấp nhất', 6),
('Tầm trung',           'tam-trung',        'Điện thoại tầm trung 4-10 triệu', 7),
('Gaming Phone',        'gaming-phone',     'Điện thoại chuyên gaming', 8);

-- ============================================================
-- DỮ LIỆU MẪU - TÀI KHOẢN ADMIN
-- ============================================================
INSERT INTO users (ten_user, email, password_hash, hovaten, SDT, quyen) VALUES
('admin', 'admin@bandienthoai.vn', '$2b$12$EXAMPLE_HASH_HERE', 'Quản Trị Viên', '0900000000', 'admin');

-- ============================================================
-- STORED PROCEDURES
-- ============================================================

DELIMITER $$

-- Procedure: Đặt hàng (tạo đơn + trừ tồn kho trong 1 transaction)
CREATE PROCEDURE sp_dat_hang(
    IN p_ma_user         INT UNSIGNED,
    IN p_ten_nguoi_nhan  VARCHAR(100),
    IN p_sdt             VARCHAR(20),
    IN p_tinh_thanh      VARCHAR(100),
    IN p_quan_huyen      VARCHAR(100),
    IN p_phuong_xa       VARCHAR(100),
    IN p_diachi_cu_the   VARCHAR(255),
    IN p_phi_giao_hang   DECIMAL(15,2),
    IN p_ma_km           INT UNSIGNED,
    IN p_so_tien_giam    DECIMAL(15,2),
    IN p_phuong_thuc_TT  VARCHAR(30),
    OUT p_ma_donhang     INT UNSIGNED,
    OUT p_loi            VARCHAR(500)
)
BEGIN
    DECLARE v_tong_tien_hang DECIMAL(15,2) DEFAULT 0;
    DECLARE v_tong_thanh_toan DECIMAL(15,2);
    DECLARE v_ma_gio INT UNSIGNED;
    DECLARE v_thieu_hang TINYINT DEFAULT 0;
    DECLARE v_donhang_code VARCHAR(30);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_loi = 'Lỗi hệ thống khi đặt hàng';
        SET p_ma_donhang = NULL;
    END;

    START TRANSACTION;

    -- Lấy ma_gio của user
    SELECT ma_gio INTO v_ma_gio FROM giohang WHERE ma_user = p_ma_user;
    IF v_ma_gio IS NULL THEN
        SET p_loi = 'Giỏ hàng trống';
        ROLLBACK;
        LEAVE sp_dat_hang;
    END IF;

    -- Kiểm tra tồn kho
    SELECT COUNT(*) INTO v_thieu_hang
    FROM chitiet_giohang ctgh
    JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
    WHERE ctgh.ma_gio = v_ma_gio AND b.ton_kho < ctgh.so_luong;

    IF v_thieu_hang > 0 THEN
        SET p_loi = 'Có sản phẩm không đủ số lượng trong kho';
        ROLLBACK;
        LEAVE sp_dat_hang;
    END IF;

    -- Tính tổng tiền hàng
    SELECT SUM(ctgh.so_luong * b.gia) INTO v_tong_tien_hang
    FROM chitiet_giohang ctgh
    JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
    WHERE ctgh.ma_gio = v_ma_gio;

    SET v_tong_thanh_toan = v_tong_tien_hang + p_phi_giao_hang - COALESCE(p_so_tien_giam, 0);

    -- Tạo mã đơn hàng
    SET v_donhang_code = CONCAT('DH', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND()*9999), 4, '0'));

    -- Tạo đơn hàng
    INSERT INTO donhang (
        ma_donhang_code, ma_user, ten_nguoi_nhan, SDT_nguoi_nhan,
        tinh_thanh, quan_huyen, phuong_xa, dia_chi_cu_the,
        tong_tien_hang, phi_giao_hang, so_tien_giam, tong_thanh_toan,
        ma_km, phuong_thuc_TT
    ) VALUES (
        v_donhang_code, p_ma_user, p_ten_nguoi_nhan, p_sdt,
        p_tinh_thanh, p_quan_huyen, p_phuong_xa, p_diachi_cu_the,
        v_tong_tien_hang, p_phi_giao_hang, COALESCE(p_so_tien_giam, 0), v_tong_thanh_toan,
        p_ma_km, p_phuong_thuc_TT
    );

    SET p_ma_donhang = LAST_INSERT_ID();

    -- Copy giỏ hàng → chi tiết đơn hàng
    INSERT INTO chitiet_donhang (
        ma_donhang, ma_bienthe, ten_sanpham, ram_gb, rom_gb, mau_sac,
        hinh_anh_url, so_luong, don_gia, thanh_tien
    )
    SELECT
        p_ma_donhang, ctgh.ma_bienthe,
        sp.ten_sanpham, b.ram_gb, b.rom_gb, b.mau_sac,
        (SELECT image_url FROM hinhanh_sanpham h
         WHERE h.ma_bienthe = b.ma_bienthe AND h.la_anh_chinh = 1 LIMIT 1),
        ctgh.so_luong, b.gia, ctgh.so_luong * b.gia
    FROM chitiet_giohang ctgh
    JOIN bienthe_sanpham b ON ctgh.ma_bienthe = b.ma_bienthe
    JOIN sanpham sp ON b.ma_sanpham = sp.ma_sanpham
    WHERE ctgh.ma_gio = v_ma_gio;

    -- Trừ tồn kho
    UPDATE bienthe_sanpham b
    JOIN chitiet_giohang ctgh ON b.ma_bienthe = ctgh.ma_bienthe
    SET b.ton_kho = b.ton_kho - ctgh.so_luong
    WHERE ctgh.ma_gio = v_ma_gio;

    -- Xóa giỏ hàng
    DELETE FROM chitiet_giohang WHERE ma_gio = v_ma_gio;

    COMMIT;
    SET p_loi = NULL;
END$$

DELIMITER ;

-- ============================================================
-- CÁC QUERIES MẪU HỮU ÍCH
-- ============================================================

/*
-- 1. Lấy danh sách sản phẩm với giá thấp nhất và ảnh chính
SELECT * FROM v_sanpham_tongquan WHERE is_active = 1 ORDER BY tong_da_ban DESC LIMIT 20;

-- 2. Lấy tất cả biến thể của 1 sản phẩm
SELECT b.*, GROUP_CONCAT(h.image_url ORDER BY h.thu_tu) AS anh_bienthe
FROM bienthe_sanpham b
LEFT JOIN hinhanh_sanpham h ON b.ma_bienthe = h.ma_bienthe
WHERE b.ma_sanpham = 1 AND b.is_active = 1
GROUP BY b.ma_bienthe;

-- 3. Lấy đơn hàng của 1 user kèm chi tiết
SELECT dh.*, ctdh.*
FROM donhang dh
JOIN chitiet_donhang ctdh ON dh.ma_donhang = ctdh.ma_donhang
WHERE dh.ma_user = 1
ORDER BY dh.ngay_dat DESC;

-- 4. Top 10 sản phẩm bán chạy nhất
SELECT sp.ten_sanpham, th.ten_thuonghieu, sp.tong_da_ban, sp.diem_danh_gia
FROM sanpham sp JOIN thuonghieu th ON sp.ma_thuonghieu = th.ma_thuonghieu
WHERE sp.is_active = 1
ORDER BY sp.tong_da_ban DESC LIMIT 10;

-- 5. Doanh thu theo tháng
SELECT YEAR(ngay_dat) nam, MONTH(ngay_dat) thang,
       COUNT(*) so_don,
       SUM(tong_thanh_toan) doanh_thu
FROM donhang
WHERE trang_thai NOT IN ('da_huy', 'da_hoan_tien')
GROUP BY YEAR(ngay_dat), MONTH(ngay_dat)
ORDER BY nam DESC, thang DESC;

-- 6. Kiểm tra mã khuyến mãi hợp lệ
SELECT * FROM ma_khuyenmai
WHERE ma_code = 'SALE50'
  AND is_active = 1
  AND NOW() BETWEEN ngay_bat_dau AND ngay_ket_thuc
  AND (so_lan_toi_da IS NULL OR so_lan_da_dung < so_lan_toi_da);
*/
