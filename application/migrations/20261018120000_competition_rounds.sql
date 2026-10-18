-- ===========================================================================
-- 20261018120000 — Competition rounds: Round 1 time taken (tie-break) and the
-- prize payout record (prizes are paid outside the app). Idempotent.
-- ===========================================================================
SET NAMES utf8mb4;

SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE competition_registrations ADD COLUMN round1_duration_seconds INT UNSIGNED NULL AFTER round1_submitted_at', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'competition_registrations' AND column_name = 'round1_duration_seconds');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE competition_registrations ADD COLUMN prize_paid_at TIMESTAMP NULL AFTER prize_amount', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'competition_registrations' AND column_name = 'prize_paid_at');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE competition_registrations ADD COLUMN prize_payout_ref VARCHAR(100) NULL AFTER prize_paid_at', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'competition_registrations' AND column_name = 'prize_payout_ref');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE competition_registrations ADD COLUMN prize_paid_by INT UNSIGNED NULL AFTER prize_payout_ref', 'DO 0')
  FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'competition_registrations' AND column_name = 'prize_paid_by');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
