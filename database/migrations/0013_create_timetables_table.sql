-- =============================================================================
-- Migration: 0013_create_timetables_table
-- Table    : timetables
-- Module   : Timetable
-- Depends  : academic_sessions, academic_classes, sections, subjects, teachers
-- SQL ref  : TimetableRepository.php - timetable_name, academic_session_id, class_id, section_id, subject_id, teacher_id, status
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

CREATE TABLE IF NOT EXISTS `timetables` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `timetable_name`      VARCHAR(200)    NOT NULL,
    `academic_session_id` BIGINT UNSIGNED NOT NULL,
    `class_id`            BIGINT UNSIGNED NOT NULL,
    `section_id`          BIGINT UNSIGNED NULL DEFAULT NULL,
    `subject_id`          BIGINT UNSIGNED NULL DEFAULT NULL,
    `teacher_id`          BIGINT UNSIGNED NULL DEFAULT NULL,
    `status`              ENUM('active','inactive','draft') NOT NULL DEFAULT 'active',
    `created_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP       NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_timetables_session` (`academic_session_id`),
    KEY `idx_timetables_class` (`class_id`),
    KEY `idx_timetables_section` (`section_id`),
    KEY `idx_timetables_teacher` (`teacher_id`),
    KEY `idx_timetables_status` (`status`),
    KEY `idx_timetables_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_timetables_session`
        FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_timetables_class`
        FOREIGN KEY (`class_id`) REFERENCES `academic_classes` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_timetables_section`
        FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_timetables_subject`
        FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_timetables_teacher`
        FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Class timetable assignments.';
