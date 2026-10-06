<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<footer class="amcq-footer">
  <div class="amcq-container">
    <nav class="amcq-footer__links" aria-label="Footer">
      <a href="<?= base_url('about') ?>">About</a>
      <a href="<?= base_url('contact') ?>">Contact</a>
      <a href="<?= base_url('terms') ?>">Terms</a>
      <a href="<?= base_url('privacy') ?>">Privacy</a>
      <a href="<?= base_url('competition/rules') ?>">Competition Rules</a>
      <a href="<?= base_url('refund-policy') ?>">Refund &amp; Payment Policy</a>
      <a href="<?= base_url('correct-me-policy') ?>">Correct Me Policy</a>
    </nav>
    <p class="amcq-muted amcq-footer__note">
      Easy learning. Healthy competition. Lifetime recognition. Fair chance for all.
    </p>
    <p class="amcq-muted amcq-footer__note">
      &copy; <?= date('Y') ?> AcademicMCQ
    </p>
  </div>
</footer>
</body>
</html>
