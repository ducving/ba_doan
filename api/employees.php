<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Cấu hình header
header('Content-Type: application/json');

// Khởi tạo và kiểm tra Token
JWT::setSecretKey(JWT_SECRET_KEY);
$decoded = JWT::verifyToken();

if (!$decoded) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Chỉ cho phép Admin quản lý nhân viên
if (($decoded['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Admin access required']);
    exit();
}

$employee = new Employee();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Lấy 1 nhân viên
            $result = $employee->getById($_GET['id']);
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên']);
            }
        } else {
            // Lấy danh sách
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $data = $employee->getAll($limit, $offset);
            $total = $employee->count();
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['employee_code']) || !isset($data['full_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc (employee_code, full_name)']);
            break;
        }
        $result = $employee->create($data);
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(500);
        }
        echo json_encode($result);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID nhân viên']);
            break;
        }
        $result = $employee->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID nhân viên']);
            break;
        }
        $result = $employee->delete($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
