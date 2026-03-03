<?php
require_once __DIR__ . '/Database.php';

class Product {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Lấy tất cả sản phẩm
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        // Filter theo category_id
        if (isset($filters['category_id'])) {
            $where[] = "category_id = ?";
            $params[] = $filters['category_id'];
        }

        // Filter theo status
        if (isset($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        // Filter theo featured
        if (isset($filters['featured'])) {
            $where[] = "featured = ?";
            $params[] = $filters['featured'];
        }

        // Search theo tên
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Pagination
        $page = isset($filters['page']) ? max(1, (int)$filters['page']) : 1;
        $limit = isset($filters['limit']) ? max(1, min(100, (int)$filters['limit'])) : 20;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $whereClause 
                ORDER BY p.sort_order ASC, p.id DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Parse images JSON
        foreach ($products as &$product) {
            if ($product['images']) {
                $product['images'] = json_decode($product['images'], true) ?: [];
            } else {
                $product['images'] = [];
            }
        }

        // Lấy tổng số bản ghi
        $countSql = "SELECT COUNT(*) as total FROM products p $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, -2)); // Bỏ limit và offset
        $total = $countStmt->fetch()['total'];

        return [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }

    // Lấy sản phẩm theo ID
    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product && $product['images']) {
            $product['images'] = json_decode($product['images'], true) ?: [];
        } elseif ($product) {
            $product['images'] = [];
        }

        return $product;
    }

    // Lấy sản phẩm theo slug
    public function getBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $product = $stmt->fetch();

        if ($product && $product['images']) {
            $product['images'] = json_decode($product['images'], true) ?: [];
        } elseif ($product) {
            $product['images'] = [];
        }

        return $product;
    }

    // Kiểm tra slug đã tồn tại chưa
    public function slugExists($slug, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT id FROM products WHERE slug = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug, $excludeId]);
        } else {
            $sql = "SELECT id FROM products WHERE slug = ?";
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

    // Kiểm tra category có tồn tại không
    public function categoryExists($categoryId) {
        $sql = "SELECT id FROM categories WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->rowCount() > 0;
    }

    // Tạo sản phẩm mới
    public function create($data) {
        $categoryId = $data['category_id'] ?? null;
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? $this->createSlug($name);
        $description = $data['description'] ?? null;
        $shortDescription = $data['short_description'] ?? null;
        $price = $data['price'] ?? 0;
        $salePrice = $data['sale_price'] ?? null;
        $sku = $data['sku'] ?? null;
        $stockQuantity = $data['stock_quantity'] ?? 0;
        $image = $data['image'] ?? null;
        $images = isset($data['images']) && is_array($data['images']) ? json_encode($data['images']) : null;
        $status = $data['status'] ?? 'active';
        $featured = isset($data['featured']) ? (int)$data['featured'] : 0;
        $sortOrder = $data['sort_order'] ?? 0;

        // Validate
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Tên sản phẩm không được để trống'
            ];
        }

        if (!$categoryId || !$this->categoryExists($categoryId)) {
            return [
                'success' => false,
                'message' => 'Danh mục không hợp lệ'
            ];
        }

        // Kiểm tra slug
        if ($this->slugExists($slug)) {
            return [
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ];
        }

        // Validate price
        $price = max(0, (float)$price);
        if ($salePrice !== null) {
            $salePrice = max(0, (float)$salePrice);
        }

        // Validate status
        if (!in_array($status, ['active', 'inactive', 'out_of_stock'])) {
            $status = 'active';
        }

        // Validate stock
        $stockQuantity = max(0, (int)$stockQuantity);

        try {
            $sql = "INSERT INTO products (category_id, name, slug, description, short_description, price, 
                    sale_price, sku, stock_quantity, image, images, status, featured, sort_order, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $categoryId, $name, $slug, $description, $shortDescription, $price, 
                $salePrice, $sku, $stockQuantity, $image, $images, $status, $featured, $sortOrder
            ]);
            
            $productId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Tạo sản phẩm thành công',
                'product_id' => $productId,
                'product' => $this->getById($productId)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi tạo sản phẩm: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật sản phẩm
    public function update($id, $data) {
        // Kiểm tra sản phẩm có tồn tại không
        $product = $this->getById($id);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Sản phẩm không tồn tại'
            ];
        }

        $categoryId = $data['category_id'] ?? $product['category_id'];
        $name = $data['name'] ?? $product['name'];
        $slug = $data['slug'] ?? $product['slug'];
        $description = $data['description'] ?? $product['description'];
        $shortDescription = $data['short_description'] ?? $product['short_description'];
        $price = $data['price'] ?? $product['price'];
        $salePrice = isset($data['sale_price']) ? $data['sale_price'] : $product['sale_price'];
        $sku = $data['sku'] ?? $product['sku'];
        $stockQuantity = $data['stock_quantity'] ?? $product['stock_quantity'];
        $image = $data['image'] ?? $product['image'];
        $images = isset($data['images']) && is_array($data['images']) ? json_encode($data['images']) : $product['images'];
        $status = $data['status'] ?? $product['status'];
        $featured = isset($data['featured']) ? (int)$data['featured'] : $product['featured'];
        $sortOrder = $data['sort_order'] ?? $product['sort_order'];

        // Validate
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'Tên sản phẩm không được để trống'
            ];
        }

        if (!$this->categoryExists($categoryId)) {
            return [
                'success' => false,
                'message' => 'Danh mục không hợp lệ'
            ];
        }

        // Kiểm tra slug nếu thay đổi
        if ($slug !== $product['slug'] && $this->slugExists($slug, $id)) {
            return [
                'success' => false,
                'message' => 'Slug đã tồn tại'
            ];
        }

        // Validate price
        $price = max(0, (float)$price);
        if ($salePrice !== null) {
            $salePrice = max(0, (float)$salePrice);
        }

        // Validate status
        if (!in_array($status, ['active', 'inactive', 'out_of_stock'])) {
            $status = $product['status'];
        }

        // Validate stock
        $stockQuantity = max(0, (int)$stockQuantity);

        try {
            $sql = "UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, 
                    short_description = ?, price = ?, sale_price = ?, sku = ?, stock_quantity = ?, 
                    image = ?, images = ?, status = ?, featured = ?, sort_order = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $categoryId, $name, $slug, $description, $shortDescription, $price, 
                $salePrice, $sku, $stockQuantity, $image, $images, $status, $featured, $sortOrder, $id
            ]);
            
            return [
                'success' => true,
                'message' => 'Cập nhật sản phẩm thành công',
                'product' => $this->getById($id)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật sản phẩm: ' . $e->getMessage()
            ];
        }
    }

    // Xóa sản phẩm
    public function delete($id) {
        // Kiểm tra sản phẩm có tồn tại không
        $product = $this->getById($id);
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Sản phẩm không tồn tại'
            ];
        }

        try {
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Xóa sản phẩm thành công'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Lỗi xóa sản phẩm: ' . $e->getMessage()
            ];
        }
    }
}
?>
