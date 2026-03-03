<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Category.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Upload config
$UPLOAD_REL_DIR = 'uploads/categories';
$UPLOAD_ABS_DIR = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'categories';

if (!is_dir($UPLOAD_ABS_DIR)) {
    @mkdir($UPLOAD_ABS_DIR, 0755, true);
}

function saveUploadedImage($file, $uploadAbsDir, $uploadRelDir) {
    if (!isset($file) || !is_array($file)) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new Exception('Upload ảnh thất bại (error code: ' . ($file['error'] ?? 'unknown') . ')');
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if (!empty($file['size']) && $file['size'] > $maxBytes) {
        throw new Exception('Ảnh quá lớn (tối đa 5MB)');
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowedExt, true)) {
        throw new Exception('Định dạng ảnh không hỗ trợ (chỉ: jpg, jpeg, png, webp, gif)');
    }

    // Kiểm tra MIME
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (function_exists('finfo_open') && !empty($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if ($mime && !in_array($mime, $allowedMime, true)) {
                throw new Exception('File upload không phải ảnh hợp lệ');
            }
        }
    }

    $safeName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs = rtrim($uploadAbsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('File upload không hợp lệ');
    }

    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        throw new Exception('Không thể lưu ảnh upload');
    }

    return rtrim($uploadRelDir, '/') . '/' . $safeName;
}

// Lấy method và xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$category = new Category();

// Xử lý các method
switch ($method) {
    case 'GET':
        // Lấy danh sách hoặc chi tiết danh mục
        if (isset($_GET['id'])) {
            // Lấy danh mục theo ID
            $id = (int)$_GET['id'];
            $result = $category->getById($id);
            
            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ]);
            }
        } elseif (isset($_GET['slug'])) {
            // Lấy danh mục theo slug
            $slug = Security::sanitizeInput($_GET['slug']);
            $result = $category->getBySlug($slug);
            
            if ($result) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Danh mục không tồn tại'
                ]);
            }
        } else {
            // Lấy tất cả danh mục
            $status = $_GET['status'] ?? null;
            $result = $category->getAll($status);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'count' => count($result)
            ]);
        }
        break;

    case 'POST':
        // Tạo danh mục mới (yêu cầu authentication)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ]);
            exit();
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $isMultipart = stripos($contentType, 'multipart/form-data') !== false;
        $isJson = stripos($contentType, 'application/json') !== false;

        if ($isMultipart) {
            $data = $_POST ?? [];
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
            echo json_encode([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ'
            ]);
            exit();
        }

        // Sanitize input
        if (isset($data['name'])) {
            $data['name'] = Security::sanitizeInput($data['name']);
        }
        if (isset($data['slug'])) {
            $data['slug'] = Security::sanitizeInput($data['slug']);
        }
        if (isset($data['description'])) {
            $data['description'] = Security::sanitizeInput($data['description']);
        }
        if (isset($data['image'])) {
            // Không sanitize image để hỗ trợ Base64 và path
            $data['image'] = $data['image'];
        }

        $result = $category->create($data);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'PUT':
        // Cập nhật danh mục (yêu cầu authentication)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ]);
            exit();
        }

        $data = json_encode([]); // Default empty
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // Fallback to JSON
            $data = json_decode(file_get_contents('php://input'), true);
        }
        
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu ID danh mục'
            ]);
            exit();
        }

        $id = (int)$data['id'];
        unset($data['id']); // Xóa id khỏi data để không update id

        // Sanitize input
        if (isset($data['name'])) {
            $data['name'] = Security::sanitizeInput($data['name']);
        }
        if (isset($data['slug'])) {
            $data['slug'] = Security::sanitizeInput($data['slug']);
        }
        if (isset($data['description'])) {
            $data['description'] = Security::sanitizeInput($data['description']);
        }
        if (isset($data['image'])) {
            $data['image'] = $data['image'];
        }

        $result = $category->update($id, $data);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa danh mục (yêu cầu authentication)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ]);
            exit();
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu ID danh mục'
            ]);
            exit();
        }

        $id = (int)$_GET['id'];
        $result = $category->delete($id);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
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
