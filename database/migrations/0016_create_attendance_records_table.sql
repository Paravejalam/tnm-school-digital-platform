-- =============================================================================
-- Migration: 0016_create_attendance_records_table
-- Table    : attendance_records
-- Module   : AttendanceRecord
-- Depends  : attendance
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attendance_id` BIGINT UNSIGNED NOT NULL,
    `status`        ENUM('present','absent','late','excused','holiday') NOT NULL DEFAULT 'present',
    `note`          TEXT            NULL,
    `recorded_by`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `recorded_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_attendance_records_attendance_id` (`attendance_id`),
    KEY `idx_attendance_records_status` (`status`),
    KEY `idx_attendance_records_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_attendance_records_attendance`
        FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detailed individual attendance record entries.';
