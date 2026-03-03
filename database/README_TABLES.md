# Hướng dẫn các bảng Database

## Tổng quan

Database `caffe` cần **tối thiểu 1 bảng** để hệ thống hoạt động, nhưng khuyến nghị tạo đầy đủ để có tính năng bảo mật và quản lý tốt hơn.

## Các bảng cần tạo

### ✅ BẢNG BẮT BUỘC (Phải có)

#### 1. **users** - Bảng người dùng
**Mục đích:** Lưu thông tin tài khoản người dùng

**Các trường:**
- `id` - ID tự tăng (Primary Key)
- `name` - Tên người dùng
- `email` - Email (Unique, dùng để đăng nhập)
- `password` - Mật khẩu đã hash
- `status` - Trạng thái: active, inactive, banned
- `role` - Vai trò: user, admin
- `phone` - Số điện thoại (tùy chọn)
- `avatar` - Đường dẫn ảnh đại diện (tùy chọn)
- `email_verified_at` - Thời gian xác thực email
- `last_login_at` - Lần đăng nhập cuối
- `created_at` - Thời gian tạo
- `updated_at` - Thời gian cập nhật

**Index:**
- `idx_email` - Tìm kiếm theo email
- `idx_status` - Lọc theo trạng thái
- `idx_role` - Lọc theo vai trò

---

### ⚠️ BẢNG KHUYẾN NGHỊ (Nên có)

#### 2. **login_attempts** - Lịch sử đăng nhập
**Mục đích:** Tracking các lần đăng nhập để bảo mật và phân tích

**Các trường:**
- `id` - ID tự tăng
- `user_id` - ID user (nếu đăng nhập thành công)
- `email` - Email đã thử đăng nhập
- `ip_address` - Địa chỉ IP
- `user_agent` - Thông tin trình duyệt
- `success` - 1 = thành công, 0 = thất bại
- `attempt_time` - Thời gian thử đăng nhập

**Lợi ích:**
- Phân tích hành vi đăng nhập
- Phát hiện tấn công brute force
- Audit trail cho bảo mật

---

### 🔧 BẢNG TÙY CHỌN (Có thể thêm sau)

#### 3. **refresh_tokens** - Refresh token
**Mục đích:** Lưu refresh token để làm mới JWT token

**Khi nào cần:**
- Khi muốn refresh token mà không cần đăng nhập lại
- Token có thời hạn ngắn, cần refresh thường xuyên

**Các trường:**
- `id` - ID tự tăng
- `user_id` - ID user
- `token` - Refresh token (Unique)
- `expires_at` - Thời gian hết hạn
- `revoked_at` - Thời gian thu hồi (nếu user logout)

---

#### 4. **password_resets** - Reset mật khẩu
**Mục đích:** Lưu token reset mật khẩu khi user quên mật khẩu

**Khi nào cần:**
- Khi có tính năng "Quên mật khẩu"
- Gửi link reset qua email

**Các trường:**
- `id` - ID tự tăng
- `email` - Email cần reset
- `token` - Token reset (Unique)
- `expires_at` - Thời gian hết hạn (thường 1 giờ)
- `used_at` - Thời gian sử dụng token

---

#### 5. **user_sessions** - Quản lý session
**Mục đích:** Quản lý session của user (thay vì dùng file system)

**Khi nào cần:**
- Khi muốn quản lý session trong database
- Có thể xem danh sách session đang active
- Có thể logout tất cả thiết bị

**Các trường:**
- `id` - ID tự tăng
- `user_id` - ID user
- `session_token` - Token session (Unique)
- `ip_address` - Địa chỉ IP
- `user_agent` - Thông tin trình duyệt
- `last_activity` - Hoạt động cuối
- `expires_at` - Thời gian hết hạn

---

## Cách tạo database

### Option 1: Tạo tất cả bảng (Khuyến nghị)
```bash
mysql -u root -p < database/schema.sql
```

### Option 2: Chỉ tạo bảng bắt buộc
Chạy SQL sau trong MySQL:
```sql
CREATE DATABASE IF NOT EXISTS caffe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE caffe;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tóm tắt

| Bảng | Bắt buộc | Mục đích |
|------|----------|----------|
| `users` | ✅ **CÓ** | Lưu thông tin user |
| `login_attempts` | ⚠️ Khuyến nghị | Tracking đăng nhập |
| `refresh_tokens` | ❌ Tùy chọn | Refresh JWT token |
| `password_resets` | ❌ Tùy chọn | Reset mật khẩu |
| `user_sessions` | ❌ Tùy chọn | Quản lý session |

**Kết luận:** Để hệ thống đăng ký/đăng nhập hoạt động, bạn **CHỈ CẦN** bảng `users`. Các bảng khác là tùy chọn để mở rộng tính năng.
