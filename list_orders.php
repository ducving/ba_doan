<?php
require_once __DIR__ . '/classes/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    echo "Listing last 5 orders:\n";
    $stmt = $db->query("SELECT id, full_name, created_at FROM orders ORDER BY id DESC LIMIT 5");
    $orders = $stmt->fetchAll();
    foreach ($orders as $o) {
        echo "ID: {$o['id']}, Name: {$o['full_name']}, Created: {$o['created_at']}\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
