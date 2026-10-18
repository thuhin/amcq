-- ===========================================================================
-- TEST DATA for the admin panel. NEVER on production or any shared server.
-- Idempotent: safe to run twice.
--
--   Dev admin:  admin@amcq.test   password: DevAdmin#2026
--
-- On a real server create the admin from the command line instead:
--   php index.php cli/admin create
-- Also adds: one draft and one in-review question (both complete enough to
-- exercise the verify gate), one verified question, a pending Correct Me
-- report, and a testimonial without guardian consent.
-- ===========================================================================
SET NAMES utf8mb4;

INSERT INTO users (name, email, password_hash, user_type, status)
SELECT 'Dev Admin', 'admin@amcq.test', '$2y$10$PJvyU3Ra18J1rQNq6cx9aOudeddBw.xZF62qMD0PCGUnbBwDPsw3C', 'admin', 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@amcq.test' AND user_type = 'admin');

-- 1. A draft: no explanation, no source yet, so it cannot be verified.
INSERT INTO questions (chapter_id, topic_id, difficulty, stem, explanation, source_ref, origin, status)
SELECT 6, (SELECT id FROM topics WHERE chapter_id = 6 AND code = '6.1'), 'easy',
       '[TEST draft] 1/4 ভগ্নাংশটির হর কত?', NULL, NULL, 'manual', 'draft' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE stem = '[TEST draft] 1/4 ভগ্নাংশটির হর কত?');
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'A', '1', 0 FROM questions WHERE stem = '[TEST draft] 1/4 ভগ্নাংশটির হর কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'B', '4', 1 FROM questions WHERE stem = '[TEST draft] 1/4 ভগ্নাংশটির হর কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'C', '5', 0 FROM questions WHERE stem = '[TEST draft] 1/4 ভগ্নাংশটির হর কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'D', '3', 0 FROM questions WHERE stem = '[TEST draft] 1/4 ভগ্নাংশটির হর কত?';

-- 2. In review and complete: ready to verify and publish.
INSERT INTO questions (chapter_id, topic_id, difficulty, stem, explanation, source_ref, origin, status)
SELECT 6, (SELECT id FROM topics WHERE chapter_id = 6 AND code = '6.1'), 'medium',
       '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?',
       'লব ও হর দুটিকেই 2 দিয়ে গুণ করলে 4/6 পাওয়া যায়; মান একই থাকে।',
       'NCTB Class 5 Math, Ch 6 (test source)', 'manual', 'in_review' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE stem = '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?');
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'A', '4/6', 1 FROM questions WHERE stem = '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'B', '3/4', 0 FROM questions WHERE stem = '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'C', '2/6', 0 FROM questions WHERE stem = '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'D', '5/6', 0 FROM questions WHERE stem = '[TEST review] 2/3 এর সমান ভগ্নাংশ কোনটি?';

-- 3. Verified, waiting to be published.
INSERT INTO questions (chapter_id, topic_id, difficulty, stem, explanation, source_ref, origin, status, verified_by, verified_at)
SELECT 6, (SELECT id FROM topics WHERE chapter_id = 6 AND code = '6.2'), 'hard',
       '[TEST verified] 3/5 + 1/5 এর মান কত?',
       'হর সমান হলে শুধু লব যোগ করতে হয়: 3 + 1 = 4, তাই 4/5।',
       'NCTB Class 5 Math, Ch 6 (test source)', 'manual', 'verified',
       (SELECT id FROM users WHERE email = 'admin@amcq.test' AND user_type = 'admin'), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM questions WHERE stem = '[TEST verified] 3/5 + 1/5 এর মান কত?');
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'A', '4/10', 0 FROM questions WHERE stem = '[TEST verified] 3/5 + 1/5 এর মান কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'B', '4/5', 1 FROM questions WHERE stem = '[TEST verified] 3/5 + 1/5 এর মান কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'C', '2/5', 0 FROM questions WHERE stem = '[TEST verified] 3/5 + 1/5 এর মান কত?';
INSERT IGNORE INTO question_options (question_id, label, body, is_correct)
SELECT id, 'D', '3/10', 0 FROM questions WHERE stem = '[TEST verified] 3/5 + 1/5 এর মান কত?';

-- A pending Correct Me report from the demo student Rahim, on the first
-- published Fractions question. He says option B is the right answer.
INSERT INTO correction_requests (user_id, question_id, claimed_correct_option_id, what_is_wrong, user_explanation, source_ref, status)
SELECT u.id, q.id, (SELECT id FROM question_options WHERE question_id = q.id AND label = 'B'),
       'I think the marked answer is wrong.', 'TEST report: please check the options again, option B looks right to me.',
       'Class 5 textbook p.41', 'pending'
FROM users u
JOIN questions q ON q.id = (SELECT MIN(id) FROM questions WHERE chapter_id = 6 AND status = 'published')
WHERE u.phone = '01700000001'
  AND NOT EXISTS (SELECT 1 FROM correction_requests r WHERE r.user_id = u.id AND r.question_id = q.id AND r.status = 'pending');

-- A testimonial that cannot be published yet: no guardian consent recorded.
INSERT INTO testimonials (student_name, class_label, school_name, quote, rating, is_published)
SELECT 'Test Student', 'Class 5', 'Test School', '[TEST] AcademicMCQ helps me practise every day.', 5, 0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM testimonials WHERE quote = '[TEST] AcademicMCQ helps me practise every day.');
