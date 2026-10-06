<?php defined('BASEPATH') OR exit('No direct script access allowed');
$status = array('pending' => array('Pending', 'badge--warn', 'clock'), 'approved' => array('Approved', 'badge--ok', 'check-circle'), 'rejected' => array('Not approved', 'badge--muted', 'x-circle')); ?>
<section class="section section--tight">
  <div class="amcq-container">
    <h1>Correct Me</h1>
    <p class="amcq-muted">Found a mistake in a question? Report it from the answer review. Each approved correction earns 1 Academic Point.</p>

    <div class="amcq-card">
      <h2 class="h3">Your path</h2>
      <p><b><?= (int) $approved ?></b> approved correction<?= $approved == 1 ? '' : 's' ?></p>
      <ol class="ladder ladder--row">
        <?php foreach ($milestones as $m): $ok = $approved >= $m['approved_required']; ?>
          <li class="<?= $ok ? 'is-reached' : '' ?>"><span class="ladder__dot"><?= $ok ? icon('check') : '' ?></span>
            <b><?= (int) $m['approved_required'] ?> approved</b><small><?= e($m['title']) ?></small><small class="amcq-muted"><?= e($m['description']) ?></small></li>
        <?php endforeach; ?>
      </ol>
      <!-- v2.0: eligibility for an assessment, never a promise of a job. -->
      <p class="note"><?= icon('bulb') ?> Milestones make you eligible to be considered. They are not a job offer, and every role has its own assessment.</p>
    </div>

    <div class="amcq-card">
      <h2 class="h3">Your submissions</h2>
      <?php if ($requests): ?>
      <ul class="corrections">
        <?php foreach ($requests as $r): list($label, $cls, $ic) = $status[$r['status']]; ?>
        <li><span class="badge <?= $cls ?>"><?= icon($ic) ?> <?= $label ?></span>
          <span><b><?= math_text(mb_strimwidth($r['stem'], 0, 90, '…')) ?></b><small class="amcq-muted"><?= e($r['what_is_wrong']) ?></small>
          <?php if ($r['status'] === 'approved'): ?><small class="ok">Correction approved · +1 Academic Point</small><?php endif; ?>
          <?php if ($r['review_note']): ?><small class="amcq-muted">Reviewer: <?= e($r['review_note']) ?></small><?php endif; ?></span>
          <time class="amcq-muted"><?= date('j M Y', strtotime($r['created_at'])) ?></time></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">No submissions yet. Use “Correct Me” on any question in the answer review.</p><?php endif; ?>
    </div>
  </div>
</section>
