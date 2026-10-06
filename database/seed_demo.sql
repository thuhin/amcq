-- ===========================================================================
--  DEMO DATA — local development only. NEVER load into production.
--  Fake students so the leaderboard, rank and dashboard have something to
--  show. All phone numbers use the 0170000xxxx range and are not real.
-- ===========================================================================
SET NAMES utf8mb4;

-- Schools as named in the homepage designs (02-07).
INSERT INTO schools (id, name, district, is_verified) VALUES
(1, 'Dhaka Collegiate School',              'Dhaka',      1),
(2, 'Chattogram Govt. Girls\' High School',  'Chattogram', 1),
(3, 'Rajshahi Model School',                'Rajshahi',   1),
(4, 'Ideal School & College',               'Dhaka',      1),
(5, 'Rajuk Uttara Model College',           'Dhaka',      1),
(6, 'Viqarunnisa Noon School & College',    'Dhaka',      1),
(7, 'Nosirabad Govt. High School',          'Mymensingh', 1);

-- id 1 is the demo login: phone 01700000001 (OTP is shown on screen in development).
INSERT INTO users (id, name, phone, display_name, class_id, school_id, referral_code, phone_verified_at) VALUES
(1,  'Rahim Ahmed',    '01700000001', 'Rahim A.',    1, 1, 'RAHIM1', NOW()),
(2,  'Fatema Begum',   '01700000002', 'Fatema B.',   1, 1, 'FATEMA', NOW()),
(3,  'Samiul Islam',   '01700000003', 'Samiul I.',   1, 3, 'SAMIUL', NOW()),
(4,  'Nusrat Jahan',   '01700000004', 'Nusrat J.',   1, 2, 'NUSRAT', NOW()),
(5,  'Rafi Hasan',     '01700000005', 'Rafi H.',     1, 1, 'RAFIH5', NOW()),
(6,  'Tasnim Akter',   '01700000006', 'Tasnim A.',   1, 2, 'TASNIM', NOW()),
(7,  'Arif Hossain',   '01700000007', 'Arif H.',     1, 3, 'ARIFH7', NOW()),
(8,  'Mim Chowdhury',  '01700000008', 'Mim C.',      1, 1, 'MIMC08', NOW()),
(9,  'Sakib Rahman',   '01700000009', 'Sakib R.',    1, 2, 'SAKIB9', NOW()),
(10, 'Jannat Ara',     '01700000010', 'Jannat A.',   1, 3, 'JANNAT', NOW()),
(11, 'Tanvir Alam',    '01700000011', 'Tanvir A.',   1, 1, 'TANVIR', NOW()),
(12, 'Riya Das',       '01700000012', 'Riya D.',     1, 2, 'RIYAD2', NOW());

-- Points spread across tiers (Starter .. Diamond) so every tier renders.
INSERT INTO user_lifetime_points (user_id, total_points, tier_id) VALUES
(2, 12847, 5), (3, 11234, 5), (4, 6420, 4), (5, 2310, 3), (6, 1180, 3),
(7, 760, 2),   (8, 505, 2),   (9, 340, 1),  (10, 120, 1), (11, 60, 0), (12, 10, 0);
-- Demo user's points are written as a ledger, so the history page has rows
-- and the total is provably the sum of its transactions.
INSERT INTO point_transactions (user_id, source, points, ref_type, ref_id, description, created_at) VALUES
(1, 'streak',          1,  'demo', 1, 'Streak completed: 25 quizzes at 60%+',    NOW() - INTERVAL 20 DAY),
(1, 'correction',      1,  'demo', 2, 'Correction approved',                     NOW() - INTERVAL 15 DAY),
(1, 'chapter_mastery', 5,  'demo', 3, 'Chapter mastery: দশমিক ভগ্নাংশ',          NOW() - INTERVAL 10 DAY),
(1, 'competition_round1', 10, 'demo', 4, 'Scored 85%+ in Round 1',               NOW() - INTERVAL 40 DAY),
(1, 'competition_final', 20, 'demo', 5, 'Final round participation',             NOW() - INTERVAL 35 DAY),
(1, 'adjustment',      493, 'demo', 6, 'Demo opening balance',                   NOW() - INTERVAL 60 DAY);
INSERT INTO user_lifetime_points (user_id, total_points, tier_id)
SELECT 1, SUM(points), 2 FROM point_transactions WHERE user_id = 1;   -- 530 = Silver Scholar

