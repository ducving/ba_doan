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
        throw new Exception('Định dạng ảnh không hỗ trợ. Chỉ chấp nhận: ' . implode(', ', $allowedExt));
    }
    $safeName = 'avatar_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs = rtrim($uploadAbsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        throw new Exception('Không thể lưu ảnh upload');
    }
    return rtrim($uploadRelDir, '/') . '/' . $safeName;
}

// Kiểm tra authentication
$payload = JWT::verifyToken();

if (!$payload) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$userModel = new User();

switch ($method) {
    case 'GET':
        // 1. Trường hợp lấy thông tin chi tiết (nếu có id)
        if (isset($_GET['id'])) {
            // Chỉ admin mới được xem người khác, user thường chỉ được xem chính mình
            $targetId = (int)$_GET['id'];
            if ($payload['role'] !== 'admin' && $payload['user_id'] != $targetId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem thông tin này']);
                exit();
            }
            $user = $userModel->getUserById($targetId);
            if ($user) {
                echo json_encode(['success' => true, 'data' => $user]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            }
            exit();
        }

        // 2. Trường hợp lấy danh sách (Chỉ dành cho Admin)
        if ($payload['role'] !== 'admin') {
            // User thường thì trả về thông tin chính mình nếu không có ID
            $user = $userModel->getUserById($payload['user_id']);
            echo json_encode(['success' => true, 'data' => $user]);
            exit();
        }

        $role_filter = $_GET['role'] ?? 'user';
        if ($role_filter === 'all') {
            $users = $userModel->getAllUsers();
        } else {
            $users = $userModel->getUsersByRole($role_filter);
        }

        echo json_encode([
            'success' => true,
            'count' => count($users),
            'data' => $users
        ]);
        break;

    case 'POST':
        // PHP mặc định không xử lý multipart/form-data cho PUT, nên ta dùng POST + _method=PUT hoặc xử lý riêng
        if (isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
            $method = 'PUT';
        } else {
            $method = 'PUT';
        }
    case 'PUT':
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }

        $targetUserId = $payload['user_id'];
        if (isset($data['id']) && $payload['role'] === 'admin') {
            $targetUserId = (int)$data['id'];
        }

        $currentUser = $userModel->getUserById($targetUserId);
        if (!$currentUser) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
            exit();
        }

        if (stripos($contentType, 'multipart/form-data') !== false) {
            try {
                $avatarPath = saveUploadedImage($_FILES['avatar'] ?? null, $UPLOAD_ABS_DIR, $UPLOAD_REL_DIR);
                if ($avatarPath) {
                    $data['avatar'] = $avatarPath;
                    if (!empty($currentUser['avatar'])) {
                        $oldFileAbs = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentUser['avatar']);
                        if (file_exists($oldFileAbs) && is_file($oldFileAbs)) {
                            @unlink($oldFileAbs);
                        }
                    }
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
        }
        
        if (!$data && empty($_FILES)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit();
        }

        if (isset($data['name'])) $data['name'] = Security::sanitizeInput($data['name']);
        if (isset($data['phone'])) $data['phone'] = Security::sanitizeInput($data['phone']);
        if (isset($data['address'])) $data['address'] = Security::sanitizeInput($data['address']);
        
        if ($payload['role'] !== 'admin') {
            unset($data['role']);
            unset($data['status']);
        }

        $result = $userModel->update($targetUserId, $data);
        if ($result['success']) {
            $result['user'] = $userModel->getUserById($targetUserId);
        }
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa người dùng (Chỉ dành cho Admin)
        if ($payload['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Chỉ Admin mới có quyền xóa người dùng']);
            exit();
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
            exit();
        }

        $result = $userModel->delete($_GET['id']);
        echo json_encode($result);
        break;
}
?>
