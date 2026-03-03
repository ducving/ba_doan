# Hướng dẫn Test API Categories & Products trên Postman

## 📋 Chuẩn bị

### 1. Đảm bảo server đang chạy
- XAMPP/WAMP/LAMP đã khởi động
- Apache và MySQL đang chạy
- Database đã được tạo và chạy migration: `database/migration_categories_products.sql`

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

### 4. Lấy Token (BẮT BUỘC cho POST/PUT/DELETE)
Trước khi test API categories và products, bạn cần:
1. Đăng nhập hoặc đăng ký để lấy token
2. Copy token từ response
3. Sử dụng token trong header `Authorization: Bearer YOUR_TOKEN`

**Lưu ý:** Các request GET không cần token, nhưng POST/PUT/DELETE bắt buộc phải có token.

---

## 🚀 Test API Categories

### 1. Lấy tất cả danh mục (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/categories.php`
- **Headers:** Không cần

#### Response Thành Công (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Cà phê",
            "slug": "ca-phe",
            "description": "Các loại cà phê",
            "image": null,
            "status": "active",
            "sort_order": 1,
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00"
        }
    ],
    "count": 1
}
```

---

### 2. Lấy danh mục theo ID (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/categories.php?id=1`
- **Headers:** Không cần

#### Response Thành Công (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Cà phê",
        "slug": "ca-phe",
        "description": "Các loại cà phê",
        "image": null,
        "status": "active",
        "sort_order": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

#### Response Lỗi (404):
```json
{
    "success": false,
    "message": "Danh mục không tồn tại"
}
```

---

### 3. Lấy danh mục theo slug (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/categories.php?slug=ca-phe`
- **Headers:** Không cần

#### Response Thành Công (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Cà phê",
        "slug": "ca-phe",
        ...
    }
}
```

---

### 4. Lọc danh mục theo status (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/categories.php?status=active`
- **Headers:** Không cần

---

### 5. Tạo danh mục mới (POST - CẦN TOKEN)

#### Request Setup:
- **Method:** `POST`
- **URL:** `http://localhost/doan/api/categories.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
      "name": "Cà phê",
      "description": "Các loại cà phê thơm ngon",
      "image": "https://example.com/category-image.jpg",
      "status": "active",
      "sort_order": 1
  }
  ```

#### Response Thành Công (201):
```json
{
    "success": true,
    "message": "Tạo danh mục thành công",
    "category_id": 1,
    "category": {
        "id": 1,
        "name": "Cà phê",
        "slug": "ca-phe",
        "description": "Các loại cà phê thơm ngon",
        "image": "https://example.com/category-image.jpg",
        "status": "active",
        "sort_order": 1,
        "created_at": "2024-01-01 10:00:00",
        "updated_at": "2024-01-01 10:00:00"
    }
}
```

#### Response Lỗi (400):
```json
{
    "success": false,
    "message": "Tên danh mục không được để trống"
}
```

#### Response Lỗi (401):
```json
{
    "success": false,
    "message": "Token không hợp lệ hoặc đã hết hạn"
}
```

**Lưu ý:** 
- Nếu không cung cấp `slug`, hệ thống sẽ tự động tạo từ `name`
- `status` chỉ nhận giá trị: `active` hoặc `inactive`
- `sort_order` dùng để sắp xếp (số nhỏ hơn hiển thị trước)

---

### 6. Cập nhật danh mục (PUT - CẦN TOKEN)

#### Request Setup:
- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/categories.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
      "id": 1,
      "name": "Cà phê Premium",
      "description": "Cà phê cao cấp",
      "status": "active",
      "sort_order": 1
  }
  ```

#### Response Thành Công (200):
```json
{
    "success": true,
    "message": "Cập nhật danh mục thành công",
    "category": {
        "id": 1,
        "name": "Cà phê Premium",
        "slug": "ca-phe",
        ...
    }
}
```

**Lưu ý:** 
- Bắt buộc phải có `id` trong body
- Chỉ cần gửi các field muốn cập nhật, không cần gửi tất cả

---

### 7. Xóa danh mục (DELETE - CẦN TOKEN)

#### Request Setup:
- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/categories.php?id=1`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

#### Response Thành Công (200):
```json
{
    "success": true,
    "message": "Xóa danh mục thành công"
}
```

#### Response Lỗi (400):
```json
{
    "success": false,
    "message": "Không thể xóa danh mục vì còn sản phẩm thuộc danh mục này"
}
```

