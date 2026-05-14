-- ============================================================
-- ClimateShield AI — Video Demo Seed
-- Child Immunization Tracker
--
-- HOW TO RUN (after docker compose up -d):
--   docker compose exec db mysql -u root cvs < docker/demo_seed.sql
--
-- This resets all demo data to a clean, video-ready state.
-- All accounts use password: Admin@1234
-- ============================================================

USE `cvs`;

-- ── Disable FK checks so we can truncate in any order ──────
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `system_logs`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `caregiver_assignments`;
TRUNCATE TABLE `vaccination_cards`;
TRUNCATE TABLE `vaccination_schedule`;
TRUNCATE TABLE `children`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `vaccines`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Users
-- Password hash = bcrypt of "Admin@1234"
-- ============================================================
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`, `location`, `id_number`, `created_at`) VALUES
(1, 'System Admin',        'admin@jarida.io',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254700000001', 'admin',           'Nairobi', '12345678', NOW()),
(2, 'Dr. Amara Osei',      'doctor@jarida.io',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254711000002', 'doctor',          'Kisumu',  '23456789', NOW()),
(3, 'Grace Wambui',        'grace@jarida.io',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254722000003', 'Guardian',        'Nairobi', '34567890', NOW()),
-- Kisumu parent — ties to ClimateShield HIGH-risk zone
(4, 'Akinyi Otieno',       'akinyi@jarida.io',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254733000004', 'Guardian',        'Kisumu',  '45678901', NOW()),
(5, 'CHW Fatuma Ali',      'chw@jarida.io',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+254744000005', 'SocialCaregiver', 'Kisumu',  '56789012', NOW());

-- ============================================================
-- Kenya KEPI Vaccines
-- ============================================================
INSERT INTO `vaccines` (`vaccine_id`, `name`, `disease_prevented`, `recommended_age`, `recommended_age_days`, `dose_number`, `dose_description`, `route_of_administration`, `site_of_administration`, `side_effects`) VALUES
( 1, 'BCG',              'Tuberculosis',                                          'At birth',   0,   1, 'Single dose at birth',              'Intradermal',   'Right upper arm', 'Small blister, mild fever'),
( 2, 'OPV 0',           'Polio',                                                  'At birth',   0,   1, 'Birth dose oral polio vaccine',     'Oral',          'Mouth',           'Rare mild diarrhoea'),
( 3, 'OPV 1',           'Polio',                                                  '6 weeks',   42,   1, 'First dose oral polio vaccine',     'Oral',          'Mouth',           'Rare mild diarrhoea'),
( 4, 'OPV 2',           'Polio',                                                  '10 weeks',  70,   2, 'Second dose oral polio vaccine',    'Oral',          'Mouth',           'Rare mild diarrhoea'),
( 5, 'OPV 3',           'Polio',                                                  '14 weeks',  98,   3, 'Third dose oral polio vaccine',     'Oral',          'Mouth',           'Rare mild diarrhoea'),
( 6, 'DPT-HepB-Hib 1',  'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib',     '6 weeks',   42,   1, 'First pentavalent dose',            'Intramuscular', 'Left thigh',      'Redness, mild fever'),
( 7, 'DPT-HepB-Hib 2',  'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib',     '10 weeks',  70,   2, 'Second pentavalent dose',           'Intramuscular', 'Left thigh',      'Redness, mild fever'),
( 8, 'DPT-HepB-Hib 3',  'Diphtheria, Pertussis, Tetanus, Hepatitis B, Hib',     '14 weeks',  98,   3, 'Third pentavalent dose',            'Intramuscular', 'Left thigh',      'Redness, mild fever'),
( 9, 'PCV 1',           'Pneumococcal disease',                                   '6 weeks',   42,   1, 'First pneumococcal dose',           'Intramuscular', 'Right thigh',     'Mild redness, fever'),
(10, 'PCV 2',           'Pneumococcal disease',                                   '10 weeks',  70,   2, 'Second pneumococcal dose',          'Intramuscular', 'Right thigh',     'Mild redness, fever'),
(11, 'PCV 3',           'Pneumococcal disease',                                   '14 weeks',  98,   3, 'Third pneumococcal dose',           'Intramuscular', 'Right thigh',     'Mild redness, fever'),
(12, 'RV 1',            'Rotavirus',                                              '6 weeks',   42,   1, 'First rotavirus dose',              'Oral',          'Mouth',           'Mild diarrhoea'),
(13, 'RV 2',            'Rotavirus',                                              '10 weeks',  70,   2, 'Second rotavirus dose',             'Oral',          'Mouth',           'Mild diarrhoea'),
(14, 'IPV',             'Polio',                                                  '14 weeks',  98,   1, 'Inactivated polio vaccine',         'Intramuscular', 'Right thigh',     'Mild soreness'),
(15, 'Measles 1',       'Measles',                                                '9 months',  270,  1, 'First measles dose',                'Subcutaneous',  'Right upper arm', 'Mild rash, low fever'),
(16, 'Measles 2 / Rubella', 'Measles, Rubella',                                  '18 months', 540,  2, 'Second measles-rubella dose',       'Subcutaneous',  'Right upper arm', 'Mild rash, low fever'),
(17, 'Yellow Fever',    'Yellow Fever',                                           '9 months',  270,  1, 'Single lifetime dose',              'Subcutaneous',  'Right upper arm', 'Mild fever, headache'),
(18, 'Vitamin A',       'Vitamin A deficiency',                                   '6 months',  180,  1, 'Supplement at 6 months',            'Oral',          'Mouth',           'Rare nausea'),
(19, 'Vitamin A 2',     'Vitamin A deficiency',                                   '12 months', 365,  2, 'Supplement at 12 months',           'Oral',          'Mouth',           'Rare nausea');

-- ============================================================
-- Children
-- child 1 & 2 → Grace Wambui, Nairobi  (good compliance story)
-- child 3 & 4 → Akinyi Otieno, Kisumu  (missed doses = ClimateShield alert)
-- ============================================================
INSERT INTO `children` (`child_id`, `name`, `date_of_birth`, `gender`, `health_id`, `guardian_id`, `created_at`) VALUES
(1, 'Amara Wambui',   '2024-08-15', 'Female', 'KE-2024-0001', 3, DATE_SUB(NOW(), INTERVAL 270 DAY)),
(2, 'Brian Wambui',   '2023-03-10', 'Male',   'KE-2023-0045', 3, DATE_SUB(NOW(), INTERVAL 400 DAY)),
(3, 'Zuri Otieno',    '2024-05-01', 'Female', 'KE-2024-0112', 4, DATE_SUB(NOW(), INTERVAL 200 DAY)),
(4, 'Kofi Otieno',    '2023-08-22', 'Male',   'KE-2023-0098', 4, DATE_SUB(NOW(), INTERVAL 380 DAY));

-- ============================================================
-- Vaccination Schedule — Amara Wambui (Nairobi, mostly on track)
-- ============================================================
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(1,  1,  '2024-08-15', 1, 'Completed', '2024-08-15', 2),
(1,  2,  '2024-08-15', 1, 'Completed', '2024-08-15', 2),
(1,  3,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),
(1,  6,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),
(1,  9,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),
(1, 12,  '2024-09-26', 1, 'Completed', '2024-09-26', 2),
(1,  4,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),
(1,  7,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),
(1, 10,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),
(1, 13,  '2024-10-24', 2, 'Completed', '2024-10-24', 2),
(1,  5,  '2024-11-21', 3, 'Completed', '2024-11-21', 2),
(1,  8,  '2024-11-21', 3, 'Completed', '2024-11-21', 2),
(1, 11,  '2024-11-21', 3, 'Completed', '2024-11-21', 2),
(1, 14,  '2024-11-21', 1, 'Completed', '2024-11-21', 2),
(1, 18,  '2025-02-15', 1, 'Completed', '2025-02-15', 2),
(1, 15,  '2025-05-12', 1, 'Missed',    NULL, NULL),      -- Measles 1 — MISSED (demo focus)
(1, 17,  '2025-05-12', 1, 'Missed',    NULL, NULL),      -- Yellow Fever — MISSED
(1, 19,  '2025-08-15', 2, 'Pending',   NULL, NULL),
(1, 16,  '2026-02-15', 2, 'Pending',   NULL, NULL);

-- ── Brian Wambui — fully up to date ─────────────────────────
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(2,  1,  '2023-03-10', 1, 'Completed', '2023-03-10', 2),
(2,  2,  '2023-03-10', 1, 'Completed', '2023-03-10', 2),
(2,  3,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2,  6,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2,  9,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2, 12,  '2023-04-21', 1, 'Completed', '2023-04-21', 2),
(2,  4,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2,  7,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 10,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2, 13,  '2023-05-19', 2, 'Completed', '2023-05-19', 2),
(2,  5,  '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2,  8,  '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2, 11,  '2023-06-16', 3, 'Completed', '2023-06-16', 2),
(2, 14,  '2023-06-16', 1, 'Completed', '2023-06-16', 2),
(2, 18,  '2023-09-10', 1, 'Completed', '2023-09-10', 2),
(2, 15,  '2023-12-05', 1, 'Completed', '2023-12-05', 2),
(2, 17,  '2023-12-05', 1, 'Completed', '2023-12-05', 2),
(2, 19,  '2024-03-10', 2, 'Completed', '2024-03-10', 2),
(2, 16,  '2024-09-10', 2, 'Completed', '2024-09-10', 2);

-- ── Zuri Otieno (Kisumu) — multiple missed doses, HIGH-risk county ──
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(3,  1,  '2024-05-01', 1, 'Completed', '2024-05-01', 2),
(3,  2,  '2024-05-01', 1, 'Completed', '2024-05-01', 2),
(3,  3,  '2024-06-12', 1, 'Completed', '2024-06-12', 2),
(3,  6,  '2024-06-12', 1, 'Completed', '2024-06-12', 2),
(3,  9,  '2024-06-12', 1, 'Completed', '2024-06-12', 2),
(3, 12,  '2024-06-12', 1, 'Completed', '2024-06-12', 2),
(3,  4,  '2024-07-10', 2, 'Missed',    NULL, NULL),  -- OPV 2 missed
(3,  7,  '2024-07-10', 2, 'Missed',    NULL, NULL),  -- DPT 2 missed
(3, 10,  '2024-07-10', 2, 'Missed',    NULL, NULL),  -- PCV 2 missed
(3, 13,  '2024-07-10', 2, 'Missed',    NULL, NULL),  -- RV 2 missed
(3,  5,  '2024-08-07', 3, 'Missed',    NULL, NULL),
(3,  8,  '2024-08-07', 3, 'Missed',    NULL, NULL),
(3, 11,  '2024-08-07', 3, 'Missed',    NULL, NULL),
(3, 14,  '2024-08-07', 1, 'Missed',    NULL, NULL),
(3, 18,  '2024-11-01', 1, 'Pending',   NULL, NULL),
(3, 15,  '2025-02-01', 1, 'Pending',   NULL, NULL),
(3, 17,  '2025-02-01', 1, 'Pending',   NULL, NULL),
(3, 19,  '2025-05-01', 2, 'Pending',   NULL, NULL),
(3, 16,  '2025-11-01', 2, 'Pending',   NULL, NULL);

-- ── Kofi Otieno (Kisumu) — partially completed ──────────────
INSERT INTO `vaccination_schedule` (`child_id`, `vaccine_id`, `due_date`, `dose_number`, `status`, `completed_date`, `administered_by`) VALUES
(4,  1,  '2023-08-22', 1, 'Completed', '2023-08-22', 2),
(4,  2,  '2023-08-22', 1, 'Completed', '2023-08-22', 2),
(4,  3,  '2023-10-03', 1, 'Completed', '2023-10-03', 2),
(4,  6,  '2023-10-03', 1, 'Completed', '2023-10-03', 2),
(4,  9,  '2023-10-03', 1, 'Completed', '2023-10-03', 2),
(4, 12,  '2023-10-03', 1, 'Completed', '2023-10-03', 2),
(4,  4,  '2023-10-31', 2, 'Completed', '2023-10-31', 2),
(4,  7,  '2023-10-31', 2, 'Completed', '2023-10-31', 2),
(4, 10,  '2023-10-31', 2, 'Completed', '2023-10-31', 2),
(4, 13,  '2023-10-31', 2, 'Completed', '2023-10-31', 2),
(4,  5,  '2023-11-28', 3, 'Completed', '2023-11-28', 2),
(4,  8,  '2023-11-28', 3, 'Completed', '2023-11-28', 2),
(4, 11,  '2023-11-28', 3, 'Completed', '2023-11-28', 2),
(4, 14,  '2023-11-28', 1, 'Completed', '2023-11-28', 2),
(4, 18,  '2024-02-22', 1, 'Completed', '2024-02-22', 2),
(4, 15,  '2024-05-18', 1, 'Missed',    NULL, NULL),  -- Measles 1 missed — ClimateShield alert
(4, 17,  '2024-05-18', 1, 'Missed',    NULL, NULL),  -- Yellow Fever missed
(4, 19,  '2024-08-22', 2, 'Pending',   NULL, NULL),
(4, 16,  '2025-02-22', 2, 'Pending',   NULL, NULL);

-- ============================================================
-- Notifications
-- ============================================================
INSERT INTO `notifications` (`user_id`, `child_id`, `message`, `is_read`, `status`, `created_at`) VALUES
-- Grace / Nairobi
(3, 1, '⚠ Missed vaccination: Measles 1 was due May 12, 2025 — please rebook at your nearest clinic.', 0, 'Pending', NOW()),
(3, 1, '⚠ Missed vaccination: Yellow Fever was due May 12, 2025 — please rebook.', 0, 'Pending', NOW()),
(3, 1, 'Upcoming: Vitamin A 2 due August 15, 2025 — clinic reminder.', 1, 'Sent', DATE_SUB(NOW(), INTERVAL 7 DAY)),
-- Akinyi / Kisumu — ClimateShield AI generated alerts
(4, 3, '🚨 CLIMATE ALERT: High cholera risk detected in Kisumu (74mm rainfall forecast). Zuri has 8 missed/pending doses — visit Kisumu County Referral Hospital urgently.', 0, 'Pending', NOW()),
(4, 3, '🚨 CLIMATE ALERT: High malaria risk in Kisumu. Zuri Otieno is under-vaccinated. SMS sent via Africa\'s Talking.', 0, 'Pending', NOW()),
(4, 4, '⚠ Missed vaccination: Measles 1 was due May 18, 2024 for Kofi. Rebook now — cholera outbreak risk HIGH in Kisumu.', 0, 'Pending', NOW()),
(4, 4, '⚠ Missed vaccination: Yellow Fever was due May 18, 2024 for Kofi. Please visit your CHW.', 0, 'Pending', NOW());

-- ============================================================
-- Caregiver assignment — CHW Fatuma covers Kisumu children
-- ============================================================
INSERT INTO `caregiver_assignments` (`caregiver_id`, `child_id`, `assigned_at`) VALUES
(5, 3, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5, 4, DATE_SUB(NOW(), INTERVAL 30 DAY));

-- ============================================================
-- System Logs
-- ============================================================
INSERT INTO `system_logs` (`description`, `user_id`, `created_at`) VALUES
('User logged in',                              3, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Child registered: Amara Wambui',              3, DATE_SUB(NOW(), INTERVAL 270 DAY)),
('Vaccination marked completed: BCG',           2, DATE_SUB(NOW(), INTERVAL 270 DAY)),
('Vaccination marked completed: OPV 0',         2, DATE_SUB(NOW(), INTERVAL 270 DAY)),
('Child registered: Brian Wambui',              3, DATE_SUB(NOW(), INTERVAL 400 DAY)),
('Child registered: Zuri Otieno',               4, DATE_SUB(NOW(), INTERVAL 200 DAY)),
('Child registered: Kofi Otieno',               4, DATE_SUB(NOW(), INTERVAL 380 DAY)),
('ClimateShield AI: HIGH cholera alert fired for Kisumu — 12 children queued for SMS', 1, NOW()),
('ClimateShield AI: HIGH malaria alert fired for Kisumu — 12 children queued for SMS', 1, NOW()),
('SMS dispatched via Africa\'s Talking to +254733000004 (Kisumu)',                      1, NOW()),
('User logged in',                              1, NOW()),
('Admin viewed system logs',                    1, NOW());

SELECT 'Demo seed complete. Accounts: admin@jarida.io, grace@jarida.io, akinyi@jarida.io, doctor@jarida.io, chw@jarida.io — all password: Admin@1234' AS status;
