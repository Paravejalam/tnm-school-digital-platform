-- =============================================================================
-- Seeder: 0008_seed_demo_holiday_calendars.sql
-- Inserts: Indian school holidays for the 2026-2027 academic session
-- Authority: .github/AGENT.md
-- Order: Run AFTER 0002_seed_admin_user.sql (academic_session_id FK optional)
-- =============================================================================

USE `tnm_school_platform`;

INSERT IGNORE INTO `holiday_calendars` (`id`, `academic_session_id`, `holiday_name`, `holiday_date`, `holiday_type`, `is_recurring`, `description`, `status`) VALUES
(1,  1, 'Independence Day',              '2026-08-15', 'national',  1, 'Independence Day — flag hoisting ceremony',                     'active'),
(2,  1, 'Raksha Bandhan',                '2026-08-19', 'religious', 1, 'Raksha Bandhan festival',                                       'active'),
(3,  1, 'Janmashtami',                   '2026-08-27', 'religious', 1, 'Sri Krishna Janmashtami',                                        'active'),
(4,  1, 'Teachers Day',                  '2026-09-05', 'school',    1, 'Teachers Day celebration — half-day',                            'active'),
(5,  1, 'Gandhi Jayanti',                '2026-10-02', 'national',  1, 'Mahatma Gandhi Jayanti',                                         'active'),
(6,  1, 'Dussehra Break',                '2026-10-17', 'school',    0, 'Dussehra break from Oct 17 to Oct 22',                          'active'),
(7,  1, 'Diwali Break Start',            '2026-10-30', 'religious', 0, 'Diwali break from Oct 30 to Nov 5',                             'active'),
(8,  1, 'Childrens Day',                 '2026-11-14', 'school',    1, 'Childrens Day celebration — cultural events',                   'active'),
(9,  1, 'Christmas',                     '2026-12-25', 'religious', 1, 'Christmas Day',                                                  'active'),
(10, 1, 'Winter Break Start',            '2026-12-28', 'school',    0, 'Winter break from Dec 28 to Jan 3',                             'active'),
(11, 1, 'Republic Day',                  '2027-01-26', 'national',  1, 'Republic Day — parade and cultural programme',                  'active'),
(12, 1, 'Maha Shivaratri',               '2027-02-17', 'religious', 1, 'Maha Shivaratri',                                                'active'),
(13, 1, 'Holi',                          '2027-03-06', 'religious', 1, 'Holi — festival of colours',                                    'active'),
(14, 1, 'End of Session',                '2027-03-31', 'school',    0, 'Last working day of academic session 2026-2027',                'active');
