<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container narrow">
    <h1><?= e($heading) ?></h1>
    <!-- Legal and policy text has to come from the business and its lawyer;
         none is invented here. -->
    <div class="amcq-card">
      <p>This page is being prepared and will be published before launch.</p>
      <?php if ($slug !== 'contact'): ?>
      <p class="amcq-muted">Questions in the meantime? Use the <a href="<?= site_url('contact') ?>">contact page</a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
