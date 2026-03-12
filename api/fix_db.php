<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

$database = new Database();
$db = $database->getConnection();

function addColumnIfNeeded($db, $table, $column, $definition) {
    try {
        $db->query("SELECT $column FROM $table LIMIT 1");
        echo "Cột '$column' đã có sẵn.<br>";
    } catch (PDOException $e) {
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "<b>Đã thêm thành công cột '$column' vào bảng '$table'</b>.<br>";
    }
}

try {
    // 1. Tạo bảng vouchers nếu chưa có
    $sql_vouchers = "CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
        discount_type ENUM('fixed', 'percent') NOT NULL DEFAULT 'fixed',
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($sql_vouchers);
    echo "Khởi tạo cấu trúc bảng vouchers cơ sở xong.<br>";

    // 2. Thêm từng cột còn thiếu nếu bảng đã tồn tại sẵn từ trước đó với cấu trúc khác
    addColumnIfNeeded($db, 'vouchers', 'discount_type', "ENUM('fixed', 'percent') NOT NULL DEFAULT 'fixed'");
    addColumnIfNeeded($db, 'vouchers', 'min_order_amount', "DECIMAL(10,2) DEFAULT 0");
    addColumnIfNeeded($db, 'vouchers', 'max_discount_amount', "DECIMAL(10,2) DEFAULT NULL");
    addColumnIfNeeded($db, 'vouchers', 'expiry_date', "DATETIME NOT NULL DEFAULT '2026-12-31 00:00:00'");
    addColumnIfNeeded($db, 'vouchers', 'usage_limit', "INT DEFAULT NULL");
    addColumnIfNeeded($db, 'vouchers', 'used_count', "INT DEFAULT 0");
    addColumnIfNeeded($db, 'vouchers', 'is_active', "BOOLEAN DEFAULT 1");
    addColumnIfNeeded($db, 'vouchers', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    // 3. Chèn voucher mẫu
    $db->exec("INSERT IGNORE INTO vouchers (code, discount_amount, discount_type, expiry_date, is_active) VALUES 
        ('LUCKY10K', 10000, 'fixed', '2026-12-31', 1),
        ('SALE20', 20, 'percent', '2026-12-31', 1)");
    echo "Dữ liệu mẫu đã sẵn sàng.<br>";

    echo "<b>HOÀN TẤT: Đã đồng bộ cấu trúc database!</b>";

} catch (Exception $e) {
    echo "LỖI LỚN: " . $e->getMessage();
}
?>
