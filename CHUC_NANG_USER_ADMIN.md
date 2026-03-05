# Xác Định Chức Năng User & Admin

## 👥 Vai Trò trong Hệ Thống

| Vai Trò | Mô Tả | Quyền Hạn |
|---------|-------|----------|
| **Customer** | Khách hàng mua sắm | Giới hạn - chỉ xem & mua |
| **Admin** | Quản trị viên hệ thống | Toàn quyền quản lý |

---

## 📋 CHỨC NĂNG CUSTOMER (user_type = 'customer')

### 1️⃣ **Xem & Tìm Kiếm Sản Phẩm**
- ✅ Xem danh sách các danh mục xe đạp
- ✅ Tìm kiếm sản phẩm theo tên
- ✅ Lọc theo: danh mục, giá, thương hiệu, đánh giá
- ✅ Xem chi tiết sản phẩm (hình ảnh, thông số, giá)
- ✅ Xem đánh giá & bình luận sản phẩm

### 2️⃣ **Quản Lý Giỏ Hàng**
- ✅ Thêm sản phẩm vào giỏ hàng
- ✅ **Kiểm tra tồn kho**: số lượng thêm không vượt quá tồn kho có sẵn
- ✅ Xem giỏ hàng (danh sách sản phẩm + giá)
- ✅ Thay đổi số lượng sản phẩm trong giỏ
- ✅ **Kiểm tra tồn kho lại**: khi thay đổi số lượng, không ước vượt quá tồn kho
- ✅ Xóa sản phẩm khỏi giỏ
- ✅ Xóa toàn bộ giỏ hàng
- ❌ Không được xem giỏ hàng của user khác

### 3️⃣ **Thanh Toán & Đặt Hàng**
- ✅ Xem tóm tắt đơn hàng (sản phẩm, giá, tổng)
- ✅ **Kiểm tra lại tồn kho** trước khi xác nhận: nếu sản phẩm hết hàng được thông báo
- ✅ Nhập địa chỉ giao hàng
- ✅ Chọn phương thức thanh toán:
  - Thẻ tín dụng (Credit Card)
  - Chuyển khoản ngân hàng (Bank Transfer)
  - Thanh toán khi nhận (COD)
- ✅ Xác nhận đặt hàng
- ✅ Xem mã đơn hàng & trạng thái thanh toán
- ❌ Không được hủy đơn sau khi xác nhận (phải liên hệ admin)

### 4️⃣ **Quản Lý Đơn Hàng**
- ✅ Xem danh sách đơn hàng của mình
- ✅ Xem chi tiết từng đơn hàng:
  - Sản phẩm & số lượng
  - Tổng tiền
  - Trạng thái: pending, confirmed, shipped, delivered, cancelled
  - Thời gian giao dự kiến
- ✅ Theo dõi trạng thái giao hàng
- ✅ Xem ngày giao hàng thực tế
- ❌ Không được chỉnh sửa đơn hàng đã xác nhận

### 5️⃣ **Đánh Giá & Bình Luận**
- ✅ Viết đánh giá sản phẩm (sau khi mua)
- ✅ Chọn sao đánh giá (1-5 sao)
- ✅ Viết nội dung bình luận
- ✅ Xem trạng thái đánh giá:
  - pending: chờ duyệt
  - approved: được hiển thị
  - rejected: bị từ chối
- ✅ Chỉnh sửa đánh giá chưa duyệt
- ✅ Xóa đánh giá của mình
- ❌ Không được xem đánh giá bị rejected

### 6️⃣ **Danh Sách Yêu Thích**
- ✅ Thêm sản phẩm vào danh sách yêu thích
- ✅ Xem danh sách yêu thích
- ✅ Xóa sản phẩm khỏi danh sách yêu thích
- ✅ Thêm sản phẩm yêu thích vào giỏ hàng

### 7️⃣ **Quản Lý Tài Khoản**
- ✅ Đăng ký tài khoản mới
- ✅ Đăng nhập
- ✅ Xem thông tin tài khoản:
  - Username, email
  - Họ tên, số điện thoại
  - Địa chỉ
- ✅ Cập nhật thông tin cá nhân
- ✅ Thay đổi mật khẩu
- ✅ Đăng xuất
- ✅ Xóa tài khoản (soft delete - mark as 'inactive') lỗi khóa tài khoản
- ❌ Không được xem thông tin user khác

### 8️⃣ **Giảm Giá Sản Phẩm**
- ✅ Xem sản phẩm có giảm giá (hiển thị % giảm)
- ✅ Xem giá gốc vs giá sau giảm
- ✅ Tự động tính giá mới khi đã giảm
- ❌ Không được tạo hoặc thay đổi % giảm

---

