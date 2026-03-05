<?php
require_once __DIR__ . '/Database.php';

class Attendance {
    private $db;
    private $table = 'attendance';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Lấy trạng thái chấm công của nhân viên hôm nay
     */
    public function getTodayStatus($employeeId) {
        $date = date('Y-m-d');
        $sql = "SELECT a.*, e.salary, e.hourly_rate 
                FROM " . $this->table . " a
                JOIN employees e ON a.employee_id = e.id
                WHERE a.employee_id = ? AND a.date = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $date]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Nếu đã có dữ liệu tính sẵn trong DB thì ưu tiên dùng
            if ($row['total_hours'] > 0) {
                return $row;
            }

            // Trường hợp chưa có hoặc đang tính line (chưa checkout hoặc vừa checkout)
            $row['total_hours'] = 0;
            $row['daily_wage'] = 0;

            if ($row['check_in'] && $row['check_out']) {
                $start = strtotime($row['check_in']);
                $end = strtotime($row['check_out']);
                $diffSeconds = $end - $start;
                $row['total_hours'] = round($diffSeconds / 3600, 2);

                if ($row['hourly_rate'] > 0) {
                    $row['daily_wage'] = floor($row['total_hours'] * $row['hourly_rate']);
                } else {
                    $dailyRate = $row['salary'] / 26;
                    $hourlyRate = $dailyRate / 8;
                    $row['daily_wage'] = floor($row['total_hours'] * $hourlyRate);
                }
            }
        }
            return $row;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Thực hiện Check-in
     */
    public function checkIn($employeeId, $note = null) {
        $date = date('Y-m-d');
        $checkInTime = date('Y-m-d H:i:s');
        
        // 1. Kiểm tra xem đã check-in hôm nay chưa
        $sqlCheck = "SELECT id FROM " . $this->table . " WHERE employee_id = ? AND date = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([$employeeId, $date]);
        
        if ($stmtCheck->rowCount() > 0) {
            return [
                'success' => false,
                'message' => 'Bạn đã chấm công vào hôm nay rồi'
            ];
        }

        // 2. Xác định trạng thái (Đi muộn nếu sau 08:00)
        $status = (date('H:i') > '08:00') ? 'late' : 'present';

        // 3. Kiểm tra khung giờ làm việc (08:00 - 22:00)
        $currentTime = date('H:i');
        if ($currentTime < '08:00' || $currentTime > '22:00') {
            return [
                'success' => false,
                'message' => 'Hệ thống chỉ cho phép chấm công từ 08:00 đến 22:00'
            ];
        }

        // 4. Lưu vào database
        $sql = "INSERT INTO " . $this->table . " (employee_id, check_in, date, status, note) VALUES (?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $checkInTime, $date, $status, $note]);
            return [
                'success' => true,
                'message' => 'Check-in thành công lúc ' . $checkInTime,
                'data' => [
                    'time' => $checkInTime,
                    'status' => $status
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Thực hiện Check-out
     */
    public function checkOut($employeeId) {
        $date = date('Y-m-d');
        
        // 1. Kiểm tra xem đã Check-in chưa
        $sqlCheck = "SELECT * FROM " . $this->table . " WHERE employee_id = ? AND date = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute([$employeeId, $date]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'success' => false,
                'message' => 'Bạn chưa chấm công vào hôm nay'
            ];
        }

        if ($row['check_out']) {
            return [
                'success' => false,
                'message' => 'Bạn đã chấm công ra hôm nay rồi'
            ];
        }

        $checkOutTime = date('Y-m-d H:i:s');

        // 3. Tính toán số giờ và tiền lương
        $totalHours = 0;
        $dailyWage = 0;
        
        $start = strtotime($row['check_in']);
        $end = strtotime($checkOutTime);
        
        // Xử lý làm xuyên đêm (end < start)
        if ($end < $start) {
            $end += 86400;
        }
        
        $diffSeconds = $end - $start;
        $totalHours = round($diffSeconds / 3600, 2);

        // Lấy lương từ bảng employees
        $sqlEmp = "SELECT salary, hourly_rate FROM employees WHERE id = ?";
        $stmtEmp = $this->db->prepare($sqlEmp);
        $stmtEmp->execute([$row['employee_id']]);
        $emp = $stmtEmp->fetch();
        
        if ($emp) {
            if ($emp['hourly_rate'] > 0) {
                $dailyWage = floor($totalHours * $emp['hourly_rate']);
            } else {
                $dailyRate = $emp['salary'] / 26;
                $hourlyRate = $dailyRate / 8;
                $dailyWage = floor($totalHours * $hourlyRate);
            }
        }

        // 4. Cập nhật giờ về và các thông số tính toán
        $sql = "UPDATE " . $this->table . " SET check_out = ?, total_hours = ?, daily_wage = ? WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$checkOutTime, $totalHours, $dailyWage, $row['id']]);
            return [
                'success' => true,
                'message' => 'Check-out thành công lúc ' . $checkOutTime,
                'data' => [
                    'time' => $checkOutTime,
                    'total_hours' => $totalHours,
                    'daily_wage' => $dailyWage
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy lịch sử chấm công
     */
    public function getHistory($employeeId, $limit = 30, $startDate = null, $endDate = null) {
        $params = [$employeeId];
        $sql = "SELECT a.*, e.salary, e.hourly_rate 
                FROM " . $this->table . " a
                JOIN employees e ON a.employee_id = e.id
                WHERE a.employee_id = ?";
        
        if ($startDate && $endDate) {
            $sql .= " AND a.date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY a.date DESC";
        
        if (!$startDate) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
        }

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                // Ưu tiên dùng dữ liệu đã lưu trong DB (nhưng phải dương)
                if (isset($row['total_hours']) && $row['total_hours'] > 0) {
                    continue;
                }

                $row['total_hours'] = 0;
                $row['daily_wage'] = 0;

                if ($row['check_in'] && $row['check_out']) {
                    $start = strtotime($row['check_in']);
                    $end = strtotime($row['check_out']);
                    
                    // Nếu end < start tức là làm xuyên đêm qua ngày hôm sau
                    if ($end < $start) {
                        $end += 86400; // Cộng thêm 1 ngày (24h)
                    }
                    
                    $diffSeconds = $end - $start;
                    $row['total_hours'] = round($diffSeconds / 3600, 2);

                    if (isset($row['hourly_rate']) && $row['hourly_rate'] > 0) {
                        $row['daily_wage'] = floor($row['total_hours'] * $row['hourly_rate']);
                    } else if (isset($row['salary'])) {
                        $dailyRate = $row['salary'] / 26;
                        $hourlyRate = $dailyRate / 8;
                        $row['daily_wage'] = floor($row['total_hours'] * $hourlyRate);
                    }
                }
            }
            return $rows;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Xem toàn bộ (Dành cho Admin)
     */
    public function getAllAttendanceByDate($date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT a.*, e.full_name, e.employee_code, e.salary, e.hourly_rate 
                FROM " . $this->table . " a
                JOIN employees e ON a.employee_id = e.id
                WHERE a.date = ?
                ORDER BY a.check_in ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                // Nếu đã tính sẵn trong DB thì ưu tiên dùng
                if (isset($row['total_hours']) && $row['total_hours'] > 0) {
                    continue;
                }

                $row['total_hours'] = 0;
                $row['daily_wage'] = 0;

                if ($row['check_in'] && $row['check_out']) {
                    $start = strtotime($row['check_in']);
                    $end = strtotime($row['check_out']);
                    $diffSeconds = $end - $start;
                    $row['total_hours'] = round($diffSeconds / 3600, 2);

                    if (isset($row['hourly_rate']) && $row['hourly_rate'] > 0) {
                        $row['daily_wage'] = floor($row['total_hours'] * $row['hourly_rate']);
                    } else if (isset($row['salary'])) {
                        $dailyRate = $row['salary'] / 26;
                        $hourlyRate = $dailyRate / 8;
                        $row['daily_wage'] = floor($row['total_hours'] * $hourlyRate);
                    }
                }
            }
            return $rows;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Lấy lịch sử chấm công có bộ lọc (Tháng, Tuần, Năm)
     */
    public function getHistoryByFilter($employeeId, $filters = []) {
        $conditions = ["a.employee_id = ?"];
        $params = [$employeeId];

        if (!empty($filters['year'])) {
            $conditions[] = "YEAR(a.date) = ?";
            $params[] = (int)$filters['year'];
        }

        if (!empty($filters['month'])) {
            $conditions[] = "MONTH(a.date) = ?";
            $params[] = (int)$filters['month'];
        }

        if (!empty($filters['week'])) {
            $conditions[] = "WEEK(a.date, 1) = ?";
            $params[] = (int)$filters['week'];
        }

        $sql = "SELECT a.*, e.salary, e.hourly_rate 
                FROM " . $this->table . " a
                JOIN employees e ON a.employee_id = e.id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY a.date DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                if (isset($row['total_hours']) && $row['total_hours'] > 0) continue;

                $row['total_hours'] = 0;
                $row['daily_wage'] = 0;

                if ($row['check_in'] && $row['check_out']) {
                    $start = strtotime($row['check_in']);
                    $end = strtotime($row['check_out']);
                    $diffSeconds = $end - $start;
                    $row['total_hours'] = round($diffSeconds / 3600, 2);

                    if (isset($row['hourly_rate']) && $row['hourly_rate'] > 0) {
                        $row['daily_wage'] = floor($row['total_hours'] * $row['hourly_rate']);
                    } else if (isset($row['salary'])) {
                        $dailyRate = $row['salary'] / 26;
                        $hourlyRate = $dailyRate / 8;
                        $row['daily_wage'] = floor($row['total_hours'] * $hourlyRate);
                    }
                }
            }
            return $rows;
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
