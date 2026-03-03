-- ============================================
-- MIGRATION: Thêm bảng categories và products
-- ============================================
-- Chạy file này để thêm 2 bảng mới vào database hiện có
-- Không cần chạy nếu đã chạy schema.sql đầy đủ

USE caffe;

-- ============================================
-- BẢNG: categories (DANH MỤC SẢN PHẨM)
-- ============================================
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
-- BẢNG: products (SẢN PHẨM)
-- ============================================
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

-- ============================================
-- INSERT DỮ LIỆU MẪU (TÙY CHỌN)
-- ============================================
-- Bỏ comment các dòng dưới nếu muốn thêm dữ liệu mẫu

/*
-- Thêm một số danh mục mẫu
INSERT INTO categories (name, slug, description, status, sort_order) VALUES
('Cà phê', 'ca-phe', 'Các loại cà phê', 'active', 1),
('Trà', 'tra', 'Các loại trà', 'active', 2),
('Nước ép', 'nuoc-ep', 'Nước ép trái cây', 'active', 3),
('Bánh ngọt', 'banh-ngot', 'Bánh ngọt và bánh kem', 'active', 4);

-- Thêm một số sản phẩm mẫu
INSERT INTO products (category_id, name, slug, description, price, status, featured) VALUES
(1, 'Cà phê đen', 'ca-phe-den', 'Cà phê đen đậm đà', 25000, 'active', 1),
(1, 'Cà phê sữa', 'ca-phe-sua', 'Cà phê sữa thơm ngon', 30000, 'active', 1),
(2, 'Trà đào', 'tra-dao', 'Trà đào mát lạnh', 35000, 'active', 0),
(2, 'Trà sữa', 'tra-sua', 'Trà sữa thơm ngon', 40000, 'active', 1);
*/