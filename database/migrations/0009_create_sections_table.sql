-- =============================================================================
-- Migration: 0009_create_sections_table
-- Table    : sections
-- Module   : Section
-- Depends  : academic_classes
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `sections` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`   BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(50)     NOT NULL COMMENT 'e.g. A, B, C',
    `capacity`   SMALLINT UNSIGNED NULL DEFAULT NULL,
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sections_class_name` (`class_id`, `name`),
    KEY `idx_sections_class_id` (`class_id`),
    KEY `idx_sections_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_sections_class`
        FOREIGN KEY (`class_id`) REFERENCES `academic_classes` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Sections (divisions) within an academic class.';
