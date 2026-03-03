<?php
/**
 * File test đơn giản để kiểm tra API có hoạt động không
 * Truy cập: http://localhost/doan/test_api.php
 */

echo "<h2>Test API Connection</h2>";

// Test 1: Kiểm tra file tồn tại
echo "<h3>1. Kiểm tra files:</h3>";
$files = [
    'api/register.php',
    'api/login.php',
    'api/verify_token.php',
    'classes/User.php',
    'classes/Database.php',
    'config/database.php',
    'config/config.php'
];

foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $status = $exists ? '✅' : '❌';
    echo "$status $file<br>";
}

// Test 2: Kiểm tra database config
echo "<h3>2. Kiểm tra Database Config:</h3>";
if (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
    echo "✅ DB_HOST: " . DB_HOST . "<br>";
    echo "✅ DB_USER: " . DB_USER . "<br>";
    echo "✅ DB_NAME: " . DB_NAME . "<br>";
} else {
    echo "❌ Không tìm thấy config/database.php<br>";
}

// Test 3: Test kết nối database
echo "<h3>3. Test kết nối Database:</h3>";
try {
    require_once __DIR__ . '/classes/Database.php';
    $db = new Database();
    echo "✅ Kết nối database thành công!<br>";
} catch (Exception $e) {
    echo "❌ Lỗi kết nối: " . $e->getMessage() . "<br>";
}

// Test 4: URLs để test
echo "<h3>4. URLs để test trên Postman:</h3>";
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
echo "Base URL: <strong>$baseUrl</strong><br><br>";
echo "Register: <code>POST $baseUrl/api/register.php</code><br>";
echo "Login: <code>POST $baseUrl/api/login.php</code><br>";
echo "Verify Token: <code>GET $baseUrl/api/verify_token.php</code><br>";

echo "<hr>";
echo "<p><strong>Nếu thấy tất cả ✅, API đã sẵn sàng!</strong></p>";
?>
