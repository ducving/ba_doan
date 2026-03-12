<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/database.php';
include_once '../classes/Database.php';
include_once '../classes/Voucher.php';

$database = new Database();
$db = $database->getConnection();
$voucher = new Voucher($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
            $result = $voucher->getByCode($code);
            if ($result) {
                // Check valid
                $now = date('Y-m-d H:i:s');
                if ($result['status'] !== 'active') {
                    echo json_encode(["status" => "error", "message" => "Voucher không hoạt động."]);
                } elseif ($result['start_date'] && $result['start_date'] > $now) {
                    echo json_encode(["status" => "error", "message" => "Voucher chưa tới thời gian áp dụng."]);
                } elseif ($result['end_date'] && $result['end_date'] < $now) {
                    echo json_encode(["status" => "error", "message" => "Voucher đã hết hạn."]);
                } elseif ($result['usage_limit'] !== null && $result['used_count'] >= $result['usage_limit']) {
                    echo json_encode(["status" => "error", "message" => "Voucher đã hết lượt sử dụng."]);
                } else {
                    echo json_encode(["status" => "success", "data" => $result]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Voucher không tồn tại."]);
            }
        } elseif (isset($_GET['id'])) {
            $result = $voucher->getById($_GET['id']);
            if ($result) {
                echo json_encode(["status" => "success", "data" => $result]);
            } else {
                echo json_encode(["status" => "error", "message" => "Voucher không tồn tại."]);
            }
        } else {
            $stmt = $voucher->getAll();
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
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (
            !empty($data['code']) &&
            isset($data['discount_amount'])
        ) {
            $id = $voucher->create($data);
            if ($id) {
                echo json_encode(["status" => "success", "message" => "Tạo voucher thành công.", "id" => $id]);
            } else {
                echo json_encode(["status" => "error", "message" => "Không thể tạo voucher. (Code đã tồn tại hoặc lỗi SQL)"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Dữ liệu không đầy đủ (cần code và discount_amount)."]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($_GET['id'])) {
            if ($voucher->update($_GET['id'], $data)) {
                echo json_encode(["status" => "success", "message" => "Cập nhật voucher thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Không thể cập nhật voucher."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Thiếu ID voucher."]);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            if ($voucher->delete($_GET['id'])) {
                echo json_encode(["status" => "success", "message" => "Xóa voucher thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Không thể xóa voucher."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Thiếu ID voucher."]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Phương thức không được hỗ trợ."]);
        break;
}
?>
