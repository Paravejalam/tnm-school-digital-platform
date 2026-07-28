-- =============================================================================
-- Seeder: 0006_seed_demo_teachers.sql
-- Inserts: Sample teaching staff records
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0001_seed_roles_and_permissions.sql (user_id FK optional)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `teachers` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `designation`, `gender`, `date_joined`, `status`) VALUES
(1, 'EMP001', 'Rajesh',    'Kumar',     'rajesh.kumar@tnmschool.local',     '9876543210', 'Mathematics',       'Senior Teacher',     'male',   '2015-06-15', 'active'),
(2, 'EMP002', 'Sunita',    'Sharma',    'sunita.sharma@tnmschool.local',    '9876543211', 'Science',           'Head of Department',  'female', '2016-04-01', 'active'),
(3, 'EMP003', 'Amit',      'Verma',     'amit.verma@tnmschool.local',      '9876543212', 'English',           'Teacher',            'male',   '2018-07-10', 'active'),
(4, 'EMP004', 'Priya',     'Singh',     'priya.singh@tnmschool.local',     '9876543213', 'Hindi',             'Teacher',            'female', '2019-04-05', 'active'),
(5, 'EMP005', 'Vikram',    'Patel',     'vikram.patel@tnmschool.local',    '9876543214', 'Social Studies',    'Teacher',            'male',   '2020-08-20', 'active'),
(6, 'EMP006', 'Neha',      'Gupta',     'neha.gupta@tnmschool.local',      '9876543215', 'Computer Science',  'Teacher',            'female', '2021-04-12', 'active'),
(7, 'EMP007', 'Suresh',    'Yadav',     'suresh.yadav@tnmschool.local',    '9876543216', 'Physical Education','Sports Coordinator', 'male',   '2017-09-01', 'active'),
(8, 'EMP008', 'Anita',     'Mishra',    'anita.mishra@tnmschool.local',    '9876543217', 'Art & Craft',       'Teacher',            'female', '2020-06-01', 'active'),
(9, 'EMP009', 'Ravi',      'Joshi',     'ravi.joshi@tnmschool.local',      '9876543218', 'Music',             'Teacher',            'male',   '2019-07-15', 'active'),
(10,'EMP010', 'Kavita',    'Das',       'kavita.das@tnmschool.local',      '9876543219', 'Sanskrit',          'Teacher',            'female', '2022-04-01', 'active');
