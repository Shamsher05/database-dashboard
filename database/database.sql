-- ============================================
-- Database Management Dashboard - Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS database_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE database_dashboard;

CREATE TABLE IF NOT EXISTS records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    age INT NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional sample data (uncomment if you want a few starter rows)
-- INSERT INTO records (name, email, phone, age, address) VALUES
-- ('John Doe', 'john@example.com', '9876543210', 28, 'Delhi, India'),
-- ('Jane Smith', 'jane@example.com', '9123456780', 32, 'Mumbai, India');