**Lưu ý:** 
- Không thể xóa danh mục nếu còn sản phẩm thuộc danh mục đó

---

## 🚀 Test API Products

### 1. Lấy tất cả sản phẩm (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php`
- **Headers:** Không cần

#### Response Thành Công (200):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "category_id": 1,
            "name": "Cà phê đen",
            "slug": "ca-phe-den",
            "description": "Cà phê đen đậm đà",
            "short_description": "Cà phê đen",
            "price": "25000.00",
            "sale_price": null,
            "sku": "CF001",
            "stock_quantity": 100,
            "image": "https://example.com/product.jpg",
            "images": [],
            "status": "active",
            "featured": 1,
            "sort_order": 1,
            "created_at": "2024-01-01 10:00:00",
            "updated_at": "2024-01-01 10:00:00",
            "category_name": "Cà phê",
            "category_slug": "ca-phe"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 1,
        "total_pages": 1
    }
}
```

---

### 2. Lấy sản phẩm theo ID (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?id=1`
- **Headers:** Không cần

#### Response Thành Công (200):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "category_id": 1,
        "name": "Cà phê đen",
        ...
    }
}
```

---

### 3. Lấy sản phẩm theo slug (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?slug=ca-phe-den`
- **Headers:** Không cần

---

### 4. Lọc sản phẩm theo danh mục (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?category_id=1`
- **Headers:** Không cần

---

### 5. Lọc sản phẩm theo status (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?status=active`
- **Headers:** Không cần

**Lưu ý:** `status` có thể là: `active`, `inactive`, `out_of_stock`

---

### 6. Lọc sản phẩm nổi bật (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?featured=1`
- **Headers:** Không cần

---

### 7. Tìm kiếm sản phẩm (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?search=cà phê`
- **Headers:** Không cần

**Lưu ý:** Tìm kiếm theo tên và mô tả sản phẩm

---

### 8. Phân trang sản phẩm (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?page=1&limit=10`
- **Headers:** Không cần

**Lưu ý:** 
- `page`: Số trang (mặc định: 1)
- `limit`: Số sản phẩm mỗi trang (mặc định: 20, tối đa: 100)

---

### 9. Kết hợp nhiều filter (GET - Không cần token)

#### Request Setup:
- **Method:** `GET`
- **URL:** `http://localhost/doan/api/products.php?category_id=1&status=active&featured=1&page=1&limit=10`
- **Headers:** Không cần

---

### 10. Tạo sản phẩm mới (POST - CẦN TOKEN)

#### Request Setup:
- **Method:** `POST`
- **URL:** `http://localhost/doan/api/products.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
      "category_id": 1,
      "name": "Cà phê đen",
      "description": "Cà phê đen đậm đà, thơm ngon",
      "short_description": "Cà phê đen",
      "price": 25000,
      "sale_price": 20000,
      "sku": "CF001",
      "stock_quantity": 100,
      "image": "https://example.com/product.jpg",
      "images": [
          "https://example.com/product-1.jpg",
          "https://example.com/product-2.jpg"
      ],
      "status": "active",
      "featured": 1,
      "sort_order": 1
  }
  ```

#### Upload ảnh trực tiếp (form-data - multipart)
Nếu bạn muốn **upload file ảnh** thay vì gửi URL, dùng **Body → form-data**:
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
  (Postman sẽ tự set `Content-Type: multipart/form-data`)
- **form-data fields:**
  - `category_id` (Text): `1`
  - `name` (Text): `Cà phê đen`
  - `price` (Text): `25000`
  - `stock_quantity` (Text): `100`
  - `status` (Text): `active`
  - `featured` (Text): `1`
  - `image` (File): chọn **1 file ảnh** (ảnh chính)
  - `images[]` (File): chọn **nhiều file ảnh** (ảnh phụ)

**Lưu ý:**
- Ảnh sẽ được lưu vào: `uploads/products/`
- `image` lưu đường dẫn ảnh chính (string)
- `images` lưu danh sách đường dẫn ảnh phụ (array → JSON)

#### Response Thành Công (201):
```json
{
    "success": true,
    "message": "Tạo sản phẩm thành công",
    "product_id": 1,
    "product": {
        "id": 1,
        "category_id": 1,
        "name": "Cà phê đen",
        "slug": "ca-phe-den",
        ...
    }
}
```

#### Response Lỗi (400):
```json
{
    "success": false,
    "message": "Tên sản phẩm không được để trống"
}
```

hoặc

