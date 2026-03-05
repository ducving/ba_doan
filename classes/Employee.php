<?php
require_once __DIR__ . '/Database.php';

class Employee {
    private $db;
    private $table = 'employees';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy danh sách nhân viên
    public function getAll($limit = 10, $offset = 0) {
        $sql = "SELECT e.*, u.name as account_name 
                FROM " . $this->table . " e
                LEFT JOIN users u ON e.user_id = u.id
                ORDER BY e.created_at DESC 
                LIMIT ? OFFSET ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Lấy chi tiết nhân viên
    public function getById($id) {
        $sql = "SELECT e.*, u.name as account_name 
                FROM " . $this->table . " e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE e.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết nhân viên theo User ID
    public function getByUserId($userId) {
        $sql = "SELECT * FROM " . $this->table . " WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy chi tiết nhân viên theo Mã nhân viên
    public function getByCode($code) {
        $sql = "SELECT * FROM " . $this->table . " WHERE employee_code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm nhân viên mới
    public function create($data) {
        $sql = "INSERT INTO " . $this->table . " 
                (user_id, employee_code, full_name, email, phone, position, department, salary, hourly_rate, hire_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['user_id'] ?? null,
                $data['employee_code'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['position'] ?? null,
                $data['department'] ?? null,
                $data['salary'] ?? 0,
                $data['hourly_rate'] ?? 0,
                $data['hire_date'] ?? date('Y-m-d'),
                $data['status'] ?? 'active'
            ]);
            return [
                'success' => true,
                'id' => $this->db->lastInsertId(),
                'message' => 'Thêm nhân viên thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật nhân viên
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        $allowedFields = ['user_id', 'employee_code', 'full_name', 'email', 'phone', 'position', 'department', 'salary', 'hourly_rate', 'hire_date', 'status'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'Không có dữ liệu cập nhật'];
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return ['success' => true, 'message' => 'Cập nhật thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Xóa nhân viên
    public function delete($id) {
        $sql = "DELETE FROM " . $this->table . " WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Xóa nhân viên thành công'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // Đếm tổng số nhân viên
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
}
?>
