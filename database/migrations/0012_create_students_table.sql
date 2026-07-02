-- =============================================================================
-- Migration: 0012_create_students_table
-- Table    : students
-- Module   : Student
-- Depends  : users, academic_sessions, academic_classes, sections
-- SQL ref  : StudentRepository.php - admission_number, first_name, last_name, email, phone, class_name, section, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `students` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`             BIGINT UNSIGNED NULL     DEFAULT NULL COMMENT 'Linked platform user account',
    `admission_number`    VARCHAR(50)     NOT NULL,
    `roll_number`         VARCHAR(30)     NULL     DEFAULT NULL,
    `first_name`          VARCHAR(100)    NOT NULL,
    `last_name`           VARCHAR(100)    NOT NULL,
    `email`               VARCHAR(255)    NULL     DEFAULT NULL,
    `phone`               VARCHAR(30)     NULL     DEFAULT NULL,
    `date_of_birth`       DATE            NULL     DEFAULT NULL,
    `gender`              ENUM('male','female','other','prefer_not_to_say') NULL DEFAULT NULL,
    `academic_session_id` BIGINT UNSIGNED NULL     DEFAULT NULL,
    `class_id`            BIGINT UNSIGNED NULL     DEFAULT NULL,
    `section_id`          BIGINT UNSIGNED NULL     DEFAULT NULL,
    `class_name`          VARCHAR(100)    NULL     DEFAULT NULL COMMENT 'Denormalised for query compatibility',
    `section`             VARCHAR(50)     NULL     DEFAULT NULL COMMENT 'Denormalised for query compatibility',
    `status`              ENUM('active','inactive','graduated','transferred','withdrawn') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_students_admission_number` (`admission_number`),
    KEY `idx_students_roll_number` (`roll_number`),
    KEY `idx_students_user_id` (`user_id`),
    KEY `idx_students_session_id` (`academic_session_id`),
    KEY `idx_students_class_id` (`class_id`),
    KEY `idx_students_section_id` (`section_id`),
    KEY `idx_students_status` (`status`),
    KEY `idx_students_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_students_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_students_session`
        FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_students_class`
        FOREIGN KEY (`class_id`) REFERENCES `academic_classes` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_students_section`
        FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Student enrollment records.';
