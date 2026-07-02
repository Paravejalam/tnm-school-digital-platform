-- =============================================================================
-- Views: database/views/views.sql
-- Authority: .github/AGENT.md
-- =============================================================================

USE `tnm_school_platform`;

-- ---------------------------------------------------------------------------
-- View: student_overview
-- Purpose: Full student record joined with class and session names.
--          Replaces denormalised class_name / section columns in queries.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `student_overview` AS
SELECT
    s.id                            AS student_id,
    s.admission_number,
    s.roll_number,
    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
    s.first_name,
    s.last_name,
    s.email,
    s.phone,
    s.date_of_birth,
    s.gender,
    s.status,
    sess.session_name               AS academic_session,
    ac.class_name,
    sec.name                        AS section_name,
    s.created_at,
    s.updated_at
FROM `students` s
LEFT JOIN `academic_sessions` sess ON sess.id = s.academic_session_id
LEFT JOIN `academic_classes`  ac   ON ac.id   = s.class_id
LEFT JOIN `sections`          sec  ON sec.id  = s.section_id
WHERE s.deleted_at IS NULL;

-- ---------------------------------------------------------------------------
-- View: teacher_overview
-- Purpose: Full teacher record with user account status.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `teacher_overview` AS
SELECT
    t.id                            AS teacher_id,
    t.employee_id,
    CONCAT(t.first_name, ' ', t.last_name) AS full_name,
    t.first_name,
    t.last_name,
    t.email,
    t.phone,
    t.department,
    t.designation,
    t.gender,
    t.date_joined,
    t.status,
    u.email                         AS user_account_email,
    u.is_active                     AS user_account_active,
    t.created_at,
    t.updated_at
FROM `teachers` t
LEFT JOIN `users` u ON u.id = t.user_id
WHERE t.deleted_at IS NULL;

-- ---------------------------------------------------------------------------
-- View: attendance_summary
-- Purpose: Aggregated attendance counts per student per session.
--          Used for reporting and dashboard widgets.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `attendance_summary` AS
SELECT
    a.student_id,
    a.academic_session_id,
    sess.session_name,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    s.admission_number,
    ac.class_name,
    sec.name                                AS section_name,
    COUNT(*)                                AS total_days,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_days,
    SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END) AS absent_days,
    SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END) AS late_days,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused_days,
    ROUND(
        100.0 * SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(*),
        2
    )                                       AS attendance_percentage
FROM `attendance` a
JOIN `students`          s    ON s.id    = a.student_id
JOIN `academic_sessions` sess ON sess.id = a.academic_session_id
LEFT JOIN `academic_classes` ac  ON ac.id  = a.class_id
LEFT JOIN `sections`         sec ON sec.id = a.section_id
WHERE a.deleted_at IS NULL
GROUP BY
    a.student_id,
    a.academic_session_id,
    sess.session_name,
    student_name,
    s.admission_number,
    ac.class_name,
    section_name;

-- ---------------------------------------------------------------------------
-- View: timetable_overview
-- Purpose: Full timetable with teacher, class, and subject names.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `timetable_overview` AS
SELECT
    tt.id                AS timetable_id,
    tt.timetable_name,
    tt.status,
    sess.session_name    AS academic_session,
    ac.class_name,
    sec.name             AS section_name,
    sub.name             AS subject_name,
    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
    t.employee_id        AS teacher_employee_id,
    tt.created_at,
    tt.updated_at
FROM `timetables` tt
JOIN  `academic_sessions` sess ON sess.id = tt.academic_session_id
JOIN  `academic_classes`  ac   ON ac.id   = tt.class_id
LEFT JOIN `sections`   sec ON sec.id  = tt.section_id
LEFT JOIN `subjects`   sub ON sub.id  = tt.subject_id
LEFT JOIN `teachers`   t   ON t.id    = tt.teacher_id
WHERE tt.deleted_at IS NULL;

-- ---------------------------------------------------------------------------
-- View: active_academic_session
-- Purpose: Returns the single currently active academic session.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE VIEW `active_academic_session` AS
SELECT *
FROM `academic_sessions`
WHERE `is_current` = 1
  AND `deleted_at` IS NULL
LIMIT 1;