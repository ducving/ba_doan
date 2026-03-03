<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/JWT.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Chỉ cho phép GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Verify token
$payload = JWT::verifyToken();

if ($payload) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Token hợp lệ',
        'user' => [
            'user_id' => $payload['user_id'],
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? 'user'
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token không hợp lệ hoặc đã hết hạn'
    ]);
}
?>
