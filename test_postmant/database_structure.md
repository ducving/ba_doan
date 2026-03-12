# TÀI LIỆU CẤU TRÚC CƠ SỞ DỮ LIỆU (DATABASE SCHEMA)

Dưới đây là chi tiết các bảng và trường dữ liệu trong hệ thống cơ sở dữ liệu `caffe`, được phân loại theo từng nhóm chức năng.

---

## 1. NHÓM NGƯỜI DÙNG & BẢO MẬT

### 1.1 Bảng `users` (Người dùng)

Lưu trữ thông tin tài khoản của khách hàng, nhân viên và quản trị viên.

| Tên trường   | Kiểu dữ liệu | Ràng buộc            | Mô tả                          |
| :----------- | :----------- | :------------------- | :----------------------------- |
| `id`         | INT          | PK, AI               | Mã định danh duy nhất          |
| `name`       | VARCHAR(100) | NOT NULL             | Tên hiển thị/Họ tên            |
| `email`      | VARCHAR(100) | UNIQUE               | Địa chỉ email (Dùng đăng nhập) |
| `password`   | VARCHAR(255) | NOT NULL             | Mật khẩu đã mã hóa             |
| `role`       | ENUM         | 'user', 'admin'      | Vai trò trong hệ thống         |
| `status`     | ENUM         | 'active', 'inactive' | Trạng thái tài khoản           |
| `google_id`  | VARCHAR(255) | NULL                 | ID nếu đăng nhập qua Google    |
| `phone`      | VARCHAR(20)  | NULL                 | Số điện thoại                  |
| `address`    | TEXT         | NULL                 | Địa chỉ mặc định               |
| `avatar`     | LONGTEXT     | NULL                 | Đường dẫn ảnh đại diện         |
| `created_at` | DATETIME     | DEFAULT NOW          | Thời điểm tạo tài khoản        |
| `updated_at` | DATETIME     | DEFAULT NOW          | Thời điểm cập nhật cuối        |

### 1.2 Bảng `login_attempts` (Lịch sử đăng nhập)

| Tên trường     | Kiểu dữ liệu | Mô tả                      |
| :------------- | :----------- | :------------------------- |
| `id`           | INT          | PK                         |
| `user_id`      | INT          | FK (users.id)              |
| `email`        | VARCHAR(100) | Email thử đăng nhập        |
| `ip_address`   | VARCHAR(45)  | Địa chỉ IP người dùng      |
| `success`      | TINYINT      | 1: Thành công, 0: Thất bại |
| `attempt_time` | DATETIME     | Thời gian thực hiện        |

### 1.3 Bảng `refresh_tokens` (JWT Refresh)

| Tên trường   | Kiểu dữ liệu | Mô tả                    |
| :----------- | :----------- | :----------------------- |
| `id`         | INT          | PK                       |
| `user_id`    | INT          | FK (users.id)            |
| `token`      | VARCHAR(500) | Token để làm mới session |
| `expires_at` | DATETIME     | Thời gian hết hạn        |

---

## 2. NHÓM SẢN PHẨM & DANH MỤC

### 2.1 Bảng `categories` (Danh mục sản phẩm)

| Tên trường    | Kiểu dữ liệu | Mô tả                               |
| :------------ | :----------- | :---------------------------------- |
| `id`          | INT          | PK                                  |
| `name`        | VARCHAR(100) | Tên danh mục (Cà phê, Trà, Bánh...) |
| `slug`        | VARCHAR(100) | Đường dẫn tĩnh thân thiện           |
| `description` | TEXT         | Mô tả danh mục                      |
| `image`       | LONGTEXT     | Hình ảnh đại diện danh mục          |
| `status`      | ENUM         | 'active', 'inactive'                |

### 2.2 Bảng `products` (Sản phẩm)

| Tên trường       | Kiểu dữ liệu  | Mô tả                                |
| :--------------- | :------------ | :----------------------------------- |
| `id`             | INT           | PK                                   |
| `category_id`    | INT           | FK (categories.id)                   |
| `name`           | VARCHAR(200)  | Tên sản phẩm                         |
| `price`          | DECIMAL(10,2) | Giá bán gốc                          |
| `sale_price`     | DECIMAL(10,2) | Giá sau khuyến mãi                   |
| `stock_quantity` | INT           | Số lượng tồn kho                     |
| `status`         | ENUM          | 'active', 'inactive', 'out_of_stock' |
| `image`          | LONGTEXT      | Ảnh chính sản phẩm                   |
| `featured`       | TINYINT       | 1: Sản phẩm nổi bật                  |

---

## 3. NHÓM ĐƠN HÀNG & THANH TOÁN

### 3.1 Bảng `orders` (Đơn hàng)

