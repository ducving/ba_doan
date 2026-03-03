<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/banner.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Upload config
$UPLOAD_REL_DIR = 'uploads/banners';
$UPLOAD_ABS_DIR = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'banners';

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

    $safeName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs = rtrim($uploadAbsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        throw new Exception('Không thể lưu ảnh upload');
    }

    return rtrim($uploadRelDir, '/') . '/' . $safeName;
}

// Lấy method và xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$banner = new Banner();

switch ($method) {
    case 'GET':
        // Lấy danh sách banner (Khách xem được, không cần token)
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = Security::sanitizeInput($_GET['status']);
        } else {
            // Mặc định khách xem chỉ thấy banner active
            $filters['status'] = 'active';
        }
        
        // Nếu là admin đang quản lý banner thì có thể muốn xem tất cả
        $payload = JWT::verifyToken();
        if ($payload && isset($payload['role']) && $payload['role'] === 'admin' && isset($_GET['all'])) {
            unset($filters['status']);
        }

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $result = $banner->getById($id);
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Banner không tồn tại']);
            }
        } else {
            $result = $banner->getAll($filters);
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Thêm banner (Yêu cầu Admin)
        $payload = JWT::verifyToken();
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện thao tác này']);
            exit();
        }

        if (($payload['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền Admin']);
            exit();
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
            try {
                $uploaded = saveUploadedImage($_FILES['image'] ?? null, $UPLOAD_ABS_DIR, $UPLOAD_REL_DIR);
                if ($uploaded) {
                    $data['image'] = $uploaded;
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }

        if (!$data || !is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit();
        }

        $data['title'] = Security::sanitizeInput($data['title'] ?? '');
        $data['link'] = Security::sanitizeInput($data['link'] ?? '');
        
        $result = $banner->create($data);
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        echo json_encode($result);
        break;

    case 'PUT':
        // Cập nhật banner (Yêu cầu Admin)
        $payload = JWT::verifyToken();
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit();
        }

        if (($payload['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền Admin']);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID banner']);
            exit();
        }

        $id = (int)$data['id'];
        unset($data['id']);

        $data['title'] = Security::sanitizeInput($data['title'] ?? '');
        $data['link'] = Security::sanitizeInput($data['link'] ?? '');

        $result = $banner->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa banner (Yêu cầu Admin)
        $payload = JWT::verifyToken();
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit();
        }

        if (($payload['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền Admin']);
            exit();
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID banner']);
            exit();
        }

        $id = (int)$_GET['id'];
        $result = $banner->delete($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
