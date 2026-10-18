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
| Search, Notifications | header/sidebar in designs | `/search`, `/notifications` |
| FAQ in Bangla and English (toggle; remembered for the visit; a student's profile language is the default) | site design language | `/faq`, `/faq?lang=en` |
| Certificates: list, public printable page, verify a code | sidebar in design 12 | `/certificates`, `/certificate/{code}`, `/certificates/verify` |
| Invite Friends (tracking only, no reward) | no mockup; site design language | `/invite`, `/signup?ref=CODE` |
| Contact Us | no mockup; site design language | `/contact` |
| Top Schools This Week, testimonials | homepage designs | `/schools`, `/testimonials` |
| Leaderboard, Competition, Pricing, How It Works | §4.6–4.8 | |
| Competition rounds: Round 1 and the final | §10; quiz design 08 | `/competition/round1`, `/competition/final` |
| **Admin panel**: questions, Correct Me, messages, testimonials, students, running the competition | no mockup; site design language | `/admin` ([docs/admin-panel.md](docs/admin-panel.md)) |

Rules implemented: 5 easy / 3 medium / 2 hard per quiz; Tk 1 wallet debit for
signed-in students; 3 free quizzes for guests, kept on signup; streak of 25 quizzes
at 60%+ each within 100 h = 1 point; chapter mastery; tiers; national rank;
one-time Tk 99 competition registration; Round 1 (10/10/10, weighted 1/1.5/2, 45 min,
own random set per student), top 5% qualify (ties at the cut go through), the final
(15/20/15, 75 min, one question at a time, no going back), ties broken by time taken,
prizes and winner points from the prize table, certificates for every participant.

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
- **Competition decisions still open** (see `docs/admin-panel.md`, "Competition"):
  - The final is taken online in the app; proctoring is arranged outside it.
  - Prize money is paid outside the app; an admin records each payment.
  - The demo data shows "+10 for 85%+ in Round 1" and "+20 for final participation",
    but no blueprint rule in the repo defines them, so they are not awarded. Only the
    prize table's winner points are.
  - Blueprint v2.0 says Round 1 is "max 50", but 10/10/10 at 1/1.5/2 is 45. The app
    shows the real maximum (45).
- **Referral rewards.** Invites are tracked; there is no reward yet (Academic Points
  come only from streak, mastery, competition and corrections).
- **Admin money tools.** Admins can see a student's wallet and points but cannot change
  them: no refunds or adjustments from the panel.
- **Legal pages.** Placeholders; the text must come from the business.
- **Contact details are placeholders.** Address, phone, email and hours on the
  Contact Us page and footer come from `CONTACT_*` in `constants.php`; set the
  real ones there (or per server in `env.php`). Messages are stored in
  `contact_messages`, not emailed; read them in `/admin/messages`.
- **Intro video: no voice-over.** How It Works plays `asset/video/intro.mp4`, a
  39-second animated walkthrough with English and Bangla on screen and no sound.
  It is rendered from `docs/intro-video/intro.html` by
  `python docs/intro-video/render.py` (needs PHP, Chrome and the internet), which
  reads the numbers it shows from `constants.php`: re-run it when a rule changes.
  A real recording can replace it: set `INTRO_VIDEO_URL` (a YouTube embed URL)
  in `env.php`, and that wins.
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

5. **Admin**: create one from the command line (it asks for email, name, password;
   there is no web screen that creates admins), then open `/admin`:

   ```bash
   php index.php cli/admin create
   ```

   `database/reset_local.sh` also loads a development admin, `admin@amcq.test`
   (password in `database/test_data/20261018100100_admin_test_data.sql`). That
   account is test data: never load it on a server.

## Migrations (existing databases)

Fresh install: `database/reset_local.sh` builds everything from `database/`.
Existing database: apply only what changed, without wiping data:

```bash
./migrate.sh               # application/migrations/*.sql: schema + reference data
./migrate.sh --test-data   # also database/test_data/*.sql (refused when CI_ENV=production)
./migrate.sh --list        # show what would run
```

Every file is idempotent (safe to re-run) and runs in timestamp order. The
password is asked for at run time, never read from a file. Verified: a database
built from the previous schema plus `./migrate.sh --test-data`, run twice, is
identical to a fresh build, in structure and in data.

## Database files

| File | Contents | Production? |
|---|---|---|
| `database/schema.sql` | 40 tables. **Drops and recreates them.** | First install only |
| `database/seed.sql` | Tiers, difficulty weights, prizes, career ladder, curriculum | Yes |
| `database/seed_sample_questions.sql` | 50 AI-drafted questions, `origin='ai'` | **No**, not until a teacher has checked them |
| `database/seed_demo.sql` | Fake students, wallets, points | **Never** |
| `database/test_data/*.sql` | Test data per migration (e.g. sample contact messages) | **Never** |
| `application/migrations/*.sql` | Incremental, idempotent schema changes | Yes, via `./migrate.sh` |

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

`env.php` needs `ENABLE_LIVE_PAYMENTS` and `ENABLE_AI_GENERATION` set to `FALSE` (the
example file says so for development), or the simulated top-up checks fail. If `php`
is not on the PATH, set `PHP_BIN` to a PHP 7.4 binary (the admin command-line checks
call it). Point the script at another URL with `AMCQ_URL`.

309 checks (89 for the site, 129 for the admin panel, 79 for students admin, invites, the competition rounds and certificates, 9 for the bilingual FAQ, 3 for the intro video) drive it like a browser and
verify the database after each step:
grading, the 5/3/2 mix, ownership, CSRF, the guest limit, sign-in, Tk 1 debits,
streak and mastery rules, double-submit safety, the timer and pause, Try Harder,
the design pages, that every wallet
and points total equals the sum of its ledger, and the admin panel's sign-in
lock-out, question workflow, Correct Me decisions, messages and testimonial consent.

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
