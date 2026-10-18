"""
Render the intro video: docs/intro-video/intro.html -> asset/video/intro.mp4
(and asset/video/intro-poster.png). Re-run it whenever a rule shown in the
video changes; the numbers are read from application/config/constants.php.

    python docs/intro-video/render.py [--php PATH] [--chrome PATH]

Needs PHP (to read the constants), Google Chrome, and the internet (fonts).
Encoding runs in a headless Chrome of its own, frame by frame (exact 30 fps).
"""
import argparse, http.server, json, os, shutil, subprocess, sys, tempfile, threading, urllib.parse

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, "..", ".."))
OUT_DIR = os.path.join(ROOT, "asset", "video")

ap = argparse.ArgumentParser()
ap.add_argument("--php", default=os.environ.get("PHP_BIN", "php"))
ap.add_argument("--sheet", help="also save a contact sheet of frames decoded from the encoded video")
ap.add_argument("--chrome", default=os.environ.get("CHROME_BIN", r"C:\Program Files\Google\Chrome\Application\chrome.exe"))
args = ap.parse_args()

# 1. The rules, from the one place they are defined.
php = r"""
define('BASEPATH', 1); define('APPPATH', getcwd() . '/application/');
require APPPATH . 'config/constants.php';
echo json_encode(array(
  'questions' => QUIZ_QUESTION_COUNT, 'easy' => QUIZ_MIX_EASY, 'medium' => QUIZ_MIX_MEDIUM, 'hard' => QUIZ_MIX_HARD,
  'minutes' => QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60, 'fee' => QUIZ_FEE_TAKA + 0,
  'streak' => STREAK_REQUIRED_QUIZZES, 'pass' => STREAK_PASS_PERCENTAGE, 'window' => STREAK_WINDOW_HOURS,
  'streakPoints' => STREAK_POINTS, 'free' => GUEST_FREE_QUIZZES,
));
"""
cfg = json.loads(subprocess.run([args.php, "-r", php], cwd=ROOT, capture_output=True, text=True, check=True).stdout)
print("rules:", cfg)

# 2. Serve the source, and take the recording back over POST.
done = threading.Event()
saved = {}
class Handler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *a, **k): super().__init__(*a, directory=HERE, **k)
    def log_message(self, *a): pass
    def do_POST(self):
        qs = urllib.parse.parse_qs(urllib.parse.urlparse(self.path).query)
        kind = qs.get("type", ["video/mp4"])[0]
        body = self.rfile.read(int(self.headers["Content-Length"]))
        if kind.startswith("sheet"):
            if args.sheet:
                with open(args.sheet, "wb") as f: f.write(body)
            saved.update(sheet=kind)
            self.send_response(204); self.end_headers(); return
        if kind == "error":
            saved.update(error=body.decode("utf-8", "replace"))
            self.send_response(204); self.end_headers(); done.set(); return
        ext = "mp4" if "mp4" in kind else "webm"
        os.makedirs(OUT_DIR, exist_ok=True)
        path = os.path.join(OUT_DIR, "intro." + ext)
        with open(path, "wb") as f: f.write(body)
        saved.update(path=path, type=kind, bytes=len(body))
        self.send_response(204); self.end_headers(); done.set()
server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), Handler)
threading.Thread(target=server.serve_forever, daemon=True).start()
base = "http://127.0.0.1:%d/intro.html#cfg=%s" % (server.server_address[1], urllib.parse.quote(json.dumps(cfg)))

profile = tempfile.mkdtemp(prefix="amcq-intro-")
common = [args.chrome, "--headless=new", "--user-data-dir=" + profile, "--window-size=1280,720", "--hide-scrollbars", "--no-first-run"]
try:
    # 3. Encode frame by frame (WebCodecs + mp4-muxer, inside the page).
    chrome = subprocess.Popen(common + [base + "&mode=encode" + ("&verify=1" if args.sheet else "")],
                              stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    finished = done.wait(timeout=300)
    chrome.terminate(); chrome.wait(timeout=30)
    if not finished:
        sys.exit("No video arrived within 5 minutes.")
    if "error" in saved:
        sys.exit("Encoding failed in Chrome: " + saved["error"])
    print("video:", saved)

    # 4. The poster: a still of the opening title.
    poster = os.path.join(OUT_DIR, "intro-poster.png")
    subprocess.run(common + ["--virtual-time-budget=8000", "--screenshot=" + poster, base + "&mode=poster"],
                   stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=120, check=True)
    print("poster:", poster, os.path.getsize(poster), "bytes")
finally:
    server.shutdown()
    shutil.rmtree(profile, ignore_errors=True)
