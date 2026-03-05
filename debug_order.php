<?php
require_once __DIR__ . '/classes/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    echo "Checking orders table:\n";
    $stmt = $db->query("SELECT id FROM orders WHERE id = 5");
    $order = $stmt->fetch();
    if ($order) {
        echo "Order 5 exists in orders table.\n";
    } else {
        echo "Order 5 does NOT exist in orders table.\n";
    }

    echo "\nChecking order_items table for order_id = 5:\n";
    $stmt = $db->query("SELECT id FROM order_items WHERE order_id = 5");
    $items = $stmt->fetchAll();
    echo "Number of items for order 5: " . count($items) . "\n";

    echo "\nChecking order_status_history table for order_id = 5:\n";
    $stmt = $db->query("SELECT id FROM order_status_history WHERE order_id = 5");
    $history = $stmt->fetchAll();
    echo "Number of history records for order 5: " . count($history) . "\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
