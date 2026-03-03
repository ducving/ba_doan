<?php
// Cấu hình chung
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // HTTPS only (uncomment khi dùng HTTPS)
header('Referrer-Policy: strict-origin-when-cross-origin');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (tắt trong production)
// Trong production, đặt display_errors = 0 và log_errors = 1
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi ra màn hình
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/php_errors.log');

// JWT Secret Key (nên thay đổi trong production)
define('JWT_SECRET_KEY', 'your-super-secret-key-change-this-in-production-' . date('Y'));
define('JWT_EXPIRATION', 3600 * 24); // 24 giờ
?>
