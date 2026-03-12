<?php
class Voucher {
    private $conn;
    private $table_name = "vouchers";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByCode($code) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE code = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (code, discount_amount, start_date, end_date, usage_limit, status) 
                  VALUES (:code, :discount_amount, :start_date, :end_date, :usage_limit, :status)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":code", $data['code']);
        $stmt->bindParam(":discount_amount", $data['discount_amount']);
        
        $start_date = isset($data['start_date']) && !empty($data['start_date']) ? $data['start_date'] : null;
        $stmt->bindParam(":start_date", $start_date);
        
        $end_date = isset($data['end_date']) && !empty($data['end_date']) ? $data['end_date'] : null;
        $stmt->bindParam(":end_date", $end_date);
        
        $usage_limit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? $data['usage_limit'] : null;
        $stmt->bindParam(":usage_limit", $usage_limit);
        
        $status = isset($data['status']) ? $data['status'] : 'active';
        $stmt->bindParam(":status", $status);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET
                  code = :code,
                  discount_amount = :discount_amount,
                  start_date = :start_date,
                  end_date = :end_date,
                  usage_limit = :usage_limit,
                  status = :status
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":code", $data['code']);
        $stmt->bindParam(":discount_amount", $data['discount_amount']);
        
        $start_date = isset($data['start_date']) && !empty($data['start_date']) ? $data['start_date'] : null;
        $stmt->bindParam(":start_date", $start_date);
        
        $end_date = isset($data['end_date']) && !empty($data['end_date']) ? $data['end_date'] : null;
        $stmt->bindParam(":end_date", $end_date);
        
        $usage_limit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? $data['usage_limit'] : null;
        $stmt->bindParam(":usage_limit", $usage_limit);
        
        $status = isset($data['status']) ? $data['status'] : 'active';
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }

    public function updateUsedCount($id) {
        $query = "UPDATE " . $this->table_name . " SET used_count = used_count + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
}
?>
