-- ============================================
-- SCHEMA TỐI THIỂU - CHỈ BẢNG BẮT BUỘC
-- ============================================
-- File này chỉ tạo bảng users - đủ để hệ thống hoạt động
-- Nếu muốn đầy đủ tính năng, dùng file schema.sql

-- Tạo database
CREATE DATABASE IF NOT EXISTS caffe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE caffe;

-- Bảng users (BẮT BUỘC)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
