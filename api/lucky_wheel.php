<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/JWT.php';

JWT::setSecretKey(JWT_SECRET_KEY);

$method = $_SERVER['REQUEST_METHOD'];

// Kiểm tra user đăng nhập
$payload = JWT::verifyToken();
if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$userId = $payload['user_id'];

$database = new Database();
$db = $database->getConnection();

if ($method === 'GET') {
    // API Lấy danh sách phần thưởng cho Vòng Quay (Cấu hình 5 ô: 10k, 20k, 50k, 100k và Không trúng)
    try {
        $sectors = [
            ['id' => 'p_10k', 'label' => '10k', 'type' => 'point', 'value' => 10000, 'color' => '#fca5a5'],
            ['id' => 'p_20k', 'label' => '20k ', 'type' => 'point', 'value' => 20000, 'color' => '#86efac'],
            ['id' => 'unlucky', 'label' => 'Hụt rồi', 'type' => 'none', 'value' => 0, 'color' => '#d8b4fe'],
            ['id' => 'p_50k', 'label' => '50k ', 'type' => 'point', 'value' => 50000, 'color' => '#fde047'],
            ['id' => 'p_100k', 'label' => '100k ', 'type' => 'point', 'value' => 100000, 'color' => '#93c5fd'],
        ];

        echo json_encode([
            'success' => true,
            'data' => $sectors
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    // API Xử lý quay trúng thưởng (cộng điểm hoặc nhận voucher)
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if(!isset($data['sector'])) {
             echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
             exit();
        }
        $sector = $data['sector'];

        $db->beginTransaction();

        $message = "Bạn không trúng gì cả. Chúc bạn may mắn lần sau!";
        if ($sector['type'] === 'point' && $sector['value'] > 0) {
            // Scale điểm: 10.000đ = 1 điểm (Ví dụ 50k = 5 điểm)
            $pointsToEarn = (int)($sector['value'] / 10000);
            
            // Đảm bảo có bản ghi user_points
            $stmtInit = $db->prepare("INSERT IGNORE INTO user_points (user_id, points, total_earned, total_spent) VALUES (?,0,0,0)");
            $stmtInit->execute([$userId]);
            
            // Cộng điểm
            $stmtUpdate = $db->prepare("UPDATE user_points SET points = points + ?, total_earned = total_earned + ? WHERE user_id = ?");
            $stmtUpdate->execute([$pointsToEarn, $pointsToEarn, $userId]);
            
            // Ghi lịch sử
            $stmtLog = $db->prepare("INSERT INTO point_transactions (user_id, order_id, type, points, note) VALUES (?,NULL,?,?,?)");
            $stmtLog->execute([$userId, 'earn', $pointsToEarn, 'Trúng thưởng từ Vòng Quay (' . $sector['label'] . ')']);
            
            $message = "Chúc mừng! Bạn quay trúng +" . $pointsToEarn . " Điểm Halu (Tương đương " . number_format($sector['value']) . "đ).";
        } else if ($sector['type'] === 'voucher') {
            $voucherCode = $sector['value'];
            $message = "Chúc mừng! Bạn quay trúng Voucher giảm giá: " . $voucherCode;
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => $message,
            'prize' => $sector
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
