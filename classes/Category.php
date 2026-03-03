<?php
require_once __DIR__ . '/Database.php';

class Category {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy tất cả danh mục
    public function getAll($status = null) {
        if ($status) {
            $sql = "SELECT * FROM categories WHERE status = ? ORDER BY sort_order ASC, id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status]);
        } else {
            $sql = "SELECT * FROM categories ORDER BY sort_order ASC, id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    // Lấy danh mục theo ID
    public function getById($id) {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Lấy danh mục theo slug
    public function getBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    // Kiểm tra slug đã tồn tại chưa
    public function slugExists($slug, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT id FROM categories WHERE slug = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug, $excludeId]);
        } else {
            $sql = "SELECT id FROM categories WHERE slug = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
        }
        return $stmt->rowCount() > 0;
    }

    // Tạo slug từ tên
    public function createSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Nếu slug đã tồn tại, thêm số vào cuối
        $originalSlug = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Tạo danh mục mới
    public function create($data) {
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? $this->createSlug($name);
        $description = $data['description'] ?? null;
        $image = $data['image'] ?? null;
        $status = $data['status'] ?? 'active';
        $sortOrder = $data['sort_order'] ?? 0;

        // Validate
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Tên danh mục không được để trống'
            ];
        }

        // Kiểm tra slug
        if ($this->slugExists($slug)) {
            return [
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ];
        }

        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }

        try {
            $sql = "INSERT INTO categories (name, slug, description, image, status, sort_order, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $slug, $description, $image, $status, $sortOrder]);
            
            $categoryId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Tạo danh mục thành công',
                'category_id' => $categoryId,
                'category' => $this->getById($categoryId)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi tạo danh mục: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật danh mục
    public function update($id, $data) {
        // Kiểm tra danh mục có tồn tại không
        $category = $this->getById($id);
        if (!$category) {
            return [
                'success' => false,
                'message' => 'Danh mục không tồn tại'
            ];
        }

        $name = $data['name'] ?? $category['name'];
        $slug = $data['slug'] ?? $category['slug'];
        $description = $data['description'] ?? $category['description'];
        $image = $data['image'] ?? $category['image'];
        $status = $data['status'] ?? $category['status'];
        $sortOrder = $data['sort_order'] ?? $category['sort_order'];

        // Validate
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Tên danh mục không được để trống'
            ];
        }

        // Kiểm tra slug nếu thay đổi
        if ($slug !== $category['slug'] && $this->slugExists($slug, $id)) {
            return [
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ];
        }

        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            $status = $category['status'];
        }

        try {
            $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, 
                    status = ?, sort_order = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $slug, $description, $image, $status, $sortOrder, $id]);
            
            return [
                'success' => true,
                'message' => 'Cập nhật danh mục thành công',
                'category' => $this->getById($id)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật danh mục: ' . $e->getMessage()
            ];
        }
    }

    // Xóa danh mục
    public function delete($id) {
        // Kiểm tra danh mục có tồn tại không
        $category = $this->getById($id);
        if (!$category) {
            return [
                'success' => false,
                'message' => 'Danh mục không tồn tại'
            ];
        }

        // Kiểm tra xem có sản phẩm nào thuộc danh mục này không
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Không thể xóa danh mục vì còn sản phẩm thuộc danh mục này'
            ];
        }

        try {
            $sql = "DELETE FROM categories WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Xóa danh mục thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xóa danh mục: ' . $e->getMessage()
            ];
        }
    }
}
?>
