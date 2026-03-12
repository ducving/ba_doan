<?php
class UserVoucher {
    private $conn;
    private $table_name = "user_vouchers";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Thêm voucher vào ví của User (khi quay trúng)
    public function assignVoucher($user_id, $voucher_id) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, voucher_id, is_used) 
                  VALUES (:user_id, :voucher_id, 0)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":voucher_id", $voucher_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Lấy danh sách voucher trong ví của User
    public function getUserVouchers($user_id, $is_used = null) {
        $query = "SELECT uv.id as user_voucher_id, uv.is_used, uv.used_at, uv.created_at as received_at,
                         v.id as voucher_id, v.code, v.discount_amount, v.start_date, v.end_date, v.status
                  FROM " . $this->table_name . " uv
                  JOIN vouchers v ON uv.voucher_id = v.id
                  WHERE uv.user_id = :user_id";
                  
        if ($is_used !== null) {
            $query .= " AND uv.is_used = :is_used";
        }
        $query .= " ORDER BY uv.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        if ($is_used !== null) {
            $stmt->bindParam(":is_used", $is_used);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Đánh dấu voucher đã được sử dụng (khi User thanh toán thành công)
    public function markAsUsed($user_id, $voucher_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_used = 1, used_at = NOW() 
                  WHERE user_id = :user_id AND voucher_id = :voucher_id AND is_used = 0";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":voucher_id", $voucher_id);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