```json
{
    "success": false,
    "message": "Danh mục không hợp lệ"
}
```

**Lưu ý:** 
- `category_id` bắt buộc và phải tồn tại trong bảng categories
- `price` bắt buộc, `sale_price` tùy chọn
- `images` là mảng JSON, sẽ được lưu dưới dạng JSON string
- `status` có thể là: `active`, `inactive`, `out_of_stock`
- `featured`: 1 = nổi bật, 0 = không nổi bật

---

### 11. Cập nhật sản phẩm (PUT - CẦN TOKEN)

#### Request Setup:
- **Method:** `PUT`
- **URL:** `http://localhost/doan/api/products.php`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer YOUR_TOKEN_HERE
  ```
- **Body:** Chọn `raw` → `JSON`, nhập:
  ```json
  {
      "id": 1,
      "name": "Cà phê đen Premium",
      "price": 30000,
      "sale_price": 25000,
      "stock_quantity": 50,
      "status": "active"
  }
  ```

#### Response Thành Công (200):
```json
{
    "success": true,
    "message": "Cập nhật sản phẩm thành công",
    "product": {
        "id": 1,
        "name": "Cà phê đen Premium",
        ...
    }
}
```

**Lưu ý:** 
- Bắt buộc phải có `id` trong body
- Chỉ cần gửi các field muốn cập nhật

---

### 12. Xóa sản phẩm (DELETE - CẦN TOKEN)

#### Request Setup:
- **Method:** `DELETE`
- **URL:** `http://localhost/doan/api/products.php?id=1`
- **Headers:**
  ```
  Authorization: Bearer YOUR_TOKEN_HERE
  ```

#### Response Thành Công (200):
```json
{
    "success": true,
    "message": "Xóa sản phẩm thành công"
}
```

---

## 📝 Hướng dẫn từng bước trên Postman

### Bước 1: Lấy Token
1. Tạo request: `Login` hoặc `Register`
2. Copy `token` từ response
3. Lưu token vào Environment Variable (xem Tips bên dưới)

### Bước 2: Tạo Collection mới
1. Mở Postman
2. Click **New** → **Collection**
3. Đặt tên: `PHP API - Categories & Products`

### Bước 3: Tạo Request Categories

#### 3.1. GET All Categories
1. Click **Add Request** trong collection
2. Đặt tên: `1. GET - All Categories`
3. Method: **GET**
4. URL: `http://localhost/doan/api/categories.php`
5. Click **Send**

#### 3.2. POST Create Category
1. Tạo request mới: `2. POST - Create Category`
2. Method: **POST**
3. URL: `http://localhost/doan/api/categories.php`
4. Headers:
   - `Content-Type: application/json`
   - `Authorization: Bearer {{token}}`
5. Body (raw JSON):
   ```json
   {
       "name": "Cà phê",
       "description": "Các loại cà phê",
       "status": "active",
       "sort_order": 1
   }
   ```
6. Click **Send**

#### 3.3. PUT Update Category
1. Tạo request mới: `3. PUT - Update Category`
2. Method: **PUT**
3. URL: `http://localhost/doan/api/categories.php`
4. Headers:
   - `Content-Type: application/json`
   - `Authorization: Bearer {{token}}`
5. Body (raw JSON):
   ```json
   {
       "id": 1,
       "name": "Cà phê Premium",
       "description": "Cà phê cao cấp"
   }
   ```
6. Click **Send**

#### 3.4. DELETE Category
1. Tạo request mới: `4. DELETE - Delete Category`
2. Method: **DELETE**
3. URL: `http://localhost/doan/api/categories.php?id=1`
4. Headers:
   - `Authorization: Bearer {{token}}`
5. Click **Send**

### Bước 4: Tạo Request Products

#### 4.1. GET All Products
1. Tạo request mới: `5. GET - All Products`
2. Method: **GET**
3. URL: `http://localhost/doan/api/products.php`
4. Click **Send**

#### 4.2. POST Create Product
1. Tạo request mới: `6. POST - Create Product`
2. Method: **POST**
3. URL: `http://localhost/doan/api/products.php`
4. Headers:
   - `Content-Type: application/json`
   - `Authorization: Bearer {{token}}`
5. Body (raw JSON):
   ```json
   {
       "category_id": 1,
       "name": "Cà phê đen",
       "description": "Cà phê đen đậm đà",
       "price": 25000,
       "stock_quantity": 100,
       "status": "active",
       "featured": 1
   }
   ```
6. Click **Send**

