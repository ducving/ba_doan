<?php
require_once __DIR__ . '/Database.php';

class Stats {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getDashboardStats() {
        try {
            // 1. Tổng doanh thu (hoàn tất)
            $sqlRevenue = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
            $totalRevenue = $this->db->query($sqlRevenue)->fetch()['total'] ?? 0;

            // 2. Doanh thu hôm nay vs hôm qua
            $sqlToday = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()";
            $todayRevenue = $this->db->query($sqlToday)->fetch()['total'] ?? 0;

            $sqlYesterday = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            $yesterdayRevenue = $this->db->query($sqlYesterday)->fetch()['total'] ?? 0;

            // Tính % tăng trưởng
            $revenueDelta = 0;
            if ($yesterdayRevenue > 0) {
                $revenueDelta = (($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100;
            } else if ($todayRevenue > 0) {
                $revenueDelta = 100;
            }

            // 3. Tổng số đơn hàng và đơn chờ
            $sqlOrders = "SELECT COUNT(*) as total FROM orders";
            $totalOrders = $this->db->query($sqlOrders)->fetch()['total'] ?? 0;

            $sqlPending = "SELECT COUNT(*) as total FROM orders WHERE status IN ('pending', 'processing')";
            $pendingOrders = $this->db->query($sqlPending)->fetch()['total'] ?? 0;

            // 4. Tổng sản phẩm và hết hàng
            $sqlProducts = "SELECT COUNT(*) as total FROM products";
            $totalProducts = $this->db->query($sqlProducts)->fetch()['total'] ?? 0;

            $sqlOutOfStock = "SELECT COUNT(*) as total FROM products WHERE stock_quantity <= 0 OR status = 'inactive'";
            $outOfStock = $this->db->query($sqlOutOfStock)->fetch()['total'] ?? 0;

            // 5. Dữ liệu biểu đồ doanh thu 7 ngày qua
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $label = ($i == 0) ? "Hôm nay" : date('d/m', strtotime($date));
                
                $sqlDay = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' AND DATE(created_at) = ?";
                $stmt = $this->db->prepare($sqlDay);
                $stmt->execute([$date]);
                $dayTotal = $stmt->fetch()['total'] ?? 0;
                
                $chartData[] = [
                    'name' => $label,
                    'revenue' => (float)$dayTotal
                ];
            }

            // 6. Phân bổ danh mục
            $sqlCategories = "SELECT c.name, COUNT(p.id) as value 
                             FROM categories c 
                             LEFT JOIN products p ON c.id = p.category_id 
                             GROUP BY c.id";
            $pieData = $this->db->query($sqlCategories)->fetchAll();

            // 7. Top 5 sản phẩm bán chạy nhất
            $sqlTopSellers = "SELECT p.name, SUM(oi.quantity) as total_sold 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             JOIN orders o ON oi.order_id = o.id
                             WHERE o.status = 'completed'
                             GROUP BY oi.product_id 
                             ORDER BY total_sold DESC 
                             LIMIT 5";
            $topSellers = $this->db->query($sqlTopSellers)->fetchAll();

            // 8. Đơn hàng gần đây
            $sqlRecentOrders = "SELECT id, full_name, total_amount, status, payment_method, created_at 
                               FROM orders 
                               ORDER BY created_at DESC 
                               LIMIT 5";
            $recentOrders = $this->db->query($sqlRecentOrders)->fetchAll();

            return [
                'success' => true,
                'data' => [
                    'totalRevenue' => (float)$totalRevenue,
                    'todayRevenue' => (float)$todayRevenue,
                    'revenueDelta' => round($revenueDelta, 1),
                    'totalOrders' => (int)$totalOrders,
                    'pendingOrders' => (int)$pendingOrders,
                    'totalProducts' => (int)$totalProducts,
                    'outOfStock' => (int)$outOfStock,
                    'chartData' => $chartData,
                    'pieData' => $pieData,
                    'topSellers' => $topSellers,
                    'recentOrders' => $recentOrders
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi lấy thống kê: ' . $e->getMessage()
            ];
        }
    }
}
?>
