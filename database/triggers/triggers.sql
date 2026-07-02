-- =============================================================================
-- Triggers: database/triggers/triggers.sql
-- Authority: .github/AGENT.md Section 24.9 — Audit Logging
-- Note: Triggers run server-side; they do not depend on PHP runtime.
-- =============================================================================

USE `tnm_school_platform`;

DELIMITER $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_users_audit_insert
-- Purpose : Log new user creation to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_users_audit_insert` $$
CREATE TRIGGER `trg_users_audit_insert`
AFTER INSERT ON `users`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `new_values`)
    VALUES (
        NEW.id,
        'created',
        'User',
        NEW.id,
        JSON_OBJECT(
            'id',    NEW.id,
            'name',  NEW.name,
            'email', NEW.email
        )
    );
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_users_audit_update
-- Purpose : Log user record changes to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_users_audit_update` $$
CREATE TRIGGER `trg_users_audit_update`
AFTER UPDATE ON `users`
FOR EACH ROW
BEGIN
    IF (OLD.name <> NEW.name OR OLD.email <> NEW.email OR OLD.is_active <> NEW.is_active) THEN
        INSERT INTO `audit_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`)
        VALUES (
            NEW.id,
            'updated',
            'User',
            NEW.id,
            JSON_OBJECT('name', OLD.name, 'email', OLD.email, 'is_active', OLD.is_active),
            JSON_OBJECT('name', NEW.name, 'email', NEW.email, 'is_active', NEW.is_active)
        );
    END IF;
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_students_audit_insert
-- Purpose : Log new student enrollment to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_students_audit_insert` $$
CREATE TRIGGER `trg_students_audit_insert`
AFTER INSERT ON `students`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`action`, `entity_type`, `entity_id`, `new_values`)
    VALUES (
        'created',
        'Student',
        NEW.id,
        JSON_OBJECT(
            'id',               NEW.id,
            'admission_number', NEW.admission_number,
            'first_name',       NEW.first_name,
            'last_name',        NEW.last_name,
            'status',           NEW.status
        )
    );
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_students_soft_delete_audit
-- Purpose : Log student soft-delete (deleted_at set) to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_students_soft_delete_audit` $$
CREATE TRIGGER `trg_students_soft_delete_audit`
AFTER UPDATE ON `students`
FOR EACH ROW
BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO `audit_logs` (`action`, `entity_type`, `entity_id`, `old_values`)
        VALUES (
            'deleted',
            'Student',
            OLD.id,
            JSON_OBJECT(
                'id',               OLD.id,
                'admission_number', OLD.admission_number,
                'status',           OLD.status
            )
        );
    END IF;
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_teachers_audit_insert
-- Purpose : Log new teacher record to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_teachers_audit_insert` $$
CREATE TRIGGER `trg_teachers_audit_insert`
AFTER INSERT ON `teachers`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`action`, `entity_type`, `entity_id`, `new_values`)
    VALUES (
        'created',
        'Teacher',
        NEW.id,
        JSON_OBJECT(
            'id',          NEW.id,
            'employee_id', NEW.employee_id,
            'first_name',  NEW.first_name,
            'last_name',   NEW.last_name,
            'status',      NEW.status
        )
    );
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_attendance_audit_insert
-- Purpose : Log attendance marking to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_attendance_audit_insert` $$
CREATE TRIGGER `trg_attendance_audit_insert`
AFTER INSERT ON `attendance`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`action`, `entity_type`, `entity_id`, `new_values`)
    VALUES (
        'created',
        'Attendance',
        NEW.id,
        JSON_OBJECT(
            'student_id',      NEW.student_id,
            'attendance_date', NEW.attendance_date,
            'status',          NEW.status
        )
    );
END $$

-- ---------------------------------------------------------------------------
-- Trigger: trg_attendance_audit_update
-- Purpose : Log attendance status change to audit_logs.
-- ---------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_attendance_audit_update` $$
CREATE TRIGGER `trg_attendance_audit_update`
AFTER UPDATE ON `attendance`
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO `audit_logs` (`action`, `entity_type`, `entity_id`, `old_values`, `new_values`)
        VALUES (
            'updated',
            'Attendance',
            NEW.id,
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status)
        );
    END IF;
END $$

DELIMITER ;