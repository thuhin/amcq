-- ===========================================================================
--  AcademicMCQ — base schema
--  Derived from Master Blueprint v2.0 §17, with the gaps noted below filled.
--
--  utf8mb4 throughout: Bangla question text needs it, and utf8 (3-byte) would
--  silently truncate on any 4-byte codepoint.
--
--  Money is DECIMAL, never FLOAT. Binary floats cannot represent 0.01, and a
--  wallet debited 1.00 at a time would drift away from its true balance.
-- ===========================================================================

SET NAMES utf8mb4;
SET time_zone = '+06:00';   -- Asia/Dhaka

-- ---------------------------------------------------------------------------
--  Curriculum: class -> subject -> chapter
--  Blueprint §17 stored class/subject/chapter as free-text strings on
--  quiz_attempts. Normalised here: as strings, "Math" and "Mathematics" become
--  different subjects and per-chapter progress (a dashboard feature) cannot be
--  aggregated reliably.
-- ---------------------------------------------------------------------------
CREATE TABLE classes (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(50)  NOT NULL,          -- 'Class 5'
    slug            VARCHAR(50)  NOT NULL UNIQUE,   -- 'class-5'
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0, -- Class 5 only at launch
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subjects (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    class_id        INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(100) NOT NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subject_slug (class_id, slug),
    CONSTRAINT fk_subject_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chapters (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    subject_id      INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    slug            VARCHAR(150) NOT NULL,
    sort_order      SMALLINT     NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chapter_slug (subject_id, slug),
    CONSTRAINT fk_chapter_subject FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Question bank
--  Not in the blueprint's schema at all, though §17 references question_id and
--  the whole product depends on it. Modelled to support the stated rules:
--  10 questions per quiz split 5 easy / 3 medium / 2 hard (§6.1), an
--  explanation shown for every wrong answer, and a textbook source reference
--  (the trust claim in the brand guideline §4.5).
-- ---------------------------------------------------------------------------
CREATE TABLE questions (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chapter_id      INT UNSIGNED NOT NULL,
    difficulty      ENUM('easy','medium','hard') NOT NULL,
    stem            TEXT         NOT NULL,          -- the question itself
    explanation     TEXT         NULL,              -- shown on the Learn view
    source_ref      VARCHAR(255) NULL,              -- 'NCTB Class 5 Math, Ch 3, p.41'
    -- AI-generated questions must be verified before they can be served.
    -- Blueprint v2.0 revision note: independent verification BEFORE publish,
    -- with "Correct Me" as the final safety net, not the primary check.
    origin          ENUM('ai','manual','imported') NOT NULL DEFAULT 'manual',
    status          ENUM('draft','verified','published','retired')
                                 NOT NULL DEFAULT 'draft',
    verified_by     INT UNSIGNED NULL,
    verified_at     TIMESTAMP    NULL,
    times_served    INT UNSIGNED NOT NULL DEFAULT 0,
    times_correct   INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    -- The hot path: "give me N published easy questions in this chapter."
    KEY idx_question_pick (chapter_id, difficulty, status),
    CONSTRAINT fk_question_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Options live in their own table rather than four columns on `questions`,
-- so option order can be shuffled per attempt and a question is not locked
-- to exactly four choices.
CREATE TABLE question_options (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    question_id     INT UNSIGNED NOT NULL,
    label           CHAR(1)      NOT NULL,          -- 'A'..'D'
    body            TEXT         NOT NULL,
    is_correct      TINYINT(1)   NOT NULL DEFAULT 0,
    UNIQUE KEY uq_option_label (question_id, label),
    CONSTRAINT fk_option_question FOREIGN KEY (question_id)
        REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Users
--  phone is UNIQUE but nullable: the launch flow is phone -> OTP, while a
--  guest attempt has no identity at all. MySQL permits many NULLs in a
--  UNIQUE column, which is what makes both cases work in one table.
-- ---------------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NULL,
    phone           VARCHAR(20)  NULL UNIQUE,
    email           VARCHAR(100) NULL,
    password_hash   VARCHAR(255) NULL,              -- NULL while OTP-only
    display_name    VARCHAR(50)  NULL,              -- shown on leaderboards
    class_id        INT UNSIGNED NULL,
    school_name     VARCHAR(150) NULL,
    user_type       ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_class FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Wallet — real money, in Taka. Kept strictly apart from Academic Points
--  (blueprint v2.0 revision note, brand guideline §6.1): points are
--  reputation, never spendable, and must never appear in a wallet total.
--
--  `balance` is a cached running total. The ledger in wallet_transactions is
--  the source of truth; balance exists so a quiz page need not sum every row.
--  Any adjustment must write both, inside one transaction.
-- ---------------------------------------------------------------------------
CREATE TABLE wallets (
    user_id         INT UNSIGNED PRIMARY KEY,
    balance         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id),
    -- A wallet must never go negative. Enforced in the database because this
    -- is the one invariant no application bug is allowed to break.
    CONSTRAINT ck_wallet_non_negative CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED  NOT NULL,
    -- Signed: positive credits (top-up, refund), negative debits (quiz fee).
    amount          DECIMAL(10,2) NOT NULL,
    balance_after   DECIMAL(10,2) NOT NULL,         -- snapshot, for statements
    type            ENUM('topup','quiz_fee','competition_fee','refund','adjustment')
                                  NOT NULL,
    gateway         ENUM('bkash','nagad','manual','system') NULL,
    -- The gateway's own id. UNIQUE so a retried or replayed callback cannot
    -- credit the same top-up twice; NULLs are exempt, so internal debits
    -- (quiz fees) that have no gateway reference are unaffected.
    gateway_txn_id  VARCHAR(100)  NULL UNIQUE,
    description     VARCHAR(255)  NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wallet_txn_user (user_id, created_at),
    CONSTRAINT fk_wallet_txn_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Quiz attempts
--  user_id NULL = guest attempt, identified by session_id (blueprint §17).
--  Guest rows are what a later signup claims, so the result can be saved.
-- ---------------------------------------------------------------------------
CREATE TABLE quiz_attempts (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED  NULL,
    session_id      VARCHAR(100)  NULL,             -- guests
    chapter_id      INT UNSIGNED  NOT NULL,
    score           TINYINT UNSIGNED NULL,          -- correct count, NULL until finished
    percentage      DECIMAL(5,2)  NULL,
    fee_charged     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    -- Only a finished attempt counts toward a streak. An abandoned one must
    -- not, or a student could farm streak credit by starting and quitting.
    status          ENUM('in_progress','completed','abandoned')
                                  NOT NULL DEFAULT 'in_progress',
    -- Set once on completion rather than recomputed: the pass threshold may
    -- change, and a past attempt's streak eligibility must not change with it.
    counts_for_streak TINYINT(1)  NOT NULL DEFAULT 0,
    started_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP     NULL,
    KEY idx_attempt_user (user_id, completed_at),
    KEY idx_attempt_session (session_id),
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_attempt_chapter FOREIGN KEY (chapter_id) REFERENCES chapters(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per question served. The blueprint kept these in a JSON `answers`
-- column; as rows they can be indexed, which is what the "weak chapters" and
-- per-question difficulty stats on the dashboard actually need.
CREATE TABLE quiz_attempt_answers (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_id      BIGINT UNSIGNED NOT NULL,
    question_id     INT UNSIGNED  NOT NULL,
    position        TINYINT UNSIGNED NOT NULL,      -- 1..10 as shown
    selected_option_id INT UNSIGNED NULL,           -- NULL = unanswered
    is_correct      TINYINT(1)    NULL,
    answered_at     TIMESTAMP     NULL,
    UNIQUE KEY uq_attempt_position (attempt_id, position),
    CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id)
        REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_answer_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Academic Points — reputation. Never decrease, never spendable (§5.3).
--  Default tier is 'Starter': blueprint v2.0 fixed the original schema, which
--  defaulted new users to 'Bronze Learner' despite that requiring 100 points.
-- ---------------------------------------------------------------------------
CREATE TABLE user_lifetime_points (
    user_id         INT UNSIGNED PRIMARY KEY,
    total_points    INT UNSIGNED NOT NULL DEFAULT 0,
    current_tier    VARCHAR(20)  NOT NULL DEFAULT 'Starter',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,
    -- National rank is derived by ordering on this column, not stored. A
    -- stored rank would need rewriting for every user on every point award.
    KEY idx_points_rank (total_points DESC),
    CONSTRAINT fk_points_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE point_transactions (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    source          ENUM('streak','correction','chapter_mastery','competition','referral','adjustment')
                                 NOT NULL,
    points          INT          NOT NULL,          -- signed only for admin fixes
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_point_txn_user (user_id, created_at),
    CONSTRAINT fk_point_txn_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Streaks: 25 quizzes, each scoring 60%+, within 100 hours = 1 point.
--  Blueprint v2.0 fixed the rule from "60%+ average" to "each quiz 60%+",
--  which contradicted its own worked example.
-- ---------------------------------------------------------------------------
CREATE TABLE user_quiz_streaks (
    user_id                 INT UNSIGNED PRIMARY KEY,
    current_streak_quizzes  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    current_streak_started_at TIMESTAMP NULL,       -- window opens on quiz 1
    best_streak_quizzes     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_streak_points     INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_streak_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  Competition
-- ---------------------------------------------------------------------------
CREATE TABLE competitions (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(150) NOT NULL,
    year            SMALLINT     NOT NULL,
    entry_fee       DECIMAL(10,2) NOT NULL DEFAULT 99.00,
    round1_opens_at    DATETIME  NULL,
    round1_closes_at   DATETIME  NULL,
    round1_time_limit_minutes SMALLINT NOT NULL DEFAULT 45,
    final_at        DATETIME     NULL,
    status          ENUM('draft','open','round1','final','completed')
                                 NOT NULL DEFAULT 'draft',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE competition_registrations (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    competition_id  INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    fee_paid        TINYINT(1)   NOT NULL DEFAULT 0,
    wallet_txn_id   BIGINT UNSIGNED NULL,           -- the Tk 99 debit
    round1_score    SMALLINT     NULL,
    -- Round 1 is timed (45 min) with randomized questions. Recording the
    -- actual duration is what makes a time-based disqualification reviewable.
    round1_duration_seconds INT  NULL,
    round1_passed   TINYINT(1)   NOT NULL DEFAULT 0, -- top 5% qualify
    final_score     SMALLINT     NULL,
    final_duration_seconds INT   NULL,              -- tie-break, not reg time
    final_rank      SMALLINT     NULL,
    prize_amount    DECIMAL(10,2) NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- One registration per student per competition. The fee is one-time, so
    -- a double-submitted form must not create a second payable row.
    UNIQUE KEY uq_registration (competition_id, user_id),
    CONSTRAINT fk_reg_competition FOREIGN KEY (competition_id) REFERENCES competitions(id),
    CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reg_wallet_txn FOREIGN KEY (wallet_txn_id)
        REFERENCES wallet_transactions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  "Correct Me" — students report bad questions, earn a point when approved.
-- ---------------------------------------------------------------------------
CREATE TABLE correction_requests (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    question_id     INT UNSIGNED NOT NULL,
    claimed_correct_option_id INT UNSIGNED NULL,
    what_is_wrong   TEXT         NULL,
    user_explanation TEXT        NULL,
    source_ref      VARCHAR(255) NULL,              -- optional citation
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by     INT UNSIGNED NULL,
    reviewed_at     TIMESTAMP    NULL,
    review_note     VARCHAR(255) NULL,
    -- Guards the award, not the decision: an approved correction must grant
    -- its point exactly once, even if the admin screen is submitted twice.
    points_awarded  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_correction_status (status, created_at),
    CONSTRAINT fk_correction_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_correction_question FOREIGN KEY (question_id) REFERENCES questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
--  CodeIgniter database session driver (config: sess_save_path = 'ci_sessions')
-- ---------------------------------------------------------------------------
CREATE TABLE ci_sessions (
    id              VARCHAR(128) NOT NULL,
    ip_address      VARCHAR(45)  NOT NULL,
    timestamp       INT UNSIGNED NOT NULL DEFAULT 0,
    data            BLOB         NOT NULL,
    PRIMARY KEY (id),
    KEY idx_sessions_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
