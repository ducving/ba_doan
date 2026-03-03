# PHP API - Đăng ký và Đăng nhập

Project PHP API với kết nối database MySQL, hỗ trợ đăng ký và đăng nhập.

## Cấu trúc thư mục

```
doan/
├── api/
│   ├── register.php      # API đăng ký
│   ├── login.php         # API đăng nhập
│   └── verify_token.php  # API verify JWT token
├── classes/
│   ├── Database.php      # Class kết nối database
│   ├── User.php          # Class xử lý user
│   ├── Security.php      # Class bảo mật (rate limiting, sanitization)
│   └── JWT.php           # Class xử lý JWT token
├── config/
│   ├── config.php        # Cấu hình chung
│   └── database.php      # Cấu hình database
├── database/
│   └── schema.sql        # File SQL tạo database và bảng
├── storage/              # Thư mục lưu logs và rate limit (tự động tạo)
├── index.php             # Router chính
├── .htaccess             # Apache rewrite rules
├── .gitignore            # Git ignore file
└── README.md             # File hướng dẫn
```

## Cài đặt

### 1. Cấu hình database

Mở file `config/database.php` và cập nhật thông tin kết nối:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'caffe');
```

### 2. Tạo database

Chạy file SQL trong `database/schema.sql` để tạo database và bảng:

```bash
mysql -u root -p < database/schema.sql
```

Hoặc import vào phpMyAdmin.

### 3. Cấu hình web server

#### Apache
- Đảm bảo mod_rewrite đã được bật
- File `.htaccess` đã được tạo

#### Nginx
Cấu hình rewrite trong nginx.conf:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## API Endpoints

### 1. Đăng ký (Register)

**URL:** `POST /api/register.php`

**Request Body:**
```json
{
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "password": "password123"
}
```

**Response Success (201):**
```json
{
    "success": true,
    "message": "Đăng ký thành công",
    "user_id": 1,
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 86400
}
```

**Response Error (400):**
```json
{
    "success": false,
    "message": "Email đã được sử dụng"
}
```

### 2. Đăng nhập (Login)

**URL:** `POST /api/login.php`

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response Success (200):**
```json
{
    "success": true,
    "message": "Đăng nhập thành công",
    "user": {
        "id": 1,
        "name": "Nguyễn Văn A",
        "email": "user@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 86400
}
```

**Response Error (401):**
```json
{
    "success": false,
    "message": "Email hoặc mật khẩu không đúng"
}
```

## Test API

### Sử dụng cURL

**Đăng ký:**
```bash
curl -X POST http://localhost/doan/api/register.php \
  -H "Content-Type: application/json" \
  -d '{"name":"Nguyễn Văn A","email":"test@example.com","password":"Password123"}'
```

**Đăng nhập:**
```bash
curl -X POST http://localhost/doan/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"Password123"}'
```

**Verify Token:**
```bash
curl -X GET http://localhost/doan/api/verify_token.php \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Sử dụng Postman

1. Tạo request mới
2. Chọn method POST
3. Nhập URL: `http://localhost/doan/api/register.php` hoặc `http://localhost/doan/api/login.php`
4. Chọn tab Body > raw > JSON
5. Nhập JSON data như ví dụ trên
6. Gửi request

## Yêu cầu hệ thống

- PHP >= 7.4
- MySQL >= 5.7 hoặc MariaDB >= 10.2
- Apache với mod_rewrite hoặc Nginx
- PDO extension cho PHP

## Bảo mật

### Các biện pháp bảo mật đã triển khai:

1. **Password Security**
   - Mật khẩu được hash bằng `password_hash()` với thuật toán bcrypt
   - Password validation mạnh: tối thiểu 8 ký tự, có chữ hoa, chữ thường, số

2. **SQL Injection Protection**
   - Sử dụng PDO với prepared statements
   - Tất cả queries đều sử dụng parameter binding

3. **XSS Protection**
   - Input sanitization với `htmlspecialchars()`
   - Output encoding

4. **Rate Limiting**
   - Chống brute force attack: giới hạn 5 lần đăng nhập sai trong 5 phút
   - Giới hạn đăng ký: 3 lần trong 1 giờ (chống spam)

5. **JWT Authentication**
   - JWT token được tạo sau khi đăng nhập/đăng ký thành công
   - Token có thời hạn 24 giờ
   - API verify token để xác thực request

6. **Security Headers**
   - X-Content-Type-Options: nosniff
   - X-Frame-Options: DENY
   - X-XSS-Protection: 1; mode=block
   - Strict-Transport-Security (HTTPS)
   - Referrer-Policy

7. **Input Validation**
   - Email validation với `filter_var()`
   - Password strength validation
   - Name length validation
   - Sanitize tất cả input

8. **Logging**
   - Log tất cả security events (login success/failed, register, rate limit)
   - Log lưu trong `storage/security.log`
   - PHP errors log trong `storage/php_errors.log`

9. **Error Handling**
   - Không hiển thị lỗi chi tiết ra client (production)
   - Log errors vào file thay vì hiển thị

### Sử dụng JWT Token

Sau khi đăng nhập/đăng ký thành công, bạn sẽ nhận được JWT token. Sử dụng token này cho các request cần authentication:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Lưu ý Production

1. **Thay đổi JWT Secret Key**
   - Mở `config/config.php` và thay đổi `JWT_SECRET_KEY` thành một chuỗi ngẫu nhiên mạnh

2. **Cấu hình HTTPS**
   - Bật HTTPS và uncomment dòng `Strict-Transport-Security` trong `config/config.php`

3. **Database Security**
   - Sử dụng user database riêng với quyền hạn tối thiểu
   - Không commit file `config/database.php` nếu có thông tin nhạy cảm

4. **File Permissions**
   - Thư mục `storage/` cần quyền ghi (chmod 755)
   - Không cho phép truy cập trực tiếp file log qua web

5. **CORS Configuration**
   - Thay đổi `Access-Control-Allow-Origin: *` thành domain cụ thể trong production