## 🛠️ CHỨC NĂNG ADMIN (user_type = 'admin')

### 1️⃣ **Quản Lý Danh Mục Sản Phẩm**
- ✅ Xem danh sách danh mục
- ✅ Thêm danh mục mới
- ✅ Chỉnh sửa tên danh mục
- ✅ Chỉnh sửa mô tả danh mục
- ✅ Xóa danh mục (soft delete)
- ✅ Bật/tắt danh mục (is_active)

### 2️⃣ **Quản Lý Thương Hiệu**
- ✅ Xem danh sách thương hiệu
- ✅ Thêm thương hiệu mới
- ✅ Chỉnh sửa thương hiệu
- ✅ Xóa thương hiệu
- ✅ Bật/tắt thương hiệu

### 3️⃣ **Quản Lý Sản Phẩm**
- ✅ Xem danh sách tất cả sản phẩm
- ✅ Thêm sản phẩm mới:
  - Tên, mã sản phẩm
  - Danh mục, thương hiệu
  - Giá bán
  - Kích thước, màu sắc
  - Mô tả chi tiết
- ✅ Chỉnh sửa thông tin sản phẩm
- ✅ Xóa sản phẩm
- ✅ Bật/tắt sản phẩm (is_active)
- ✅ Quản lý hình ảnh sản phẩm:
  - Tải lên hình ảnh
  - Đặt hình chính (is_main)
  - Xóa hình ảnh

### 5️⃣ **Quản Lý Đơn Hàng**
- ✅ Xem danh sách tất cả đơn hàng
- ✅ Lọc đơn hàng theo:
  - Trạng thái (pending, confirmed, shipped, delivered, cancelled)
  - Khoảng thời gian
  - Khách hàng
- ✅ Xem chi tiết đơn hàng
- ✅ Cập nhật trạng thái đơn:
  - pending → confirmed (xác nhận đơn)
  - confirmed → shipped (đã gửi)
  - shipped → delivered (đã giao)
  - cancelled (hủy đơn)
- ✅ Hủy đơn hàng & hoàn tiền
- ✅ Xuất hóa đơn/biên lai
- ✅ In danh sách vận chuyển

### 6️⃣ **Quản Lý Thanh Toán**
- ✅ Xem danh sách thanh toán
- ✅ Xem trạng thái thanh toán:
  - pending: chờ thanh toán
  - paid: đã thanh toán
  - failed: thất bại
- ✅ Cập nhật trạng thái thanh toán
- ✅ Xem chi tiết giao dịch:
  - Mã giao dịch (transaction_id)
  - Phương thức thanh toán
  - Số tiền
  - Ngày thanh toán
- ✅ Hoàn tiền/hoàn lại thanh toán

### 8️⃣ **Quản Lý Giảm Giá**
- ✅ Cài đặt % giảm giá cho sản phẩm (0% - 90%)
- ✅ Cập nhật % giảm giá
- ✅ Xóa giảm giá (đặt về 0%)
- ✅ Xem danh sách sản phẩm có giảm giá
- ✅ Xem giá gốc vs giá sau giảm
- ✅ Bật/tắt giảm giá cho sản phẩm
- ✅ Áp dụng giảm giá cho danh mục (tất cả sản phẩm trong danh mục)

### 9️⃣ **Quản Lý Đánh Giá & Bình Luận**
- ✅ Xem danh sách đánh giá chờ duyệt (status = 'pending')
- ✅ Duyệt đánh giá (status = 'approved')
- ✅ Từ chối đánh giá (status = 'rejected')
- ✅ Xóa đánh giá không phù hợp
- ✅ Xem tất cả đánh giá của sản phẩm
- ✅ Xem hình ảnh đánh giá

### 8️⃣ **Quản Lý Mã Khuyến Mãi**
- ✅ Xem danh sách mã khuyến mãi
- ✅ Tạo mã khuyến mãi mới:
  - Mã code
  - Loại chiết khấu: % hoặc số tiền cụ thể
  - Giá tối thiểu để áp dụng
  - Số lần sử dụng tối đa
  - Thời hạn (start_date, end_date)
- ✅ Chỉnh sửa mã khuyến mãi


### 🔟 **Quản Lý Người Dùng**
- ✅ Xem danh sách tất cả người dùng
- ✅ Tìm kiếm người dùng theo:
  - Username, email
  - Tên
- ✅ Xem thông tin chi tiết user
- ✅ Xem lịch sử mua hàng của user
- ✅ Cập nhật trạng thái người dùng:
  - active: hoạt động
  - inactive: vô hiệu
  - banned: bị cấm
- ✅ Khoá tài khoản (ban user)
- ✅ Mở khoá tài khoản (unban)
- ✅ Xem lịch mua hàng của từng user
- ❌ Không được xem mật khẩu user

