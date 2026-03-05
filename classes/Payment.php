<?php
require_once __DIR__ . '/Database.php';

class Payment {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy danh sách giao dịch thanh toán
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['order_id'])) {
            $where[] = "order_id = ?";
            $params[] = $filters['order_id'];
        }

        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Phân trang
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? max(1, min(100, (int)$filters['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.*, o.id as original_order_id, u.name as user_name 
                FROM order_payments p
                LEFT JOIN orders o ON p.order_id = o.id
                LEFT JOIN users u ON p.user_id = u.id
                $whereClause 
                ORDER BY p.id DESC LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll();

            // Lấy tổng số
            $countSql = "SELECT COUNT(*) as total FROM order_payments $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetch()['total'];

            return [
                'success' => true,
                'data' => $payments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi lấy danh sách thanh toán: ' . $e->getMessage()
            ];
        }
    }

    // Lấy chi tiết một giao dịch
    public function getById($id) {
        $sql = "SELECT p.*, u.name as user_name, u.email as user_email 
                FROM order_payments p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Cập nhật trạng thái giao dịch (Dành cho Admin)
    public function update($id, $data) {
        try {
            $status = $data['status'] ?? 'paid';
            $note = $data['note'] ?? '';
            $transId = $data['transaction_id'] ?? null;
            
            $sql = "UPDATE order_payments SET status = ?, note = ?, transaction_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $note, $transId, $id]);
            
            return [
                'success' => true,
                'message' => 'Cập nhật giao dịch thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ];
        }
    }
}
?>
