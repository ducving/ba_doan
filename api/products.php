<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

// Khởi tạo JWT
JWT::setSecretKey(JWT_SECRET_KEY);

// Upload config
$UPLOAD_REL_DIR = 'uploads/products';
$UPLOAD_ABS_DIR = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';

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

    // Kiểm tra MIME (best-effort)
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

function normalizeMultiFiles($filesField) {
    // Chuyển $_FILES['images'] (multi) thành mảng các file item chuẩn
    $out = [];
    if (!is_array($filesField) || !isset($filesField['name']) || !is_array($filesField['name'])) {
        return $out;
    }

    $count = count($filesField['name']);
    for ($i = 0; $i < $count; $i++) {
        $out[] = [
            'name' => $filesField['name'][$i] ?? null,
            'type' => $filesField['type'][$i] ?? null,
            'tmp_name' => $filesField['tmp_name'][$i] ?? null,
            'error' => $filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $filesField['size'][$i] ?? 0,
        ];
    }

    return $out;
}

// Lấy method và xử lý request
$method = $_SERVER['REQUEST_METHOD'];
$product = new Product();

// Xử lý các method
switch ($method) {
    case 'GET':
        // Lấy danh sách hoặc chi tiết sản phẩm
        if (isset($_GET['id'])) {
            // Lấy sản phẩm theo ID
            $id = (int)$_GET['id'];
            $result = $product->getById($id);
            
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
                    'message' => 'Sản phẩm không tồn tại'
                ]);
            }
        } elseif (isset($_GET['slug'])) {
            // Lấy sản phẩm theo slug
            $slug = Security::sanitizeInput($_GET['slug']);
            $result = $product->getBySlug($slug);
            
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
                    'message' => 'Sản phẩm không tồn tại'
                ]);
            }
        } else {
            // Lấy danh sách sản phẩm với filters
            $filters = [];
            
            if (isset($_GET['category_id'])) {
                $filters['category_id'] = (int)$_GET['category_id'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = Security::sanitizeInput($_GET['status']);
            }
            
            if (isset($_GET['featured'])) {
                $filters['featured'] = (int)$_GET['featured'];
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
            
            $result = $product->getAll($filters);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result['products'],
                'pagination' => $result['pagination']
            ]);
        }
        break;

    case 'POST':
        // Tạo sản phẩm mới (yêu cầu authentication)
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

            // Upload ảnh (ảnh chính: image; nhiều ảnh: images[])
            try {
                $uploadedMain = saveUploadedImage($_FILES['image'] ?? null, $UPLOAD_ABS_DIR, $UPLOAD_REL_DIR);
                if ($uploadedMain) {
                    $data['image'] = $uploadedMain;
                }

                $extraImages = [];
                foreach (normalizeMultiFiles($_FILES['images'] ?? null) as $fileItem) {
                    $uploaded = saveUploadedImage($fileItem, $UPLOAD_ABS_DIR, $UPLOAD_REL_DIR);
                    if ($uploaded) {
                        $extraImages[] = $uploaded;
                    }
                }
                if (!empty($extraImages)) {
                    $data['images'] = $extraImages;
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit();
            }
        } elseif ($isJson) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // fallback: cố gắng parse json
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
        if (isset($data['short_description'])) {
            $data['short_description'] = Security::sanitizeInput($data['short_description']);
        }
        if (isset($data['sku'])) {
            $data['sku'] = Security::sanitizeInput($data['sku']);
        }
        if (isset($data['image'])) {
            // Không sanitize image vì nó là Base64 hoặc path ảnh
            $data['image'] = $data['image']; 
        }

        $result = $product->create($data);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'PUT':
        // Cập nhật sản phẩm (yêu cầu authentication)
        $payload = JWT::verifyToken();
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ]);
            exit();
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu ID sản phẩm'
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
        if (isset($data['short_description'])) {
            $data['short_description'] = Security::sanitizeInput($data['short_description']);
        }
        if (isset($data['sku'])) {
            $data['sku'] = Security::sanitizeInput($data['sku']);
        }
        if (isset($data['image'])) {
            $data['image'] = Security::sanitizeInput($data['image']);
        }

        $result = $product->update($id, $data);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
        break;

    case 'DELETE':
        // Xóa sản phẩm (yêu cầu authentication)
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
                'message' => 'Thiếu ID sản phẩm'
            ]);
            exit();
        }

        $id = (int)$_GET['id'];
        $result = $product->delete($id);
        
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
