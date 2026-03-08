-- ============================================
-- HỆ THỐNG TÍCH ĐIỂM KHÁCH HÀNG
-- Chạy script này trong phpMyAdmin
-- ============================================

-- Bảng lưu điểm hiện có của user
CREATE TABLE IF NOT EXISTS user_points (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL UNIQUE,
  points       INT NOT NULL DEFAULT 0 COMMENT 'Điểm hiện có',
  total_earned INT NOT NULL DEFAULT 0 COMMENT 'Tổng điểm đã kiếm',
  total_spent  INT NOT NULL DEFAULT 0 COMMENT 'Tổng điểm đã dùng',
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lịch sử giao dịch điểm
CREATE TABLE IF NOT EXISTS point_transactions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  order_id   INT NULL,
  type       ENUM('earn', 'redeem', 'refund') NOT NULL COMMENT 'earn=cộng, redeem=dùng, refund=hoàn',
  points     INT NOT NULL COMMENT 'Số điểm (dương=cộng, âm=trừ)',
  note       VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm cột vào bảng orders (bỏ qua nếu đã tồn tại)
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS points_earned   INT DEFAULT 0 COMMENT 'Điểm được cộng sau khi hoàn tất',
  ADD COLUMN IF NOT EXISTS points_redeemed INT DEFAULT 0 COMMENT 'Điểm đã dùng để giảm giá',
  ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Số tiền giảm từ điểm';
