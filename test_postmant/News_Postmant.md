# Hướng dẫn Test API Tin tức (News) trên Postman

## 📋 Chuẩn bị

### 1. Đảm bảo API đã sẵn sàng

- Database đã có bảng `news` (chạy script `migration_news.sql`).
- Đã đăng nhập và lấy được `token` của tài khoản có quyền `admin` (Dùng API Đăng nhập).

### 2. URL Base

Giả sử project của bạn ở: `http://localhost/doan/`

---

## 🚀 Test các API Endpoints

### 1. Lấy danh sách tin tức (Khách & Admin)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/news.php`
- **Params (Tuỳ chọn):**
  - `status=active` (Mặc định cho khách)
  - `status=inactive`
  - `all=1` (Dành cho admin để lấy tất cả, yêu cầu truyền Token ở Header)
- **Headers (Nếu cần lấy tất cả với tư cách admin):**
  ```
  Authorization: Bearer YOUR_ADMIN_TOKEN_HERE
  ```

#### Response Thành Công (200):

```json
{
  "success": true,
  "news": [
    {
      "id": 1,
      "title": "Khai trương quán cà phê mới",
      "content": "Nội dung chi tiết của tin tức...",
      "image": "uploads/news/20231025_102030_abcdef.jpg",
      "status": "active",
      "created_at": "2023-10-25 10:20:30",
      "updated_at": "2023-10-25 10:20:30"
    }
  ]
}
```

---

### 2. Lấy chi tiết một tin tức cụ thể

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/news.php?id=1`
- **Headers:** Không bắt buộc.

#### Response Thành Công (200):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Khai trương quán cà phê mới",
    "content": "Nội dung chi tiết của tin tức...",
    "image": "uploads/news/20231025_102030_abcdef.jpg",
    "status": "active",
    "created_at": "2023-10-25 10:20:30",
    "updated_at": "2023-10-25 10:20:30"
  }
}
```

---

### 3. Thêm mới tin tức (Yêu cầu Admin)

#### Request Setup:

- **Method:** `POST`
- **URL:** `http://localhost/doan/api/news.php`
- **Headers:**
  ```
  Authorization: Bearer YOUR_ADMIN_TOKEN_HERE
  ```
- **Body:** Chuyển sang tab **Body**, chọn `form-data` (vì có upload ảnh):
  - Key: `title` | Value: `Tiêu đề tin tức test` | Type: Text
  - Key: `content` | Value: `Nội dung của tin tức test này là...` | Type: Text
  - Key: `status` | Value: `active` | Type: Text
  - Key: `image` | Value: _(Chọn file ảnh từ máy tính)_ | Type: File

#### Response Thành Công (201):

```json
{
    "success": true,
    "message": "Tạo tin tức thành công",
    "news_id": 1,
    "news": { ... }
}
```

---

### 4. Cập nhật tin tức (Yêu cầu Admin)

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/news.php`
- **Headers:**
  ```
  Authorization: Bearer YOUR_ADMIN_TOKEN_HERE
  Content-Type: application/json
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
    "id": 1,
    "title": "Tiêu đề đã được cập nhật",
    "content": "Nội dung cũng đã được thay đổi.",
    "status": "active"
  }
  ```

#### Response Thành Công (200):

```json
{
    "success": true,
    "message": "Cập nhật tin tức thành công",
    "news": {
        "id": 1,
        "title": "Tiêu đề đã được cập nhật",
        ...
    }
}
```

---

### 5. Xóa tin tức (Yêu cầu Admin)

#### Request Setup:

- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/news.php?id=1` _(Sửa `id=1` thành ID bạn muốn xoá)_
- **Headers:**
  ```
  Authorization: Bearer YOUR_ADMIN_TOKEN_HERE
  ```

#### Response Thành Công (200):

```json
{
  "success": true,
  "message": "Xóa tin tức thành công"
}
```

---

## 📝 Lưu ý:

1. **Quyền Admin:** Các thao tác POST, PUT, DELETE đều phải có token của Admin. Để lấy được token admin, bạn cần đổi role của một user thành `admin` trong database và đăng nhập (hoặc dùng token admin hiện tại nếu có).
2. **Thêm ảnh:** Phương thức POST (Thêm mới) hỗ trợ gửi ảnh qua `form-data`. Còn PUT (cập nhật) hiện tại demo ở JSON nên không kèm ảnh, nếu muốn upload ảnh qua PUT (thực tế PHP nhận file qua PUT/PATCH khá rườm rà) thì thường người ta sẽ tạo 1 POST request riêng dán nhãn là "UPDATE" hoặc viết xử lý custom. Với API trên mình đang nhận JSON content cho hàm UPDATE.
