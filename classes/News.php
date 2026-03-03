<?php
require_once __DIR__ . '/Database.php';

class News {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy tất cả tin tức
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT * FROM news $whereClause ORDER BY id DESC";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return [
                'success' => true,
                'news' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi lấy danh sách tin tức: ' . $e->getMessage()
            ];
        }
    }

    // Lấy tin tức theo ID
    public function getById($id) {
        $sql = "SELECT * FROM news WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Tạo tin tức mới
    public function create($data) {
        $title = $data['title'] ?? null;
        $content = $data['content'] ?? null;
        $image = $data['image'] ?? '';
        $status = $data['status'] ?? 'active';

        if (empty($title)) {
            return [
                'success' => false,
                'message' => 'Tiêu đề tin tức không được để trống'
            ];
        }

        if (empty($content)) {
            return [
                'success' => false,
                'message' => 'Nội dung tin tức không được để trống'
            ];
        }

        try {
            $sql = "INSERT INTO news (title, content, image, status, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $content, $image, $status]);
            
            $newsId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Tạo tin tức thành công',
                'news_id' => $newsId,
                'news' => $this->getById($newsId)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi tạo tin tức: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật tin tức
    public function update($id, $data) {
        $news = $this->getById($id);
        if (!$news) {
            return [
                'success' => false,
                'message' => 'Tin tức không tồn tại'
            ];
        }

        $title = $data['title'] ?? $news['title'];
        $content = $data['content'] ?? $news['content'];
        $image = $data['image'] ?? $news['image'];
        $status = $data['status'] ?? $news['status'];

        try {
            $sql = "UPDATE news SET title = ?, content = ?, image = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $content, $image, $status, $id]);
            
            return [
                'success' => true,
                'message' => 'Cập nhật tin tức thành công',
                'news' => $this->getById($id)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật tin tức: ' . $e->getMessage()
            ];
        }
    }

    // Xóa tin tức
    public function delete($id) {
        $news = $this->getById($id);
        if (!$news) {
            return [
                'success' => false,
                'message' => 'Tin tức không tồn tại'
            ];
        }

        try {
            $sql = "DELETE FROM news WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Xóa tin tức thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xóa tin tức: ' . $e->getMessage()
            ];
        }
    }
}
?>
