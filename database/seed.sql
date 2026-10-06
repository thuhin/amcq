-- ===========================================================================
--  AcademicMCQ — reference data. Safe for every environment.
--  Values from Master Blueprint v2.0 §8, §9, §10, §11, §13.
-- ===========================================================================
SET NAMES utf8mb4;

-- §9 difficulty: weight is the competition scoring weight; target is §8.
INSERT INTO difficulties (code, label, weight, cognitive_skill, target_per_chapter) VALUES
('easy',   'Easy',   1.0, 'Remembering',          60),
('medium', 'Medium', 1.5, 'Understanding/Applying',30),
('hard',   'Hard',   2.0, 'Analyzing/Evaluating', 20);

-- §11 tiers. Starter at 0 is the v2.0 fix.
INSERT INTO tiers (id, code, title, min_points, badge_icon) VALUES
(0, 'starter',  'Starter',        0,     NULL),
(1, 'bronze',   'Bronze Learner', 100,   'medal-bronze'),
(2, 'silver',   'Silver Scholar', 500,   'medal-silver'),
(3, 'gold',     'Gold Master',    1000,  'medal-gold'),
(4, 'platinum', 'Platinum Pro',   5000,  'gem'),
(5, 'diamond',  'Diamond Legend', 10000, 'crown'),
(6, 'grandmaster','Grand Master', 50000, 'star');

-- §13 career ladder, v2.0 wording: eligibility, never a guaranteed job.
INSERT INTO career_milestones (id, approved_required, title, description) VALUES
(1, 1,   'Proofreader',        'Proofreader badge for your first approved correction.'),
(2, 10,  'Tutor Interview',    'Eligible to be interviewed for a paid tutor role.'),
(3, 25,  'Assistant Teacher',  'Eligible for the Assistant Teacher assessment.'),
(4, 100, 'Subject Expert',     'Eligible for the Subject Expert assessment.');

INSERT INTO badges (code, name, description, icon, category) VALUES
('tier_bronze',   'Bronze Learner', 'Reached 100 Academic Points',  'medal-bronze', 'tier'),
('tier_silver',   'Silver Scholar', 'Reached 500 Academic Points',  'medal-silver', 'tier'),
('tier_gold',     'Gold Master',    'Reached 1,000 Academic Points','medal-gold',   'tier'),
('tier_platinum', 'Platinum Pro',   'Reached 5,000 Academic Points','gem',          'tier'),
('tier_diamond',  'Diamond Legend', 'Reached 10,000 Academic Points','crown',       'tier'),
('tier_grandmaster','Grand Master', 'Reached 50,000 Academic Points','star',        'tier'),
('proofreader',   'Proofreader',    'First approved correction',    'pencil-check', 'correction'),
('first_streak',  'Streak Starter', 'Completed your first 25-quiz streak', 'flame', 'streak'),
('chapter_master','Chapter Master', 'Mastered a chapter',           'book-check',   'mastery'),
('finalist',      'National Finalist','Qualified for the final round','trophy',     'competition'),
-- Achievements shown on the dashboard (design 12).
('seven_day_streak',        '7-Day Streak',          'Practised on 7 days in a row',          'flame',  'streak'),
('subject_50_mcqs',         '50 MCQs',               'Answered 50 questions in one subject',  'file',   'practice'),
('competition_participant', 'Competition Participant','Registered for the national competition','trophy','competition');

-- ---------------------------------------------------------------------------
--  Curriculum. Bangla medium is live; the other two are "Coming Soon" in the
--  homepage design. Class 5 only at launch (v2.0 revision note).
-- ---------------------------------------------------------------------------
INSERT INTO mediums (id, name, slug, description, is_active, sort_order) VALUES
(1, 'Bangla Medium',   'bangla-medium',   'NCTB Curriculum (বাংলা মাধ্যম), Class 5 to SSC', 1, 1),
(2, 'English Version', 'english-version', 'NCTB Curriculum (English Version), Class 5 to SSC', 0, 2),
(3, 'English Medium',  'english-medium',  'UK Curriculum (Cambridge/Edexcel), Primary to O-Level', 0, 3);

INSERT INTO classes (id, medium_id, name, slug, sort_order, is_active) VALUES
(1, 1, 'Class 5',  'class-5',  5,  1),
(2, 1, 'Class 6',  'class-6',  6,  0),
(3, 1, 'Class 7',  'class-7',  7,  0),
(4, 1, 'Class 8',  'class-8',  8,  0),
(5, 1, 'Class 9',  'class-9',  9,  0),
(6, 1, 'Class 10', 'class-10', 10, 0);

INSERT INTO subjects (id, class_id, name, name_bn, slug, icon, sort_order, description) VALUES
(1, 1, 'Mathematics', 'গণিত', 'mathematics', 'calculator', 1, 'NCTB প্রাথমিক গণিত, পঞ্চম শ্রেণি। প্রতিটি অধ্যায়ের টপিকভিত্তিক MCQ, ধাপে ধাপে ব্যাখ্যাসহ।'),
(2, 1, 'Science', 'বিজ্ঞান', 'science', 'atom', 2, 'NCTB প্রাথমিক বিজ্ঞান, পঞ্চম শ্রেণি।'),
(3, 1, 'Bangla', 'বাংলা', 'bangla', 'book', 3, 'NCTB আমার বাংলা বই, পঞ্চম শ্রেণি।'),
(4, 1, 'English', 'ইংরেজি', 'english', 'type', 4, 'NCTB English For Today, Class 5.'),
(5, 1, 'Islam & Moral Education', 'ইসলাম ও নৈতিক শিক্ষা', 'islam-moral', 'people', 5, 'NCTB ইসলাম ও নৈতিক শিক্ষা, পঞ্চম শ্রেণি।'),
(6, 1, 'Bangladesh & Global Studies', 'বাংলাদেশ ও বিশ্বপরিচয়', 'bgs', 'globe', 6, 'NCTB প্রাথমিক বাংলাদেশ ও বিশ্বপরিচয়, পঞ্চম শ্রেণি।');

