# 🚀 Hướng dẫn Test Nhanh trên Postman

## Bước 1: Import Collection vào Postman

1. Mở Postman
2. Click **Import** (góc trên bên trái)
3. Chọn file `postman_collection.json` trong project
4. Click **Import**

→ Bạn sẽ thấy collection "PHP API - Đăng ký/Đăng nhập" với 5 requests sẵn

---

## Bước 2: Tạo Environment (Tùy chọn nhưng khuyến nghị)

1. Click **Environments** → **+** (Create Environment)
2. Đặt tên: `Local Development`
3. Thêm variables:
   - `base_url`: `http://localhost/doan`
   - `token`: (để trống, sẽ tự động điền sau khi login)
4. Click **Save**
5. Chọn environment này ở góc trên bên phải

---

## Bước 3: Test Đăng Ký

1. Chọn request **"1. Register - Đăng ký"**
2. Kiểm tra URL: `http://localhost/doan/api/register.php`
3. Kiểm tra Body có JSON:
   ```json
   {
       "name": "Nguyễn Văn A",
       "email": "test@example.com",
       "password": "Password123"
   }
   ```
4. Click **Send**
5. **Kỳ vọng:** Status 201, nhận được `token` và `user_id`

---

## Bước 4: Test Đăng Nhập

1. Chọn request **"2. Login - Đăng nhập"**
2. Kiểm tra Body:
   ```json
   {
       "email": "test@example.com",
       "password": "Password123"
   }
   ```
3. Click **Send**
4. **Kỳ vọng:** Status 200, nhận được `token` và `user` info
5. **QUAN TRỌNG:** Token sẽ tự động được lưu vào environment variable `token`

---

## Bước 5: Test Verify Token

1. Chọn request **"3. Verify Token"**
2. Kiểm tra Header có: `Authorization: Bearer {{token}}`
3. Click **Send**
4. **Kỳ vọng:** Status 200, nhận được user info

---

## ⚠️ Lưu ý quan trọng

### 1. Đảm bảo Database đã được tạo
```bash
mysql -u root -p < database/schema_minimal.sql
```
Hoặc tạo thủ công database `caffe` (hoặc `caffe` nếu bạn đã đổi trong config)

### 2. Kiểm tra Database Name
Mở `config/database.php`, đảm bảo:
```php
define('DB_NAME', 'caffe'); // hoặc 'caffe' nếu bạn đã đổi
```

### 3. URL có thể khác
Nếu project của bạn không ở `http://localhost/doan/`, hãy:
- Sửa URL trong Postman collection
- Hoặc tạo Environment variable `base_url`

### 4. Password phải mạnh
- Tối thiểu 8 ký tự
- Có chữ hoa (A-Z)
- Có chữ thường (a-z)
- Có số (0-9)

Ví dụ: `Password123`, `MyPass2024`, `Test1234`

---

## 🐛 Troubleshooting

### Lỗi: "Kết nối database thất bại"
✅ **Giải pháp:**
- Kiểm tra MySQL đang chạy
- Kiểm tra `config/database.php`
- Đảm bảo database đã được tạo

### Lỗi: 404 Not Found
✅ **Giải pháp:**
- Kiểm tra URL có đúng không
- Thử truy cập trực tiếp: `http://localhost/doan/api/register.php`
- Kiểm tra `.htaccess` có hoạt động

### Lỗi: 500 Internal Server Error
✅ **Giải pháp:**
- Tạo thư mục `storage/` với quyền ghi
- Xem log: `storage/php_errors.log`

### Token không hoạt động
✅ **Giải pháp:**
- Đảm bảo đã chọn đúng Environment
- Kiểm tra token có được lưu sau khi login
- Token có thể đã hết hạn (24 giờ)

---

## 📋 Checklist Test

- [ ] Import Postman collection thành công
- [ ] Database đã được tạo
- [ ] Đăng ký thành công (Status 201)
- [ ] Đăng nhập thành công (Status 200)
- [ ] Token được lưu tự động
- [ ] Verify token thành công (Status 200)
- [ ] Test password yếu (Status 400)
- [ ] Test đăng nhập sai (Status 401)

---

Chúc bạn test thành công! 🎉
