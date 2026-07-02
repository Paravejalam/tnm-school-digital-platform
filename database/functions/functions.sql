-- =============================================================================
-- Functions: database/functions/functions.sql
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

DELIMITER $$

-- ---------------------------------------------------------------------------
-- Function: fn_student_attendance_percentage
-- Purpose : Returns attendance percentage for a student in a session.
-- Returns : DECIMAL(5,2) — e.g., 87.50
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS `fn_student_attendance_percentage` $$
CREATE FUNCTION `fn_student_attendance_percentage` (
    p_student_id          BIGINT UNSIGNED,
    p_academic_session_id BIGINT UNSIGNED
)
RETURNS DECIMAL(5, 2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_total   INT UNSIGNED DEFAULT 0;
    DECLARE v_present INT UNSIGNED DEFAULT 0;

    SELECT
        COUNT(*),
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END)
    INTO v_total, v_present
    FROM `attendance`
    WHERE
        student_id          = p_student_id
        AND academic_session_id = p_academic_session_id
        AND deleted_at IS NULL;

    IF v_total = 0 THEN
        RETURN 0.00;
    END IF;

    RETURN ROUND(100.0 * v_present / v_total, 2);
END $$

-- ---------------------------------------------------------------------------
-- Function: fn_current_academic_session_id
-- Purpose : Returns the ID of the currently active academic session.
-- Returns : BIGINT UNSIGNED or NULL
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS `fn_current_academic_session_id` $$
CREATE FUNCTION `fn_current_academic_session_id` ()
RETURNS BIGINT UNSIGNED
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_id BIGINT UNSIGNED DEFAULT NULL;

    SELECT `id` INTO v_id
    FROM `academic_sessions`
    WHERE `is_current` = 1
      AND `deleted_at` IS NULL
    LIMIT 1;

    RETURN v_id;
END $$

-- ---------------------------------------------------------------------------
-- Function: fn_student_full_name
-- Purpose : Returns concatenated full name for a student ID.
-- Returns : VARCHAR(201) or NULL
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS `fn_student_full_name` $$
CREATE FUNCTION `fn_student_full_name` (
    p_student_id BIGINT UNSIGNED
)
RETURNS VARCHAR(201)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_name VARCHAR(201) DEFAULT NULL;

    SELECT CONCAT(first_name, ' ', last_name)
    INTO v_name
    FROM `students`
    WHERE id = p_student_id
      AND deleted_at IS NULL
    LIMIT 1;

    RETURN v_name;
END $$

DELIMITER ;