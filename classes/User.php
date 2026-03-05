<?php
require_once __DIR__ . '/Database.php';

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Kiểm tra email đã tồn tại chưa
    public function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    // Đăng ký user mới
    public function register($name, $email, $password, $avatar = null, $role = 'user', $status = 'active') {
        // Kiểm tra email đã tồn tại
        if ($this->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Email đã được sử dụng'
            ];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user vào database
        $sql = "INSERT INTO users (name, email, password, avatar, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $email, $hashedPassword, $avatar, $role, $status]);
            
            $userId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Đăng ký thành công',
                'user_id' => $userId
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi đăng ký: ' . $e->getMessage()
            ];
        }
    }

    // Đăng nhập
    public function login($email, $password) {
        $sql = "SELECT id, name, email, password, role, status FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ];
        }

        // Kiểm tra status
        if (isset($user['status']) && $user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Tài khoản đang bị khóa hoặc chưa kích hoạt'
            ];
        }

        // Kiểm tra password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng'
            ];
        }

        // Xóa password khỏi response
        unset($user['password']);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user
        ];
    }

    // Lấy thông tin user theo ID
    public function getUserById($id) {
        $sql = "SELECT id, name, email, phone, address, avatar, role, status, created_at FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Cập nhật thông tin user
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        // Danh sách các trường cho phép cập nhật
        $allowedFields = ['name', 'phone', 'address', 'avatar', 'status', 'role'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return [
                'success' => false,
                'message' => 'Không có dữ liệu hợp lệ để cập nhật'
            ];
        }

        // Kiểm tra nếu cập nhật password (nên có method riêng nhưng tạm thời hỗ trợ ở đây nếu cần)
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $params[] = $id;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Cập nhật thông tin thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ];
        }
    }
    // Lấy danh sách tất cả user
    public function getAllUsers($limit = 10, $offset = 0) {
        $sql = "SELECT id, name, email, phone, address, avatar, role, status, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // Đếm tổng số user
    public function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM users";
        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>
