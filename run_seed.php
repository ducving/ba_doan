<?php 
include_once 'config/database.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = file_get_contents('database/seed_vouchers.sql');
if ($conn->multi_query($sql)) {
    echo 'Seed Vouchers successful';
} else {
    echo 'Error: ' . $conn->error;
}
$conn->close();
?>
