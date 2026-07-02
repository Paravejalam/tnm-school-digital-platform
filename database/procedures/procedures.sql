-- =============================================================================
-- Stored Procedures: database/procedures/procedures.sql
-- Authority: .github/AGENT.md
-- Note: These are production-ready stored procedures for reporting and search.
-- =============================================================================

USE `tnm_school_platform`;

DELIMITER $$

-- ---------------------------------------------------------------------------
-- Procedure: sp_attendance_report
-- Purpose  : Returns attendance summary for a class within a date range.
-- Parameters:
--   p_academic_session_id  BIGINT  — Session to report on
--   p_class_id             BIGINT  — Class to report on (NULL = all classes)
--   p_date_from            DATE    — Start date inclusive
--   p_date_to              DATE    — End date inclusive
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_attendance_report` $$
CREATE PROCEDURE `sp_attendance_report` (
    IN p_academic_session_id BIGINT UNSIGNED,
    IN p_class_id            BIGINT UNSIGNED,
    IN p_date_from           DATE,
    IN p_date_to             DATE
)
BEGIN
    SELECT
        a.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.admission_number,
        ac.class_name,
        sec.name                     AS section_name,
        COUNT(*)                     AS total_records,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END) AS late_count,
        SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused_count,
        ROUND(
            100.0 * SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*),
            2
        ) AS attendance_percentage
    FROM `attendance` a
    JOIN `students`          s   ON s.id   = a.student_id
    JOIN `academic_classes`  ac  ON ac.id  = a.class_id
    LEFT JOIN `sections`     sec ON sec.id = a.section_id
    WHERE
        a.deleted_at IS NULL
        AND a.academic_session_id = p_academic_session_id
        AND (p_class_id IS NULL OR a.class_id = p_class_id)
        AND a.attendance_date BETWEEN p_date_from AND p_date_to
    GROUP BY
        a.student_id,
        student_name,
        s.admission_number,
        ac.class_name,
        section_name
    ORDER BY
        ac.class_name,
        section_name,
        student_name;
END $$

-- ---------------------------------------------------------------------------
-- Procedure: sp_search_students
-- Purpose  : Paginated student search with optional filters.
-- Parameters:
--   p_name          VARCHAR — Partial match on full name (NULL = skip)
--   p_admission_no  VARCHAR — Exact match on admission number (NULL = skip)
--   p_class_id      BIGINT  — Filter by class (NULL = all)
--   p_status        VARCHAR — Filter by status (NULL = all)
--   p_page          INT     — Page number (1-based)
--   p_per_page      INT     — Records per page
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_search_students` $$
CREATE PROCEDURE `sp_search_students` (
    IN p_name         VARCHAR(200),
    IN p_admission_no VARCHAR(50),
    IN p_class_id     BIGINT UNSIGNED,
    IN p_status       VARCHAR(50),
    IN p_page         INT UNSIGNED,
    IN p_per_page     INT UNSIGNED
)
BEGIN
    DECLARE v_offset INT UNSIGNED DEFAULT (p_page - 1) * p_per_page;

    SELECT
        s.id,
        s.admission_number,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) AS full_name,
        s.email,
        s.phone,
        ac.class_name,
        sec.name   AS section_name,
        sess.session_name AS academic_session,
        s.status,
        s.created_at
    FROM `students` s
    LEFT JOIN `academic_classes`  ac   ON ac.id   = s.class_id
    LEFT JOIN `sections`          sec  ON sec.id  = s.section_id
    LEFT JOIN `academic_sessions` sess ON sess.id = s.academic_session_id
    WHERE
        s.deleted_at IS NULL
        AND (p_name         IS NULL OR CONCAT(s.first_name, ' ', s.last_name) LIKE CONCAT('%', p_name, '%'))
        AND (p_admission_no IS NULL OR s.admission_number = p_admission_no)
        AND (p_class_id     IS NULL OR s.class_id = p_class_id)
        AND (p_status       IS NULL OR s.status = p_status)
    ORDER BY s.id DESC
    LIMIT p_per_page OFFSET v_offset;
END $$

-- ---------------------------------------------------------------------------
-- Procedure: sp_search_teachers
-- Purpose  : Paginated teacher search with optional filters.
-- Parameters:
--   p_name         VARCHAR — Partial match on full name (NULL = skip)
--   p_employee_id  VARCHAR — Exact match on employee ID (NULL = skip)
--   p_department   VARCHAR — Exact match on department (NULL = skip)
--   p_status       VARCHAR — Filter by status (NULL = all)
--   p_page         INT     — Page number (1-based)
--   p_per_page     INT     — Records per page
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_search_teachers` $$
CREATE PROCEDURE `sp_search_teachers` (
    IN p_name        VARCHAR(200),
    IN p_employee_id VARCHAR(50),
    IN p_department  VARCHAR(150),
    IN p_status      VARCHAR(50),
    IN p_page        INT UNSIGNED,
    IN p_per_page    INT UNSIGNED
)
BEGIN
    DECLARE v_offset INT UNSIGNED DEFAULT (p_page - 1) * p_per_page;

    SELECT
        t.id,
        t.employee_id,
        CONCAT(t.first_name, ' ', t.last_name) AS full_name,
        t.email,
        t.phone,
        t.department,
        t.designation,
        t.status,
        t.date_joined,
        t.created_at
    FROM `teachers` t
    WHERE
        t.deleted_at IS NULL
        AND (p_name        IS NULL OR CONCAT(t.first_name, ' ', t.last_name) LIKE CONCAT('%', p_name, '%'))
        AND (p_employee_id IS NULL OR t.employee_id = p_employee_id)
        AND (p_department  IS NULL OR t.department = p_department)
        AND (p_status      IS NULL OR t.status = p_status)
    ORDER BY t.id DESC
    LIMIT p_per_page OFFSET v_offset;
END $$

DELIMITER ;