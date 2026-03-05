# Hướng dẫn Test API trên Postman

## 📋 Chuẩn bị

### 1. Đảm bảo server đang chạy

- XAMPP/WAMP/LAMP đã khởi động
- Apache và MySQL đang chạy
- Database đã được tạo (chạy `database/schema.sql` hoặc `schema_minimal.sql`)

### 2. Cấu hình database

Mở file `config/database.php` và kiểm tra thông tin:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'caffe');
```

### 3. URL Base

Giả sử project của bạn ở: `http://localhost/doan/`

---

## 🚀 Test các API Endpoints

### 1. Test Đăng Ký (Register)

#### Request Setup:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/register.php`
- **Headers:**
  ```
  Content-Type: application/json
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
    "name": "Nguyễn Văn A",
    "email": "test@example.com",
    "password": "Password123"
  }
  ```

#### Response Thành Công (201):

```json
{
  "success": true,
  "message": "Đăng ký thành công",
  "user_id": 1,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6InRlc3RAZXhhbXBsZS5jb20iLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6MTcwMDA4NjQwMH0.xxx",
  "token_type": "Bearer",
  "expires_in": 86400
}
```

#### Response Lỗi (400):

```json
{
  "success": false,
  "message": "Email đã được sử dụng"
}
```

**Lưu ý:**

- Password phải có ít nhất 8 ký tự
- Phải có chữ hoa, chữ thường, và số
- Email phải hợp lệ

---

### 2. Test Đăng Nhập (Login)

#### Request Setup:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/login.php`
- **Headers:**
  ```
  Content-Type: application/json
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
    "email": "test@example.com",
    "password": "Password123"
  }
  ```

#### Response Thành Công (200):

```json
{
  "success": true,
  "message": "Đăng nhập thành công",
  "user": {
    "id": 1,
    "name": "Nguyễn Văn A",
    "email": "test@example.com"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6InRlc3RAZXhhbXBsZS5jb20iLCJpYXQiOjE3MDAwMDAwMDAsImV4cCI6MTcwMDA4NjQwMH0.xxx",
  "token_type": "Bearer",
  "expires_in": 86400
}
```

#### Response Lỗi (401):

```json
{
  "success": false,
  "message": "Email hoặc mật khẩu không đúng"
}
```

**Lưu ý:**

- Copy `token` từ response để dùng cho các request cần authentication
- Sau 5 lần đăng nhập sai, sẽ bị rate limit (429)

---

### 3. Test Verify Token

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/verify_token.php`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
  (Thay `YOUR_TOKEN_HERE` bằng token nhận được từ login/register)

#### Response Thành Công (200):

```json
{
  "success": true,
  "message": "Token hợp lệ",
  "user": {
    "user_id": 1,
    "email": "test@example.com"
  }
}
```

#### Response Lỗi (401):

```json
{
  "success": false,
  "message": "Token không hợp lệ hoặc đã hết hạn"
}
```

---

### 4. Test Lấy Danh Sách User (List Users)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/get_users.php?page=1&limit=10`
- **Headers:**
  ```
  Authorization: Bearer YOUR_ADMIN_TOKEN_HERE
  ```
  (Phải là token của user có role là `admin`)

#### Response Thành Công (200):

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Admin",
            "email": "admin@example.com",
            "role": "admin",
            "status": "active",
            ...
        }
    ],
    "pagination": {
        "total": 1,
        "page": 1,
        "limit": 10,
        "total_pages": 1
    }
}
```

#### Response Lỗi (403):

```json
{
  "success": false,
  "message": "Bạn không có quyền truy cập danh sách người dùng"
}
```

---

### 5. Quản lý Nhân viên (Employees)

(Yêu cầu Token Admin)

#### A. Lấy danh sách nhân viên:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/employees.php?page=1&limit=10`
- **Headers:** `Authorization: Bearer ADMIN_TOKEN`

#### B. Thêm nhân viên mới:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/employees.php`
- **Body (JSON):**
  ```json
  {
    "employee_code": "NV001",
    "full_name": "Nguyễn Văn A",
    "email": "nva@example.com",
    "position": "Pha chế",
    "salary": 7000000,
    "hire_date": "2023-10-01"
  }
  ```

#### C. Cập nhật nhân viên:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/employees.php?id=1`
- **Body (JSON):** `{"position": "Trưởng nhóm pha chế", "salary": 9000000}`

#### D. Xóa nhân viên:

- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/employees.php?id=1`

---

## 📝 Hướng dẫn từng bước trên Postman

### Bước 1: Tạo Collection mới

1. Mở Postman
2. Click **New** → **Collection**
3. Đặt tên: `PHP API - Đăng ký/Đăng nhập`

### Bước 2: Tạo Request Đăng Ký

1. Click **Add Request** trong collection
2. Đặt tên: `1. Register`
3. Chọn method: **POST**
4. Nhập URL: `http://localhost/doan/api/register.php`
5. Vào tab **Headers**, thêm:
   - Key: `Content-Type`
   - Value: `application/json`
