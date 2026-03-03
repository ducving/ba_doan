<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/News.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Header chuẩn cho API
header('Content-Type: application/json');

// Upload config
$UPLOAD_REL_DIR = 'uploads/news';
$UPLOAD_ABS_DIR = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'news';

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
        throw new Exception('Định dạng ảnh không hỗ trợ: ' . ($file['name'] ?? 'Không rõ') . ' (Chỉ chấp nhận: jpg, jpeg, png, webp, gif)');
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
$newsModel = new News();

switch ($method) {
    case 'GET':
        // Lấy danh sách tin tức (Khách xem được, không cần token)
        $filters = [];
        if (isset($_GET['status'])) {
            $filters['status'] = Security::sanitizeInput($_GET['status']);
        } else {
            // Mặc định khách xem chỉ thấy tin tức active
            $filters['status'] = 'active';
        }
        
        // Nếu là admin đang quản lý tin tức thì có thể xem tất cả
        $payload = JWT::verifyToken();
        if ($payload && isset($payload['role']) && $payload['role'] === 'admin' && isset($_GET['all'])) {
            unset($filters['status']);
        }

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $result = $newsModel->getById($id);
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tin tức không tồn tại']);
            }
        } else {
            $result = $newsModel->getAll($filters);
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Thêm tin tức (Yêu cầu Admin)
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
        $data['content'] = $data['content'] ?? ''; // Không sanitize quá gắt cho content vì có thể có HTML
        
        $result = $newsModel->create($data);
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        echo json_encode($result);
        break;

    case 'PUT':
        // Cập nhật tin tức (Yêu cầu Admin)
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
            echo json_encode(['success' => false, 'message' => 'Thiếu ID tin tức']);
            exit();
        }

        $id = (int)$data['id'];
        unset($data['id']);

        if (isset($data['title'])) {
            $data['title'] = Security::sanitizeInput($data['title'] ?? '');
        }

        $result = $newsModel->update($id, $data);
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa tin tức (Yêu cầu Admin)
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
            echo json_encode(['success' => false, 'message' => 'Thiếu ID tin tức']);
            exit();
        }

        $id = (int)$_GET['id'];
        $result = $newsModel->delete($id);
        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
