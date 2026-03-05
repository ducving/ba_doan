<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Lấy method và xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$payment = new Payment();

// Xử lý các method
switch ($method) {
    case 'GET':
        // Authentication check (User xem giao dịch của mình, Admin xem tất cả)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
            exit();
        }

        if (isset($_GET['id'])) {
            // Xem chi tiết một giao dịch
            $id = (int)$_GET['id'];
            $result = $payment->getById($id);
            
            // Bảo mật: Nếu không phải admin, chỉ cho xem giao dịch của chính mình
            if ($result && $payload['role'] !== 'admin' && $result['user_id'] != $payload['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem giao dịch này']);
                exit();
            }

            if ($result) {
                http_response_code(200);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Giao dịch không tồn tại']);
            }
        } else {
            // Lấy danh sách giao dịch với filters
            $filters = [];
            
            // Nếu không phải admin, chỉ lấy giao dịch của chính mình
            if ($payload['role'] !== 'admin') {
                $filters['user_id'] = $payload['user_id'];
            } else if (isset($_GET['user_id'])) {
                $filters['user_id'] = (int)$_GET['user_id'];
            }
            
            if (isset($_GET['order_id'])) {
                $filters['order_id'] = (int)$_GET['order_id'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = Security::sanitizeInput($_GET['status']);
            }
            
            if (isset($_GET['page'])) $filters['page'] = (int)$_GET['page'];
            if (isset($_GET['limit'])) $filters['limit'] = (int)$_GET['limit'];

            $result = $payment->getAll($filters);
            echo json_encode($result);
        }
        break;

    case 'PUT':
        // Cập nhật trạng thái giao dịch (Chỉ dành cho Admin)
        $payload = JWT::verifyToken();
        if (!$payload || $payload['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID giao dịch']);
            exit();
        }

        $id = (int)$data['id'];
        unset($data['id']);

        $result = $payment->update($id, $data);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