#### 4.3. GET Products with Filters
1. Tạo request mới: `7. GET - Products by Category`
2. Method: **GET**
3. URL: `http://localhost/doan/api/products.php?category_id=1&status=active&featured=1`
4. Click **Send**

#### 4.4. PUT Update Product
1. Tạo request mới: `8. PUT - Update Product`
2. Method: **PUT**
3. URL: `http://localhost/doan/api/products.php`
4. Headers:
   - `Content-Type: application/json`
   - `Authorization: Bearer {{token}}`
5. Body (raw JSON):
   ```json
   {
       "id": 1,
       "price": 30000,
       "sale_price": 25000,
       "stock_quantity": 50
   }
   ```
6. Click **Send**

#### 4.5. DELETE Product
1. Tạo request mới: `9. DELETE - Delete Product`
2. Method: **DELETE**
3. URL: `http://localhost/doan/api/products.php?id=1`
4. Headers:
   - `Authorization: Bearer {{token}}`
5. Click **Send**

---

## 🧪 Test Cases

### Test Case 1: Tạo danh mục thành công
```json
{
    "name": "Trà",
    "description": "Các loại trà",
    "status": "active",
    "sort_order": 2
}
```
**Kỳ vọng:** Status 201, nhận được category_id và category data

### Test Case 2: Tạo danh mục thiếu tên
```json
{
    "description": "Mô tả",
    "status": "active"
}
```
**Kỳ vọng:** Status 400, message "Tên danh mục không được để trống"

### Test Case 3: Tạo danh mục không có token
**Kỳ vọng:** Status 401, message "Token không hợp lệ hoặc đã hết hạn"

### Test Case 4: Tạo sản phẩm thành công
```json
{
    "category_id": 1,
    "name": "Cà phê sữa",
    "price": 30000,
    "stock_quantity": 50,
    "status": "active"
}
```
**Kỳ vọng:** Status 201, nhận được product_id và product data

### Test Case 5: Tạo sản phẩm với category_id không tồn tại
```json
{
    "category_id": 999,
    "name": "Sản phẩm test",
    "price": 10000
}
```
**Kỳ vọng:** Status 400, message "Danh mục không hợp lệ"

### Test Case 6: Tạo sản phẩm thiếu tên
```json
{
    "category_id": 1,
    "price": 10000
}
```
**Kỳ vọng:** Status 400, message "Tên sản phẩm không được để trống"

### Test Case 7: Lấy sản phẩm theo category_id
URL: `GET /api/products.php?category_id=1`
**Kỳ vọng:** Status 200, chỉ trả về sản phẩm thuộc category_id=1

### Test Case 8: Tìm kiếm sản phẩm
URL: `GET /api/products.php?search=cà phê`
**Kỳ vọng:** Status 200, trả về sản phẩm có tên hoặc mô tả chứa "cà phê"

### Test Case 9: Phân trang sản phẩm
URL: `GET /api/products.php?page=2&limit=10`
**Kỳ vọng:** Status 200, trả về trang 2 với 10 sản phẩm mỗi trang

### Test Case 10: Xóa danh mục có sản phẩm
**Kỳ vọng:** Status 400, message "Không thể xóa danh mục vì còn sản phẩm thuộc danh mục này"

### Test Case 11: Cập nhật sản phẩm không có token
**Kỳ vọng:** Status 401, message "Token không hợp lệ hoặc đã hết hạn"

---

## 🔧 Troubleshooting

### Lỗi: "Kết nối database thất bại"
- Kiểm tra MySQL đang chạy
- Kiểm tra thông tin trong `config/database.php`
- Đảm bảo database `caffe` đã được tạo
- Chạy migration: `database/migration_categories_products.sql`

### Lỗi: "Table 'categories' doesn't exist"
- Chạy file migration: `database/migration_categories_products.sql`
- Hoặc import `database/schema.sql` đầy đủ

### Lỗi: 401 Unauthorized
- Kiểm tra token có hợp lệ không
- Đảm bảo header: `Authorization: Bearer TOKEN` (có khoảng trắng sau Bearer)
- Token có thể đã hết hạn (24 giờ), cần đăng nhập lại

### Lỗi: 400 "Danh mục không hợp lệ"
- Kiểm tra `category_id` có tồn tại trong bảng categories không
- Đảm bảo đã tạo danh mục trước khi tạo sản phẩm

### Lỗi: 400 "Không thể xóa danh mục vì còn sản phẩm"
- Xóa tất cả sản phẩm thuộc danh mục đó trước
- Hoặc chuyển sản phẩm sang danh mục khác

