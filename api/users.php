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
        // ... (giữ nguyên logic GET)
        $userId = $payload['user_id'];
        
        if (isset($_GET['id']) && $payload['role'] === 'admin') {
            $userId = (int)$_GET['id'];
        }

        $user = $userModel->getUserById($userId);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Người dùng không tồn tại'
            ]);
        }
        break;

    case 'POST':
        // PHP mặc định không xử lý multipart/form-data cho PUT, nên ta dùng POST + _method=PUT hoặc xử lý riêng
        // Để đơn giản, cho phép dùng POST để update nếu có file upload
        if (isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
            $method = 'PUT';
        } else {
            // Nếu gửi POST bình thường tới endpoint update thì cũng coi như PUT
            $method = 'PUT';
        }
        // Tiếp tục xuống case PUT
    case 'PUT':
        // Cập nhật thông tin user
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST;
        } else {
            $data = json_decode(file_get_contents('php://input'), true);
        }

        // Xác định ID người dùng mục tiêu (admin có thể sửa người khác)
        $targetUserId = $payload['user_id'];
        if (isset($data['id']) && $payload['role'] === 'admin') {
            $targetUserId = (int)$data['id'];
        }

        // Lấy thông tin user hiện tại để kiểm tra ảnh cũ
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
                    
                    // Xóa ảnh cũ nếu có
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
            $msg = 'Dữ liệu không hợp lệ';
            if ($_SERVER['REQUEST_METHOD'] === 'PUT' && stripos($contentType, 'multipart/form-data') !== false) {
                $msg = 'PHP không hỗ trợ phương thức PUT để nhận file. Vui lòng chuyển sang phương thức POST để upload ảnh.';
            }
            echo json_encode([
                'success' => false,
                'message' => $msg
            ]);
            exit();
        }

        // Sanitize dữ liệu
        if (isset($data['name'])) $data['name'] = Security::sanitizeInput($data['name']);
        if (isset($data['phone'])) $data['phone'] = Security::sanitizeInput($data['phone']);
        if (isset($data['address'])) $data['address'] = Security::sanitizeInput($data['address']);
        
        // Chặn user thường đổi role hoặc status của chính mình
        if ($payload['role'] !== 'admin') {
            unset($data['role']);
            unset($data['status']);
        }

        $result = $userModel->update($targetUserId, $data);
        
        if ($result['success']) {
            // Lấy lại thông tin mới để trả về
            $updatedUser = $userModel->getUserById($targetUserId);
            $result['user'] = $updatedUser;
        }

        echo json_encode($result);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>
