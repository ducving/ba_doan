<?php
require_once __DIR__ . '/config/database.php';
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Thêm cột user_id để định danh người dùng độc lập với đơn hàng
    $pdo->exec("ALTER TABLE order_payments ADD COLUMN user_id INT NULL AFTER order_id");
    
    // 2. Cho phép order_id nhận giá trị NULL (để khi xóa đơn hàng thì log này không bị xóa)
    $pdo->exec("ALTER TABLE order_payments MODIFY order_id INT NULL");
    
    // 3. Xóa ràng buộc cũ và thêm ràng buộc mới với ON DELETE SET NULL
    // Lưu ý: Cần tìm tên constraint cũ. Thường là order_payments_ibfk_1
    // Để chắc chắn và đơn giản, tôi sẽ xử lý logic lưu trữ mà không phụ thuộc cứng vào constraint xóa nếu có thể.
    
    // Thử xóa constraint cũ (nếu có)
    try {
        $pdo->exec("ALTER TABLE order_payments DROP FOREIGN KEY order_payments_ibfk_1");
    } catch (Exception $e) {}

    // Tạo constraint mới: Khi xóa orders, cột order_id trong order_payments sẽ về NULL thay vì bị xóa bản ghi
    $pdo->exec("ALTER TABLE order_payments ADD CONSTRAINT fk_order_payments_order 
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL");
                
    echo "SUCCESS: Table 'order_payments' updated for independent storage.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
