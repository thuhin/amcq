/* AcademicMCQ — progressive enhancement only. Every page works without this
   file; the server enforces the quiz time limit and decides navigation. */
(function () {
  'use strict';

  var form = document.getElementById('quiz-form');
  if (!form) return;

  // Selected-option highlight for browsers without CSS :has().
  form.addEventListener('change', function (e) {
    if (e.target.name !== 'option_id') return;
    form.querySelectorAll('.amcq-option').forEach(function (el) {
      el.classList.toggle('amcq-option--selected', el.contains(e.target));
    });
  });

  // Countdown. Display only: the deadline is checked on the server, so a
  // paused or edited clock in the browser cannot buy extra time.
  var timer = document.getElementById('timer');
  if (timer) {
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
