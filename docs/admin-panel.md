# Admin panel

**Status: built.** Open `/admin`. This document says how it works and why.
The rules it enforces are the ones already in `CLAUDE.md` and the blueprint; the
panel adds no product rules and changes no calculation.

## 1. What it is for

Before this, every operator task meant editing MySQL by hand: publishing
questions, deciding Correct Me reports, reading Contact Us messages, adding
testimonials. The admin panel replaces that and nothing else.

## 2. Scope

| Included | Screen |
|---|---|
| Sign in, change own password | `/admin/login`, `/admin/password` |
| What needs attention, quiz coverage per chapter | `/admin` |
| Write, edit, verify, publish, retire questions | `/admin/questions` |
| Decide Correct Me reports | `/admin/corrections` |
| Read and track Contact Us messages | `/admin/messages` |
| Add and publish testimonials (with consent) | `/admin/testimonials` |
| See competitions and registration counts, read only | `/admin/competition` |

| Not included | Why |
|---|---|
| Opening or running the national competition | The app has no Round 1 or Final code. Opening registration would let students pay Tk 99 for an event that cannot run. |
| Student accounts, wallet or points adjustments | Money and reputation need their own rules and an audit trail. Separate work. |
| Creating admins from a screen | Command line only (section 3). |
| WhatsApp Challenge, Friday Fun, School Competition | POST-MVP in blueprint v2.0 (`CLAUDE.md`). |
| Payments, SMS, sending email | Not built anywhere in the app. |

## 3. Signing in

- An admin is a row in `users` with `user_type = 'admin'`, an `email` and a
  `password_hash`. The schema already had all three, so there is no account table.
- Admins are created **from the command line only**. A screen that can create
  admins is a way to become one. The tool asks for each value instead of taking
  arguments: CodeIgniter refuses `@` in URI segments, and an argument would put
  a password in the process list. Piped input works, one line per prompt.

  ```bash
  php index.php cli/admin create     # email, name, password
  php index.php cli/admin passwd     # email, new password; also clears a lock-out
  php index.php cli/admin list
  php index.php cli/admin suspend    # email   (refused for the only active admin)
  php index.php cli/admin enable     # email
  ```

- Sign in at `/admin` with email and password. The student phone/OTP login never
  signs anyone into `/admin`, and an admin session is not a student session:
  they use separate session keys.
- Passwords: at least `ADMIN_PASSWORD_MIN_LENGTH` (10) characters, stored with
  `password_hash`, upgraded automatically if the hash cost ever rises.
- **Lock-out.** `ADMIN_LOGIN_MAX_FAILS` (5) wrong passwords for one email since
  its last success, or `ADMIN_LOGIN_MAX_FAILS_IP` (20) from one address, inside
  `ADMIN_LOGIN_LOCK_MINUTES` (15) locks sign-in. While locked even the right
  password is refused, and refused attempts are not recorded (waiting does not
  extend the lock). Attempts live in `admin_login_attempts`. Trade-off: someone
  can deliberately lock a known admin email for 15 minutes; a password reset
  from the command line clears it.
- A wrong email and a wrong password give the same message and take the same
  time, so the form does not reveal which emails are admins.
- The session id is renewed at sign-in and after a password change. It ends
  after `ADMIN_SESSION_IDLE_MINUTES` (60) without a request (student sessions
  last 30 days; admin sessions must not). The account is read from the database
  on every request, so a suspended admin is signed out at once.
- Admin pages send `Cache-Control: no-store`, `X-Frame-Options: DENY` and
  `noindex`. Every change is a POST protected by the existing CSRF setting; a
  GET to a change URL is refused with 405.

## 4. Day-to-day

### 4.1 Add a question and put it in front of students
1. Questions, **New question**. Pick the chapter (and topic), difficulty, write
   the stem and four options, select the round button beside the correct one,
   add the explanation and the source. It is saved as a **draft**, origin
   "written by hand".
2. **Send to review**, then **Verify and publish** (or **Verify** and **Publish**
   separately). Verifying records who and when.
3. Only published questions are served. A chapter is playable at 5 easy, 3
   medium and 2 hard published; below that students see "Coming Soon".

### 4.2 Decide a Correct Me report
1. Corrections lists pending reports, oldest first (tabs: Pending, Approved,
   Not approved).
2. Open one: the question as students see it with the marked answer and the
   option the student says is right, their explanation and source, and the
   question's current status.
3. **Approve**: the student earns `CORRECTION_APPROVED_POINTS` (1) Academic
   Point and is notified. By default the question is pulled back to *In review*
   so a question found wrong stops being served; open it, fix it, publish it
   again. Untick the box to leave it live.
4. **Not approved**: a reason is required. Students read it on their Correct Me
   page as "Reviewer: ...".
5. A decision is final. Points never decrease.