INSERT INTO user_quiz_streaks (user_id, current_streak_quizzes, current_streak_started_at, completed_streaks, best_streak_quizzes)
VALUES (1, 0, NULL, 1, 25);

INSERT INTO rank_snapshots (user_id, week_start, national_rank, total_points)
SELECT u.user_id, DATE(NOW() - INTERVAL 7 DAY) - INTERVAL WEEKDAY(NOW() - INTERVAL 7 DAY) DAY,
       CASE u.user_id WHEN 1 THEN 12 ELSE 99 END, u.total_points
FROM user_lifetime_points u;

-- Wallet: real money, kept on its own ledger.
INSERT INTO wallets (user_id, balance) VALUES (1, 0.00);
INSERT INTO wallet_transactions (user_id, amount, balance_after, type, gateway, gateway_txn_id, description, created_at) VALUES
(1, 50.00, 50.00, 'topup', 'bkash', 'DEMO-BKASH-0001', 'bKash top-up', NOW() - INTERVAL 3 DAY);
UPDATE wallets SET balance = 50.00 WHERE user_id = 1;

INSERT INTO user_settings (user_id) SELECT id FROM users;

INSERT INTO competition_registrations (competition_id, user_id, fee_paid, status) VALUES (1, 2, 1, 'registered');

-- "Top Schools This Week": the design's figures, as last week's snapshot.
-- In real use School_model::refresh_week() computes these from quiz attempts.
INSERT INTO school_weekly_scores (school_id, week_start, quizzes, average_score, total_score)
SELECT id, DATE(NOW()) - INTERVAL WEEKDAY(NOW()) DAY, q, avg_s, tot FROM (
  SELECT 1 id, 412 q, 89.247 avg_s, 9876 tot UNION ALL
  SELECT 4,    388,   88.963,       9312     UNION ALL
  SELECT 5,    351,   87.810,       8644     UNION ALL
  SELECT 6,    344,   86.532,       8765     UNION ALL
  SELECT 7,    301,   85.914,       7402     UNION ALL
  SELECT 3,    120,   76.432,       2210     UNION ALL
  SELECT 2,     95,   71.200,       1530) x;

-- "What Students Say": the design's three sample cards. DEMO ONLY. Real
-- testimonials need a real student and recorded guardian consent.
INSERT INTO testimonials (student_name, class_label, school_name, quote, rating, photo, is_published, sort_order) VALUES
('Rafi',   'Class 5', 'Dhaka Collegiate School',              'খুব ভালো লাগছে। প্রশ্নগুলো স্কুলের বইয়ের সাথে মিলে যায়।',          5, 'img/design/student-1.jpg', 1, 1),
('Nusrat', 'Class 6', 'Chattogram Govt. Girls\' High School', 'ভুল করলে সাথে সাথে ব্যাখ্যা পাওয়া যায়। এতে অনেক শেখা হয়।',      5, 'img/design/student-2.jpg', 1, 2),
('Samiul', 'Class 8', 'Rajshahi Model School',                'প্র্যাকটিস, পয়েন্ট আর র‍্যাঙ্কিং আছে, তাই নিয়মিত পড়তে ইচ্ছে করে।', 5, 'img/design/student-3.jpg', 1, 3);

-- Competition dates as shown on the dashboard design (12).
UPDATE competitions SET final_at = '2026-11-20 10:00:00' WHERE id = 1;
