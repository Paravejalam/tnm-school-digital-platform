-- =============================================================================
-- Migration: 0008_create_academic_classes_table
-- Table    : academic_classes
-- Module   : AcademicClass
-- Depends  : academic_sessions
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `academic_classes` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_name`          VARCHAR(100)    NOT NULL,
    `grade_level`         TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Numeric grade (1-12)',
    `academic_session_id` BIGINT UNSIGNED NOT NULL,
    `status`              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_academic_classes_session` (`academic_session_id`),
    KEY `idx_academic_classes_status` (`status`),
    KEY `idx_academic_classes_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_academic_classes_session`
        FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Class grade groupings per academic session.';
