-- =============================================================================
-- Migration: 0010_create_subjects_table
-- Table    : subjects
-- Module   : Subject
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `subjects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)    NOT NULL,
    `code`        VARCHAR(50)     NULL DEFAULT NULL,
    `description` TEXT            NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subjects_code` (`code`),
    KEY `idx_subjects_status` (`status`),
    KEY `idx_subjects_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subject catalogue.';
