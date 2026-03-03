# Hướng dẫn Test API Đơn hàng (Orders) trên Postman

## 📋 Chuẩn bị

### 1. Đảm bảo server đang chạy

- XAMPP/WAMP/Laragon đã khởi động.
- Apache và MySQL đang chạy.
- Database đã được chạy migration: `database/migration_orders.sql`.

### 2. URL Base

Mặc định: `http://localhost/doan/api/orders.php`

### 3. Lấy Token (Authentication)

Đa số các thao tác (trừ đặt hàng cho khách vãng lai) yêu cầu header:

- **Key:** `Authorization`
- **Value:** `Bearer YOUR_JWT_TOKEN` (Lấy từ API Login).

**Lưu ý:**

- **Admin:** Có quyền xem tất cả đơn hàng, cập nhật trạng thái và xóa.
- **User:** Chỉ có quyền xem và tạo đơn hàng của chính mình.

---

## 🚀 Test API Endpoints

### 1. Lấy danh sách đơn hàng (GET - CẦN TOKEN)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/orders.php`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

#### Query Params (Lọc & Phân trang):

- `status`: `pending`, `processing`, `completed`, `cancelled`.
- `payment_status`: `pending`, `paid`, `failed`.
- `search`: Tìm theo họ tên, email hoặc số điện thoại.
- `page`: Số trang (mặc định: 1).
- `limit`: Số đơn hàng mỗi trang (mặc định: 20).

---

### 2. Xem chi tiết một đơn hàng (GET - CẦN TOKEN)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/orders.php?id=1`
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
    "user_id": 1,
    "full_name": "Nguyễn Văn A",
    "email": "test@example.com",
    "phone": "0987654321",
    "address": "123 Đường ABC, Quận 1",
    "total_amount": "55000.00",
    "status": "pending",
    "payment_method": "cod",
    "payment_status": "pending",
    "note": "Giao hàng sớm",
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "product_id": 1,
        "quantity": 2,
        "price": "20000.00",
        "total_price": "40000.00",
        "product_name": "Cà phê đen",
        "product_image": "uploads/products/cafe.jpg"
      }
    ]
  }
}
```

---

### 3. Đặt hàng mới (POST)

#### Request Setup:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/orders.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE (Tùy chọn)
  ```
- **Body (raw JSON):**

```json
{
  "full_name": "Nguyễn Văn Khách",
  "email": "khach@gmail.com",
  "phone": "0988777666",
  "address": "789 Đường XYZ, Quận 3, TP.HCM",
  "note": "Giao hàng giờ hành chính",
  "payment_method": "cod",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ]
}
```

**Cơ chế xử lý:**

- Tự động kiểm tra tồn kho (`stock_quantity`).
- Tự động tính tổng tiền dựa trên giá sản phẩm hiện tại.
- Trừ tồn kho ngay khi đặt hàng thành công.

---

### 4. Cập nhật đơn hàng (PUT - CẦN TOKEN ADMIN)

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/orders.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```
- **Body (raw JSON):**

```json
{
  "id": 1,
  "status": "processing",
  "payment_status": "paid",
  "full_name": "Nguyên Văn A (Sửa tên)",
  "phone": "0911222333",
  "address": "Địa chỉ mới"
}
```

**Tính năng đặc biệt:**

- Nếu cập nhật `status` từ bất kỳ trạng thái nào sang `cancelled`, hệ thống sẽ **tự động hoàn trả số lượng sản phẩm** vào lại kho (`stock_quantity`).

---

### 5. Xóa đơn hàng (DELETE - CẦN TOKEN ADMIN)

#### Request Setup:

- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/orders.php?id=1`
- **Headers:**
  ```
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```

---

## 📝 Hướng dẫn từng bước trên Postman

### Bước 1: Tạo Collection

1. Click **New** -> **Collection** -> Đặt tên: `API Đơn Hàng (Orders)`.
2. Vào tab **Authorization** của Collection, chọn Type: `Bearer Token` và dán Token vào. Các request con sẽ tự động dùng token này.

### Bước 2: Tạo Request POST (Đặt hàng)

1. Add Request -> Tên: `Tạo Đơn Hàng`.
2. Chọn method `POST`, nhập URL.
3. Vào tab **Body**, chọn `raw`, định dạng `JSON`. Coppy mẫu JSON ở phần trên vào.

### Bước 3: Kiểm tra Transaction

1. Thử đặt 1 đơn hàng thành công.
2. Kiểm tra database bảng `products`, cột `stock_quantity` xem đã bị trừ chưa.
3. Dùng API PUT chuyển đơn hàng đó sang `cancelled`.
4. Kiểm tra lại `stock_quantity` xem đã được cộng lại chưa.

---

## 🧪 Test Cases

| STT | Test Case                    | Dữ liệu đầu vào                     | Kỳ vọng                                  |
| :-- | :--------------------------- | :---------------------------------- | :--------------------------------------- |
| 1   | Đặt hàng thành công          | Thông tin đầy đủ, tồn kho đủ        | Trả về 201, `success: true`              |
| 2   | Đặt hàng khi hết kho         | `quantity` lớn hơn `stock_quantity` | Trả về 400, "Sản phẩm ... không đủ hàng" |
| 3   | Sửa trạng thái (Admin)       | `status: "cancelled"`               | Trả về 200, Kho sản phẩm được cộng lại   |
| 4   | Bảo mật (User xem đơn Admin) | Dùng token User xem ID đơn Admin    | Trả về 403, "Bạn không có quyền..."      |
| 5   | Xóa đơn hàng                 | ID đơn hàng hợp lệ                  | Trả về 200, đơn hàng mất khỏi DB         |

---

## 🔧 Troubleshooting

- **Lỗi 401:** Token hết hạn hoặc sai định dạng (thiếu chữ `Bearer `).
- **Lỗi 403:** Bạn đang dùng tài khoản User để thực hiện hành động của Admin (như sửa trạng thái).
- **Lỗi 400 "Sản phẩm không tồn tại":** Kiểm tra lại `product_id` trong mảng `items` có đúng với bảng `products` không.
- **Lỗi 500:** Kiểm tra kết nối database hoặc xem log lỗi trong PHP.