### 1️⃣1️⃣ **Báo Cáo & Thống Kê**
- ✅ Xem tổng doanh thu (bán/tháng/năm)
- ✅ Xem số đơn hàng (thành công, thất bại, hủy)
- ✅ Sản phẩm bán chạy nhất (top 10)
- ✅ Sản phẩm bán chậm nhất
- ✅ Khách hàng mua nhiều nhất
- ✅ Danh mục bán chạy nhất
- ✅ Thương hiệu được yêu thích
- ✅ Tỷ lệ đánh giá sao trung bình
- ✅ Xuất báo cáo (PDF, Excel)

### 1️⃣2️⃣ **Quản Lý Tài Khoản Admin**
- ✅ Xem thông tin tài khoản
- ✅ Thay đổi mật khẩu
- ✅ Xem lịch hoạt động (audit log)
- ✅ Đăng xuất

---

## 🔐 PERMISSION MATRIX (Bảng Quyền Hạn)

| Chức Năng | Customer | Admin |
|-----------|----------|-------|
| **Sản Phẩm - Xem** | ✅ | ✅ |
| **Sản Phẩm - Tạo/Edit/Delete** | ❌ | ✅ |
| **Danh Mục - Xem** | ✅ | ✅ |
| **Danh Mục - Tạo/Edit/Delete** | ❌ | ✅ |
| **Thương Hiệu - Xem** | ✅ | ✅ |
| **Thương Hiệu - Tạo/Edit/Delete** | ❌ | ✅ |
| **Giỏ Hàng - Xem Riêng** | ✅ | ❌ |
| **Giỏ Hàng - Xem Toàn Bộ** | ❌ | ✅ |
| **Đơn Hàng - Xem Riêng** | ✅ | ❌ |
| **Đơn Hàng - Xem Toàn Bộ** | ❌ | ✅ |
| **Đơn Hàng - Cập Nhật Trạng Thái** | ❌ | ✅ |
| **Thanh Toán - Xem Riêng** | ✅ | ❌ |
| **Thanh Toán - Xem Toàn Bộ** | ❌ | ✅ |
| **Thanh Toán - Hoàn Tiền** | ❌ | ✅ |
| **Đánh Giá - Viết** | ✅ | ❌ |
| **Đánh Giá - Duyệt/Xóa** | ❌ | ✅ |
| **Giảm Giá - Xem** | ✅ | ✅ |
| **Giảm Giá - Quản Lý** | ❌ | ✅ |
| **Người Dùng - Xem Riêng** | ✅ | ❌ |
| **Người Dùng - Quản Lý Toàn Bộ** | ❌ | ✅ |
| **Tồn Kho - Xem** | ❌ | ✅ |
| **Tồn Kho - Cập Nhật** | ❌ | ✅ |
| **Báo Cáo - Xem** | ❌ | ✅ |

---

## 🔗 IMPLEMENT trong Backend (Node.js/Python)

### Kiểm tra quyền hạn (Middleware)
```python
def check_role(required_role):
    def decorator(func):
        def wrapper(*args, **kwargs):
            user = get_current_user()  # Từ JWT token
            if user.user_type != required_role:
                return {"error": "Không có quyền truy cập"}, 403
            return func(*args, **kwargs)
        return wrapper
    return decorator

# Sử dụng
@app.route('/admin/products', methods=['POST'])
@check_role('admin')
def create_product():
    # Chỉ admin mới được vào
    pass

@app.route('/user/orders', methods=['GET'])
@check_role('customer')
def get_my_orders():
    # Chỉ customer mới được vào
    pass
```

### Kiểm tra quyền truy cập dữ liệu
```python
def get_orders(user_id, current_user):
    """
    Nếu customer: chỉ lấy đơn hàng của mình
    Nếu admin: lấy tất cả đơn hàng
    """
    if current_user.user_type == 'customer':
        if user_id != current_user.user_id:
            return {"error": "Không thể xem đơn hàng của người khác"}, 403
        return db.query(Order).filter(Order.user_id == user_id).all()
    else:  # admin
        return db.query(Order).all()
```

---

## 📝 Ghi Chú Quan Trọng

1. **Customer** không thể xem/chỉnh sửa dữ liệu của customer khác
2. **Admin** có toàn quyền quản lý hệ thống (cẩn thận)
3. Sử dụng **JWT token** trong header: `Authorization: Bearer <token>`
4. Token chứa `user_type` để kiểm tra quyền
5. Luôn **log hoạt động** của admin (audit trail)
6. Các hành động quan trọng (xóa, hoàn tiền) cần xác nhận 2 bước
7. Password phải **hash** với bcrypt, không lưu plaintext
