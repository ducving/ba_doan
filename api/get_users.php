<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

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

// Kiểm tra token (Authentication)
$decoded = JWT::verifyToken();
if (!$decoded) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn cần đăng nhập để thực hiện chức năng này'
    ]);
    exit();
}

// Kiểm tra quyền (Authorization) - Chỉ cho phép admin lấy danh sách user
if (($decoded['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập danh sách người dùng'
    ]);
    exit();
}

// Lấy tham số phân trang
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Khởi tạo User class và lấy dữ liệu
$userObj = new User();
$users = $userObj->getAllUsers($limit, $offset);
$total = $userObj->getTotalUsers();

http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => $users,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>
