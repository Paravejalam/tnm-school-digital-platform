-- =============================================================================
-- Migration: 0011_create_teachers_table
-- Table    : teachers
-- Module   : Teacher
-- Depends  : users
-- SQL ref  : TeacherRepository.php - employee_id, first_name, last_name, email, phone, department, designation, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `teachers` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL     DEFAULT NULL COMMENT 'Linked platform user account',
    `employee_id` VARCHAR(50)     NOT NULL,
    `first_name`  VARCHAR(100)    NOT NULL,
    `last_name`   VARCHAR(100)    NOT NULL,
    `email`       VARCHAR(255)    NOT NULL,
    `phone`       VARCHAR(30)     NULL     DEFAULT NULL,
    `department`  VARCHAR(150)    NULL     DEFAULT NULL,
    `designation` VARCHAR(150)    NULL     DEFAULT NULL,
    `gender`      ENUM('male','female','other','prefer_not_to_say') NULL DEFAULT NULL,
    `date_joined` DATE            NULL     DEFAULT NULL,
    `status`      ENUM('active','inactive','on_leave','terminated') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_teachers_employee_id` (`employee_id`),
    UNIQUE KEY `uq_teachers_email` (`email`),
    KEY `idx_teachers_user_id` (`user_id`),
    KEY `idx_teachers_status` (`status`),
    KEY `idx_teachers_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_teachers_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Teacher staff records.';
