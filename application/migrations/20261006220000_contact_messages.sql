-- ===========================================================================
-- 20261006220000 — Contact Us messages. Idempotent.
-- ===========================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id         INT UNSIGNED NULL,
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
    CONSTRAINT ck_contact_reachable CHECK (phone IS NOT NULL OR email IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
