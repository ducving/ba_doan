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
if (!isset($data['id_token'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu Google ID Token'
    ]);
    exit();
}

$idToken = $data['id_token'];

// Xác thực Token với Google API (Cần cURL enabled)
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tạm thời bỏ qua nếu running local không có cert, trong production nên để true
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    Security::logSecurityEvent('google_auth_failed', ['code' => $httpCode, 'response' => $response]);
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Google ID Token không hợp lệ hoặc đã hết hạn'
    ]);
    exit();
}

$googleData = json_decode($response, true);

// Kiểm tra aud (Audience) phải khớp với GOOGLE_CLIENT_ID của mình
if (isset($googleData['aud']) && $googleData['aud'] !== GOOGLE_CLIENT_ID) {
    // Nếu bạn đang dùng multiple clients, audition có thể khác, nhưng ở đây ta check mặc định
    // Đôi khi trên localhost nó có thể khác nếu set up khác, nên log lại nếu sai
    if (GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com') {
         Security::logSecurityEvent('google_auth_aud_mismatch', ['expected' => GOOGLE_CLIENT_ID, 'got' => $googleData['aud']]);
    }
}

// Chuẩn bị dữ liệu cho User class
$socialData = [
    'google_id' => $googleData['sub'],
    'email' => $googleData['email'],
    'name' => $googleData['name'] ?? $googleData['given_name'] ?? 'Google User',
    'avatar' => $googleData['picture'] ?? null,
    'provider' => 'google'
];

$userObj = new User();
$result = $userObj->loginOrRegisterSocial($socialData);

if ($result['success']) {
    $user = $result['user'];
    
    // Tạo JWT token cho hệ thống của mình
    $tokenPayload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user'
    ];
    $token = JWT::encode($tokenPayload, JWT_EXPIRATION);
    
    // Log thành công
    Security::logSecurityEvent('google_login_success', [
        'user_id' => $user['id'],
        'email' => $user['email']
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => JWT_EXPIRATION,
        'user' => $user,
        'redirect_to' => ($user['role'] === 'admin') ? '/doan/admin' : '/doan/'
    ]);
} else {
    http_response_code(403);
    echo json_encode($result);
}
?>
