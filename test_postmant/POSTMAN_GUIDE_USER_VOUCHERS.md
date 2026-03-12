# Hướng Dẫn Test API Ví Voucher Của Người Dùng (User Vouchers) bằng Postman

**Base URL:** `http://localhost/doan/api/user_vouchers.php`

API này chịu trách nhiệm quản lý **Ví Voucher cá nhân** của từng khách hàng. Nó lưu lại lịch sử người dùng đã quay trúng voucher nào từ Minigame Vòng Quay, theo dõi việc họ đã dùng voucher đó hay chưa.

---

## 1. Lưu Voucher Vào Ví Người Dùng (POST)

Sử dụng API này ngay thời điểm khách hàng **quay trúng** phần thưởng tại Minigame. Hệ thống sẽ:
1. Xác minh mã voucher có tồn tại không.
2. Kiểm tra xem kho ưu đãi còn phát lượt không (usage_limit).
3. Đưa voucher đó vào ví cá nhân của User.
4. Tăng `used_count` ở kho tổng lên 1 lượt.

*   **Method:** `POST`
*   **URL:** `http://localhost/doan/api/user_vouchers.php`
*   **Body Type:** `raw` -> `JSON`
*   **Dữ liệu Gửi Đi:**
    ```json
    {
        "user_id": 1,
        "code": "LUCKY50K"
    }
    ```
    *(Ghi chú: Thay `user_id` bằng ID của user đang đăng nhập. Có thể truyền `voucher_id` thay cho `code` cũng được).*
*   **Response Thành Công:**
    ```json
    {
        "status": "success",
        "message": "Lưu voucher vào ví thành công.",
        "user_voucher_id": "3",
        "voucher": {
            "id": 2,
            "code": "LUCKY50K",
            "discount_amount": "50000.00"
            // ... các thông tin khác
        }
    }
    ```

---

## 2. Lấy Danh Sách Voucher Trong Ví (GET)

Hiển thị danh sách các mã giảm giá cá nhân mà người dùng đang sở hữu (trong trang Quản lý cá nhân hoặc lúc Thanh toán giỏ hàng).

*   **Method:** `GET`
*   **URL:** `http://localhost/doan/api/user_vouchers.php?user_id=1`
    *(Truyền dọc URL `user_id` của khách)*
*   **Lọc Nâng Cao:** Nếu bạn chỉ muốn lấy ra những mã **chưa sử dụng** để cho khách hàng chọn lúc thanh toán, hãy thêm `&is_used=0`
    *   `http://localhost/doan/api/user_vouchers.php?user_id=1&is_used=0` (Mã chưa dùng)
    *   `http://localhost/doan/api/user_vouchers.php?user_id=1&is_used=1` (Mã đã từng được thanh toán)
*   **Response Mẫu:**
    ```json
    {
        "status": "success",
        "data": [
            {
                "user_voucher_id": "1",
                "is_used": "0",
                "used_at": null,
                "received_at": "2026-03-11 19:00:00",
                "voucher_id": "2",
                "code": "LUCKY50K",
                "discount_amount": "50000.00",
                "start_date": "2026-03-11 18:30:00",
                "end_date": "2026-03-25 18:30:00",
                "status": "active"
            }
        ]
    }
    ```

---

## 3. Đánh Dấu Voucher Đã Sử Dụng (PUT)

Được gọi tự động từ phía Backend/Frontend lúc **tạo đơn hàng thành công** để "tiêu hủy" voucher này trong ví, ngăn cản khách xài lại mã này cho đơn tiếp theo.

*   **Method:** `PUT`
*   **URL:** `http://localhost/doan/api/user_vouchers.php`
*   **Body Type:** `raw` -> `JSON`
*   **Dữ liệu Gửi Đi:**
    ```json
    {
        "user_id": 1,
        "voucher_id": 2
    }
    ```
    *(Ghi chú: `voucher_id` này là mã ID gốc của loại voucher `LUCKY50K`, hệ thống sẽ tự động chạy vào ví của người dùng đang có ID=1, và "xóa" một cái thẻ `LUCKY50K` tìm thấy chưa sử dụng của họ).*
*   **Response Thành Công:**
    ```json
    {
        "status": "success",
        "message": "Đã đánh dấu voucher là đã sử dụng."
    }
    ```
