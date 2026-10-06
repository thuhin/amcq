-- ===========================================================================
-- 20261006200000 — features from the design mockups.
-- Brings a database built from the earlier schema up to date. Idempotent:
-- every step checks first, so re-running is harmless. Fresh installs get all
-- of this from database/schema.sql + seed.sql and need not run it.
-- ===========================================================================
SET NAMES utf8mb4;

-- Add a column only if it is missing. (MySQL 8 has no ADD COLUMN IF NOT EXISTS.)
-- subjects.description — "About Subject" tab (design 11)
SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE subjects ADD COLUMN description TEXT NULL AFTER icon', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'subjects' AND column_name = 'description');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- quiz_attempts.mode — "Try Harder Quiz" (design 10)
SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE quiz_attempts ADD COLUMN mode ENUM(''standard'',''hard'') NOT NULL DEFAULT ''standard'' AFTER topic_id', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'quiz_attempts' AND column_name = 'mode');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- quiz_attempts.paused_at / paused_seconds — timer pause (design 08)
SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE quiz_attempts ADD COLUMN paused_at TIMESTAMP NULL AFTER duration_seconds', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'quiz_attempts' AND column_name = 'paused_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE quiz_attempts ADD COLUMN paused_seconds INT UNSIGNED NOT NULL DEFAULT 0 AFTER paused_at', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'quiz_attempts' AND column_name = 'paused_seconds');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- badges: 'practice' category for the dashboard achievements (design 12)
ALTER TABLE badges MODIFY category ENUM('tier','correction','streak','competition','mastery','practice') NOT NULL;
INSERT IGNORE INTO badges (code, name, description, icon, category) VALUES
('seven_day_streak',        '7-Day Streak',           'Practised on 7 days in a row',            'flame',  'streak'),
('subject_50_mcqs',         '50 MCQs',                'Answered 50 questions in one subject',    'file',   'practice'),
('competition_participant', 'Competition Participant','Registered for the national competition', 'trophy', 'competition');

-- "What Students Say" (homepage designs). Real testimonials only; see schema.sql.
CREATE TABLE IF NOT EXISTS testimonials (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL,
    student_name    VARCHAR(100) NOT NULL,
    class_label     VARCHAR(30)  NOT NULL,
    school_name     VARCHAR(200) NULL,
    quote           VARCHAR(500) NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
    photo           VARCHAR(255) NULL,
    consent_at      TIMESTAMP    NULL,
    is_published    TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_testimonial_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_testimonial_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Top Schools This Week" (homepage designs). Filled weekly by
-- `php index.php cli/schools refresh`.
CREATE TABLE IF NOT EXISTS school_weekly_scores (
    school_id       INT UNSIGNED NOT NULL,
    week_start      DATE         NOT NULL,
    quizzes         INT UNSIGNED NOT NULL DEFAULT 0,
    average_score   DECIMAL(6,3) NOT NULL DEFAULT 0,
    total_score     INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (school_id, week_start),
    KEY idx_week_avg (week_start, average_score DESC),
    KEY idx_week_total (week_start, total_score DESC),
    CONSTRAINT fk_sws_school FOREIGN KEY (school_id) REFERENCES schools(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reference data: subject descriptions (only where still empty).
UPDATE subjects SET description = 'NCTB প্রাথমিক গণিত, পঞ্চম শ্রেণি। প্রতিটি অধ্যায়ের টপিকভিত্তিক MCQ, ধাপে ধাপে ব্যাখ্যাসহ।' WHERE slug = 'mathematics' AND class_id = 1 AND description IS NULL;
UPDATE subjects SET description = 'NCTB প্রাথমিক বিজ্ঞান, পঞ্চম শ্রেণি।'               WHERE slug = 'science'     AND class_id = 1 AND description IS NULL;
UPDATE subjects SET description = 'NCTB আমার বাংলা বই, পঞ্চম শ্রেণি।'                  WHERE slug = 'bangla'      AND class_id = 1 AND description IS NULL;
UPDATE subjects SET description = 'NCTB English For Today, Class 5.'                   WHERE slug = 'english'     AND class_id = 1 AND description IS NULL;
UPDATE subjects SET description = 'NCTB ইসলাম ও নৈতিক শিক্ষা, পঞ্চম শ্রেণি।'          WHERE slug = 'islam-moral' AND class_id = 1 AND description IS NULL;
UPDATE subjects SET description = 'NCTB প্রাথমিক বাংলাদেশ ও বিশ্বপরিচয়, পঞ্চম শ্রেণি।' WHERE slug = 'bgs'         AND class_id = 1 AND description IS NULL;

-- Practice starts from a topic row (design 11): topics for Decimals (7) and
-- Percentage (8). UNIQUE (chapter_id, code) makes INSERT IGNORE idempotent.
INSERT IGNORE INTO topics (chapter_id, code, name, summary, sort_order) VALUES
(7, '7.1', 'দশমিক ভগ্নাংশের মৌলিক অনুশীলন', 'ধারণা, স্থানীয় মান ও চার প্রক্রিয়া', 1),
(7, '7.2', 'দশমিকের গুণ ও ভাগ',           'দশমিক সংখ্যার গুণ ও ভাগের নিয়ম',     2),
(7, '7.3', 'দশমিকের ব্যবহার',             'টাকা ও পরিমাপে দশমিক',               3),
(8, '8.1', 'শতকরার মৌলিক অনুশীলন',         'শতকরা, ভগ্নাংশ ও দশমিকের রূপান্তর',  1),
(8, '8.2', 'শতকরার ব্যবহার',              'লাভ-ক্ষতি ও ছাড়ের হিসাব',             2);

-- Questions in those chapters that had no topic go to the basics topic.
UPDATE questions q JOIN topics t ON t.chapter_id = q.chapter_id AND t.code = '7.1'
   SET q.topic_id = t.id WHERE q.chapter_id = 7 AND q.topic_id IS NULL;
UPDATE questions q JOIN topics t ON t.chapter_id = q.chapter_id AND t.code = '8.1'
   SET q.topic_id = t.id WHERE q.chapter_id = 8 AND q.topic_id IS NULL;