-- Mathematics, NCTB Class 5 (প্রাথমিক গণিত, পঞ্চম শ্রেণি).
-- CONTENT TEAM: verify names and order against the current printed book
-- before launch. The mockups show an illustrative 10-chapter list that
-- contradicts the homepage's own "Chapters: 12", so neither was copied.
INSERT INTO chapters (id, subject_id, name, name_bn, slug, chapter_no, sort_order) VALUES
(1,  1, 'Multiplication',         'গুণ',                       'multiplication',     1,  1),
(2,  1, 'Division',               'ভাগ',                       'division',           2,  2),
(3,  1, 'Simplification',         'সরলীকরণ',                   'simplification',     3,  3),
(4,  1, 'Average',                'গড়',                       'average',            4,  4),
(5,  1, 'Multiples and Divisors', 'গুণিতক ও গুণনীয়ক',           'multiples-divisors', 5,  5),
(6,  1, 'Fractions',              'ভগ্নাংশ',                    'fractions',          6,  6),
(7,  1, 'Decimal Fractions',      'দশমিক ভগ্নাংশ',               'decimals',           7,  7),
(8,  1, 'Percentage',             'শতকরা',                     'percentage',         8,  8),
(9,  1, 'Geometry',               'জ্যামিতি',                    'geometry',           9,  9),
(10, 1, 'Measurement',            'পরিমাপ',                    'measurement',        10, 10),
(11, 1, 'Time',                   'সময়',                       'time',               11, 11),
(12, 1, 'Data Handling',          'উপাত্ত সংগ্রহ ও বিন্যস্তকরণ',   'data',               12, 12);

-- Topics for Fractions, from the chapter-page design (11).
INSERT INTO topics (chapter_id, code, name, summary, sort_order) VALUES
(6, '6.1', 'ভগ্নাংশের প্রাথমিক ধারণা', 'ভগ্নাংশ কী, ভগ্নাংশের অংশগুলো',          1),
(6, '6.2', 'সমমান ভগ্নাংশ',          'সমমান ভগ্নাংশ নির্ণয়',                  2),
(6, '6.3', 'ভগ্নাংশের তুলনা',         'ভগ্নাংশ বড় না ছোট নির্ণয়',              3),
(6, '6.4', 'ভগ্নাংশের যোগ ও বিয়োগ',   'ভগ্নাংশ যোগ ও বিয়োগের নিয়ম',            4),
(6, '6.5', 'মিশ্র ভগ্নাংশ',           'মিশ্র ভগ্নাংশ ও অপ্রকৃত ভগ্নাংশের রূপান্তর', 5);

-- Practice starts from a topic row (design 11), so every playable chapter
-- has topics. Topics without a full 5/3/2 set show "Coming Soon".
INSERT INTO topics (chapter_id, code, name, summary, sort_order) VALUES
(7, '7.1', 'দশমিক ভগ্নাংশের মৌলিক অনুশীলন', 'ধারণা, স্থানীয় মান ও চার প্রক্রিয়া', 1),
(7, '7.2', 'দশমিকের গুণ ও ভাগ',           'দশমিক সংখ্যার গুণ ও ভাগের নিয়ম',     2),
(7, '7.3', 'দশমিকের ব্যবহার',             'টাকা ও পরিমাপে দশমিক',               3),
(8, '8.1', 'শতকরার মৌলিক অনুশীলন',         'শতকরা, ভগ্নাংশ ও দশমিকের রূপান্তর',  1),
(8, '8.2', 'শতকরার ব্যবহার',              'লাভ-ক্ষতি ও ছাড়ের হিসাব',             2);

-- Other subjects: chapter rows exist so the selector and counts work, but the
-- names are placeholders for the content team to replace from the NCTB books.
-- Left inactive so no student is sent to an empty quiz.
INSERT INTO chapters (subject_id, name, slug, chapter_no, sort_order, is_active)
SELECT s.id, CONCAT('Chapter ', n.n), CONCAT('chapter-', n.n), n.n, n.n, 0
FROM subjects s
JOIN (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
      UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
      UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14) n
WHERE (s.slug = 'science'     AND n.n <= 14)
   OR (s.slug = 'bangla'      AND n.n <= 12)
   OR (s.slug = 'english'     AND n.n <= 12)
   OR (s.slug = 'islam-moral' AND n.n <= 10)
   OR (s.slug = 'bgs'         AND n.n <= 12);

-- ---------------------------------------------------------------------------
--  Competition (§10). Dates left NULL: "Coming Soon" until an admin sets them.
-- ---------------------------------------------------------------------------
INSERT INTO competitions (id, name, year, class_id, entry_fee, prize_pool, status) VALUES
(1, 'AcademicMCQ National Competition', 2026, 1, 99.00, 187000.00, 'draft');

-- 100000 + 50000 + 25000 + 7x1000 + 10x500 = 187000, matching the stated pool.
INSERT INTO competition_prizes (competition_id, rank_from, rank_to, prize_amount, points) VALUES
(1, 1,  1,  100000.00, 50),
(1, 2,  2,   50000.00, 25),
(1, 3,  3,   25000.00, 10),
(1, 4,  10,   1000.00, 5),
(1, 11, 20,    500.00, 5);
