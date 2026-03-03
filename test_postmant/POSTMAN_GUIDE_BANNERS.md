# Hướng dẫn Test API Banner trên Postman

## 📋 Chuẩn bị

### 1. Đảm bảo server đang chạy

- XAMPP/WAMP/Laragon đã khởi động.
- Apache và MySQL đang chạy.
- Database đã được chạy migration: `database/migration_banners.sql`.

### 2. URL Base

Mặc định: `http://localhost/doan/api/banners.php`

### 3. Thư mục lưu trữ

- Banner sau khi upload sẽ được lưu tại: `uploads/banners/`.
- Hãy đảm bảo PHP có quyền ghi vào thư mục này.

---

## 🚀 Test API Endpoints

### 1. Lấy danh sách Banner hiển thị (GET - CÔNG KHAI)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/banners.php`
- **Headers:** Không cần.

**Mô tả:** API này mặc định chỉ trả về các banner có trạng thái `active` để hiển thị lên trang chủ cho khách hàng.

---

### 2. Xem tất cả hoặc chi tiết Banner (GET - CẦN TOKEN ADMIN)

#### Request Setup:

- **Method:** `GET`
- **URL:** `http://localhost/doan/api/banners.php?all=1` (Xem tất cả kể cả đang ẩn)
- **Hoặc:** `http://localhost/doan/api/banners.php?id=1` (Xem chi tiết 1 cái)
- **Headers:**
  ```
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```

---

### 3. Thêm Banner mới (POST - CẦN TOKEN ADMIN)

API này dùng để tạo banner mới. Vì có tải ảnh lên, bạn **BẮT BUỘC** phải chọn kiểu **form-data** trong Postman.

#### Cấu hình Request trong Postman:

1.  **Method:** Chọn `POST`
2.  **URL:** `http://localhost/doan/api/banners.php`
3.  **Tab Authorization:** Chọn `Bearer Token` và dán Token của Admin vào.
4.  **Tab Body:** Chọn `form-data`.

#### Các dữ liệu CẦN NHẬP CHI TIẾT:

| KEY (Tên trường) | TYPE (Kiểu) | BẮT BUỘC | GIÁ TRỊ VÍ DỤ / MÔ TẢ                                                                       |
| :--------------- | :---------- | :------- | :------------------------------------------------------------------------------------------ |
| **`image`**      | **File**    | **CÓ**   | **QUAN TRỌNG:** Di chuột vào Key, chọn type là **File**. Nhấn **Select Files** để chọn ảnh. |
| `title`          | Text        | Không    | Tiêu đề của banner (Ví dụ: "Khuyến mãi Cà phê 50%")                                         |
| `link`           | Text        | Không    | Đường dẫn khi click (Ví dụ: `/products/1` hoặc `https://myweb.com`)                         |
| `sort_order`     | Text        | Không    | Số thứ tự hiển thị (Ví dụ: `1`). Số càng nhỏ càng hiện lên đầu.                             |
| `status`         | Text        | Không    | Nhập `active` để hiển thị ngay, hoặc `inactive` để ẩn.                                      |

#### Response mẫu (201):

```json
{
  "success": true,
  "message": "Tạo banner thành công",
  "banner_id": 10,
  "banner": {
    "id": 10,
    "image": "uploads/banners/20260224_...jpg",
    "status": "active"
    ...
  }
}
```

---

### 4. Cập nhật Banner (PUT - CẦN TOKEN ADMIN)

#### Request Setup:

- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/banners.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```
- **Body (raw JSON):**

```json
{
  "id": 1,
  "title": "Tiêu đề đã chỉnh sửa",
  "status": "inactive",
  "sort_order": 10
}
```

**Lưu ý:** API cập nhật (PUT) hiện tại chỉ dùng để chỉnh sửa các thông tin văn bản. Để thay đổi ảnh, vui lòng xóa và tạo mới banner.

---

### 5. Xóa Banner (DELETE - CẦN TOKEN ADMIN)

#### Request Setup:

- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/banners.php?id=1`
- **Headers:**
  ```
  Authorization: Bearer ADMIN_TOKEN_HERE
  ```

---

## 📝 Hướng dẫn từng bước trên Postman

### Bước 1: Upload Banner mới

1. Chọn method **POST**.
2. Tab **Body** -> Chọn **form-data**.
3. Di chuột vào cột **Key**, ở bên phải hiện mũi tên nhỏ -> Chọn **File**.
4. Chọn file ảnh từ máy tính của bạn.
5. Nhập các trường khác như `title`, `status`.
6. Nhấn **Send**.

### Bước 2: Kiểm tra kết quả

1. Gọi API **GET** `api/banners.php`.
2. Kiểm tra xem banner vừa tạo có hiện trong mảng `data` không.
3. Kiểm tra đường dẫn ảnh trong response (Ví dụ: `uploads/banners/20240224_...jpg`).
4. Thử copy đường dẫn đó dán vào trình duyệt để xem ảnh có hiện ra không.

---

## 🧪 Test Cases

| STT | Test Case                  | Dữ liệu đầu vào        | Kỳ vọng                                 |
| :-- | :------------------------- | :--------------------- | :-------------------------------------- |
| 1   | Tạo banner với ảnh hợp lệ  | File .jpg hoặc .png    | Trả về 201, upload thành công           |
| 2   | Tạo banner thiếu ảnh       | Không chọn file image  | Trả về 400, "Hình ảnh không được trống" |
| 3   | Upload file không phải ảnh | File .txt hoặc .zip    | Trả về 400, "Định dạng không hỗ trợ"    |
| 4   | User thường xóa banner     | Token không phải Admin | Trả về 403, "Bạn không có quyền..."     |
| 5   | Lọc banner đang hoạt động  | `GET api/banners.php`  | Chỉ liệt kê banner có `status: active`  |

---

## 🔧 Troubleshooting

- **Lỗi "Không thể lưu ảnh upload":** Kiểm tra xem thư mục `uploads/banners/` có tồn tại và có quyền ghi (Permission) không.
- **Lỗi 405 Method Not Allowed:** Kiểm tra method gọi lên có đúng (POST/PUT/GET...) như hướng dẫn không.
- **Lưu ý về URL ảnh:** Nếu response trả về link ảnh dạng `uploads/banners/...`, bạn cần thêm URL gốc của website vào trước (ví dụ `http://localhost/doan/uploads/banners/...`) để hiển thị trên web/app.
