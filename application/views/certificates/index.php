<?php defined('BASEPATH') OR exit('No direct script access allowed');
$labels = array('participation' => 'Participation Certificate', 'finalist' => 'Finalist Certificate', 'winner' => 'Winner Certificate'); ?>
<h1>Certificates</h1>
<?php if ($certificates): ?>
<ul class="cert-grid">
  <?php foreach ($certificates as $c): ?>
  <li class="cert">
    <span class="round round--amber"><?= icon('certificate') ?></span>
    <strong><?= e($labels[$c['type']]) ?></strong>
    <span><?= e($c['competition']) ?> <?= (int) $c['year'] ?></span>
    <?php if ($c['result_label']): ?><span class="amcq-muted"><?= e($c['result_label']) ?></span><?php endif; ?>
    <small class="amcq-muted">Verification ID: <b><?= e($c['verification_code']) ?></b></small>
  </li>
  <?php endforeach; ?>
</ul>
<?php else: ?>
<div class="empty-card">
  <span class="round round--amber"><?= icon('certificate') ?></span>
  <p><strong>No certificates yet.</strong><br>Every competition participant receives a certificate, with finalist and winner certificates for those who go further.</p>
  <a class="amcq-btn amcq-btn--primary" href="<?= site_url('competition') ?>">View Competition</a>
</div>
<?php endif; ?>
