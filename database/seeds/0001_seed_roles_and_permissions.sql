-- =============================================================================
-- Seeder: 0001_seed_roles_and_permissions.sql
-- Inserts: Default roles and platform permissions
-- Authority: .github/AGENT.md
-- Order: Run AFTER all migrations complete.
-- WARNING: This is MINIMAL data only. No demo records.
-- =============================================================================

USE `tnm_school_platform`;

-- ---------------------------------------------------------------------------
-- Default Roles
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`) VALUES
(1, 'Super Administrator', 'super-admin',  'Full access to all modules and system settings. Cannot be deleted.', 1),
(2, 'Administrator',       'admin',        'School administrator with access to all school operations.',         1),
(3, 'Teacher',             'teacher',      'Teaching staff with access to class, attendance, and timetable.',    1),
(4, 'Student',             'student',      'Enrolled student with read access to their own academic data.',      1),
(5, 'Parent',              'parent',       'Parent or guardian with read access to linked student data.',        1);

-- ---------------------------------------------------------------------------
-- Default Permissions (one per module action)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`name`, `slug`, `module`) VALUES
-- Users
('View Users',           'users.view',   'Auth'),
('Create Users',         'users.create', 'Auth'),
('Update Users',         'users.update', 'Auth'),
('Delete Users',         'users.delete', 'Auth'),
-- Roles
('View Roles',           'roles.view',   'Auth'),
('Assign Roles',         'roles.assign', 'Auth'),
-- Students
('View Students',        'students.view',   'Student'),
('Create Students',      'students.create', 'Student'),
('Update Students',      'students.update', 'Student'),
('Delete Students',      'students.delete', 'Student'),
-- Teachers
('View Teachers',        'teachers.view',   'Teacher'),
('Create Teachers',      'teachers.create', 'Teacher'),
('Update Teachers',      'teachers.update', 'Teacher'),
('Delete Teachers',      'teachers.delete', 'Teacher'),
-- Academic Sessions
('View Academic Sessions',   'academic-sessions.view',   'AcademicSession'),
('Create Academic Sessions', 'academic-sessions.create', 'AcademicSession'),
('Update Academic Sessions', 'academic-sessions.update', 'AcademicSession'),
('Delete Academic Sessions', 'academic-sessions.delete', 'AcademicSession'),
-- Classes
('View Classes',     'classes.view',   'AcademicClass'),
('Create Classes',   'classes.create', 'AcademicClass'),
('Update Classes',   'classes.update', 'AcademicClass'),
('Delete Classes',   'classes.delete', 'AcademicClass'),
-- Sections
('View Sections',    'sections.view',   'Section'),
('Create Sections',  'sections.create', 'Section'),
('Update Sections',  'sections.update', 'Section'),
('Delete Sections',  'sections.delete', 'Section'),
-- Subjects
('View Subjects',    'subjects.view',   'Subject'),
('Create Subjects',  'subjects.create', 'Subject'),
('Update Subjects',  'subjects.update', 'Subject'),
('Delete Subjects',  'subjects.delete', 'Subject'),
-- Attendance
('View Attendance',   'attendance.view',   'Attendance'),
('Mark Attendance',   'attendance.create', 'Attendance'),
('Update Attendance', 'attendance.update', 'Attendance'),
('Delete Attendance', 'attendance.delete', 'Attendance'),
-- Timetables
('View Timetables',   'timetables.view',   'Timetable'),
('Create Timetables', 'timetables.create', 'Timetable'),
('Update Timetables', 'timetables.update', 'Timetable'),
('Delete Timetables', 'timetables.delete', 'Timetable'),
-- Periods
('View Periods',      'periods.view',   'Period'),
('Create Periods',    'periods.create', 'Period'),
('Update Periods',    'periods.update', 'Period'),
('Delete Periods',    'periods.delete', 'Period'),
-- Holiday Calendar
('View Holiday Calendar',   'holiday-calendars.view',   'HolidayCalendar'),
('Create Holiday Calendar', 'holiday-calendars.create', 'HolidayCalendar'),
('Update Holiday Calendar', 'holiday-calendars.update', 'HolidayCalendar'),
('Delete Holiday Calendar', 'holiday-calendars.delete', 'HolidayCalendar'),
-- System
('View System Settings',   'system-settings.view',   'System'),
('Update System Settings', 'system-settings.update', 'System'),
('View Audit Logs',        'audit-logs.view',         'System');

-- ---------------------------------------------------------------------------
-- Assign ALL permissions to Super Administrator
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- ---------------------------------------------------------------------------
-- Assign admin permissions (all except system settings management)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `slug` NOT IN ('system-settings.update', 'audit-logs.view');

-- ---------------------------------------------------------------------------
-- Teacher permissions
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions`
WHERE `slug` IN (
    'students.view',
    'attendance.view', 'attendance.create', 'attendance.update',
    'timetables.view',
    'periods.view',
    'subjects.view',
    'sections.view',
    'classes.view',
    'academic-sessions.view',
    'holiday-calendars.view'
);

-- ---------------------------------------------------------------------------
-- Student permissions (read-only own data)
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions`
WHERE `slug` IN (
    'attendance.view',
    'timetables.view',
    'periods.view',
    'subjects.view',
    'holiday-calendars.view',
    'academic-sessions.view'
);