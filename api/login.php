<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/JWT.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Lấy dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra dữ liệu đầu vào
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền email và mật khẩu'
    ]);
    exit();
}

$email = Security::validateEmail($data['email']);
$password = $data['password'];

// Validate email
if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email không hợp lệ'
    ]);
    exit();
}

// Validate password không rỗng
if (empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Mật khẩu không được để trống'
    ]);
    exit();
}

// Rate limiting - chống brute force
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitKey = $email . '_' . $ipAddress;
$rateLimitCheck = Security::checkRateLimit($rateLimitKey, 5, 300); // 5 lần trong 5 phút

if (!$rateLimitCheck['allowed']) {
    Security::logSecurityEvent('rate_limit_exceeded', [
        'email' => $email,
        'ip' => $ipAddress
    ]);
    
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => $rateLimitCheck['message']
    ]);
    exit();
}

// Đăng nhập
$user = new User();
$result = $user->login($email, $password);

if ($result['success']) {
    // Reset rate limit khi đăng nhập thành công
    Security::resetRateLimit($rateLimitKey);
    
    // Tạo JWT token
    $tokenPayload = [
        'user_id' => $result['user']['id'],
        'email' => $result['user']['email'],
        'role' => $result['user']['role'] ?? 'user'
    ];
    $token = JWT::encode($tokenPayload, JWT_EXPIRATION);
    
    // Log thành công
    Security::logSecurityEvent('login_success', [
        'user_id' => $result['user']['id'],
        'email' => $email
    ]);
    
    http_response_code(200);
    $result['token'] = $token;
    $result['token_type'] = 'Bearer';
    $result['expires_in'] = JWT_EXPIRATION;

    // Gợi ý redirect cho client (frontend) để tự điều hướng
    // Admin -> trang quản lý; User -> trang thường
    $role = $result['user']['role'] ?? 'user';
    $result['redirect_to'] = ($role === 'admin') ? '/doan/admin' : '/doan/';
} else {
    // Log thất bại
    Security::logSecurityEvent('login_failed', [
        'email' => $email,
        'ip' => $ipAddress,
        'reason' => $result['message']
    ]);
    
    http_response_code(401);
}

echo json_encode($result);
?>
