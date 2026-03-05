-- ============================================
-- TẠO BẢNG employees (NHÂN VIÊN)
-- ============================================
USE caffe;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'Link tới bảng users nếu có tài khoản login',
    employee_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Mã nhân viên (ví dụ: NV001)',
    full_name VARCHAR(100) NOT NULL COMMENT 'Họ tên nhân viên',
    email VARCHAR(100) NULL COMMENT 'Email liên hệ',
    phone VARCHAR(20) NULL COMMENT 'Số điện thoại',
    position VARCHAR(50) NULL COMMENT 'Chức vụ',
    department VARCHAR(50) NULL COMMENT 'Phòng ban',
    salary DECIMAL(15, 2) DEFAULT 0.00 COMMENT 'Mức lương',
    hire_date DATE NULL COMMENT 'Ngày vào làm',
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active' COMMENT 'Trạng thái làm việc',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_employee_code (employee_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm một số dữ liệu mẫu (tùy chọn)
-- INSERT INTO employees (employee_code, full_name, email, position, salary, hire_date) VALUES 
-- ('NV001', 'Nguyễn Văn Quản Lý', 'manager@example.com', 'Quản lý', 20000000, '2023-01-01'),
-- ('NV002', 'Trần Thị Pha Chế', 'barista@example.com', 'Pha chế', 8000000, '2023-06-15');
