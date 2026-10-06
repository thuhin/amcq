-- ===========================================================================
-- TEST DATA — demo content for the design features. NEVER on production.
-- Applied by `./migrate.sh --test-data`. Idempotent.
-- ===========================================================================
SET NAMES utf8mb4;

-- Schools as named in the homepage designs.
UPDATE schools SET name = 'Chattogram Govt. Girls'' High School' WHERE id = 2 AND name = 'Chittagong Government Primary School';
INSERT IGNORE INTO schools (id, name, district, is_verified) VALUES
(4, 'Ideal School & College',            'Dhaka',      1),
(5, 'Rajuk Uttara Model College',        'Dhaka',      1),
(6, 'Viqarunnisa Noon School & College', 'Dhaka',      1),
(7, 'Nosirabad Govt. High School',       'Mymensingh', 1);

-- "Top Schools This Week": the design's figures as this week's snapshot.
INSERT IGNORE INTO school_weekly_scores (school_id, week_start, quizzes, average_score, total_score)
SELECT id, DATE(NOW()) - INTERVAL WEEKDAY(NOW()) DAY, q, avg_s, tot FROM (
  SELECT 1 id, 412 q, 89.247 avg_s, 9876 tot UNION ALL
  SELECT 4,    388,   88.963,       9312     UNION ALL
  SELECT 5,    351,   87.810,       8644     UNION ALL
  SELECT 6,    344,   86.532,       8765     UNION ALL
  SELECT 7,    301,   85.914,       7402     UNION ALL
  SELECT 3,    120,   76.432,       2210     UNION ALL
  SELECT 2,     95,   71.200,       1530) x
WHERE EXISTS (SELECT 1 FROM schools s WHERE s.id = x.id);

-- "What Students Say": the design's three sample cards, added once.
INSERT INTO testimonials (student_name, class_label, school_name, quote, rating, photo, is_published, sort_order)
SELECT * FROM (
  SELECT 'Rafi' n, 'Class 5' c, 'Dhaka Collegiate School' sc, 'খুব ভালো লাগছে। প্রশ্নগুলো স্কুলের বইয়ের সাথে মিলে যায়।' q, 5 r, 'img/design/student-1.jpg' p, 1 pub, 1 so UNION ALL
  SELECT 'Nusrat', 'Class 6', 'Chattogram Govt. Girls'' High School', 'ভুল করলে সাথে সাথে ব্যাখ্যা পাওয়া যায়। এতে অনেক শেখা হয়।', 5, 'img/design/student-2.jpg', 1, 2 UNION ALL
  SELECT 'Samiul', 'Class 8', 'Rajshahi Model School', 'প্র্যাকটিস, পয়েন্ট আর র‍্যাঙ্কিং আছে, তাই নিয়মিত পড়তে ইচ্ছে করে।', 5, 'img/design/student-3.jpg', 1, 3) t
WHERE NOT EXISTS (SELECT 1 FROM testimonials);

-- Competition final date as shown on the dashboard design.
UPDATE competitions SET final_at = '2026-11-20 10:00:00' WHERE id = 1 AND final_at IS NULL;
