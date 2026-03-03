-- ============================================
-- BẢNG 8: orders (ĐƠN HÀNG)
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'ID người dùng (nếu có)',
    full_name VARCHAR(100) NOT NULL COMMENT 'Họ tên người nhận',
    email VARCHAR(100) NOT NULL COMMENT 'Email người nhận',
    phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại',
    address TEXT NOT NULL COMMENT 'Địa chỉ giao hàng',
    total_amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền đơn hàng',
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending' COMMENT 'Trạng thái đơn hàng',
    payment_method ENUM('cod', 'banking') DEFAULT 'cod' COMMENT 'Phương thức thanh toán',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
    note TEXT NULL COMMENT 'Ghi chú đơn hàng',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 9: order_items (CHI TIẾT ĐƠN HÀNG)
-- ============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL COMMENT 'ID đơn hàng',
    product_id INT NOT NULL COMMENT 'ID sản phẩm',
    quantity INT NOT NULL COMMENT 'Số lượng',
    price DECIMAL(15, 2) NOT NULL COMMENT 'Giá sản phẩm tại thời điểm mua',
    total_price DECIMAL(15, 2) NOT NULL COMMENT 'Thành tiền',
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
