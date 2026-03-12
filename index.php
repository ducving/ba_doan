<?php
require_once __DIR__ . '/config/config.php';

// Router đơn giản
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Loại bỏ query string
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/doan', '', $path); // Loại bỏ base path nếu có
$path = trim($path, '/');

// Routing
switch ($path) {
    case '':
    case 'index.php':
        echo json_encode([
            'success' => true,
            'message' => 'PHP API eCommerce System',
            'endpoints' => [
                'POST /api/register' => 'Đăng ký tài khoản mới',
                'POST /api/login' => 'Đăng nhập',
                'GET/POST/PUT/DELETE /api/categories' => 'Quản lý danh mục',
                'GET/POST/PUT/DELETE /api/products' => 'Quản lý sản phẩm',
                'GET/POST/PUT/DELETE /api/orders' => 'Quản lý đơn hàng',
                'GET/POST/PUT/DELETE /api/banners' => 'Quản lý banner',
                'GET/PUT /api/users' => 'Xem và cập nhật thông tin cá nhân',
                'GET/POST/PUT/DELETE /api/employees' => 'Quản lý nhân viên',
                'GET /api/get_users' => 'Lấy danh sách tất cả user (Admin)'
            ]
        ]);
        break;
    
    case 'api/register':
    case 'api/register.php':
        require_once __DIR__ . '/api/register.php';
        break;
    
    case 'api/login':
    case 'api/login.php':
        require_once __DIR__ . '/api/login.php';
        break;

    case 'api/categories':
    case 'api/categories.php':
        require_once __DIR__ . '/api/categories.php';
        break;

    case 'api/products':
    case 'api/products.php':
        require_once __DIR__ . '/api/products.php';
        break;

    case 'api/orders':
    case 'api/orders.php':
        require_once __DIR__ . '/api/orders.php';
        break;

    case 'api/banners':
    case 'api/banners.php':
        require_once __DIR__ . '/api/banners.php';
        break;

    case 'api/users':
    case 'api/users.php':
    case 'api/profile_update.php':
        require_once __DIR__ . '/api/users.php';
        break;

    case 'api/employees':
    case 'api/employees.php':
        require_once __DIR__ . '/api/employees.php';
        break;

    case 'api/get_users':
    case 'api/get_users.php':
        require_once __DIR__ . '/api/get_users.php';
        break;

    case 'api/attendance':
    case 'api/attendance.php':
        require_once __DIR__ . '/api/attendance.php';
        break;

    case 'api/points.php':
        require_once __DIR__ . '/api/points.php';
        break;

    case 'api/lucky_wheel':
    case 'api/lucky_wheel.php':
    case 'lucky_wheel.php':
    case 'lucky_wheel':
        require_once __DIR__ . '/api/lucky_wheel.php';
        break;
    
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint không tồn tại'
        ]);
        break;
}
?>