| Tên trường        | Kiểu dữ liệu  | Mô tả                                             |
| :---------------- | :------------ | :------------------------------------------------ |
| `id`              | INT           | PK                                                |
| `user_id`         | INT           | FK (users.id) - ID người mua                      |
| `full_name`       | VARCHAR(100)  | Tên người nhận hàng                               |
| `total_amount`    | DECIMAL(15,2) | Tổng tiền thanh toán cuối cùng                    |
| `status`          | ENUM          | 'pending', 'processing', 'completed', 'cancelled' |
| `payment_status`  | ENUM          | 'pending', 'paid', 'failed'                       |
| `points_earned`   | INT           | Điểm cộng được sau đơn hàng                       |
| `points_redeemed` | INT           | Điểm đã sử dụng để giảm giá                       |

### 3.2 Bảng `order_items` (Chi tiết đơn hàng)

| Tên trường    | Kiểu dữ liệu  | Mô tả                          |
| :------------ | :------------ | :----------------------------- |
| `id`          | INT           | PK                             |
| `order_id`    | INT           | FK (orders.id)                 |
| `product_id`  | INT           | FK (products.id)               |
| `quantity`    | INT           | Số lượng mua                   |
| `price`       | DECIMAL(15,2) | Đơn giá tại thời điểm mua      |
| `total_price` | DECIMAL(15,2) | Thành tiền (quantity \* price) |

### 3.3 Bảng `order_payments` (Nhật ký thanh toán)

| Tên trường       | Kiểu dữ liệu  | Mô tả                         |
| :--------------- | :------------ | :---------------------------- |
| `id`             | INT           | PK                            |
| `order_id`       | INT           | FK (orders.id)                |
| `user_id`        | INT           | FK (users.id)                 |
| `payment_method` | VARCHAR(50)   | COD, Banking, v.v.            |
| `amount`         | DECIMAL(15,2) | Số tiền thanh toán            |
| `status`         | VARCHAR(50)   | Trạng thái (paid, pending...) |

---

## 4. NHÓM ĐIỂM THƯỞNG (LOYALTY)

### 4.1 Bảng `user_points` (Ví điểm)

| Tên trường     | Kiểu dữ liệu | Mô tả                                   |
| :------------- | :----------- | :-------------------------------------- |
| `id`           | INT          | PK                                      |
| `user_id`      | INT          | FK (users.id)                           |
| `points`       | INT          | Số điểm khả dụng hiện tại               |
| `total_earned` | INT          | Tổng điểm đã kiếm được từ trước tới nay |

### 4.2 Bảng `point_transactions` (Lịch sử điểm)

| Tên trường | Kiểu dữ liệu | Mô tả                                           |
| :--------- | :----------- | :---------------------------------------------- |
| `id`       | INT          | PK                                              |
| `user_id`  | INT          | FK (users.id)                                   |
| `type`     | ENUM         | 'earn' (cộng), 'redeem' (dùng), 'refund' (hoàn) |
| `points`   | INT          | Số điểm thay đổi (+/-)                          |
| `note`     | VARCHAR(255) | Lý do thay đổi                                  |

---

## 5. NHÓM NHÂN SỰ & TIN TỨC

### 5.1 Bảng `employees` (Nhân viên)

| Tên trường      | Kiểu dữ liệu  | Mô tả                               |
| :-------------- | :------------ | :---------------------------------- |
| `id`            | INT           | PK                                  |
| `user_id`       | INT           | FK (users.id) - Tài khoản đăng nhập |
| `employee_code` | VARCHAR(20)   | Mã nhân viên (VD: NV001)            |
| `salary`        | DECIMAL(15,2) | Lương cơ bản                        |
| `position`      | VARCHAR(50)   | Chức vụ                             |

### 5.2 Bảng `attendance` (Chấm công)

| Tên trường    | Kiểu dữ liệu | Mô tả                      |
| :------------ | :----------- | :------------------------- |
| `id`          | INT          | PK                         |
| `employee_id` | INT          | FK (employees.id)          |
| `date`        | DATE         | Ngày làm việc              |
| `check_in`    | DATETIME     | Thời gian vào ca           |
| `check_out`   | DATETIME     | Thời gian ra ca            |
| `total_hours` | DECIMAL(5,2) | Tổng số giờ làm            |
| `daily_wage`  | INT          | Lương thực nhận trong ngày |

### 5.3 Bảng `news` (Tin tức) & `banners` (Banner)

- **News**: id, title, content, image, status, created_at.
- **Banners**: id, title, image, link, sort_order, status.

---

_Ghi chú: Các bảng phụ khác như `password_resets` và `user_sessions` cũng tồn tại trong hệ thống để phục vụ các tính năng bảo mật nâng cao._
