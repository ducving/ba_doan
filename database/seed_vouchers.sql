INSERT INTO `vouchers` (`code`, `discount_amount`, `start_date`, `end_date`, `usage_limit`, `status`) 
VALUES 
-- Giải đặc biệt: Giảm thẳng 100k - Thời hạn 7 ngày
('FREEDRINK', 100000.00, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 10, 'active'),

-- Giải nhất: Giảm thẳng 50k - Thời hạn 14 ngày
('LUCKY50K', 50000.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 50, 'active'),

-- Giải nhì: Giảm thẳng 20k - Thời hạn 14 ngày
('LUCKY20K', 20000.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 100, 'active'),

-- Giải ba: Giảm thẳng 10k - Thời hạn 14 ngày
('LUCKY10K', 10000.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 500, 'active'),

-- Giải khuyến khích: Giảm 5k - Thời hạn 30 ngày
('LUCKY5K', 5000.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1000, 'active'),

-- Quà tặng thêm: Freeship (Giảm 30k cho tiền ship)
('FREESHIP30K', 30000.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 200, 'active');
