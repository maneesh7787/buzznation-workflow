-- ============================================
-- Project Management Portal Database Schema
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS buzznation_pm DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buzznation_pm;

-- ============================================
-- Table: users
-- Stores all user information with roles
-- ============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Sales', 'Design', 'Operation') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: projects
-- Main project/task information
-- ============================================
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(200) NOT NULL,
    initial_cost DECIMAL(10,2) DEFAULT 0.00,
    final_cost DECIMAL(10,2) DEFAULT 0.00,
    exhibition_size VARCHAR(100),
    other_instructions TEXT,
    status ENUM(
        'Submitted by Sales',
        'Pending Design Review',
        'Accepted by Design',
        'Returned by Design',
        'Design Work In Progress',
        'Completed & Sent to OPS',
        'Reviewed by OPS',
        'Reviewed & Sent to Sales',
        'Client Update Requested',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Submitted by Sales',
    sales_confirmed TINYINT(1) DEFAULT 0,
    created_by INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    timeline_days INT DEFAULT NULL,
    design_comments TEXT,
    ops_remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: project_files
-- Stores uploaded files for projects
-- ============================================
CREATE TABLE project_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    upload_stage ENUM('Sales Initial', 'Design Work', 'Client Update', 'Other') DEFAULT 'Other',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    INDEX idx_project (project_id),
    INDEX idx_stage (upload_stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: project_status_history
-- Tracks all status changes for projects
-- ============================================
CREATE TABLE project_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    changed_by INT NOT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100) NOT NULL,
    remarks TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id),
    INDEX idx_project (project_id),
    INDEX idx_date (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: notifications
-- System notifications for users
-- ============================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: email_notification_users
-- Stores email list for notifications
-- ============================================
CREATE TABLE email_notification_users (
    email_user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: project_email_notifications
-- Links projects to email notification recipients
-- ============================================
CREATE TABLE project_email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    email_user_id INT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (email_user_id) REFERENCES email_notification_users(email_user_id),
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: logs
-- Comprehensive action logging
-- ============================================
CREATE TABLE logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    role VARCHAR(20),
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    project_id INT DEFAULT NULL,
    old_status VARCHAR(100),
    new_status VARCHAR(100),
    remarks TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_project (project_id),
    INDEX idx_action (action_type),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert default admin user
-- Username: admin
-- Password: admin123 (hashed with PASSWORD_BCRYPT)
-- ============================================
INSERT INTO users (username, email, password_hash, full_name, role, is_active) 
VALUES ('admin', 'admin@buzznation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'Admin', 1);

-- ============================================
-- Insert sample email notification users
-- ============================================
INSERT INTO email_notification_users (full_name, email) VALUES
('Project Manager', 'pm@buzznation.com'),
('Design Lead', 'design@buzznation.com'),
('Operations Head', 'ops@buzznation.com');

-- ============================================
-- Insert sample users for testing
-- Password for all: password123
-- ============================================
INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES
('sales1', 'sales1@buzznation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales User 1', 'Sales', 1),
('design1', 'design1@buzznation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Design User 1', 'Design', 1),
('ops1', 'ops1@buzznation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operations User 1', 'Operation', 1);
