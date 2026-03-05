<?php
/**
 * API Chấm công (Attendance API)
 * Phương thức: POST (check-in/out), GET (xem lịch sử)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Attendance.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/JWT.php';
require_once __DIR__ . '/../classes/Security.php';

header('Content-Type: application/json');

// 1. Khởi tạo Token (nếu có)
JWT::setSecretKey(JWT_SECRET_KEY);
$decoded = JWT::verifyToken();

$employeeObj = new Employee();
$emp = null;

// 2. Tìm ID nhân viên dựa trên Token hoặc Mã nhân viên
if ($decoded) {
    $emp = $employeeObj->getByUserId($decoded['user_id']);
}

if (!$emp) {
    // Nếu không có token, thử lấy theo mã nhân viên từ request (cho trang standalone)
    $data = json_decode(file_get_contents('php://input'), true);
    $code = $data['employee_code'] ?? $_GET['employee_code'] ?? null;
    
    if ($code) {
        $emp = $employeeObj->getByCode($code);
    }
}

if (!$emp) {
    // Chỉ trả lỗi nếu đang thực hiện hành động (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp mã nhân viên hoặc đăng nhập']);
        exit();
    }
    // Nếu chỉ là GET để xem trạng thái mà không có mã, trả về rỗng
    echo json_encode(['success' => true, 'data' => null]);
    exit();
}

$employeeId = $emp['id'];
$attendanceObj = new Attendance();
$method = $_SERVER['REQUEST_METHOD'];

// 3. Xử lý logic API
switch ($method) {
    case 'POST':
        // Lưu ý: JSON Body {"action": "check_in" hoặc "check_out"}
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'check_in';
        
        if ($action === 'check_in') {
            $result = $attendanceObj->checkIn($employeeId, $data['note'] ?? null);
            echo json_encode($result);
        } elseif ($action === 'check_out') {
            $result = $attendanceObj->checkOut($employeeId);
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ (Yêu cầu check_in hoặc check_out)']);
        }
        break;

    case 'GET':
        $role = $decoded['role'] ?? 'user';
        
        // 1. Trường hợp theo Mã nhân viên (Standalone hoặc Admin view)
        if (isset($_GET['employee_code'])) {
            if (isset($_GET['type']) && $_GET['type'] === 'history') {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
                $startDate = $_GET['start_date'] ?? null;
                $endDate = $_GET['end_date'] ?? null;
                
                $data = $attendanceObj->getHistory($employeeId, $limit, $startDate, $endDate);
            } else {
                $data = $attendanceObj->getTodayStatus($employeeId);
            }
            echo json_encode([
                'success' => true,
                'data' => $data,
                'employee' => $emp
            ]);
        } 
        // 2. Chế độ Admin xem theo ngày
        else if ($role === 'admin' && isset($_GET['date'])) {
            $records = $attendanceObj->getAllAttendanceByDate($_GET['date']);
            echo json_encode([
                'success' => true,
                'date' => $_GET['date'],
                'data' => $records
            ]);
        } 
        // 3. Xem lịch sử cá nhân (Yêu cầu Login)
        else {
            $filters = [
                'year' => $_GET['year'] ?? null,
                'month' => $_GET['month'] ?? null,
                'week' => $_GET['week'] ?? null
            ];

            if ($filters['year'] || $filters['month'] || $filters['week']) {
                $history = $attendanceObj->getHistoryByFilter($employeeId, $filters);
            } else {
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
                $history = $attendanceObj->getHistory($employeeId, $limit);
            }

            echo json_encode([
                'success' => true,
                'data' => $history
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ']);
        break;
}
?>
