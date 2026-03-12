-- Bảng lưu cấu hình các ô trên Vòng Quay May Mắn (Lucky Wheel Sectors)
CREATE TABLE IF NOT EXISTS `lucky_wheel_sectors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sector_id` VARCHAR(50) NOT NULL UNIQUE, -- Định danh (p_10k, unlucky, voucher_1...)
  `label` VARCHAR(100) NOT NULL,           -- Nhãn hiển thị (10k Điểm, Hụt rồi...)
  `type` ENUM('point', 'voucher', 'none') NOT NULL DEFAULT 'point', -- Loại phần thưởng
  `value` VARCHAR(255) DEFAULT '0',        -- Giá trị (số điểm hoặc mã voucher)
  `color` VARCHAR(20) DEFAULT '#ffffff',   -- Màu sắc hiển thị trên vòng quay
  `weight` INT DEFAULT 1,                  -- Tỉ lệ trúng (trọng số)
  `is_active` TINYINT(1) DEFAULT 1,        -- Trạng thái kích hoạt
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm dữ liệu mẫu dựa trên cấu hình bạn đang dùng
INSERT INTO `lucky_wheel_sectors` (`sector_id`, `label`, `type`, `value`, `color`, `weight`) 
VALUES 
('p_10k',   '10k Điểm',  'point',   '10000',  '#fca5a5', 50),
('unlucky', 'Hụt rồi',    'none',    '0',      '#d8b4fe', 30),
('p_50k',   '50k Điểm',  'point',   '50000',  '#fde047', 10),
('p_20k',   '20k Điểm',  'point',   '20000',  '#fde047', 20),
('p_100k',  '100k Điểm', 'point',   '100000', '#fde047', 5),
('v_lucky', 'Voucher',    'voucher', 'LUCKY50K', '#86efac', 10);