### Lỗi: 404 Not Found
- Kiểm tra URL có đúng không
- Kiểm tra file `api/categories.php` và `api/products.php` có tồn tại không

### Lỗi: 500 Internal Server Error
- Kiểm tra file `storage/` có quyền ghi không
- Xem log trong `storage/php_errors.log`
- Kiểm tra PHP version >= 7.4

---

## 💡 Tips

### 1. Lưu token vào Environment Variable:
- Tạo Environment trong Postman (ví dụ: `Local Development`)
- Sau khi login, thêm script vào tab **Tests**:
  ```javascript
  var jsonData = pm.response.json();
  if (jsonData.token) {
      pm.environment.set("token", jsonData.token);
  }
  ```
- Trong các request cần token, dùng: `Bearer {{token}}`

### 2. Lưu category_id và product_id:
Sau khi tạo category/product thành công, lưu ID vào environment:
```javascript
var jsonData = pm.response.json();
if (jsonData.category_id) {
    pm.environment.set("category_id", jsonData.category_id);
}
if (jsonData.product_id) {
    pm.environment.set("product_id", jsonData.product_id);
}
```

Sau đó dùng: `{{category_id}}`, `{{product_id}}`

### 3. Test tự động:
Thêm Test script trong Postman:
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData.success).to.be.true;
});

pm.test("Response has data", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
});
```

### 4. Pre-request Script để tự động lấy token:
Tạo một request `Get Token` và chạy trước các request khác:
```javascript
// Trong Pre-request Script của collection
pm.sendRequest({
    url: pm.environment.get("base_url") + '/api/login.php',
    method: 'POST',
    header: {
        'Content-Type': 'application/json'
    },
    body: {
        mode: 'raw',
        raw: JSON.stringify({
            email: pm.environment.get("test_email"),
            password: pm.environment.get("test_password")
        })
    }
}, function (err, res) {
    if (res.json().token) {
        pm.environment.set("token", res.json().token);
    }
});
```

### 5. Export Collection:
- Click **...** trên collection → **Export**
- Lưu file để chia sẻ với team
- Import vào Postman khác để sử dụng

### 6. Sử dụng Collection Runner:
- Click **Run** trên collection
- Chọn các request muốn chạy
- Chạy tự động để test toàn bộ API

---

## 📊 Flow Test Đề Xuất

### Flow 1: Tạo danh mục và sản phẩm
1. **Login** → Lấy token
2. **POST Create Category** → Tạo danh mục "Cà phê" → Lưu category_id
3. **POST Create Product** → Tạo sản phẩm thuộc category vừa tạo
4. **GET All Products** → Kiểm tra sản phẩm đã được tạo
5. **GET Product by ID** → Xem chi tiết sản phẩm

### Flow 2: Cập nhật và xóa
1. **Login** → Lấy token
2. **GET All Categories** → Xem danh sách danh mục
3. **PUT Update Category** → Cập nhật tên danh mục
4. **PUT Update Product** → Cập nhật giá sản phẩm
5. **DELETE Product** → Xóa sản phẩm
6. **DELETE Category** → Xóa danh mục (sau khi đã xóa hết sản phẩm)

### Flow 3: Tìm kiếm và lọc
1. **GET All Products** → Xem tất cả
2. **GET Products by Category** → Lọc theo danh mục
3. **GET Products Featured** → Lọc sản phẩm nổi bật
4. **GET Products Search** → Tìm kiếm theo từ khóa
5. **GET Products Pagination** → Test phân trang

---

## 📸 Ví dụ Request/Response

### Request: Tạo Category
```
POST http://localhost/doan/api/categories.php
Headers:
  Content-Type: application/json
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Body (raw JSON):
  {
    "name": "Cà phê",
    "description": "Các loại cà phê",
    "status": "active",
    "sort_order": 1
  }
```

### Request: Tạo Product
```
POST http://localhost/doan/api/products.php
Headers:
  Content-Type: application/json
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Body (raw JSON):
  {
    "category_id": 1,
    "name": "Cà phê đen",
    "description": "Cà phê đen đậm đà",
    "price": 25000,
    "stock_quantity": 100,
    "status": "active",
    "featured": 1
  }
```

### Request: Lọc Products
```
GET http://localhost/doan/api/products.php?category_id=1&status=active&featured=1&page=1&limit=10
```

---

Chúc bạn test thành công! 🎉
