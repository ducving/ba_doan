<?php
require_once __DIR__ . '/Database.php';

class Banner {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy tất cả banner
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM banners $whereClause ORDER BY sort_order ASC, id DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return [
                'success' => true,
                'banners' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi lấy danh sách banner: ' . $e->getMessage()
            ];
        }
    }

    // Lấy banner theo ID
    public function getById($id) {
        $sql = "SELECT * FROM banners WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Tạo banner mới
    public function create($data) {
        $title = $data['title'] ?? null;
        $image = $data['image'] ?? '';
        $link = $data['link'] ?? null;
        $sortOrder = $data['sort_order'] ?? 0;
        $status = $data['status'] ?? 'active';

        if (empty($title)) {
            return [
                'success' => false,
                'message' => 'Tiêu đề banner không được để trống'
            ];
        }

        if (empty($image)) {
            return [
                'success' => false,
                'message' => 'Hình ảnh banner không được để trống'
            ];
        }

        try {
            $sql = "INSERT INTO banners (title, image, link, sort_order, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $image, $link, $sortOrder, $status]);
            
            $bannerId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Tạo banner thành công',
                'banner_id' => $bannerId,
                'banner' => $this->getById($bannerId)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi tạo banner: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật banner
    public function update($id, $data) {
        $banner = $this->getById($id);
        if (!$banner) {
            return [
                'success' => false,
                'message' => 'Banner không tồn tại'
            ];
        }

        $title = $data['title'] ?? $banner['title'];
        $image = $data['image'] ?? $banner['image'];
        $link = $data['link'] ?? $banner['link'];
        $sortOrder = $data['sort_order'] ?? $banner['sort_order'];
        $status = $data['status'] ?? $banner['status'];

        try {
            $sql = "UPDATE banners SET title = ?, image = ?, link = ?, sort_order = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $image, $link, $sortOrder, $status, $id]);
            
            return [
                'success' => true,
                'message' => 'Cập nhật banner thành công',
                'banner' => $this->getById($id)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật banner: ' . $e->getMessage()
            ];
        }
    }

    // Xóa banner
    public function delete($id) {
        $banner = $this->getById($id);
        if (!$banner) {
            return [
                'success' => false,
                'message' => 'Banner không tồn tại'
            ];
        }

        try {
            $sql = "DELETE FROM banners WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Xóa banner thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xóa banner: ' . $e->getMessage()
            ];
        }
    }
}
?>
