# Hướng dẫn Test API Users (Thông tin người dùng)

API này cho phép người dùng xem và cập nhật thông tin cá nhân của họ. Quản trị viên (Admin) có thêm quyền xem và sửa thông tin của người dùng khác.

---

## 🚀 Các Endpoints

### 1. Xem thông tin cá nhân (Profile)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/users`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

#### Response Thành Công (200):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Nguyên Văn A",
    "email": "test@example.com",
    "phone": "0987654321",
    "avatar": null,
    "role": "user",
    "status": "active",
    "created_at": "2026-03-03 10:00:00"
  }
}
```

---

### 2. Cập nhật thông tin cá nhân

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/users`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  Content-Type: application/json
  ```
- **Body (JSON):**
  ```json
  {
    "name": "Tên Mới Của Tôi",
    "phone": "0123456789",
    "address": "123 Đường ABC, Quận 1, TP.HCM"
  }
  ```

#### Response Thành Công (200):

```json
{
  "success": true,
  "message": "Cập nhật thông tin thành công",
  "user": {
    "id": 1,
    "name": "Tên Mới Của Tôi",
    "email": "test@example.com",
    "phone": "0123456789",
    "avatar": null,
    "role": "user",
    "status": "active",
    "created_at": "..."
  }
}
```

### 3. Đổi mật khẩu

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/users`
- **Body (JSON):**

```json
{
  "password": "NewSecurePassword123"
}
```

---

### 4. Cập nhật Ảnh đại diện (Avatar)

Vì PHP không hỗ trợ nhận file qua phương thức `PUT` một cách mặc định, bạn hãy sử dụng `POST`.

#### Request Setup:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/users`
- **Body (form-data):**
  - `avatar`: Chọn file ảnh (jpg, png...)
  - `name`: Tên mới (nếu muốn đổi)
  - `_method`: `PUT` (tùy chọn)

---

### 5. [Admin] Xem thông tin người dùng khác

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/users?id=2`
- **Headers:**
  ```
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```

---

### 5. [Admin] Cập nhật thông tin người dùng khác (Role/Status)

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/users`
- **Body (JSON):**
  ```json
  {
    "id": 2,
    "status": "banned",
    "role": "admin"
  }
  ```

---

## 📝 Lưu ý quan trọng

1. **Authentication:** Tất cả các request đều yêu cầu Header `Authorization: Bearer <token>`. Lấy token này từ API Login.
2. **Phân quyền:**
   - Người dùng thường chỉ có thể sửa `name`, `phone`, `password`, `avatar`.
   - Nếu người dùng thường cố tình gửi `role` hoặc `status`, hệ thống sẽ tự động loại bỏ các trường này.
   - Chỉ Admin mới có quyền dùng tham số `id` để xem/sửa người dùng khác.
3. **Validation:** Các dữ liệu gửi lên sẽ được sanitize để chống SQL Injection và XSS.
