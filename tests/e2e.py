"""
End-to-end checks against a running dev server and the local database.

    database/reset_local.sh                      # fresh data: the checks assume it
    php -S 127.0.0.1:8899 tests/dev_router.php &
    python3 tests/e2e.py

Drives the site like a browser (cookies, CSRF tokens, form posts) and checks
the database after each step. Needs the env.php URLs to point at AMCQ_URL.
"""
import re, sys, subprocess, urllib.request, urllib.parse, http.cookiejar, os
B = os.environ.get("AMCQ_URL", "http://127.0.0.1:8899")
PW = re.search(r"DB_PASSWORD', '([^']*)'", open(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'application', 'config', 'env.php')).read()).group(1)
def sql(q):
    r = subprocess.run(["mysql","-h","127.0.0.1","-u","amcq","amcq","-N","-B","-e",q], env={**os.environ,"MYSQL_PWD":PW}, capture_output=True, text=True)
    if r.returncode: raise SystemExit("SQL ERR: "+r.stderr)
    return [l.split("\t") for l in r.stdout.strip().splitlines()] if r.stdout.strip() else []
def val(q): r = sql(q); return r[0][0] if r else None

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
class Browser:
    def __init__(s):
        s.jar = http.cookiejar.CookieJar()
        s.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(s.jar), NoRedirect)
    def req(s, path, data=None, follow=True):
        url = path if path.startswith("http") else B+path
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        for _ in range(8):
            try:
                r = s.op.open(urllib.request.Request(url, data=body)); return r.status, r.geturl(), r.read().decode()
            except urllib.error.HTTPError as e:
                if e.code in (301,302,303,307) and follow:
                    url = e.headers["Location"]; body = None; continue
                if e.code in (301,302,303,307): return e.code, e.headers["Location"], ""
                return e.code, url, e.read().decode()
    def csrf(s, html): return re.search(r'name="csrf_test_name" value="([^"]+)"', html).group(1)
    def post(s, page, path, fields, follow=True):
        _,_,h = s.req(page); fields = dict(fields); fields["csrf_test_name"] = s.csrf(h); return s.req(path, fields, follow)

ok = fail = 0
def check(name, cond, info=""):
    global ok, fail
    if cond: ok += 1; print("  PASS", name)
    else: fail += 1; print("  FAIL", name, info)

CH = "/practice/class-5/mathematics?chapter=fractions"
def take_quiz(b, chapter_id=6, topic_id=None, correct=10):
    f = {"chapter_id": chapter_id}
    if topic_id: f["topic_id"] = topic_id
    st, url, html = b.post(CH, "/quiz/start", f)
    m = re.search(r"/quiz/(\d+)", url)
    if not m: return None, url, html
    aid = int(m.group(1))
    items = sql(f"SELECT qa.position, (SELECT id FROM question_options WHERE question_id=qa.question_id AND is_correct=1), (SELECT id FROM question_options WHERE question_id=qa.question_id AND is_correct=0 LIMIT 1) FROM quiz_attempt_answers qa WHERE attempt_id={aid} ORDER BY position")
    for i,(pos,good,bad) in enumerate(items):
        _,_,page = b.req(f"/quiz/{aid}?q={pos}")
        go = "submit" if int(pos)==len(items) else "next"
        b.req(f"/quiz/{aid}/answer", {"csrf_test_name": b.csrf(page), "position": pos, "option_id": good if i < correct else bad, "go": go})
    return aid, url, html

