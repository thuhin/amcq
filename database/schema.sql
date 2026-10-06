-- ===========================================================================
--  AcademicMCQ — full schema
--  Built from Master Blueprint v2.0 and the UI Brand Guideline.
--
--  Conventions
--    * utf8mb4 everywhere. Bangla content needs 4-byte codepoints; utf8 would
--      silently truncate them.
--    * Money is DECIMAL, never FLOAT. A wallet debited Tk 1 at a time would
--      drift from its true balance under binary floating point.
--    * Reference data that the product rules depend on (tiers, point sources,
--      prizes) lives in seeded tables, not in application constants, so an
--      admin screen can show it and a migration can change it.
--    * Academic Points and wallet Taka never meet. Blueprint v1.1 removed the
--      "1 point = Tk 1" framing deliberately: it read as quasi-currency under
--      Bangladesh's PSSA 2024. There is no table joining the two.
-- ===========================================================================

SET NAMES utf8mb4;
SET time_zone = '+06:00';   -- Asia/Dhaka
SET FOREIGN_KEY_CHECKS = 0;

-- ===========================================================================
--  1. CURRICULUM
--  Blueprint §17 stored class/subject/chapter as free text on quiz_attempts.
--  Normalised here: as strings, "Math" and "Mathematics" become different
--  subjects and per-chapter progress cannot be aggregated reliably.
-- ===========================================================================

