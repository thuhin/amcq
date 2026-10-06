<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
</main>

<?php if ( ! $page['bare']): ?>
<footer class="amcq-footer">
  <div class="amcq-container">
    <div class="amcq-footer__brand">
      <strong class="amcq-logo__word">Academic<span>MCQ</span></strong>
      <p class="amcq-muted">অনুশীলন • শেখা • প্রতিযোগিতা</p>
    </div>
    <nav class="amcq-footer__links" aria-label="Footer">
      <a href="<?= site_url('about') ?>">About</a>
      <a href="<?= site_url('contact') ?>">Contact</a>
      <a href="<?= site_url('terms') ?>">Terms</a>
      <a href="<?= site_url('privacy') ?>">Privacy</a>
      <a href="<?= site_url('competition#rules') ?>">Competition Rules</a>
      <a href="<?= site_url('refund-policy') ?>">Refund &amp; Payment Policy</a>
      <a href="<?= site_url('correct-me-policy') ?>">Correct Me Policy</a>
    </nav>
    <p class="amcq-muted amcq-footer__note">Easy learning. Healthy competition. Lifetime recognition. Fair chance for all.</p>
    <p class="amcq-muted amcq-footer__note">&copy; <?= date('Y') ?> AcademicMCQ</p>
  </div>
</footer>
<?php endif; ?>

<?php if ($user && ! $page['bare']): ?>
<!-- Mobile bottom nav for signed-in students (guideline §3). Wallet lives in
     the account menu on mobile to keep this to five items. -->
<nav class="amcq-tabbar" aria-label="App">
  <a href="<?= site_url('dashboard') ?>"<?= $page['nav'] === 'dashboard' ? ' aria-current="page"' : '' ?>><?= icon('home') ?><span>Home</span></a>
  <a href="<?= site_url('practice') ?>"<?= $page['nav'] === 'practice' ? ' aria-current="page"' : '' ?>><?= icon('file') ?><span>Practice</span></a>
  <a href="<?= site_url('leaderboard') ?>"<?= $page['nav'] === 'leaderboard' ? ' aria-current="page"' : '' ?>><?= icon('trophy') ?><span>Rank</span></a>
  <a href="<?= site_url('progress') ?>"<?= $page['nav'] === 'progress' ? ' aria-current="page"' : '' ?>><?= icon('chart') ?><span>Progress</span></a>
  <a href="<?= site_url('profile') ?>"<?= $page['nav'] === 'profile' ? ' aria-current="page"' : '' ?>><?= icon('user') ?><span>Profile</span></a>
</nav>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
