<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
</main>
<?php if ($page['app'] && $user): ?>
  </div>
</div>
<?php endif; ?>

<footer class="site-footer">
  <div class="amcq-container site-footer__inner">
    <div class="site-footer__brand">
      <a class="brand" href="<?= site_url('') ?>"><?php $this->load->view('partials/logo'); ?>
        <span class="brand__text"><strong class="brand__word">Academic<span>MCQ</span></strong><small class="brand__tag">Practice. Learn. Compete.</small></span></a>
      <p>অনুশীলন • শেখা • প্রতিযোগিতা</p>
      <p class="amcq-muted">Easy learning. Healthy competition. Lifetime recognition. Fair chance for all.</p>
    </div>
    <nav class="site-footer__col" aria-label="Product">
      <strong>Learn</strong>
      <a href="<?= site_url('practice') ?>">Practice</a><a href="<?= site_url('competition') ?>">Competition</a>
      <a href="<?= site_url('leaderboard') ?>">Leaderboard</a><a href="<?= site_url('pricing') ?>">Pricing</a>
    </nav>
    <nav class="site-footer__col" aria-label="Help">
      <strong>Help</strong>
      <a href="<?= site_url('how-it-works') ?>">How It Works</a><a href="<?= site_url('faq') ?>">FAQ</a>
      <a href="<?= site_url('about') ?>">About</a><a href="<?= site_url('contact') ?>">Contact</a>
    </nav>
    <nav class="site-footer__col" aria-label="Policies">
      <strong>Policies</strong>
      <a href="<?= site_url('terms') ?>">Terms</a><a href="<?= site_url('privacy') ?>">Privacy</a>
      <a href="<?= site_url('competition#rules') ?>">Competition Rules</a><a href="<?= site_url('refund-policy') ?>">Refund &amp; Payment</a>
      <a href="<?= site_url('correct-me-policy') ?>">Correct Me Policy</a>
    </nav>
  </div>
  <p class="amcq-container site-footer__copy">&copy; <?= date('Y') ?> AcademicMCQ</p>
</footer>

<?php if ($user): ?>
<!-- Mobile bottom nav (design 12): Dashboard, Practice, Competition, Leaderboard. -->
<nav class="tabbar" aria-label="App">
  <?php foreach (array('dashboard' => array('Dashboard', 'home'), 'practice' => array('Practice', 'file'), 'competition' => array('Competition', 'trophy'), 'leaderboard' => array('Leaderboard', 'bar-up')) as $k => $t): ?>
    <a href="<?= site_url($k) ?>"<?= $page['nav'] === $k ? ' aria-current="page"' : '' ?>><?= icon($t[1]) ?><span><?= $t[0] ?></span></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
