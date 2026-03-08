<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/JWT.php';

JWT::setSecretKey(JWT_SECRET_KEY);
header('Content-Type: application/json');

$method  = $_SERVER['REQUEST_METHOD'];
$payload = JWT::verifyToken();

if (!$payload) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db       = $database->getConnection();
$userId   = $payload['user_id'];

// ─── Tỉ lệ tích điểm ────────────────────────────────────────────────────────
// 10.000đ chi tiêu = 1 điểm
// 1 điểm = 1.000đ giảm giá
define('EARN_RATE',   10000); // số tiền để được 1 điểm
define('REDEEM_RATE',  1000); // 1 điểm = số tiền giảm

// ─── Helper: lấy/tạo bản ghi điểm ─────────────────────────────────────────
function getUserPoints(PDO $db, int $userId): array {
    $stmt = $db->prepare("SELECT * FROM user_points WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $db->prepare("INSERT INTO user_points (user_id, points, total_earned, total_spent) VALUES (?,0,0,0)")->execute([$userId]);
        return ['points' => 0, 'total_earned' => 0, 'total_spent' => 0];
    }
    return $row;
}

// ─── GET: lấy điểm + lịch sử ───────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $points = getUserPoints($db, $userId);

        // Lịch sử giao dịch 20 gần nhất
        $stmt = $db->prepare("
            SELECT pt.*, o.total_amount
            FROM point_transactions pt
            LEFT JOIN orders o ON pt.order_id = o.id
            WHERE pt.user_id = ?
            ORDER BY pt.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hạng thành viên
        $total = (int)$points['total_earned'];
        if ($total >= 1000)     $rank = ['name' => 'Kim cương', 'color' => '#a5f3fc', 'min' => 1000];
        elseif ($total >= 500)  $rank = ['name' => 'Vàng',      'color' => '#fbbf24', 'min' => 500];
        elseif ($total >= 100)  $rank = ['name' => 'Bạc',       'color' => '#94a3b8', 'min' => 100];
        else                    $rank = ['name' => 'Đồng',      'color' => '#cd7f32', 'min' => 0];

        echo json_encode([
            'success'      => true,
            'points'       => (int)$points['points'],
            'total_earned' => (int)$points['total_earned'],
            'total_spent'  => (int)$points['total_spent'],
            'rank'         => $rank,
            'earn_rate'    => EARN_RATE,
            'redeem_rate'  => REDEEM_RATE,
            'history'      => $history,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

// ─── POST: tính số điểm sẽ nhận / validate khi dùng điểm ─────────────────
} elseif ($method === 'POST') {
    try {
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if ($action === 'calculate') {
            // Tính điểm kiếm được từ tổng tiền
            $amount    = (float)($data['amount']     ?? 0);
            $usePoints = (int)($data['use_points']   ?? 0);

            $currentPoints = getUserPoints($db, $userId)['points'];

            // Kiểm tra không dùng quá số điểm có
            $usePoints = min($usePoints, $currentPoints);
            // Không giảm quá tổng tiền
            $discount  = $usePoints * REDEEM_RATE;
            $discount  = min($discount, $amount);
            $finalAmt  = $amount - $discount;
            $earnedPts = floor($finalAmt / EARN_RATE);

            echo json_encode([
                'success'        => true,
                'current_points' => $currentPoints,
                'use_points'     => $usePoints,
                'discount'       => $discount,
                'final_amount'   => $finalAmt,
                'will_earn'      => $earnedPts,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
