# Hướng Dẫn Test API Khuyến Mãi (Vouchers) bằng Postman

**Base URL:** `http://localhost/doan/api/vouchers.php` (hoặc tên miền/đường dẫn mà bạn thiết lập cho laragon)

Các API này cung cấp chức năng quản lý danh sách mã giảm giá (Vouchers) cũng như kiểm tra tính hợp lệ của mã trước khi áp dụng vào đơn hàng. File này mô tả chi tiết cách thiết lập các request trong phần mềm Postman để kiểm tra.

---

## 1. Lấy Danh Sách Voucher (GET)

API này trả về danh sách toàn bộ các voucher hiện có trong hệ thống, sắp xếp theo thời gian mới nhất (created_at DESC).

*   **Method:** `GET`
*   **URL:** `http://localhost/doan/api/vouchers.php`
*   **Response Thành Công:**
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "1",
                "code": "SALE10",
                "discount_type": "percent",
                "discount_value": "10.00",
                "min_order_value": "100000.00",
                "max_discount": "50000.00",
                "start_date": "2026-03-01 00:00:00",
                "end_date": "2026-03-31 23:59:59",
                "usage_limit": "100",
                "used_count": "5",
                "status": "active",
                "created_at": "2026-03-11 18:00:00",
                "updated_at": "2026-03-11 18:00:00"
            }
        ]
    }
    ```

---

## 2. Tìm & Kiểm Tra Mã Giảm Giá Trực Tiếp (GET by Code)

Rất hữu ích để tích hợp vào ứng dụng Frontend/Mobile khi khách hàng nhập mã Voucher tại màn hình Thanh toán. API sẽ đánh giá các điều kiện (Ngày bắt đầu, hết hạn, giới hạn số lần sử dụng và trạng thái).

*   **Method:** `GET`
*   **URL:** `http://localhost/doan/api/vouchers.php?code=SALE10`
*   **Trường Hợp Hợp Lệ:**
    ```json
    {
        "status": "success",
        "data": {
            "id": "1",
            "code": "SALE10",
            ...
        }
    }
    ```
*   **Trường Hợp Lỗi (Hết hạn, không tồn tại, hết lượt sử dụng):**
    ```json
    {
        "status": "error",
        "message": "Voucher đã hết hạn."
    }
    ```

---

## 3. Lấy Chi Tiết Voucher Cụ Thể (GET by ID)

Dùng trong phần quản lý Admin để lấy chi tiết 1 mã giảm giá để hiển thị form chỉnh sửa.

*   **Method:** `GET`
*   **URL:** `http://localhost/doan/api/vouchers.php?id=1`

---

## 4. Tạo Mới Một Voucher (POST)

Thêm một mã giảm giá mới vào cơ sở dữ liệu.

*   **Method:** `POST`
*   **URL:** `http://localhost/doan/api/vouchers.php`
*   **Body Type:** `raw` -> `JSON`
*   **Dữ liệu Gửi Đi (Body JSON):**
    ```json
    {
        "code": "GIAM50K",
        "discount_type": "fixed",
        "discount_value": 50000,
        "min_order_value": 200000,
        "max_discount": null,
        "start_date": "2026-03-01 00:00:00",
        "end_date": "2026-04-30 23:59:59",
        "usage_limit": 50,
        "status": "active"
    }
    ```
*   **Ghi chú:**
    *   `discount_type`: Có hai lựa chọn là `fixed` (Giảm tiền cứng) hoặc `percent` (Giảm theo phần trăm).
    *   `discount_value`: Nếu `discount_type` là `percent` thì giá trị là %, nếu `fixed` là số tiền (VND).
    *   `code`: Không được trùng lặp. Vui lòng nhập chuỗi ký tự viết Hoa / Số.

---

## 5. Cập Nhật Voucher (PUT)

Được sử dụng khi chỉnh sửa thông tin voucher.

*   **Method:** `PUT`
*   **URL:** `http://localhost/doan/api/vouchers.php?id=1` (Thay số '1' bằng mã ID của Voucher cần sửa)
*   **Body Type:** `raw` -> `JSON`
*   **Dữ liệu Gửi Đi (Body JSON):**
    ```json
    {
        "code": "GIAM50K",
        "discount_type": "fixed",
        "discount_value": 60000,
        "min_order_value": 200000,
        "max_discount": null,
        "start_date": "2026-03-01 00:00:00",
        "end_date": "2026-04-30 23:59:59",
        "usage_limit": 100,
        "status": "active"
    }
    ```

---

## 6. Xóa Voucher (DELETE)

Xóa vĩnh viễn voucher khỏi hệ thống.

*   **Method:** `DELETE`
*   **URL:** `http://localhost/doan/api/vouchers.php?id=1` (Thay số '1' bằng mã ID của Voucher muốn xóa)
*   **Response:**
    ```json
    {
        "status": "success",
        "message": "Xóa voucher thành công."
    }
    ```