print("\n[1] Guest quiz, end to end")
g = Browser()
aid, url, html = take_quiz(g, correct=8)
check("guest can start a quiz without an account", aid is not None, url)
row = sql(f"SELECT status, score, percentage, user_id IS NULL, fee_charged, counts_for_streak FROM quiz_attempts WHERE id={aid}")[0]
check("graded server-side: 8/10 = 80%", row[:3]==["completed","8","80.00"], row)
check("guest pays nothing, gets no streak credit", row[3:]==["1","0.00","0"], row)
mix = sql(f"SELECT q.difficulty, COUNT(*) FROM quiz_attempt_answers qa JOIN questions q ON q.id=qa.question_id WHERE attempt_id={aid} GROUP BY q.difficulty ORDER BY q.difficulty")
check("question mix is 5 easy / 3 medium / 2 hard", dict(mix)=={"easy":"5","medium":"3","hard":"2"}, mix)
order = [r[0] for r in sql(f"SELECT q.difficulty FROM quiz_attempt_answers qa JOIN questions q ON q.id=qa.question_id WHERE attempt_id={aid} ORDER BY position")]
check("easy first, hard last", order==["easy"]*5+["medium"]*3+["hard"]*2, order)
_,_,res = g.req(f"/quiz/{aid}/result")
check("result page: score + guest save prompt", "You scored 8/10" in res and "Create Free Account" in res)
check("result page: 'Review the 2 questions you missed'", "Review the 2 questions you missed" in res)
_,_,rev = g.req(f"/quiz/{aid}/review?filter=incorrect")
check("review shows explanation + correct/your-answer labels", "ব্যাখ্যা" in rev and "Correct answer" in rev and "Your answer" in rev)
check("review renders stacked fractions", 'class="frac"' in rev)

print("\n[1b] Timer is enforced on the server")
t = Browser()
st, url, _ = t.post(CH, "/quiz/start", {"chapter_id": 6})
tid = int(re.search(r"/quiz/(\d+)", url).group(1))
_,_,page = t.req(f"/quiz/{tid}")
secs = int(re.search(r'data-seconds-left="(\d+)"', page).group(1))
check("fresh quiz timer starts at ~10:00", 590 <= secs <= 600, secs)
sql(f"UPDATE quiz_attempts SET started_at = NOW() - INTERVAL 11 MINUTE WHERE id={tid}")
st, url, page = t.req(f"/quiz/{tid}")
check("expired quiz auto-submits to result", url.endswith(f"/quiz/{tid}/result") and val(f"SELECT status FROM quiz_attempts WHERE id={tid}")=="completed", url)
check("time taken capped at the 600s limit", val(f"SELECT duration_seconds FROM quiz_attempts WHERE id={tid}")=="600")
d = int(val(f"SELECT duration_seconds FROM quiz_attempts WHERE id={aid}"))
check("normal quiz has a real time taken (0..600s)", 0 <= d < 600, d)
sql(f"DELETE FROM quiz_attempts WHERE id={tid}")

print("\n[2] Ownership and CSRF")
other = Browser()
st,_,_ = other.req(f"/quiz/{aid}/result")
check("another browser cannot see the attempt (404)", st==404, st)
st,_,_ = g.req("/quiz/start", {"chapter_id": 6})
check("POST without CSRF token is rejected (403)", st==403, st)
st,_,_ = g.req("/quiz/start")
check("GET on a state-changing URL is refused (405)", st==405, st)

print("\n[3] Guest limit")
take_quiz(g, correct=5); take_quiz(g, correct=3)
st, url, html = g.post(CH, "/quiz/start", {"chapter_id": 6})
check(f"4th guest quiz redirects to sign-in", url.endswith("/login"), url)
check("guest has exactly 3 attempts", val("SELECT COUNT(*) FROM quiz_attempts WHERE user_id IS NULL")=="3")

print("\n[4] Sign in (existing demo user), guest results carried over")
_,_,h = g.req("/login")
st,url,h = g.req("/login", {"csrf_test_name": g.csrf(h), "phone": "+880 1700-000001"})
code = re.search(r"your code is <strong>(\d{6})</strong>", h)
check("phone normalised, OTP issued, shown in development", code is not None and url.endswith("/login/verify"), url)
check("OTP stored hashed, not plain", val("SELECT code_hash LIKE '$2y$%' FROM otp_verifications ORDER BY id DESC LIMIT 1")=="1")
st,url,h = g.req("/login/verify", {"csrf_test_name": g.csrf(h), "code": "000000" if code.group(1)!="000000" else "111111"})
check("wrong code rejected", "not right" in h)
st,url,h = g.req("/login/verify", {"csrf_test_name": g.csrf(h), "code": code.group(1)})
check("right code signs in -> dashboard", url.endswith("/dashboard"), url)
check("flash: 'Your previous result has been saved.'", "Your previous result has been saved." in h)
check("guest attempts now belong to user 1", val("SELECT COUNT(*) FROM quiz_attempts WHERE user_id=1")=="3" and val("SELECT COUNT(*) FROM quiz_attempts WHERE user_id IS NULL")=="0")
check("chapter progress rebuilt from claimed attempts", val("SELECT attempts FROM user_chapter_progress WHERE user_id=1 AND chapter_id=6")=="3")
check("dashboard greets by name", "Rahim!" in h)
st,_,h = g.req("/login/verify", {"csrf_test_name": g.csrf(h), "code": code.group(1)})
check("OTP cannot be reused", "/login/verify" not in _ or st in (200,302,303))

