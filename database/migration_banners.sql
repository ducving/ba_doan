-- ============================================
-- BẢNG 10: banners (BANNER)
-- ============================================
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NULL COMMENT 'Tiêu đề banner',
    image LONGTEXT NOT NULL COMMENT 'Đường dẫn ảnh banner',
    link VARCHAR(255) NULL COMMENT 'Liên kết khi click (tùy chọn)',
    sort_order INT DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái hiển thị',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
