-- ===========================================================================
-- 20261018100000 — Admin panel: sign-in attempt log (lock-out + audit). Idempotent.
-- Admins themselves are users rows (user_type = 'admin'), so no account table.
-- ===========================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email           VARCHAR(100) NOT NULL,
    ip_address      VARCHAR(45)  NOT NULL,
    succeeded       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_admin_attempt_email (email, created_at),
    KEY idx_admin_attempt_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
