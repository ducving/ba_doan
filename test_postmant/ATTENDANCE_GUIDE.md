# Hướng dẫn Test API Chấm công (Attendance)

Tài liệu này hướng dẫn cách sử dụng API chấm công cho hệ thống.

## 📋 Điều kiện tiên quyết

1. Bạn phải đăng nhập (`api/login`) để lấy **Bearer Token**.
2. Tài khoản của bạn phải được liên kết với một nhân viên trong bảng `employees` (trường `user_id`).

---

## 🚀 Các API Endpoints

### 1. Check-in (Vào ca)

Ghi nhận giờ bắt đầu làm việc. Hệ thống tự động đánh dấu "Đi muộn" nếu sau 08:30 sáng.

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/attendance`
- **Headers:**
  - `Content-Type: application/json`
  - `Authorization: Bearer {{token}}`
- **Body:**

```json
{
  "action": "check_in",
  "note": "Hôm nay đi làm sớm"
}
```

---

### 2. Check-out (Về)

Ghi nhận giờ kết thúc làm việc.

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/attendance`
- **Headers:**
  - `Authorization: Bearer {{token}}`
- **Body:**

```json
{
  "action": "check_out"
}
```

---

### 3. Xem lịch sử cá nhân

Nhân viên tự xem lịch sử chấm công của mình.

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/attendance?limit=30`
- **Headers:**
  - `Authorization: Bearer {{token}}`

---

### 4. Xem chấm công theo ngày (Dành cho Admin)

Quản lý xem toàn bộ nhân viên nào đã đi làm trong một ngày cụ thể.

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/attendance?date=2024-03-05`
- **Headers:**
  - `Authorization: Bearer {{ADMIN_TOKEN}}`

---

## ⚠️ Lưu ý

- Mỗi nhân viên chỉ được phép Check-in **01 lần duy nhất** trong ngày.
- Muốn Check-out thì bắt buộc phải có bản ghi Check-in trước đó.
