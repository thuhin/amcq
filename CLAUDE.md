# AcademicMCQ — working notes

CodeIgniter 3.1.13 + MySQL 8 + PHP 7.4. See README.md for setup.

## Source of truth (decided by the owner)

- **Look and features come from the design mockups** (12 PNGs in the design package).
  Build what they show; match their layout, colours and components.
- **Calculations come from the blueprint and stay as built**: 10 questions (5/3/2),
  Tk 1, 25-quiz streak at 60%+ each, points only from streak/mastery/competition/
  corrections. Where a mockup shows a different number, display the real value.
- Styles: `brand.css` (tokens sampled from the mockups) → `app.css` (base
  components) → `design.css` (the designed pages). New page styles go in design.css.

## Before changing anything

- **Product rules are constants**, defined once in `application/config/constants.php`
  (`QUIZ_FEE_TAKA`, `STREAK_PASS_PERCENTAGE`, `STREAK_REQUIRED_QUIZZES`,
  `STREAK_WINDOW_HOURS`, `QUIZ_QUESTION_COUNT`). Never re-type these numbers in a
  controller or a view — the blueprint's own revision history is a list of places
  where a duplicated rule drifted out of sync.
- **Never commit `application/config/env.php`.** It is gitignored. Per-server URLs,
  DB credentials and cookie settings live there and nowhere else.
- **CSS uses tokens only.** Add a variable to `asset/css/brand.css` rather than a
  raw hex value in a component stylesheet.

## Changing the database

Every schema change goes in **both** places:
1. `database/schema.sql` (+ `seed.sql` for reference data): fresh installs.
2. A new `application/migrations/<YYYYMMDDHHMMSS>_<name>.sql`: existing databases.
   Must be idempotent: `CREATE TABLE IF NOT EXISTS`, column adds guarded through
   information_schema (MySQL 8 has no `ADD COLUMN IF NOT EXISTS`), `INSERT IGNORE`.
Test data for it goes in `database/test_data/` with a later timestamp, never in migrations.
Check: build the previous commit's schema, run `./migrate.sh --test-data` twice, and
diff `mysqldump --no-data` against a fresh `database/reset_local.sh`.

## Rules that are easy to get wrong

- **Wallet ≠ Academic Points.** Wallet is real money in Taka and is spendable.
  Academic Points are reputation: they never decrease and can never be spent or
  converted. Blueprint v1.1 removed the "1 point = Tk 1" framing deliberately —
  it put the product under Bangladesh's e-money rules. Do not reintroduce any
  exchange between the two.
- **Streak rule is "each quiz scores 60%+", not "60%+ average."** v2.0 fixed this;
  the average reading contradicts the blueprint's own worked example.
- **New users start at the `Starter` tier, not Bronze.** Bronze requires 100
  points, so defaulting a 0-point user to Bronze breaks the tier ladder.
- **Only a completed attempt counts toward a streak.** `quiz_attempts.status`
  must be `completed`; otherwise a student can farm credit by starting and
  quitting quizzes.
- **A wallet may never go negative.** Enforced by a CHECK constraint, and the
  balance plus its ledger row must be written in one transaction.
- **Guest attempts are real.** `quiz_attempts.user_id` is NULL for guests, keyed
  by `session_id`. A visitor must be able to finish a quiz and see a result
  before being asked to register.

## Post-MVP, do not build yet

WhatsApp Challenge, Friday Fun, and School Competition are all marked POST-MVP in
blueprint v2.0, and the WhatsApp Challenge has an unresolved internal
contradiction plus an open legal question about peer point wagering.

## Where things live

- `application/models/` hold the rules: `Quiz_model` (start, grade, streak,
  mastery), `Wallet_model` (money, row-locked), `Points_model` (idempotent awards,
  tiers, rank), `Curriculum_model`, `User_model` (OTP), `Competition_model`.
- Controllers are thin; views are plain PHP under `application/views/`.
- `MY_Controller` resolves the user or guest and renders the layout.

## More rules that are easy to get wrong

- **Timezone.** PHP and the MySQL session are both pinned to +06:00 (constants.php
  and MY_Controller). Comparing a DB timestamp to PHP `time()` without this was off
  by six hours and silently broke the quiz timer and streak window.
- **Points awards are idempotent by key**, not by checking first: `Points_model::award()`
  relies on `UNIQUE (user_id, source, ref_type, ref_id)`. Always pass a real ref.
- **A quiz needs a full 5/3/2 set** of published questions or it shows "Coming Soon".
- **Escape everything** with `e()`; question text goes through `math_text()`, which
  escapes first and only then adds fraction markup.
- **Never show the OTP outside development.** `Auth::login()` only flashes it when
  `ENVIRONMENT === 'development'`.

## Verifying a change

```bash
database/reset_local.sh                      # fresh data; e2e assumes it
php -S 127.0.0.1:8899 tests/dev_router.php &
python3 tests/e2e.py                         # env.php URLs must point at :8899
```
