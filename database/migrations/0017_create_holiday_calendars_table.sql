-- =============================================================================
-- Migration: 0017_create_holiday_calendars_table
-- Table    : holiday_calendars
-- Module   : HolidayCalendar
-- Depends  : academic_sessions
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `holiday_calendars` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `academic_session_id` BIGINT UNSIGNED NULL     DEFAULT NULL,
    `holiday_name`        VARCHAR(200)    NOT NULL,
    `holiday_date`        DATE            NOT NULL,
    `holiday_type`        ENUM('national','regional','school','religious','other') NOT NULL DEFAULT 'school',
    `is_recurring`        TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1 = repeats every year',
    `description`         TEXT            NULL,
    `status`              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_holiday_calendars_date` (`holiday_date`),
    KEY `idx_holiday_calendars_session` (`academic_session_id`),
    KEY `idx_holiday_calendars_type` (`holiday_type`),
    KEY `idx_holiday_calendars_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_holiday_calendars_session`
        FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='School holiday and calendar events.';