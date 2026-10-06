/* AcademicMCQ — progressive enhancement only. Every page works without this
   file; the server enforces the quiz time limit and decides navigation. */
(function () {
  'use strict';

  var form = document.getElementById('quiz-form');
  if (!form) return;

  // Selected-option highlight for browsers without CSS :has().
  form.addEventListener('change', function (e) {
    if (e.target.name !== 'option_id') return;
    form.querySelectorAll('.opt').forEach(function (el) {
      el.classList.toggle('is-selected', el.contains(e.target));
    });
  });

  // Countdown. Display only: the deadline is checked on the server, so a
  // paused or edited clock in the browser cannot buy extra time.
  var timer = document.getElementById('timer');
  if (timer && timer.getAttribute('data-paused') !== '1') {
    var left = parseInt(timer.getAttribute('data-seconds-left'), 10);
    var out = timer.querySelector('b');
    var announced = false;
    var tick = function () {
      left = Math.max(0, left - 1);
      out.textContent = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
      if (left <= 60 && !announced) {
        timer.classList.add('is-low');
        timer.setAttribute('aria-live', 'assertive');   // one announcement at 1:00
        announced = true;
      }
      if (left === 0) {
        clearInterval(id);
        var go = document.createElement('input');
        go.type = 'hidden'; go.name = 'go'; go.value = 'submit';
        form.appendChild(go);
        form.submit();
      }
    };
    var id = setInterval(tick, 1000);
  }

  // Confirm before submitting with questions left blank. The count comes
  // from the server and includes the answer about to be saved on this page.
  form.addEventListener('submit', function (e) {
    var btn = e.submitter;
    if (!btn || btn.value !== 'submit') return;
    var blank = parseInt(form.getAttribute('data-unanswered'), 10);
    if (form.querySelector('input[name=option_id]:checked') && !form.querySelector('input[name=option_id][checked]')) blank -= 1;
    if (blank > 0 && !window.confirm(blank + ' question(s) not answered. Submit anyway?')) e.preventDefault();
  });
})();

/* Wallet: typing a custom amount deselects the preset, so it is clear which
   amount will be charged. */
(function () {
  var custom = document.querySelector('.topup input[name=custom]');
  if (!custom) return;
  custom.addEventListener('input', function () {
    if (custom.value) {
      document.querySelectorAll('.topup input[name=amount]').forEach(function (r) { r.checked = false; });
    }
  });
})();

/* Home, step 3 -> step 4 (design 07): picking a subject updates the "Start
   Practicing" card in place. Without JS each subject is a plain link that
   reloads the page with ?subject=, so nothing depends on this. */
(function () {
  var list = document.querySelector('[data-subjects]');
  if (!list) return;
  list.addEventListener('click', function (e) {
    var a = e.target.closest('[data-subject]');
    if (!a) return;
    e.preventDefault();
    list.querySelectorAll('[data-subject]').forEach(function (x) {
      var on = x === a;
      x.classList.toggle('is-selected', on);
      if (on) x.setAttribute('aria-current', 'true'); else x.removeAttribute('aria-current');
    });
    var name = a.getAttribute('data-name'), url = a.getAttribute('data-url');
    document.querySelectorAll('[data-go-title]').forEach(function (el) { el.textContent = el.textContent.split('•')[0] + '• ' + name; });
    document.querySelectorAll('[data-go-name]').forEach(function (el) { el.textContent = name; });
    document.querySelectorAll('[data-go-sub]').forEach(function (el) { el.textContent = a.getAttribute('data-ready') === '1' ? 'Chapter-wise MCQs' : 'Questions coming soon'; });
    document.querySelectorAll('[data-go-link]').forEach(function (el) { el.href = url; });
  });
})();

/* Average Score / Total Score tabs (Top Schools This Week). */
(function () {
  document.querySelectorAll('[data-tabs]').forEach(function (box) {
    box.addEventListener('click', function (e) {
      var tab = e.target.closest('[data-tab]');
      if (!tab) return;
      e.preventDefault();
      var key = tab.getAttribute('data-tab');
      box.querySelectorAll('[data-tab]').forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      box.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== key; });
    });
  });
})();

/* Character counter for textareas with data-counter (Contact Us message). */
(function () {
  document.querySelectorAll('textarea[data-counter]').forEach(function (ta) {
    var out = document.getElementById(ta.getAttribute('data-counter'));
    if (!out) return;
    var max = ta.getAttribute('maxlength');
    var update = function () { out.textContent = ta.value.length + ' / ' + max; };
    ta.addEventListener('input', update); update();
  });
})();
