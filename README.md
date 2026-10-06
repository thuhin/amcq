# AcademicMCQ

Chapter-based MCQ practice for Bangladeshi students, from Tk 1 per quiz.
Practice → learn from mistakes → build an academic rank → compete nationally.

> Easy learning. Healthy competition. Lifetime recognition. Fair chance for all.

## Stack

| Layer | Choice |
|---|---|
| Framework | CodeIgniter 3.1.13 (vendored in `system/`, as in the sibling projects) |
| PHP | 7.4 |
| Database | MySQL 8, `utf8mb4` (Bangla content needs it) |
| Front end | Server-rendered views, hand-written CSS on design tokens, ~60 lines of optional JS |

> **Framework choice.** Master Blueprint v2.0 §7 specifies Django or Laravel.
> CodeIgniter 3 was chosen to match the existing in-house projects. CI3 reached
> end of life in 2022 and gets security fixes only; this matters more than usual
> because the app handles real money.

## Design is the source of truth

The mockups in the design package decide **look and features**; the Master Blueprint
decides **calculations** (quiz length and mix, fees, streak, points, tiers). Where a
mockup shows a number the rules would not produce, such as "+15 points" or "20 MCQs",
the page shows the real calculated value instead.

| Page | Design |
|---|---|
| Home | 07 (desktop), 04 (phone) |
| Chapter page | 11 |
| Quiz | 08 |
| Result | 10 |
| Answer review | 09 |
| Dashboard + sidebar | 12 |

Illustrations in `asset/img/design/` are cropped from the mockups at mockup
resolution. Replace them with the original artwork when available; the filenames
can stay the same.

## What works

| Page | Design | URL |
|---|---|---|
| Home | 01 | `/` |
| Practice selector | guideline §4.2 | `/practice` |
| Chapter page | 11 | `/practice/class-5/mathematics` |
| Quiz | 08 | `/quiz/{id}` |
| Result | 10 | `/quiz/{id}/result` |
| Answer review | 09 | `/quiz/{id}/review` |
| Dashboard | 12 | `/dashboard` |
| Sign in (phone → OTP → name/class) | §4.9 | `/login` |
| My Progress, Rank, Wallet, Profile | §5.2–5.8 | `/progress`, `/rank`, `/wallet`, `/profile` |
| Correct Me | §5.5 | `/correct-me` |
| Search, Notifications, Certificates, FAQ | header/sidebar in designs | `/search`, `/notifications`, `/certificates`, `/faq` |
| Top Schools This Week, testimonials | homepage designs | `/schools`, `/testimonials` |
| Leaderboard, Competition, Pricing, How It Works | §4.6–4.8 | |

Rules implemented: 5 easy / 3 medium / 2 hard per quiz; Tk 1 wallet debit for
signed-in students; 3 free quizzes for guests, kept on signup; streak of 25 quizzes
at 60%+ each within 100 h = 1 point; chapter mastery; tiers; national rank;
one-time Tk 99 competition registration.

Design features: four-step practice selector, timer pause (paused time is not
counted, answers are refused while paused), "Try Harder Quiz" (same length and
rules, more hard questions), chapter coverage ("17 / 30 questions completed"),
day-streak and achievement badges, Top Schools This Week (average per quiz, so
size doesn't win), What Students Say, Watch Video.

## Not built yet

- **Payments.** No bKash/Nagad integration. In development, wallet top-ups are
  simulated; elsewhere the page says "coming soon".
- **SMS.** No gateway. In development the OTP is shown on screen; elsewhere sign-in
  cannot complete until a gateway is added in `Auth::login()`.
- **Admin.** No screens to review Correct Me submissions, verify questions or run
  the competition rounds. Competition registration is closed (`status = 'draft'`).
- **Referrals, certificates, notifications UI.** Tables exist; no pages yet.
- **Legal pages.** Placeholders; the text must come from the business.
- **Intro video.** Set `INTRO_VIDEO_URL` (a YouTube embed URL) in `env.php`;
  until then How It Works shows "coming soon".
- **Testimonials.** Only the demo seed has any (the design's three samples).
  Real ones need a real student and recorded guardian consent.
- **Content.** 50 sample questions (Fractions, Decimals, Percentage). Other
  chapters show "Coming Soon" until they hold a full 5/3/2 set.

## Local setup

1. **Config**: `cp application/config/env.php.example application/config/env.php`
   and fill in the database credentials. `env.php` is gitignored.

2. **Database** (as MySQL root, once):

   ```sql
   CREATE DATABASE amcq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'amcq'@'localhost' IDENTIFIED BY '...';
   CREATE USER 'amcq'@'127.0.0.1' IDENTIFIED BY '...';
   GRANT ALL PRIVILEGES ON amcq.* TO 'amcq'@'localhost', 'amcq'@'127.0.0.1';
   ```

   Then load everything:

   ```bash
   database/reset_local.sh            # schema + reference data + sample questions + demo users
   database/reset_local.sh --no-demo  # schema + reference data only
   ```

3. **Site at http://amcqtest.com**

   ```bash
   sudo cp deploy/nginx-amcqtest.conf /etc/nginx/sites-available/amcq
   sudo ln -s /etc/nginx/sites-available/amcq /etc/nginx/sites-enabled/amcq
   echo "127.0.0.1       amcqtest.com" | sudo tee -a /etc/hosts
   sudo nginx -t && sudo systemctl reload nginx
   ```

   The web root is the repo root, so the nginx config blocks `database/`,
   `application/`, `system/`, `tests/`, `.git` and `*.sql|*.sh|*.md|*.py`.
   Keep those rules in any production config.

4. **Demo login**: phone `01700000001` (Rahim, Silver Scholar, ৳50 wallet). The OTP
   appears on screen in development.

## Database files

| File | Contents | Production? |
|---|---|---|
| `database/schema.sql` | 39 tables. **Drops and recreates them.** | First install only |
| `database/seed.sql` | Tiers, difficulty weights, prizes, career ladder, curriculum | Yes |
| `database/seed_sample_questions.sql` | 50 AI-drafted questions, `origin='ai'` | **No**, not until a teacher has checked them |
| `database/seed_demo.sql` | Fake students, wallets, points | **Never** |

## Cron

```bash
php index.php cli/schools refresh     # weekly: Top Schools This Week
```

## Tests

```bash
database/reset_local.sh
php -S 127.0.0.1:8899 tests/dev_router.php &
python3 tests/e2e.py        # set env.php URLs to http://127.0.0.1:8899/ first
```

80 checks drive the site like a browser and verify the database after each step:
grading, the 5/3/2 mix, ownership, CSRF, the guest limit, sign-in, Tk 1 debits,
streak and mastery rules, double-submit safety, the timer and pause, Try Harder,
the design pages, and that every wallet
and points total equals the sum of its ledger.

## Conventions

- **Three value systems never mix.** Wallet = Taka (`DECIMAL`), Academic Points =
  reputation (never spent, never decrease), quiz score = performance. Separate
  tables, models, CSS classes and labels.
- **Product rules live in `constants.php`.** Fee, mix, streak, mastery, OTP.
- **One clock.** PHP and MySQL are both pinned to Asia/Dhaka (+06:00).
- **Colors come from tokens in `brand.css`.** Green and red are for correctness only.
- **Correctness is never color alone.** Always an icon and a label too.

## Design source

`AcademicMCQ_Design_Package/`: 12 mockups, the UI brand guideline, Master
Blueprint v2.0.
