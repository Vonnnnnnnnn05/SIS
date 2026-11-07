-- ==============================================================
-- Student Information System (SIS) Database Schema
-- Character Set: utf8mb4, Collation: utf8mb4_unicode_ci
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `sis_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sis_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles Table
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Academic Programs Table
DROP TABLE IF EXISTS `programs`;
CREATE TABLE `programs` (
    `program_id` INT AUTO_INCREMENT PRIMARY KEY,
    `program_code` VARCHAR(20) NOT NULL UNIQUE,
    `program_name` VARCHAR(150) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_programs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Academic Years Table
DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
    `ay_id` INT AUTO_INCREMENT PRIMARY KEY,
    `year_code` VARCHAR(20) NOT NULL UNIQUE,
    `start_year` INT NOT NULL,
    `end_year` INT NOT NULL,
    `is_current` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ay_current` (`is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Semesters Table
DROP TABLE IF EXISTS `semesters`;
CREATE TABLE `semesters` (
    `semester_id` INT AUTO_INCREMENT PRIMARY KEY,
    `ay_id` INT NOT NULL,
    `semester_term` ENUM('1st Semester', '2nd Semester', 'Summer') NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_ay_term` (`ay_id`, `semester_term`),
    INDEX `idx_semesters_active` (`is_active`),
    CONSTRAINT `fk_semesters_ay` FOREIGN KEY (`ay_id`) REFERENCES `academic_years` (`ay_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Students Table
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
    `student_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_number` VARCHAR(20) NOT NULL UNIQUE,
    `user_id` INT NULL UNIQUE,
    `first_name` VARCHAR(50) NOT NULL,
    `middle_name` VARCHAR(50) NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `civil_status` ENUM('Single', 'Married', 'Widowed', 'Separated') NOT NULL DEFAULT 'Single',
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `contact_number` VARCHAR(25) NOT NULL,
    `home_address` TEXT NOT NULL,
    `emergency_contact_name` VARCHAR(100) NOT NULL,
    `emergency_contact_number` VARCHAR(25) NOT NULL,
    `program_id` INT NOT NULL,
    `current_status` ENUM('Active', 'Inactive', 'Graduated', 'Withdrawn') NOT NULL DEFAULT 'Active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_students_program` (`program_id`),
    INDEX `idx_students_status` (`current_status`),
    INDEX `idx_students_names` (`last_name`, `first_name`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_students_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Enrollments Table
DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
    `enrollment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `semester_id` INT NOT NULL,
    `program_id` INT NOT NULL,
    `year_level` ENUM('1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year') NOT NULL,
    `enrollment_date` DATE NOT NULL,
    `enrollment_status` ENUM('Pending', 'Enrolled', 'Dropped', 'Completed') NOT NULL DEFAULT 'Enrolled',
    `remarks` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_student_semester` (`student_id`, `semester_id`),
    INDEX `idx_enrollments_semester` (`semester_id`),
    INDEX `idx_enrollments_status` (`enrollment_status`),
    INDEX `idx_enrollments_program` (`program_id`),
    CONSTRAINT `fk_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enrollments_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enrollments_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_enrollments_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. System Logs Table (Audit Trail)
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `role` VARCHAR(50) NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `target_id` VARCHAR(50) NULL,
    `details` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_module` (`module`),
    INDEX `idx_logs_action` (`action_type`),
    INDEX `idx_logs_created` (`created_at`),
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Backup Logs Table
DROP TABLE IF EXISTS `backup_logs`;
CREATE TABLE `backup_logs` (
    `backup_id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(150) NOT NULL UNIQUE,
    `file_size_bytes` BIGINT NOT NULL,
    `record_count` INT NOT NULL DEFAULT 0,
    `created_by` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_backups_created` (`created_at`),
    CONSTRAINT `fk_backups_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
