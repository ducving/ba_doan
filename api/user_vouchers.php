<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../classes/Database.php';
include_once '../classes/UserVoucher.php';
include_once '../classes/Voucher.php';

$database = new Database();
$db = $database->getConnection();
$userVoucher = new UserVoucher($db);
$voucher = new Voucher($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy danh sách Voucher của một User
        if (isset($_GET['user_id'])) {
            $user_id = $_GET['user_id'];
            $is_used = isset($_GET['is_used']) ? $_GET['is_used'] : null; // Có thể truyền 0 để lấy mã chưa dùng, 1 để lấy mã đã dùng
            
            $stmt = $userVoucher->getUserVouchers($user_id, $is_used);
            $num = $stmt->rowCount();
            
            if ($num > 0) {
                $vouchers_arr = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($vouchers_arr, $row);
                }
                echo json_encode(["status" => "success", "data" => $vouchers_arr]);
            } else {
                echo json_encode(["status" => "success", "data" => []]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Thiếu user_id."]);
        }
        break;

    case 'POST':
        // Lưu Voucher cho User (Gọi API này khi User quay trúng Minigame)
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Cần user_id và (voucher_id hoặc code)
        if (!empty($data['user_id']) && (!empty($data['voucher_id']) || !empty($data['code']))) {
            $user_id = $data['user_id'];
            
            // Tìm voucher trong kho
            $v_data = null;
            if (!empty($data['voucher_id'])) {
                $v_data = $voucher->getById($data['voucher_id']);
            } else if (!empty($data['code'])) {
                $v_data = $voucher->getByCode($data['code']);
            }
            
            if (!$v_data) {
                echo json_encode(["status" => "error", "message" => "Voucher không tồn tại trong hệ thống."]);
                break;
            }
            
            $voucher_id = $v_data['id'];
            
            // Kiểm tra số lượng giới hạn của Voucher xem còn lượt không
            if ($v_data['usage_limit'] !== null && $v_data['used_count'] >= $v_data['usage_limit']) {
                echo json_encode(["status" => "error", "message" => "Rất tiếc, Voucher này đã được phát hết."]);
                break;
            }
            
            // Tiến hành lưu Voucher vào ví của User
            $id = $userVoucher->assignVoucher($user_id, $voucher_id);
            if ($id) {
                // Tăng số lượng đã dùng ở kho tổng lên 1
                $voucher->updateUsedCount($voucher_id);
                echo json_encode([
                    "status" => "success", 
                    "message" => "Lưu voucher vào ví thành công.", 
                    "user_voucher_id" => $id, 
                    "voucher" => $v_data
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Không thể lưu voucher vào ví."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Dữ liệu không đầy đủ (cần user_id và voucher_id/code)."]);
        }
        break;

    case 'PUT':
        // Cập nhật trạng thái Voucher thành "Đã sử dụng" (Gọi khi khách chốt đơn thanh toán)
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!empty($data['user_id']) && !empty($data['voucher_id'])) {
            if ($userVoucher->markAsUsed($data['user_id'], $data['voucher_id'])) {
                echo json_encode(["status" => "success", "message" => "Đã đánh dấu voucher là đã sử dụng."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Voucher không hợp lệ, không phải của bạn hoặc đã được sử dụng trước đó."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Dữ liệu không đầy đủ."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Phương thức không được hỗ trợ."]);
        break;
}
?>
