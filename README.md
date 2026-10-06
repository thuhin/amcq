# AcademicMCQ

Chapter-based MCQ practice for Bangladeshi students, from Tk 1 per quiz.
Practice → learn from mistakes → build an academic rank → compete nationally.

> Easy learning. Healthy competition. Lifetime recognition. Fair chance for all.

## Stack

| Layer | Choice |
|---|---|
| Framework | CodeIgniter 3.1.13 (vendored in `system/`, as in the sibling projects) |
| PHP | 7.4 |
| Database | MySQL 8 (`utf8mb4` — Bangla content requires it) |
| Front end | Hand-written CSS on design tokens, no build step |

> **Note on the framework choice.** Master Blueprint v2.0 §7 specifies Django or
> Laravel. CodeIgniter 3 was chosen instead to match the existing in-house
> projects and deployment setup. CI3 reached end of life in 2022 and receives
> security fixes only, so the question-bank and payment code should avoid relying
> on framework-level protections and validate at the application layer.

## Layout

```
application/
  config/      env.php (gitignored) holds everything per-server
  controllers/ Home.php
  views/       layout/ + home/
asset/css/     brand.css (design tokens), home.css
database/      schema.sql
system/        CodeIgniter 3.1.13, committed
```

## Setup

1. **Config**

   ```bash
   cp application/config/env.php.example application/config/env.php
   ```

   Fill in the URLs and database credentials for *this* machine. `env.php` is
   gitignored and must never be copied between servers — it is the only file
   that differs between localhost and production. Tracked config files read its
   constants, so uploading `config.php` cannot point production at a dev host.

2. **Database**

   ```bash
   mysql -u root -p -e "CREATE DATABASE amcq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p amcq < database/schema.sql
   ```

3. **Environment**

   `index.php` reads `$_SERVER['CI_ENV']`, so the environment comes from the web
   server rather than a tracked file. In nginx:

   ```nginx
   fastcgi_param CI_ENV production;
   ```

   Unset falls back to `development`.

4. **Run locally**

   ```bash
   php -S 127.0.0.1:8899 -t .
   ```

## Conventions

- **Three value systems never mix.** Wallet balance is real Taka (`DECIMAL`),
  Academic Points are reputation (never spendable, never decrease), quiz score
  is performance. Separate tables, separate CSS classes, separate labels.
- **Money is `DECIMAL`, never `FLOAT`.** A wallet debited Tk 1 at a time would
  drift from its true balance under binary floating point.
- **Product rules live in `constants.php`**, not scattered through controllers —
  quiz fee, streak threshold, streak window, question count.
- **Colors come from tokens in `brand.css`.** Green and red are reserved for
  quiz correctness; reusing them elsewhere weakens the one signal that matters.
- **Correctness is never signalled by color alone** — always an icon and a label
  as well.

## Status

Scaffold only. The home page renders real markup against hard-coded sample data;
nothing is wired to the database yet.

Build order (brand guideline §18): home → practice selector → chapter → quiz →
result → learn/explanation → sign up → dashboard → progress → rank → wallet →
competition → leaderboard → Correct Me → profile → legal pages.

## Design source

`AcademicMCQ_Design_Package/` — 12 mockups, the UI brand guideline, and Master
Blueprint v2.0. Where the mockups and the guideline disagree on copy, the
mockups are newer and win.