### 4.3 Answer a contact message
Messages lists newest first, filterable by status and topic. Opening a *new*
message marks it *read*, except straight after you choose "Mark as new". Nothing
is sent from the panel: reply by phone or email, then mark it *replied* or
*closed*. The reference (`AMCQ-000042`) matches what the sender was shown.

### 4.4 Add a testimonial
Name, class, school, quote (500 characters at most), rating, position. Tick
**guardian consent** only when you have it. A testimonial **cannot be published
without recorded consent**. Consent covers the exact words: changing the name or
the quote of a consented testimonial clears the consent and unpublishes it until
consent is recorded again. Only an unpublished testimonial can be deleted.
The three samples from the demo seed show "Not recorded"; they are placeholders
and should be replaced with real ones.

### 4.5 Check quiz coverage
The dashboard lists the active chapters that are **not** playable, with published
counts against 5/3/2 and what each lacks; "Show all chapters" lists the rest. It
uses `Curriculum_model::mix_is_playable`, the same rule as the student site, so
the two cannot disagree. Only active classes are listed (Class 5 at launch).

## 5. Rules enforced

**Question workflow**

| From | Can move to |
|---|---|
| draft | in review, retired |
| in review | verified, published (verify and publish), draft, retired |
| verified | published, in review, retired |
| published | in review, retired |
| retired | draft |

- A draft cannot be published directly: it goes through review first.
- **To verify or publish, a question must be complete**: exactly four options A
  to D, one correct, all different, an explanation, and a source (blueprint v2.0:
  verification and a source before publish).
- **Saving needs** a chapter, difficulty, stem, four different non-empty options
  and one marked correct.
- **Editing live content sends it back to review.** Changing the stem, an option,
  the correct answer, the difficulty or the chapter of a verified or published
  question moves it to *In review* and clears who verified it. The screen says
  so. Editing only the explanation, source or topic does not. Line-break and
  spacing differences from the browser do not count as edits.
- **Options are updated in place by label**, never deleted and re-created, because
  `quiz_attempt_answers.selected_option_id` points at them. Finished attempts keep
  the `is_correct` they were graded with.
- **No hard delete.** Questions are retired, so history stays intact.
- Approving a report awards its point through `Points_model::award()` with the
  report id as the reference, inside one transaction on the locked report row, so
  double clicks and two admins cannot award twice. Wallet and points never meet.

## 6. Data

**No account table.** Existing columns used: `users.user_type/email/password_hash`,
`questions.verified_by/verified_at`, `correction_requests.reviewed_by/reviewed_at/review_note`,
`contact_messages.status`, `testimonials.consent_at/is_published`.

**One new table**, `admin_login_attempts(id, email, ip_address, succeeded, created_at)`,
in `database/schema.sql` and in the idempotent migration
`application/migrations/20261018100000_admin_panel.sql`. Checked as `CLAUDE.md`
asks: the previous commit's schema plus the migration and test data, applied twice,
has a structure identical to a fresh build and no duplicated rows.

**Constants** in `application/config/constants.php`: `CORRECTION_APPROVED_POINTS` 1,
`ADMIN_PASSWORD_MIN_LENGTH` 10, `ADMIN_SESSION_IDLE_MINUTES` 60,
`ADMIN_LOGIN_MAX_FAILS` 5, `ADMIN_LOGIN_MAX_FAILS_IP` 20, `ADMIN_LOGIN_LOCK_MINUTES` 15,
`ADMIN_PAGE_SIZE` 25. The student Correct Me page now reads
`CORRECTION_APPROVED_POINTS` instead of printing "+1".

**Test data**, `database/test_data/20261018100100_admin_test_data.sql`: a development
admin (`admin@amcq.test`, password in the comment at the top of the file), a draft,
an in-review and a verified question, a pending Correct Me report and an unconsented
testimonial. Like all test data it is **never loaded on production or a shared
server**; create the real admin from the command line there.

## 7. Where the code is

| Path | Role |
|---|---|
| `application/config/routes.php` | The `admin/...` routes (explicit) |
| `application/core/Admin_Controller.php` | Base class: admin check, idle timeout, headers, admin layout. Loaded from `MY_Controller.php`. |
| `application/controllers/admin/Admin_*.php` | `auth`, `dashboard`, `questions`, `corrections`, `messages`, `testimonials`, `competition`. Prefixed so class names never collide with student controllers (`Testimonials`, `Competition`). Thin. |
| `application/controllers/cli/Admin.php` | The command-line tool. Refuses to run outside the command line. |
| `application/models/` | `Admin_model` (accounts, sign-in, lock-out), `Question_model` (workflow and rules), `Testimonial_model` (consent rule), plus additions to `Correction_model` (queue, approve, reject), `Contact_model` (inbox, status), `Competition_model` (overview). Rules live here, not in controllers. |
| `application/views/admin/` | Plain PHP; everything through `e()`, question text through `math_text()`. |
| `asset/css/design.css` | An "Admin" section reusing the site's buttons, cards, tables and fields; tokens only. |
| `asset/js/app.js` | One optional enhancement: list only the topics of the chosen chapter. The form works without it (the server checks the pair). |