DROP TABLE IF EXISTS mediums;
CREATE TABLE mediums (
    id              TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(50)  NOT NULL,
    slug            VARCHAR(50)  NOT NULL UNIQUE,
    description     VARCHAR(150) NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order      SMALLINT     NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS classes;
CREATE TABLE classes (
    id              TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    medium_id       TINYINT UNSIGNED NOT NULL,
    name            VARCHAR(50)  NOT NULL,          -- 'Class 5'
    slug            VARCHAR(50)  NOT NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0, -- Class 5 only at launch
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_slug (medium_id, slug),
    CONSTRAINT fk_class_medium FOREIGN KEY (medium_id) REFERENCES mediums(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS subjects;
CREATE TABLE subjects (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    class_id        TINYINT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    name_bn         VARCHAR(100) NULL,
    slug            VARCHAR(100) NOT NULL,
    icon            VARCHAR(40)  NULL,
    description     TEXT         NULL,              -- "About Subject" tab (design 11)
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subject_slug (class_id, slug),
    CONSTRAINT fk_subject_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS chapters;
CREATE TABLE chapters (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    subject_id      SMALLINT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    name_bn         VARCHAR(150) NULL,
    slug            VARCHAR(150) NOT NULL,
    chapter_no      SMALLINT     NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chapter_slug (subject_id, slug),
    CONSTRAINT fk_chapter_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Topics within a chapter (chapter page design 11: "2.1 ভগ্নাংশের প্রাথমিক
-- ধারণা", "2.2 সমমান ভগ্নাংশ"...). Each has its own practice set, and the
-- result page reports "Performance by Topic", so questions carry a topic.
DROP TABLE IF EXISTS topics;
CREATE TABLE topics (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chapter_id      SMALLINT UNSIGNED NOT NULL,
    code            VARCHAR(10)  NOT NULL,          -- '2.1'
    name            VARCHAR(150) NOT NULL,
    summary         VARCHAR(255) NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_topic_code (chapter_id, code),
    CONSTRAINT fk_topic_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  2. QUESTION BANK
--  Not defined in the blueprint at all, though §17 references question_id and
--  the product depends on it. Targets per chapter (§8): 60 easy, 30 medium,
--  20 hard = 110. Weighted scoring (§9): easy 1, medium 1.5, hard 2.
-- ===========================================================================

DROP TABLE IF EXISTS difficulties;
CREATE TABLE difficulties (
    code            ENUM('easy','medium','hard') PRIMARY KEY,
    label           VARCHAR(20)  NOT NULL,
    -- DECIMAL because medium is 1.5. Stored rather than hard-coded so a
    -- scoring change does not require a deploy.
    weight          DECIMAL(3,1) NOT NULL,
    cognitive_skill VARCHAR(50)  NOT NULL,
    target_per_chapter SMALLINT  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS questions;
CREATE TABLE questions (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chapter_id      SMALLINT UNSIGNED NOT NULL,
    topic_id        INT UNSIGNED NULL,
    difficulty      ENUM('easy','medium','hard') NOT NULL,
    stem            TEXT         NOT NULL,
    explanation     TEXT         NULL,              -- shown on the Learn view
    source_ref      VARCHAR(255) NULL,              -- 'NCTB Class 5 Math, Ch 3, p.41'
    -- v2.0 strengthened the AI pipeline: independent verification and source
    -- referencing BEFORE publish, with "Correct Me" as the final safety net,
    -- not the primary check. A question is only servable at status='published'.
    origin          ENUM('ai','manual','imported') NOT NULL DEFAULT 'manual',
    status          ENUM('draft','in_review','verified','published','retired')
                                 NOT NULL DEFAULT 'draft',
    verified_by     INT UNSIGNED NULL,
    verified_at     TIMESTAMP    NULL,
    -- Denormalised counters. Cheap to maintain, and they drive the weak-area
    -- analytics without scanning every answer row.
    times_served    INT UNSIGNED NOT NULL DEFAULT 0,
    times_correct   INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    -- The hot path: "N published questions of this difficulty in this chapter".
    KEY idx_question_pick (chapter_id, difficulty, status),
    CONSTRAINT fk_question_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id),
    CONSTRAINT fk_question_topic FOREIGN KEY (topic_id) REFERENCES topics(id),
    KEY idx_question_topic (topic_id, difficulty, status),
    CONSTRAINT fk_question_difficulty FOREIGN KEY (difficulty) REFERENCES difficulties(code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Options are rows, not four columns on `questions`, so order can be shuffled
-- per attempt and a question is not locked to exactly four choices.
DROP TABLE IF EXISTS question_options;
CREATE TABLE question_options (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    question_id     INT UNSIGNED NOT NULL,
    label           CHAR(1)      NOT NULL,          -- 'A'..'D'
    body            TEXT         NOT NULL,
    is_correct      TINYINT(1)   NOT NULL DEFAULT 0,
    UNIQUE KEY uq_option_label (question_id, label),
    KEY idx_option_correct (question_id, is_correct),
    CONSTRAINT fk_option_question FOREIGN KEY (question_id)
        REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  3. SCHOOLS & USERS
-- ===========================================================================

DROP TABLE IF EXISTS schools;
CREATE TABLE schools (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(200) NOT NULL,
    district        VARCHAR(100) NULL,
    -- School ranking (homepage design 02). v2.0 corrected the average-score
    -- formula to divide by quizzes taken, not student count, so a school with
    -- more active students is not penalised. Cached here, recomputed by job.
    student_count   INT UNSIGNED NOT NULL DEFAULT 0,
    quizzes_taken   INT UNSIGNED NOT NULL DEFAULT 0,
    average_score   DECIMAL(5,2) NULL,
    is_verified     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_school_rank (average_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NULL,
    -- UNIQUE but nullable: the launch flow is phone -> OTP, while a guest has
    -- no identity at all. MySQL allows many NULLs in a UNIQUE column, which is
    -- what lets both live in one table.
    phone           VARCHAR(20)  NULL UNIQUE,
    email           VARCHAR(100) NULL,
    password_hash   VARCHAR(255) NULL,              -- NULL while OTP-only
    -- Leaderboards show this, never the phone or email (§4.6 privacy).
    display_name    VARCHAR(50)  NULL,
    class_id        TINYINT UNSIGNED NULL,
    school_id       INT UNSIGNED NULL,
    user_type       ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    referred_by     INT UNSIGNED NULL,
    referral_code   VARCHAR(12)  NULL UNIQUE,
    phone_verified_at TIMESTAMP  NULL,
    last_login_at   TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_class FOREIGN KEY (class_id) REFERENCES classes(id),
    CONSTRAINT fk_user_school FOREIGN KEY (school_id) REFERENCES schools(id),
    CONSTRAINT fk_user_referrer FOREIGN KEY (referred_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phone OTP. Codes are stored hashed: an OTP is a credential, and a leaked
-- table should not hand over live login codes.
DROP TABLE IF EXISTS otp_verifications;
CREATE TABLE otp_verifications (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    phone           VARCHAR(20)  NOT NULL,
    code_hash       VARCHAR(255) NOT NULL,
    purpose         ENUM('login','register','phone_change') NOT NULL DEFAULT 'login',
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at      TIMESTAMP    NOT NULL,
    consumed_at     TIMESTAMP    NULL,
    ip_address      VARCHAR(45)  NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_otp_lookup (phone, purpose, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  4. WALLET — real money, in Taka. Strictly separate from Academic Points.
-- ===========================================================================

DROP TABLE IF EXISTS wallets;
CREATE TABLE wallets (
    user_id         INT UNSIGNED PRIMARY KEY,
    -- Cached running total. wallet_transactions is the source of truth; this
    -- exists so a quiz page need not sum the whole ledger. Both are written
    -- inside one transaction, never separately.
    balance         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id),
    -- The one invariant no application bug may break.
    CONSTRAINT ck_wallet_non_negative CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS wallet_transactions;
CREATE TABLE wallet_transactions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED  NOT NULL,
    -- Signed: positive credits (top-up, refund), negative debits (quiz fee).
    amount          DECIMAL(10,2) NOT NULL,
    balance_after   DECIMAL(10,2) NOT NULL,         -- snapshot for statements
    type            ENUM('topup','quiz_fee','competition_fee','refund','prize','adjustment')
                                  NOT NULL,
    gateway         ENUM('bkash','nagad','manual','system') NULL,
    -- UNIQUE so a retried or replayed gateway callback cannot credit the same
    -- top-up twice. NULLs are exempt, so internal debits are unaffected.
    gateway_txn_id  VARCHAR(100)  NULL UNIQUE,
    description     VARCHAR(255)  NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wallet_txn_user (user_id, created_at),
    CONSTRAINT fk_wallet_txn_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gateway handshake. Kept apart from the ledger because an intent may fail or
-- be abandoned, and only a confirmed one becomes a wallet_transaction.
DROP TABLE IF EXISTS payment_intents;
CREATE TABLE payment_intents (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED  NOT NULL,
    gateway         ENUM('bkash','nagad') NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    purpose         ENUM('wallet_topup','competition_fee') NOT NULL DEFAULT 'wallet_topup',
    gateway_ref     VARCHAR(100)  NULL,
    status          ENUM('created','pending','completed','failed','cancelled')
                                  NOT NULL DEFAULT 'created',
    wallet_txn_id   BIGINT UNSIGNED NULL,           -- set once credited
    raw_response    JSON          NULL,             -- kept for dispute handling
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_intent_user (user_id, status),
    CONSTRAINT fk_intent_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_intent_wallet_txn FOREIGN KEY (wallet_txn_id)
        REFERENCES wallet_transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  5. PRACTICE QUIZZES
-- ===========================================================================

DROP TABLE IF EXISTS quiz_attempts;
CREATE TABLE quiz_attempts (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED  NULL,             -- NULL = guest
    session_id      VARCHAR(128)  NULL,             -- guests; claimed on signup
    -- Device fingerprint: the v1.1 mitigation for guest-flow farming (§14.6).
    device_hash     VARCHAR(64)   NULL,
    chapter_id      SMALLINT UNSIGNED NOT NULL,
    topic_id        INT UNSIGNED  NULL,             -- NULL = whole-chapter quiz
    -- 'hard' = "Try Harder Quiz" on the result page (design 10): same
    -- length and rules, weighted toward hard questions.
    mode            ENUM('standard','hard') NOT NULL DEFAULT 'standard',
    total_questions TINYINT UNSIGNED NOT NULL DEFAULT 10,
    score           TINYINT UNSIGNED NULL,          -- correct count
    percentage      DECIMAL(5,2)  NULL,
    fee_charged     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    wallet_txn_id   BIGINT UNSIGNED NULL,
    -- Only a completed attempt counts toward a streak; otherwise a student
    -- could farm credit by starting and quitting.
    status          ENUM('in_progress','completed','abandoned')
                                  NOT NULL DEFAULT 'in_progress',
    -- Frozen at completion: if the 60% threshold changes later, a past
    -- attempt's streak eligibility must not change with it.
    counts_for_streak TINYINT(1)  NOT NULL DEFAULT 0,
    started_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP     NULL,
    time_limit_seconds INT UNSIGNED NULL,           -- NULL = untimed practice
    duration_seconds INT UNSIGNED NULL,             -- 'Time Taken' on result
    -- Timer pause (design 08). Paused time is excluded from the limit and
    -- from Time Taken; paused_at is set while the quiz is paused.
    paused_at       TIMESTAMP     NULL,
    paused_seconds  INT UNSIGNED  NOT NULL DEFAULT 0,
    points_earned   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_attempt_user (user_id, completed_at),
    KEY idx_attempt_user_chapter (user_id, chapter_id, completed_at),
    KEY idx_attempt_session (session_id),
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_attempt_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id),
    CONSTRAINT fk_attempt_topic FOREIGN KEY (topic_id) REFERENCES topics(id),
    CONSTRAINT fk_attempt_wallet_txn FOREIGN KEY (wallet_txn_id)
        REFERENCES wallet_transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per question served. The blueprint used a JSON column; as rows they
-- are indexable, which the weak-area and difficulty analytics need.
DROP TABLE IF EXISTS quiz_attempt_answers;
CREATE TABLE quiz_attempt_answers (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_id      BIGINT UNSIGNED NOT NULL,
    question_id     INT UNSIGNED  NOT NULL,
    position        TINYINT UNSIGNED NOT NULL,      -- 1..10 as shown
    selected_option_id INT UNSIGNED NULL,           -- NULL = unanswered
    is_correct      TINYINT(1)    NULL,
    marked_for_review TINYINT(1)  NOT NULL DEFAULT 0,  -- quiz design 08
    answered_at     TIMESTAMP     NULL,
    UNIQUE KEY uq_attempt_position (attempt_id, position),
    UNIQUE KEY uq_attempt_question (attempt_id, question_id),
    CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id)
        REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_answer_question FOREIGN KEY (question_id) REFERENCES questions(id),
    CONSTRAINT fk_answer_option FOREIGN KEY (selected_option_id)
        REFERENCES question_options(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-student, per-chapter rollup for the dashboard's "Continue Learning" and
-- "Weak Areas" cards, and for chapter mastery points (up to 5 per chapter).
DROP TABLE IF EXISTS user_chapter_progress;
CREATE TABLE user_chapter_progress (
    user_id         INT UNSIGNED  NOT NULL,
    chapter_id      SMALLINT UNSIGNED NOT NULL,
    attempts        INT UNSIGNED  NOT NULL DEFAULT 0,
    best_percentage DECIMAL(5,2)  NULL,
    last_percentage DECIMAL(5,2)  NULL,
    avg_percentage  DECIMAL(5,2)  NULL,
    -- Consecutive 85%+ runs; drives chapter mastery awards.
    consecutive_mastery TINYINT UNSIGNED NOT NULL DEFAULT 0,
    mastery_points_awarded TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- cap 5
    last_attempt_at TIMESTAMP     NULL,
    PRIMARY KEY (user_id, chapter_id),
    KEY idx_progress_weak (user_id, avg_percentage),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_progress_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id),
    CONSTRAINT ck_mastery_cap CHECK (mastery_points_awarded <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  6. ACADEMIC POINTS, TIERS, STREAKS — reputation, never spendable.
-- ===========================================================================

DROP TABLE IF EXISTS tiers;
CREATE TABLE tiers (
    id              TINYINT UNSIGNED PRIMARY KEY,   -- 0 = Starter
    code            VARCHAR(20)  NOT NULL UNIQUE,
    title           VARCHAR(40)  NOT NULL,
    min_points      INT UNSIGNED NOT NULL UNIQUE,
    badge_icon      VARCHAR(40)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS user_lifetime_points;
CREATE TABLE user_lifetime_points (
    user_id         INT UNSIGNED PRIMARY KEY,
    total_points    INT UNSIGNED NOT NULL DEFAULT 0,
    -- v2.0 fix: new users start at Starter (0), not Bronze (needs 100).
    tier_id         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    -- Rank is derived by ordering, not stored: a stored rank would need
    -- rewriting for every user on every award.
    KEY idx_points_rank (total_points DESC, user_id),
    CONSTRAINT fk_points_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_points_tier FOREIGN KEY (tier_id) REFERENCES tiers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS point_transactions;
CREATE TABLE point_transactions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    source          ENUM('streak','correction','chapter_mastery','competition_round1',
                         'competition_final','competition_winner','referral','adjustment')
                                 NOT NULL,
    points          INT          NOT NULL,
    -- What earned it (attempt, correction, registration...). Paired with
    -- source, and UNIQUE, so the same event cannot award twice.
    ref_type        VARCHAR(30)  NULL,
    ref_id          BIGINT UNSIGNED NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_point_txn_user (user_id, created_at),
    UNIQUE KEY uq_point_award (user_id, source, ref_type, ref_id),
    CONSTRAINT fk_point_txn_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25 quizzes, EACH scoring 60%+, within 100 hours = 1 point, immediate reset.
-- (v2.0 fixed "60%+ average", which contradicted the worked example.)
DROP TABLE IF EXISTS user_quiz_streaks;
CREATE TABLE user_quiz_streaks (
    user_id                 INT UNSIGNED PRIMARY KEY,
    current_streak_quizzes  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    current_streak_started_at TIMESTAMP NULL,       -- window opens on quiz 1
    completed_streaks       INT UNSIGNED NOT NULL DEFAULT 0,
    best_streak_quizzes     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_streak_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS badges;
CREATE TABLE badges (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code            VARCHAR(40)  NOT NULL UNIQUE,
    name            VARCHAR(80)  NOT NULL,
    description     VARCHAR(255) NULL,
    icon            VARCHAR(40)  NULL,
    category        ENUM('tier','correction','streak','competition','mastery','practice') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS user_badges;
CREATE TABLE user_badges (
    user_id         INT UNSIGNED NOT NULL,
    badge_id        SMALLINT UNSIGNED NOT NULL,
    awarded_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ubadge_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_ubadge_badge FOREIGN KEY (badge_id) REFERENCES badges(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5 points when a referred friend registers for a competition (§6.5).
-- v2.0 reworded from "Tk 5 worth of points" to a flat point amount.
DROP TABLE IF EXISTS referrals;
CREATE TABLE referrals (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    referrer_id     INT UNSIGNED NOT NULL,
    referred_id     INT UNSIGNED NOT NULL UNIQUE,   -- referred once, ever
    status          ENUM('signed_up','qualified','rewarded') NOT NULL DEFAULT 'signed_up',
    rewarded_at     TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ref_referrer FOREIGN KEY (referrer_id) REFERENCES users(id),
    CONSTRAINT fk_ref_referred FOREIGN KEY (referred_id) REFERENCES users(id),
    CONSTRAINT ck_ref_not_self CHECK (referrer_id <> referred_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Weekly rank snapshot, for "#1,250 ↑120 from last week" (designs 10, 12).
-- Live rank is computed by ordering points; this only stores the comparison
-- point, written by a weekly job.
DROP TABLE IF EXISTS rank_snapshots;
CREATE TABLE rank_snapshots (
    user_id         INT UNSIGNED NOT NULL,
    week_start      DATE         NOT NULL,
    national_rank   INT UNSIGNED NOT NULL,
    total_points    INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, week_start),
    CONSTRAINT fk_snapshot_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard "Today's Activity" / "Recent Activity" feed. Separate from
-- notifications: every quiz is activity, but notifying on every quiz is the
-- over-notifying the guideline (§5.7) warns against.
DROP TABLE IF EXISTS user_activity;
CREATE TABLE user_activity (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    type            ENUM('quiz','points','rank','streak','tier','correction',
                         'competition','wallet') NOT NULL,
    title           VARCHAR(150) NOT NULL,
    detail          VARCHAR(255) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_activity_user (user_id, created_at),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- "What Students Say" (homepage designs 01-07). Published rows only are
-- shown, and the section is hidden when there are none: testimonials must
-- come from real students with their consent, never be made up.
DROP TABLE IF EXISTS testimonials;
CREATE TABLE testimonials (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL,
    student_name    VARCHAR(100) NOT NULL,
    class_label     VARCHAR(30)  NOT NULL,          -- 'Class 5'
    school_name     VARCHAR(200) NULL,
    quote           VARCHAR(500) NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL DEFAULT 5,
    photo           VARCHAR(255) NULL,              -- path under asset/
    consent_at      TIMESTAMP    NULL,              -- guardian consent recorded
    is_published    TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_testimonial_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_testimonial_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Top Schools This Week" (homepage designs 02-07). Written by the weekly
-- job (School_model::refresh_week). average_score = mean quiz percentage,
-- dividing by quizzes taken, not by students (blueprint v2.0 fix);
-- total_score = correct answers summed.
DROP TABLE IF EXISTS school_weekly_scores;
CREATE TABLE school_weekly_scores (
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

-- ===========================================================================
--  7. NATIONAL COMPETITION
--  Tk 99 once. Round 1: 30 Q (10/10/10), weighted 1/1.5/2 = max 50, 45 min,
--  randomized. Top 5% qualify. Final: 50 Q (15/20/15), one by one, no going
--  back, proctored. 20 winners, Tk 1,87,000 pool.
-- ===========================================================================

DROP TABLE IF EXISTS competitions;
CREATE TABLE competitions (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(150) NOT NULL,
    year            SMALLINT     NOT NULL,
    class_id        TINYINT UNSIGNED NULL,          -- eligibility
    entry_fee       DECIMAL(10,2) NOT NULL DEFAULT 99.00,
    prize_pool      DECIMAL(12,2) NOT NULL DEFAULT 187000.00,
    qualify_percent DECIMAL(4,1) NOT NULL DEFAULT 5.0,
    registration_opens_at  DATETIME NULL,
    registration_closes_at DATETIME NULL,
    round1_opens_at        DATETIME NULL,
    round1_closes_at       DATETIME NULL,
    -- v2.0: Round 1 got a real time limit.
    round1_time_limit_minutes SMALLINT NOT NULL DEFAULT 45,
    round1_question_count  TINYINT UNSIGNED NOT NULL DEFAULT 30,
    final_at               DATETIME NULL,
    final_time_limit_minutes  SMALLINT NOT NULL DEFAULT 75,
    final_question_count   TINYINT UNSIGNED NOT NULL DEFAULT 50,
    rules_html      MEDIUMTEXT   NULL,              -- shown before payment (§17)
    status          ENUM('draft','registration','round1','grading','final','completed')
                                 NOT NULL DEFAULT 'draft',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comp_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prize ladder: 1st 1 lac, 2nd 50k, 3rd 25k, 4-10th 1k, 11-20th 500,
-- plus the winner point awards (50/25/10/5).
DROP TABLE IF EXISTS competition_prizes;
CREATE TABLE competition_prizes (
    competition_id  INT UNSIGNED NOT NULL,
    rank_from       SMALLINT UNSIGNED NOT NULL,
    rank_to         SMALLINT UNSIGNED NOT NULL,
    prize_amount    DECIMAL(10,2) NOT NULL,
    points          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (competition_id, rank_from),
    CONSTRAINT fk_prize_comp FOREIGN KEY (competition_id) REFERENCES competitions(id),
    CONSTRAINT ck_prize_range CHECK (rank_to >= rank_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS competition_registrations;
CREATE TABLE competition_registrations (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    competition_id  INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    fee_paid        TINYINT(1)   NOT NULL DEFAULT 0,
    wallet_txn_id   BIGINT UNSIGNED NULL,           -- the Tk 99 debit
    status          ENUM('registered','round1_done','qualified','finalist','winner','eliminated')
                                 NOT NULL DEFAULT 'registered',
    round1_score    DECIMAL(5,1) NULL,              -- weighted, max 50.0
    round1_started_at  TIMESTAMP NULL,
    round1_submitted_at TIMESTAMP NULL,
    round1_rank     INT UNSIGNED NULL,
    final_score     DECIMAL(5,1) NULL,
    final_started_at   TIMESTAMP NULL,
    final_submitted_at TIMESTAMP NULL,
    -- v2.0: tie-break on time taken, never on registration timestamp.
    final_duration_seconds INT UNSIGNED NULL,
    final_rank      SMALLINT UNSIGNED NULL,
    prize_amount    DECIMAL(10,2) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- One-time fee: a double-submitted form must not make a second row.
    UNIQUE KEY uq_registration (competition_id, user_id),
    KEY idx_reg_round1 (competition_id, round1_score DESC),
    CONSTRAINT fk_reg_competition FOREIGN KEY (competition_id) REFERENCES competitions(id),
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reg_wallet_txn FOREIGN KEY (wallet_txn_id)
        REFERENCES wallet_transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each registrant gets their own randomized set (v2.0), so answers are keyed
-- per registration and round.
DROP TABLE IF EXISTS competition_answers;
CREATE TABLE competition_answers (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    registration_id INT UNSIGNED NOT NULL,
    round           ENUM('round1','final') NOT NULL,
    position        TINYINT UNSIGNED NOT NULL,
    question_id     INT UNSIGNED NOT NULL,
    selected_option_id INT UNSIGNED NULL,
    is_correct      TINYINT(1)   NULL,
    weight          DECIMAL(3,1) NOT NULL,          -- frozen at serve time
    answered_at     TIMESTAMP    NULL,
    UNIQUE KEY uq_comp_position (registration_id, round, position),
    CONSTRAINT fk_canswer_reg FOREIGN KEY (registration_id)
        REFERENCES competition_registrations(id) ON DELETE CASCADE,
    CONSTRAINT fk_canswer_question FOREIGN KEY (question_id) REFERENCES questions(id),
    CONSTRAINT fk_canswer_option FOREIGN KEY (selected_option_id)
        REFERENCES question_options(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS certificates;
CREATE TABLE certificates (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    -- Public, unguessable id printed on the certificate and its QR code.
    verification_code CHAR(12)   NOT NULL UNIQUE,
    user_id         INT UNSIGNED NOT NULL,
    competition_id  INT UNSIGNED NOT NULL,
    type            ENUM('participation','finalist','winner') NOT NULL,
    -- Snapshot: the certificate must keep showing the name it was issued
    -- under even if the student later edits their profile.
    student_name    VARCHAR(100) NOT NULL,
    result_label    VARCHAR(100) NULL,              -- '3rd place', 'Finalist'
    issued_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cert (user_id, competition_id, type),
    CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_cert_comp FOREIGN KEY (competition_id) REFERENCES competitions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  8. "CORRECT ME" + CAREER LADDER
-- ===========================================================================

DROP TABLE IF EXISTS correction_requests;
CREATE TABLE correction_requests (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    question_id     INT UNSIGNED NOT NULL,
    claimed_correct_option_id INT UNSIGNED NULL,
    what_is_wrong   TEXT         NULL,
    user_explanation TEXT        NULL,
    source_ref      VARCHAR(255) NULL,
    -- AI pre-filter (§6.4) runs first; only flagged cases reach a human.
    ai_verdict      ENUM('likely_valid','likely_invalid','unsure') NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by     INT UNSIGNED NULL,
    reviewed_at     TIMESTAMP    NULL,
    review_note     VARCHAR(255) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_correction_status (status, created_at),
    KEY idx_correction_user (user_id, status),
    CONSTRAINT fk_correction_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_correction_question FOREIGN KEY (question_id) REFERENCES questions(id),
    CONSTRAINT fk_correction_option FOREIGN KEY (claimed_correct_option_id)
        REFERENCES question_options(id),
    CONSTRAINT fk_correction_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- The point for an approval is guarded by point_transactions.uq_point_award
-- (source='correction', ref_id=this id), so it cannot be granted twice.

-- 1 -> Proofreader badge, 10 -> tutor interview, 25 -> Assistant Teacher
-- assessment, 100 -> Subject Expert assessment. v2.0: "eligible for
-- assessment", never a guaranteed job.
DROP TABLE IF EXISTS career_milestones;
CREATE TABLE career_milestones (
    id              TINYINT UNSIGNED PRIMARY KEY,
    approved_required SMALLINT UNSIGNED NOT NULL UNIQUE,
    title           VARCHAR(80)  NOT NULL,
    description     VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS user_career_milestones;
CREATE TABLE user_career_milestones (
    user_id         INT UNSIGNED NOT NULL,
    milestone_id    TINYINT UNSIGNED NOT NULL,
    reached_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assessment_status ENUM('eligible','invited','passed','declined') NOT NULL DEFAULT 'eligible',
    PRIMARY KEY (user_id, milestone_id),
    CONSTRAINT fk_ucm_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_ucm_milestone FOREIGN KEY (milestone_id) REFERENCES career_milestones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================================
--  9. NOTIFICATIONS, SESSIONS
-- ===========================================================================

DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    type            ENUM('streak','points','tier','competition','result',
                         'correction','wallet') NOT NULL,
    title           VARCHAR(150) NOT NULL,
    body            VARCHAR(500) NULL,
    link            VARCHAR(255) NULL,
    read_at         TIMESTAMP    NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_user (user_id, read_at, created_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS user_settings;
CREATE TABLE user_settings (
    user_id         INT UNSIGNED PRIMARY KEY,
    language        ENUM('bn','en') NOT NULL DEFAULT 'bn',
    show_on_leaderboard TINYINT(1) NOT NULL DEFAULT 1,
    notify_streak   TINYINT(1)   NOT NULL DEFAULT 1,
    notify_competition TINYINT(1) NOT NULL DEFAULT 1,
    notify_sms      TINYINT(1)   NOT NULL DEFAULT 0,  -- avoid over-notifying
    CONSTRAINT fk_settings_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Us form. Stored, not emailed: there is no outgoing mail yet.
-- Staff read these in the database (admin screen to come).
DROP TABLE IF EXISTS contact_messages;
CREATE TABLE contact_messages (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL,              -- set when signed in
    name            VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  NULL,
    email           VARCHAR(150) NULL,
    topic           ENUM('general','payment','competition','question','school','technical') NOT NULL DEFAULT 'general',
    message         TEXT         NOT NULL,
    ip_address      VARCHAR(45)  NULL,
    user_agent      VARCHAR(255) NULL,
    status          ENUM('new','read','replied','closed') NOT NULL DEFAULT 'new',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_status (status, created_at),
    KEY idx_contact_ip (ip_address, created_at),
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id),
    -- Every message must be answerable: a phone or an email, at least one.
    CONSTRAINT ck_contact_reachable CHECK (phone IS NOT NULL OR email IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CodeIgniter database session driver (sess_save_path = 'ci_sessions').
DROP TABLE IF EXISTS ci_sessions;
CREATE TABLE ci_sessions (
    id              VARCHAR(128) NOT NULL,
    ip_address      VARCHAR(45)  NOT NULL,
    timestamp       INT UNSIGNED NOT NULL DEFAULT 0,
    data            BLOB         NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sessions_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
