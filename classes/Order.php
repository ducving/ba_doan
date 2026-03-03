<?php
require_once __DIR__ . '/Database.php';

class Order {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy danh sách đơn hàng
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['payment_status'])) {
            $where[] = "payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = "(full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Pagination
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? max(1, min(100, (int)$filters['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM orders $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            // Lấy tổng số bản ghi
            $countSql = "SELECT COUNT(*) as total FROM orders $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetch()['total'];

            return [
                'success' => true,
                'orders' => $orders,
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
                'message' => 'Lỗi lấy danh sách đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    // Lấy chi tiết đơn hàng theo ID
    public function getById($id) {
        try {
            $sql = "SELECT * FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                return null;
            }

            // Lấy chi tiết các sản phẩm trong đơn hàng
            $itemSql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.slug as product_slug 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?";
            $itemStmt = $this->db->prepare($itemSql);
            $itemStmt->execute([$id]);
            $order['items'] = $itemStmt->fetchAll();

            // Lấy lịch sử trạng thái đơn hàng
            $historySql = "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC";
            $historyStmt = $this->db->prepare($historySql);
            $historyStmt->execute([$id]);
            $order['status_history'] = $historyStmt->fetchAll();

            return $order;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Tạo đơn hàng mới
    public function create($data) {
        $userId = $data['user_id'] ?? null;
        $fullName = $data['full_name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $note = $data['note'] ?? null;
        $paymentMethod = $data['payment_method'] ?? 'cod';
        $items = $data['items'] ?? []; // Array of ['product_id', 'quantity']

        // Validate
        if (empty($fullName) || empty($phone) || empty($address) || empty($items)) {
            return [
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin và chọn sản phẩm'
            ];
        }

        try {
            $this->db->beginTransaction();

            // 1. Tính tổng tiền và kiểm tra sản phẩm
            $totalAmount = 0;
            $itemsToInsert = [];

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = max(1, (int)$item['quantity']);

                $productSql = "SELECT name, price, sale_price, stock_quantity FROM products WHERE id = ? FOR UPDATE";
                $productStmt = $this->db->prepare($productSql);
                $productStmt->execute([$productId]);
                $product = $productStmt->fetch();

                if (!$product) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => "Sản phẩm (ID: $productId) không tồn tại"];
                }

                if ($product['stock_quantity'] < $quantity) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => "Sản phẩm '{$product['name']}' không đủ hàng"];
                }

                $price = $product['sale_price'] ?? $product['price'];
                $itemTotal = $price * $quantity;
                $totalAmount += $itemTotal;

                $itemsToInsert[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total_price' => $itemTotal
                ];
            }

            // 2. Tạo đơn hàng
            $orderSql = "INSERT INTO orders (user_id, full_name, email, phone, address, total_amount, note, payment_method) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $orderStmt = $this->db->prepare($orderSql);
            $orderStmt->execute([$userId, $fullName, $email, $phone, $address, $totalAmount, $note, $paymentMethod]);
            $orderId = $this->db->lastInsertId();

            // 3. Tạo chi tiết đơn hàng và cập nhật tồn kho
            $itemInsertSql = "INSERT INTO order_items (order_id, product_id, quantity, price, total_price) VALUES (?, ?, ?, ?, ?)";
            $itemInsertStmt = $this->db->prepare($itemInsertSql);

            $stockUpdateSql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $stockUpdateStmt = $this->db->prepare($stockUpdateSql);

            foreach ($itemsToInsert as $item) {
                $itemInsertStmt->execute([
                    $orderId, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['total_price']
                ]);

                $stockUpdateStmt->execute([$item['quantity'], $item['product_id']]);
            }

            // 4. Ghi lại lịch sử trạng thái đầu tiên
            $this->addStatusHistory($orderId, 'pending', 'Đơn hàng được tạo thành công');

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'order_id' => $orderId
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi đặt hàng: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật đơn hàng (Trạng thái)
    public function update($id, $data) {
        $order = $this->getById($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
        }

        $status = $data['status'] ?? $order['status'];
        $paymentStatus = $data['payment_status'] ?? $order['payment_status'];
        $fullName = $data['full_name'] ?? $order['full_name'];
        $phone = $data['phone'] ?? $order['phone'];
        $address = $data['address'] ?? $order['address'];

        // Validate status
        $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            $status = $order['status'];
        }

        $validPaymentStatuses = ['pending', 'paid', 'failed'];
        if (!in_array($paymentStatus, $validPaymentStatuses)) {
            $paymentStatus = $order['payment_status'];
        }

        try {
            // Nếu chuyển sang trạng thái 'cancelled', hoàn lại tồn kho
            if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
                $this->db->beginTransaction();
                
                foreach ($order['items'] as $item) {
                    $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }

                $updateSql = "UPDATE orders SET status = ?, payment_status = ?, full_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$status, $paymentStatus, $fullName, $phone, $address, $id]);
                
                // Ghi lịch sử nếu status thay đổi
                if ($status !== $order['status']) {
                    $this->addStatusHistory($id, $status, "Trạng thái đơn hàng được cập nhật bởi Admin");
                }
                
                $this->db->commit();
            } else {
                $updateSql = "UPDATE orders SET status = ?, payment_status = ?, full_name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$status, $paymentStatus, $fullName, $phone, $address, $id]);
                
                // Ghi lịch sử nếu status thay đổi
                if ($status !== $order['status']) {
                    $this->addStatusHistory($id, $status, "Trạng thái đơn hàng được cập nhật bởi Admin");
                }
            }

            return [
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'order' => $this->getById($id)
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    // Xóa đơn hàng
    public function delete($id) {
        $order = $this->getById($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
        }

        try {
            $this->db->beginTransaction();

            // Nếu đơn hàng chưa bị hủy, thì khi xóa có nên hoàn tồn kho không? 
            // Thường thì chỉ xóa đơn hàng đã hủy hoặc rác. 
            // Nhưng để an toàn, nếu chưa hủy thì hoàn lại.
            if ($order['status'] !== 'cancelled') {
                foreach ($order['items'] as $item) {
                    $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }

            $sql = "DELETE FROM orders WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Xóa đơn hàng thành công'
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi xóa đơn hàng: ' . $e->getMessage()
            ];
        }
    }

    // Thêm lịch sử trạng thái đơn hàng
    private function addStatusHistory($orderId, $status, $note = '') {
        try {
            $sql = "INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId, $status, $note]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