The layout is a top bar and a left **side menu** (Dashboard, Questions,
Corrections, Messages, Testimonials, Competition), with a count on the sections
that have something waiting. From 900px wide it is a sticky sidebar; on a phone
it becomes a menu button at the left of the top bar. The links are drawn once, in
`views/admin/partials/nav.php`, and used for both. On a phone the coverage table
also moves each chapter's status under its name.

## 8. Security

- `password_hash`; same message and timing for unknown email and wrong password.
- Lock-out per email and per IP; session renewed at sign-in; 60-minute idle timeout; account re-checked every request.
- Separate admin session; a student session never grants admin access.
- CSRF on every POST; GET never changes data, with one deliberate exception: opening a *new* message marks it *read* (idempotent).
- Admins cannot be created or promoted from the web.
- Output escaped everywhere; no-store, noindex, frame denial on admin pages; the post-login redirect only ever goes to an admin path.
- **Use HTTPS.** Over plain HTTP the admin password crosses the network in the clear. Put TLS in front before real use.
- Optional extra layer at the web server: restrict `/admin` by IP address or HTTP Basic Auth in nginx.

## 9. Setting it up

```bash
./migrate.sh                                  # existing database (a fresh one: database/reset_local.sh)
php index.php cli/admin create                # asks for email, name, password
# open /admin
```

On a real server: upload the code, apply the migration, create the admin with the
command above, and do **not** load `database/test_data/`.

**Development shortcut.** To fill in the test login with one click, define
`ADMIN_DEV_LOGIN_EMAIL` and `ADMIN_DEV_LOGIN_PASSWORD` in your local `env.php` (see
`env.php.example`) and click the shield on `/admin/login`. Because it prints a
password into the page, it is rendered only when all three hold: the app runs in
`development`, the server name is local (`.test`, `.localhost` or `127.0.0.1`), and
both constants are defined. The credentials live only in the gitignored `env.php`,
never in tracked code.

On a shared **dev server** (not a local host name) it can be switched on too, by
adding `define('ADMIN_DEV_LOGIN_ALLOW_REMOTE', TRUE);` to that server's `env.php`.
Then anyone who can open `/admin/login` can read the password in the page source,
so restrict `/admin` by IP in nginx, and never do this on production (production
mode never shows it anyway).

## 10. Tests

`tests/e2e.py` runs **218 checks, all passing**: the original 89 plus 129 for the
panel, driven like a browser with the database checked after each step:

- sign-in: the dev shortcut is rendered only with local credentials and never leaks the password, same message for wrong email and password, lock-out, right password refused while locked, student session refused, CSRF and GET refusals, idle timeout, suspended admin signed out, password change
- command line: every refusal, create, duplicate (case-insensitive), suspend/enable, the only active admin protected, `passwd` clearing a lock-out
- questions: each validation, escaping, the verify gate, publish, the edit-returns-to-review rules, option ids unchanged, CRLF not counted as an edit, retire and restore, filters
- coverage: 9 of 10 published is not playable and says what is missing; the 10th makes the chapter playable for students; retiring one makes it "Coming Soon" again
- corrections: approve gives exactly one point (also on a double submit), notifies, pulls the question; reject needs a reason and gives nothing; decided reports stay decided; the student sees both outcomes
- messages, testimonials (consent gate, consent cleared on new wording, homepage follows), competition read only

Setup: `database/reset_local.sh` first; `env.php` needs `ENABLE_LIVE_PAYMENTS` and
`ENABLE_AI_GENERATION` set to `FALSE` (as `env.php.example` advises) and its URLs
matching `AMCQ_URL`; set `PHP_BIN` to a PHP 7.4 binary if `php` is not on the PATH
(the command-line checks call it).

## 11. Known limits

- One role. Anyone who can sign in can do everything above, and the same admin may write and verify a question.
- No screen to see or manage students, wallets or points.
- Testimonial photos are not editable here (the column exists, left empty).
- The lock-out can be triggered against a known admin email (section 3).

## 12. Decisions taken (change any of them)

| Decision | Built as | Alternative |
|---|---|---|
| Who creates admins | Command line only | A screen for a "super admin" |
| Roles | One role | Reviewer and editor roles |
| Two-person rule | Not enforced | Verifier must differ from author |
| Source before verification | Required | Optional |
| Editing a live question | Returns to review | Stays live with a warning |
| Approving a report | Pulls the question by default | Leaves it live |
| Competition | Read only | Status controls, once rounds exist |
| Opening a message | Marks it read | A "mark read" button only |
| Changing a consented testimonial's wording | Clears consent, unpublishes | Keeps consent |
| Extra nginx restriction on `/admin` | Recommended, optional | App login only |
