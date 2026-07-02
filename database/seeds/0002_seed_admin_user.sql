-- =============================================================================
-- Seeder: 0002_seed_admin_user.sql
-- Inserts: Initial platform administrator account
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0001_seed_roles_and_permissions.sql
-- SECURITY: Change this password immediately after first login.
--           Default password: Admin@TNM2026
--           bcrypt hash (cost=12) generated offline.
-- =============================================================================

USE `tnm_school_platform`;

-- ---------------------------------------------------------------------------
-- Admin user
-- Password: Admin@TNM2026  (MUST be changed after first login)
-- Hash: bcrypt cost=12
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password_hash`, `is_active`) VALUES
(
    1,
    'Platform Administrator',
    'admin@tnmschool.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1
);

-- ---------------------------------------------------------------------------
-- Assign Super Administrator role to admin user
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, 1);

-- ---------------------------------------------------------------------------
-- Default academic session placeholder
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `academic_sessions` (`id`, `session_name`, `start_date`, `end_date`, `status`, `is_current`) VALUES
(1, '2026-2027', '2026-04-01', '2027-03-31', 'active', 1);

-- ---------------------------------------------------------------------------
-- Default system settings
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `system_settings` (`key`, `value`, `type`, `module`, `description`, `is_public`) VALUES
('app.name',             'T.N. Memorial Public School Digital Platform', 'string',  'System', 'Application display name', 1),
('app.timezone',         'Asia/Kolkata',                                 'string',  'System', 'Application timezone',    1),
('app.currency',         'INR',                                           'string',  'System', 'Default currency',        1),
('academic.default_session_id', '1',                                     'integer', 'AcademicSession', 'Default active session ID', 0),
('attendance.working_days', 'monday,tuesday,wednesday,thursday,friday',  'string',  'Attendance', 'Comma-separated working days', 0),
('attendance.late_threshold_minutes', '15',                              'integer', 'Attendance', 'Minutes late before marked as late', 0);