-- ============================================
-- TẠO BẢNG attendance (CHẤM CÔNG)
-- ============================================
USE caffe;

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NULL,
    date DATE NOT NULL,
    status ENUM('present', 'late', 'early_leave', 'absent') DEFAULT 'present',
    note TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date), -- Mỗi nhân viên chỉ có 1 bản ghi chấm công mỗi ngày
    INDEX idx_date (date),
    INDEX idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
