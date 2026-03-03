<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Lấy method và xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$order = new Order();

// Xử lý các method
switch ($method) {
    case 'GET':
        // Authentication check (Tuỳ vào business: User xem đơn của mình, Admin xem tất cả)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
            exit();
        }

        if (isset($_GET['id'])) {
            // Xem chi tiết một đơn hàng
            $id = (int)$_GET['id'];
            $result = $order->getById($id);
            
            // Bảo mật: Nếu không phải admin, chỉ cho xem đơn hàng của chính mình
            if ($result && $payload['role'] !== 'admin' && $result['user_id'] != $payload['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem đơn hàng này']);
                exit();
            }

            if ($result) {
                http_response_code(200);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại']);
            }
        } else {
            // Lấy danh sách đơn hàng với filters
            $filters = [];
            
            // Nếu không phải admin, chỉ lấy đơn của chính mình
            if ($payload['role'] !== 'admin') {
                $filters['user_id'] = $payload['user_id'];
            } else if (isset($_GET['user_id'])) {
                $filters['user_id'] = (int)$_GET['user_id'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = Security::sanitizeInput($_GET['status']);
            }
            
            if (isset($_GET['payment_status'])) {
                $filters['payment_status'] = Security::sanitizeInput($_GET['payment_status']);
            }
            
            if (isset($_GET['search'])) {
                $filters['search'] = Security::sanitizeInput($_GET['search']);
            }
            
            if (isset($_GET['page'])) {
                $filters['page'] = (int)$_GET['page'];
            }
            
            if (isset($_GET['limit'])) {
                $filters['limit'] = (int)$_GET['limit'];
            }
            
            $result = $order->getAll($filters);
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Tạo đơn hàng mới
        // Cho phép cả khách vãng lai (guest) và user đã login
        $payload = JWT::verifyToken();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit();
        }

        // Nếu đã login, gắn user_id vào đơn hàng
        if ($payload) {
            $data['user_id'] = $payload['user_id'];
        }

        // Sanitize
        $data['full_name'] = Security::sanitizeInput($data['full_name'] ?? '');
        $data['email'] = Security::sanitizeInput($data['email'] ?? '');
        $data['phone'] = Security::sanitizeInput($data['phone'] ?? '');
        $data['address'] = Security::sanitizeInput($data['address'] ?? '');
        $data['note'] = Security::sanitizeInput($data['note'] ?? '');

        $result = $order->create($data);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'PUT':
        // Cập nhật đơn hàng (Chủ yếu dành cho Admin cập nhật trạng thái)
        $payload = JWT::verifyToken();
        if (!$payload || $payload['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng']);
            exit();
        }

        $id = (int)$data['id'];
        unset($data['id']);

        $result = $order->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa đơn hàng (Chỉ dành cho Admin)
        $payload = JWT::verifyToken();
        if (!$payload || $payload['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
            exit();
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID đơn hàng']);
            exit();
        }

        $id = (int)$_GET['id'];
        $result = $order->delete($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
