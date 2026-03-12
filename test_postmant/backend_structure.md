# Cấu trúc Backend Project

Dưới đây là sơ đồ cấu trúc thư mục của hệ thống Backend:

```text
├── config/
│   └── database.php       # Cấu hình kết nối cơ sở dữ liệu
├── api/
│   ├── auth/              # Xử lý đăng nhập, xác thực
│   ├── order/             # API bán hàng
│   ├── product/           # API quản lý hàng hóa
│   ├── employee/          # API quản lý nhân viên
│   ├── customer/          # API quản lý khách hàng
│   ├── salary/            # API quản lý lương
│   └── report/            # API thống kê báo cáo
├── includes/
│   └── connection.php     # Kết nối MySQLi
└── index.php              # Điểm khởi đầu của ứng dụng (Router)
```

## Chi tiết các thành phần:

- **`config/database.php`**: Chứa các thông số cấu hình như Host, Username, Password và Tên cơ sở dữ liệu.
- **`api/`**: Thư mục chính chứa tất cả các endpoint của hệ thống, được chia theo từng phân hệ chức năng (Modulization).
  - **`auth/`**: Xử lý Login, Logout, Register và phân quyền JWT.
  - **`order/`**: Quản lý giỏ hàng, đặt hàng và lịch sử đơn hàng.
  - **`product/`**: Thêm, sửa, xóa và truy vấn thông tin sản phẩm.
  - **`employee/`**: Quản lý thông tin hồ sơ nhân sự.
  - **`customer/`**: Quản lý thông tin khách hàng và điểm tích lũy.
  - **`salary/`**: Tính toán lương, thưởng và khấu trừ.
  - **`report/`**: Xuất các báo cáo doanh thu, tồn kho theo thời gian.
- **`includes/connection.php`**: File dùng để khởi tạo kết nối MySQLi dùng chung cho các phân hệ cũ hoặc đơn giản.
- **`index.php`**: Nhận yêu cầu từ Client và điều hướng (routing) đến đúng tệp xử lý trong thư mục `api/`.