6. Vào tab **Body**:
   - Chọn `raw`
   - Chọn `JSON` ở dropdown bên phải
   - Nhập JSON:
     ```json
     {
       "name": "Nguyễn Văn A",
       "email": "test@example.com",
       "password": "Password123"
     }
     ```
7. Click **Send**

### Bước 3: Tạo Request Đăng Nhập

1. Tạo request mới: `2. Login`
2. Method: **POST**
3. URL: `http://localhost/doan/api/login.php`
4. Headers: `Content-Type: application/json`
5. Body (raw JSON):
   ```json
   {
     "email": "test@example.com",
     "password": "Password123"
   }
   ```
6. Click **Send**
7. **QUAN TRỌNG:** Copy `token` từ response

### Bước 4: Tạo Request Verify Token

1. Tạo request mới: `3. Verify Token`
2. Method: **GET**
3. URL: `http://localhost/doan/api/verify_token.php`
4. Vào tab **Headers**, thêm:
   - Key: `Authorization`
   - Value: `Bearer PASTE_TOKEN_HERE` (dán token đã copy)
5. Click **Send**

---

## 🧪 Test Cases

### Test Case 1: Đăng ký thành công

```json
{
  "name": "Trần Văn B",
  "email": "tranvanb@example.com",
  "password": "MyPass123"
}
```

**Kỳ vọng:** Status 201, nhận được token

### Test Case 2: Đăng ký với email đã tồn tại

```json
{
  "name": "Người khác",
  "email": "test@example.com",
  "password": "Password123"
}
```

**Kỳ vọng:** Status 400, message "Email đã được sử dụng"

### Test Case 3: Đăng ký với password yếu

```json
{
  "name": "Người dùng",
  "email": "user@example.com",
  "password": "123"
}
```

**Kỳ vọng:** Status 400, message về yêu cầu password

### Test Case 4: Đăng nhập thành công

```json
{
  "email": "test@example.com",
  "password": "Password123"
}
```

**Kỳ vọng:** Status 200, nhận được user info và token

### Test Case 5: Đăng nhập sai mật khẩu

```json
{
  "email": "test@example.com",
  "password": "SaiMatKhau123"
}
```

**Kỳ vọng:** Status 401, message "Email hoặc mật khẩu không đúng"

### Test Case 6: Rate Limiting

Thử đăng nhập sai **6 lần liên tiếp** với cùng email/IP
**Kỳ vọng:** Lần thứ 6 sẽ nhận Status 429, message về rate limit

### Test Case 7: Verify Token hợp lệ

Header: `Authorization: Bearer VALID_TOKEN`
**Kỳ vọng:** Status 200, nhận được user info

### Test Case 8: Verify Token không hợp lệ

Header: `Authorization: Bearer invalid_token_here`
**Kỳ vọng:** Status 401, message "Token không hợp lệ"

---

## 🔧 Troubleshooting

### Lỗi: "Kết nối database thất bại"

- Kiểm tra MySQL đang chạy
- Kiểm tra thông tin trong `config/database.php`
- Đảm bảo database `caffe` đã được tạo

### Lỗi: 404 Not Found

- Kiểm tra URL có đúng không
- Kiểm tra `.htaccess` có hoạt động không
- Thử truy cập trực tiếp: `http://localhost/doan/api/register.php`

### Lỗi: 500 Internal Server Error

- Kiểm tra file `storage/` có quyền ghi không
- Xem log trong `storage/php_errors.log`
- Kiểm tra PHP version >= 7.4

### Token không hoạt động

- Đảm bảo copy đầy đủ token (rất dài)
- Kiểm tra header: `Authorization: Bearer TOKEN` (có khoảng trắng sau Bearer)
- Token có thể đã hết hạn (24 giờ)

---

## 📸 Screenshot mẫu Postman

### Request Register:

```
POST http://localhost/doan/api/register.php
Headers:
  Content-Type: application/json
Body (raw JSON):
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123"
  }
```

### Request Login:

```
POST http://localhost/doan/api/login.php
Headers:
  Content-Type: application/json
Body (raw JSON):
  {
    "email": "test@example.com",
    "password": "Password123"
  }
```

### Request Verify Token:

```
GET http://localhost/doan/api/verify_token.php
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

---

## 💡 Tips

1. **Lưu token vào Environment Variable:**
   - Tạo Environment trong Postman
   - Sau khi login, dùng script để lưu token:
     ```javascript
     var jsonData = pm.response.json();
     if (jsonData.token) {
       pm.environment.set("token", jsonData.token);
     }
     ```
   - Dùng token: `Bearer {{token}}`

2. **Test tự động:**
   - Thêm Test script trong Postman:

     ```javascript
     pm.test("Status code is 200", function () {
       pm.response.to.have.status(200);
     });

     pm.test("Response has token", function () {
       var jsonData = pm.response.json();
       pm.expect(jsonData).to.have.property("token");
     });
     ```

3. **Export Collection:**
   - Click **...** trên collection → **Export**
   - Lưu file để chia sẻ với team

---

Chúc bạn test thành công! 🎉
