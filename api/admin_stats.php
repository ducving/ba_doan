<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Stats.php';
require_once __DIR__ . '/../classes/JWT.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Authentication check
$payload = JWT::verifyToken();
if (!$payload || $payload['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
    exit();
}

$stats = new Stats();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $stats->getDashboardStats();
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    echo json_encode($result);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
