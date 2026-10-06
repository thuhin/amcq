<?php defined('BASEPATH') OR exit('No direct script access allowed');
$status = array('pending' => array('Pending', 'pill-warn', 'clock'), 'approved' => array('Approved', 'pill-ok', 'check'), 'rejected' => array('Not approved', 'pill-muted', 'x-circle')); ?>
<h1 class="page-title">Correct Me</h1>
<p class="amcq-muted">Found a mistake in a question? Report it from the answer review. Each approved correction earns 1 Academic Point.</p>
<div class="card">
  <h2>Your path · <?= (int) $approved ?> approved</h2>
  <ol class="career">
    <?php foreach ($milestones as $m): $ok = $approved >= $m['approved_required']; ?>
      <li class="<?= $ok ? 'is-reached' : '' ?>"><span class="tier-ladder__dot"><?= $ok ? icon('check') : '' ?></span>
        <div><b><?= (int) $m['approved_required'] ?> approved · <?= e($m['title']) ?></b><small><?= e($m['description']) ?></small></div></li>
    <?php endforeach; ?>
  </ol>
  <p class="note"><?= icon('bulb') ?> Milestones make you eligible to be considered. They are not a job offer, and every role has its own assessment.</p>
</div>
<div class="card">
  <h2>Your submissions</h2>
  <?php if ($requests): ?>
  <ul class="card-list">
    <?php foreach ($requests as $r): list($label, $cls, $ic) = $status[$r['status']]; ?>
    <li class="card-list__item"><span class="<?= $cls ?>"><?= icon($ic) ?> <?= $label ?></span>
      <div><strong><?= math_text(mb_strimwidth($r['stem'], 0, 90, '…')) ?></strong><p class="amcq-muted"><?= e($r['what_is_wrong']) ?></p>
        <?php if ($r['status'] === 'approved'): ?><small class="ok">Correction approved · +1 Academic Point</small><?php endif; ?>
        <?php if ($r['review_note']): ?><small class="amcq-muted">Reviewer: <?= e($r['review_note']) ?></small><?php endif; ?></div>
      <time class="amcq-muted small"><?= date('j M Y', strtotime($r['created_at'])) ?></time></li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><p class="amcq-muted">No submissions yet. Use “Correct Me” on any question in the answer review.</p><?php endif; ?>
</div>
