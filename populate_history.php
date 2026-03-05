<?php
require_once __DIR__ . '/classes/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("SELECT id, status, created_at FROM orders");
    $orders = $stmt->fetchAll();
    
    foreach ($orders as $order) {
        $checkStmt = $db->prepare("SELECT id FROM order_status_history WHERE order_id = ?");
        $checkStmt->execute([$order['id']]);
        if (!$checkStmt->fetch()) {
            $insertStmt = $db->prepare("INSERT INTO order_status_history (order_id, status, note, created_at) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$order['id'], $order['status'], 'Khởi tạo trạng thái đơn hàng', $order['created_at']]);
            echo "Added status history for Order ID: {$order['id']}\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
