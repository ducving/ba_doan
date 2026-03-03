<?php
class Security {
    // Sanitize input để chống XSS
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    // Validate và sanitize email
    public static function validateEmail($email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    // Validate password strength
    public static function validatePassword($password) {
        // Ít nhất 8 ký tự, có chữ hoa, chữ thường, số
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự'];
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 1 chữ hoa'];
        }
        if (!preg_match('/[a-z]/', $password)) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 1 chữ thường'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 1 số'];
        }
        return ['valid' => true, 'message' => 'Mật khẩu hợp lệ'];
    }

    // Generate CSRF token
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Rate limiting - chống brute force
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $file = __DIR__ . '/../storage/rate_limit_' . md5($identifier) . '.json';
        $now = time();

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            // Xóa các attempt cũ hơn timeWindow
            $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($now, $timeWindow) {
                return ($now - $timestamp) < $timeWindow;
            });
            
            // Đếm số attempt còn lại trong timeWindow
            $attemptCount = count($data['attempts']);
            
            if ($attemptCount >= $maxAttempts) {
                $remainingTime = $timeWindow - ($now - min($data['attempts']));
                return [
                    'allowed' => false,
                    'remaining_time' => $remainingTime,
                    'message' => 'Quá nhiều lần thử. Vui lòng thử lại sau ' . ceil($remainingTime / 60) . ' phút'
                ];
            }
            
            // Thêm attempt mới
            $data['attempts'][] = $now;
        } else {
            $data = ['attempts' => [$now]];
        }
        
        // Lưu lại
        if (!is_dir(__DIR__ . '/../storage')) {
            mkdir(__DIR__ . '/../storage', 0755, true);
        }
        file_put_contents($file, json_encode($data));
        
        return ['allowed' => true];
    }

    // Reset rate limit (khi đăng nhập thành công)
    public static function resetRateLimit($identifier) {
        $file = __DIR__ . '/../storage/rate_limit_' . md5($identifier) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    // Log security events
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/../storage/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }
}
?>
