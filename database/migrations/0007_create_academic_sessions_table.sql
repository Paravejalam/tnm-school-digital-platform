-- =============================================================================
-- Migration: 0007_create_academic_sessions_table
-- Table    : academic_sessions
-- Module   : AcademicSession
-- SQL ref  : AcademicSessionRepository.php - session_name, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `academic_sessions` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_name` VARCHAR(100)    NOT NULL,
    `start_date`   DATE            NOT NULL,
    `end_date`     DATE            NOT NULL,
    `status`       ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    `is_current`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Only one session active at a time',
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_academic_sessions_name` (`session_name`),
    KEY `idx_academic_sessions_status` (`status`),
    KEY `idx_academic_sessions_is_current` (`is_current`),
    KEY `idx_academic_sessions_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='School academic years/sessions.';