print("\n[5] Paid quiz from wallet")
bal0 = val("SELECT balance FROM wallets WHERE user_id=1")
s0 = val("SELECT current_streak_quizzes FROM user_quiz_streaks WHERE user_id=1")
aid2,_,_ = take_quiz(g, correct=7)
check("Tk 1 debited", float(val("SELECT balance FROM wallets WHERE user_id=1")) == float(bal0) - 1, (bal0, val("SELECT balance FROM wallets WHERE user_id=1")))
check("ledger row written with balance_after", val(f"SELECT CONCAT(amount,'|',type) FROM wallet_transactions WHERE id=(SELECT wallet_txn_id FROM quiz_attempts WHERE id={aid2})")=="-1.00|quiz_fee")
check("70% counts for streak (+1)", int(val("SELECT current_streak_quizzes FROM user_quiz_streaks WHERE user_id=1")) == int(s0)+1)
aid3,_,_ = take_quiz(g, correct=5)
check("50% does NOT count and does NOT reset", int(val("SELECT current_streak_quizzes FROM user_quiz_streaks WHERE user_id=1")) == int(s0)+1)

print("\n[6] Double submit is harmless")
pts = val("SELECT total_points FROM user_lifetime_points WHERE user_id=1")
_,_,page = g.req(f"/quiz/{aid3}/result")
for _ in range(3): g.req(f"/quiz/{aid3}/submit", {"csrf_test_name": g.csrf(page)})
check("re-submitting a finished quiz changes nothing", val("SELECT total_points FROM user_lifetime_points WHERE user_id=1")==pts and val(f"SELECT score FROM quiz_attempts WHERE id={aid3}")=="5")

