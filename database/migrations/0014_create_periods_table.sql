-- =============================================================================
-- Migration: 0014_create_periods_table
-- Table    : periods
-- Module   : Period
-- Depends  : timetables
-- SQL ref  : PeriodRepository.php - period_name, timetable_id, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `periods` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `timetable_id` BIGINT UNSIGNED NOT NULL,
    `period_name`  VARCHAR(100)    NOT NULL,
    `day_of_week`  ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `start_time`   TIME            NOT NULL,
    `end_time`     TIME            NOT NULL,
    `period_order` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT 'Sequence number within a day',
    `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_periods_timetable_id` (`timetable_id`),
    KEY `idx_periods_day` (`day_of_week`),
    KEY `idx_periods_status` (`status`),
    KEY `idx_periods_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_periods_timetable`
        FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual time periods within a timetable.';
