<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/JWT.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Upload config cho avatar
$UPLOAD_REL_DIR = 'uploads/avatars';
$UPLOAD_ABS_DIR = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';

if (!is_dir($UPLOAD_ABS_DIR)) {
    @mkdir($UPLOAD_ABS_DIR, 0755, true);
}

function saveUploadedImage($file, $uploadAbsDir, $uploadRelDir) {
    if (!isset($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Upload ảnh thất bại');
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowedExt)) {
        throw new Exception('Định dạng ảnh không hỗ trợ');
    }
    $safeName = 'avatar_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs = rtrim($uploadAbsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        throw new Exception('Không thể lưu ảnh upload');
    }
    return rtrim($uploadRelDir, '/') . '/' . $safeName;
}

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
$avatarPath = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
    try {
        $avatarPath = saveUploadedImage($_FILES['avatar'] ?? null, $UPLOAD_ABS_DIR, $UPLOAD_REL_DIR);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    $data = json_decode(file_get_contents('php://input'), true);
}

// Kiểm tra dữ liệu đầu vào
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng điền đầy đủ thông tin (name, email, password)'
    ]);
    exit();
}

// Sanitize và validate input
$name = Security::sanitizeInput($data['name']);
$email = Security::validateEmail($data['email']);
$password = $data['password'];

// Validate name
if (empty($name) || strlen($name) < 2) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tên phải có ít nhất 2 ký tự'
    ]);
    exit();
}

if (strlen($name) > 100) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tên không được vượt quá 100 ký tự'
    ]);
    exit();
}

// Validate email
if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email không hợp lệ'
    ]);
    exit();
}

// Validate password strength
$passwordValidation = Security::validatePassword($password);
if (!$passwordValidation['valid']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $passwordValidation['message']
    ]);
    exit();
}

// Rate limiting cho đăng ký (chống spam)
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitCheck = Security::checkRateLimit('register_' . $ipAddress, 3, 3600); // 3 lần trong 1 giờ

if (!$rateLimitCheck['allowed']) {
    Security::logSecurityEvent('register_rate_limit', [
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

// Đăng ký user
$user = new User();
$role = $data['role'] ?? 'user';
$status = $data['status'] ?? 'active';
$result = $user->register($name, $email, $password, $avatarPath, $role, $status);

if ($result['success']) {
    // Reset rate limit khi đăng ký thành công
    Security::resetRateLimit('register_' . $ipAddress);
    
    // Tạo JWT token tự động sau khi đăng ký
    $tokenPayload = [
        'user_id' => $result['user_id'],
        'email' => $email
    ];
    $token = JWT::encode($tokenPayload, JWT_EXPIRATION);
    
    // Log thành công
    Security::logSecurityEvent('register_success', [
        'user_id' => $result['user_id'],
        'email' => $email
    ]);
    
    http_response_code(201);
    $result['token'] = $token;
    $result['token_type'] = 'Bearer';
    $result['expires_in'] = JWT_EXPIRATION;
} else {
    // Log thất bại
    Security::logSecurityEvent('register_failed', [
        'email' => $email,
        'ip' => $ipAddress,
        'reason' => $result['message']
    ]);
    
    http_response_code(400);
}

echo json_encode($result);
?>