print("\n[7] Chapter mastery: 3 consecutive 85%+ = +5, once")
sql("UPDATE user_chapter_progress SET consecutive_mastery=0, mastery_points_awarded=0 WHERE user_id=1 AND chapter_id=7")
p0 = int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1"))
for _ in range(3): take_quiz(g, chapter_id=7, correct=9)
p1 = int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1"))
check("+5 after the 3rd 90% quiz", p1 - p0 == 5, (p0,p1))
take_quiz(g, chapter_id=7, correct=10)
check("not awarded again on a 4th", int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1")) == p1)
check("ledger has exactly one mastery row for chapter 7", val("SELECT COUNT(*) FROM point_transactions WHERE user_id=1 AND source='chapter_mastery' AND ref_type='chapter' AND ref_id=7")=="1")

print("\n[8] Streak: 25th qualifying quiz = +1 point, immediate reset")
sql("UPDATE user_quiz_streaks SET current_streak_quizzes=24, current_streak_started_at=NOW() - INTERVAL 10 HOUR WHERE user_id=1")
p0 = int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1"))
take_quiz(g, chapter_id=8, correct=6)
check("+1 point", int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1")) == p0+1)
check("streak reset to 0", val("SELECT current_streak_quizzes FROM user_quiz_streaks WHERE user_id=1")=="0")
sql("UPDATE user_quiz_streaks SET current_streak_quizzes=20, current_streak_started_at=NOW() - INTERVAL 101 HOUR WHERE user_id=1")
take_quiz(g, chapter_id=8, correct=6)
check("expired 100h window: restarts at 1, no point", val("SELECT current_streak_quizzes FROM user_quiz_streaks WHERE user_id=1")=="1" and int(val("SELECT total_points FROM user_lifetime_points WHERE user_id=1")) == p0+1)

print("\n[9] New user: registration and empty wallet")
n = Browser()
_,_,h = n.req("/login")
_,url,h = n.req("/login", {"csrf_test_name": n.csrf(h), "phone": "01811111111"})
code = re.search(r"your code is <strong>(\d{6})</strong>", h).group(1)
_,url,h = n.req("/login/verify", {"csrf_test_name": n.csrf(h), "code": code})
check("unknown phone goes to registration", url.endswith("/register"), url)
_,url,h = n.req("/register", {"csrf_test_name": n.csrf(h), "name": "Karim Uddin", "class_id": 1, "school_id": 2})
uid = val("SELECT id FROM users WHERE phone='01811111111'")
check("account created with wallet, points, streak, settings rows", val(f"SELECT (SELECT COUNT(*) FROM wallets WHERE user_id={uid})+(SELECT COUNT(*) FROM user_lifetime_points WHERE user_id={uid} AND tier_id=0)+(SELECT COUNT(*) FROM user_quiz_streaks WHERE user_id={uid})+(SELECT COUNT(*) FROM user_settings WHERE user_id={uid})")=="4")
check("display name masked to 'Karim U.'", val(f"SELECT display_name FROM users WHERE id={uid}")=="Karim U.")
st,url,h = n.post(CH, "/quiz/start", {"chapter_id": 6})
check("Tk 0 balance -> sent to wallet, nothing charged", url.endswith("/wallet") and val(f"SELECT COUNT(*) FROM quiz_attempts WHERE user_id={uid}")=="0", url)
_,_,h = n.req("/wallet")
_,url,h = n.req("/wallet/topup", {"csrf_test_name": n.csrf(h), "amount": 20})
check("simulated top-up works in development", val(f"SELECT balance FROM wallets WHERE user_id={uid}")=="20.00")
_,_,h = n.req("/wallet")
n.req("/wallet/topup", {"csrf_test_name": n.csrf(h), "amount": 20, "custom": 35})
check("custom amount wins over preset", val(f"SELECT balance FROM wallets WHERE user_id={uid}")=="55.00")
aidn,_,_ = take_quiz(n, correct=10)
check("now the quiz starts and charges Tk 1", aidn is not None and val(f"SELECT balance FROM wallets WHERE user_id={uid}")=="54.00")

print("\n[10] Signed-in pages render without PHP errors")
for p in ["/dashboard","/progress","/progress?tab=subjects","/progress?tab=history","/progress?tab=weak","/rank","/wallet","/profile","/correct-me","/leaderboard","/competition",f"/quiz/{aid2}/result",f"/quiz/{aid2}/review"]:
    st,_,h = g.req(p)
    check(f"{p}", st==200 and "A PHP Error" not in h and "Database Error" not in h, st)

print("\n[11] Correct Me")
qid = val(f"SELECT question_id FROM quiz_attempt_answers WHERE attempt_id={aid2} LIMIT 1")
_,_,h = g.req(f"/correct-me/{qid}")
_,url,h = g.req(f"/correct-me/{qid}", {"csrf_test_name": g.csrf(h), "what_is_wrong":"The explanation skips a step", "explanation":"It should show the LCM before comparing the fractions.", "source_ref":"", "claimed_option_id":""})
check("submission saved as pending", val(f"SELECT status FROM correction_requests WHERE user_id=1 AND question_id={qid}")=="pending")
_,_,h = g.req(f"/correct-me/{qid}")
_,url,h = g.req(f"/correct-me/{qid}", {"csrf_test_name": g.csrf(h), "what_is_wrong":"Again", "explanation":"Duplicate report test text.", "source_ref":"", "claimed_option_id":""})
check("duplicate pending report refused", val(f"SELECT COUNT(*) FROM correction_requests WHERE user_id=1 AND question_id={qid}")=="1")

print("\n[12] Ledger integrity")
check("every wallet balance == sum of its ledger", val("SELECT COUNT(*) FROM wallets w WHERE w.balance <> (SELECT COALESCE(SUM(amount),0) FROM wallet_transactions t WHERE t.user_id=w.user_id)")=="0")
check("every points total == sum of its ledger", val("SELECT COUNT(*) FROM user_lifetime_points p WHERE EXISTS (SELECT 1 FROM point_transactions t WHERE t.user_id=p.user_id) AND p.total_points <> (SELECT SUM(points) FROM point_transactions t WHERE t.user_id=p.user_id)")=="0")

print(f"\n{ok} passed, {fail} failed")
sys.exit(1 if fail else 0)
