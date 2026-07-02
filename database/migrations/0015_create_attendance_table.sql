-- =============================================================================
-- Migration: 0015_create_attendance_table
-- Table    : attendance
-- Module   : Attendance
-- Depends  : academic_sessions, academic_classes, sections, students
-- SQL ref  : AttendanceRepository.php - attendance_date, academic_session_id, class_id, section_id, student_id, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `attendance` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attendance_date`     DATE            NOT NULL,
    `academic_session_id` BIGINT UNSIGNED NOT NULL,
    `class_id`            BIGINT UNSIGNED NOT NULL,
    `section_id`          BIGINT UNSIGNED NULL DEFAULT NULL,
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `status`              ENUM('present','absent','late','excused','holiday') NOT NULL DEFAULT 'present',
    `remarks`             TEXT            NULL,
    `marked_by`           BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Teacher or admin user_id',
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_attendance_student_date` (`student_id`, `attendance_date`, `academic_session_id`),
    KEY `idx_attendance_date` (`attendance_date`),
    KEY `idx_attendance_session` (`academic_session_id`),
    KEY `idx_attendance_class` (`class_id`),
    KEY `idx_attendance_section` (`section_id`),
    KEY `idx_attendance_status` (`status`),
    KEY `idx_attendance_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_attendance_session`
        FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_attendance_class`
        FOREIGN KEY (`class_id`) REFERENCES `academic_classes` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_attendance_section`
        FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_attendance_student`
        FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily student attendance records.';
