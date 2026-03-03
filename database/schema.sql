-- ============================================
-- TẠO DATABASE
-- ============================================
CREATE DATABASE IF NOT EXISTS caffe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE caffe;

-- ============================================
-- BẢNG 1: users (BẢNG CHÍNH - BẮT BUỘC)
-- ============================================
-- Lưu thông tin người dùng
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active' COMMENT 'Trạng thái tài khoản',
    role ENUM('user', 'admin') DEFAULT 'user' COMMENT 'Vai trò người dùng',
    phone VARCHAR(20) NULL COMMENT 'Số điện thoại',
    address TEXT NULL COMMENT 'Địa chỉ giao hàng mặc định',
    avatar LONGTEXT NULL COMMENT 'Đường dẫn ảnh đại diện',
    email_verified_at DATETIME NULL COMMENT 'Thời gian xác thực email',
    last_login_at DATETIME NULL COMMENT 'Lần đăng nhập cuối',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 2: login_attempts (KHUYẾN NGHỊ)
-- ============================================
-- Lưu lịch sử đăng nhập để tracking và bảo mật
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'ID user nếu đăng nhập thành công',
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL COMMENT 'Thông tin trình duyệt',
    success TINYINT(1) DEFAULT 0 COMMENT '1 = thành công, 0 = thất bại',
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 3: refresh_tokens (TÙY CHỌN)
-- ============================================
-- Lưu refresh token để làm mới JWT token
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(500) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL COMMENT 'Thời gian thu hồi token',
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 4: password_resets (TÙY CHỌN)
-- ============================================
-- Lưu token reset mật khẩu
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL COMMENT 'Thời gian sử dụng token',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 5: user_sessions (TÙY CHỌN)
-- ============================================
-- Quản lý session của user (nếu cần)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 6: categories (DANH MỤC SẢN PHẨM)
-- ============================================
-- Lưu thông tin danh mục sản phẩm
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Tên danh mục',
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL slug',
    description TEXT NULL COMMENT 'Mô tả danh mục',
    image LONGTEXT NULL COMMENT 'Ảnh danh mục',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    sort_order INT DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BẢNG 7: products (SẢN PHẨM)
-- ============================================
-- Lưu thông tin sản phẩm
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL COMMENT 'ID danh mục',
    name VARCHAR(200) NOT NULL COMMENT 'Tên sản phẩm',
    slug VARCHAR(200) NOT NULL UNIQUE COMMENT 'URL slug',
    description TEXT NULL COMMENT 'Mô tả sản phẩm',
    short_description VARCHAR(500) NULL COMMENT 'Mô tả ngắn',
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Giá sản phẩm',
    sale_price DECIMAL(10, 2) NULL COMMENT 'Giá khuyến mãi',
    sku VARCHAR(50) NULL COMMENT 'Mã SKU',
    stock_quantity INT DEFAULT 0 COMMENT 'Số lượng tồn kho',
    image LONGTEXT NULL COMMENT 'Ảnh chính',
    images LONGTEXT NULL COMMENT 'Danh sách ảnh (JSON)',
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active' COMMENT 'Trạng thái',
    featured TINYINT(1) DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
    sort_order INT DEFAULT 0 COMMENT 'Thứ tự sắp xếp',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_price (price),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;