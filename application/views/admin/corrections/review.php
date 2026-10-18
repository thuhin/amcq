<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $r report with ['question'] (options keyed by label) */
$q = $r['question'];
$live = in_array($q['status'], array('verified', 'published'), TRUE);
?>
<div class="admin-head">
  <h1>Report #<?= (int) $r['id'] ?> <?= status_badge($r['status']) ?></h1>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('admin/corrections') ?>"><?= icon('arrow-left') ?> All reports</a></div>
</div>

<div class="admin-two">
  <div class="admin-side">
    <section class="amcq-card">
      <h2>The question <?= status_badge($q['status']) ?></h2>
      <p class="admin-q"><?= math_text($q['stem']) ?></p>
      <ul class="admin-opts">
        <?php foreach ($q['options'] as $label => $o): ?>
          <li class="<?= $o['is_correct'] ? 'is-right' : '' ?>"><b><?= e($label) ?></b> <span><?= math_text($o['body']) ?></span>
            <span class="tagline">
              <?php if ($o['is_correct']): ?><?= icon('check') ?> Marked correct<?php endif; ?>
              <?php if ($r['claimed_label'] === $label): ?><?= $o['is_correct'] ? ' · ' : '' ?><?= icon('user') ?> Student says this one<?php endif; ?>
            </span></li>
        <?php endforeach; ?>
      </ul>
      <?php if (trim((string) $q['explanation']) !== ''): ?><p class="inline-note"><strong>Explanation:</strong> <?= math_text($q['explanation']) ?></p><?php endif; ?>
      <?php if (trim((string) $q['source_ref']) !== ''): ?><p class="inline-note"><strong>Source:</strong> <?= e($q['source_ref']) ?></p><?php endif; ?>
      <p><a class="amcq-btn amcq-btn--tint amcq-btn--sm" href="<?= site_url('admin/questions/' . (int) $q['id']) ?>"><?= icon('pencil') ?> Open question #<?= (int) $q['id'] ?> in the editor</a></p>
    </section>

    <section class="amcq-card">
      <h2>What the student reported</h2>
      <dl class="kv">
        <dt>From</dt><dd><?= e($r['student']) ?><?= $r['display_name'] ? ' (' . e($r['display_name']) . ')' : '' ?></dd>
        <dt>Sent</dt><dd><?= e(date('j M Y, g:i a', strtotime($r['created_at']))) ?></dd>
        <dt>Source given</dt><dd><?= trim((string) $r['source_ref']) !== '' ? e($r['source_ref']) : '<span class="amcq-muted">none</span>' ?></dd>
      </dl>
      <h3 class="admin-h2">What is wrong</h3>
      <p class="admin-quote"><?= e($r['what_is_wrong']) ?></p>
      <h3 class="admin-h2">Why</h3>
      <p class="admin-quote"><?= e($r['user_explanation']) ?></p>
    </section>
  </div>

  <aside class="admin-side">
    <?php if ($r['status'] === 'pending'): ?>
    <section class="amcq-card">
      <h2>Approve</h2>
      <p class="inline-note">The student earns <?= (int) CORRECTION_APPROVED_POINTS ?> Academic Point<?= CORRECTION_APPROVED_POINTS === 1 ? '' : 's' ?> and is notified. This cannot be undone.</p>
      <?= form_open('admin/corrections/' . (int) $r['id'] . '/approve', array('novalidate' => TRUE)) ?>
        <?php if ($live): ?>
          <label class="check"><input type="checkbox" name="pull_question" value="1" checked> Pull the question back to review until it is fixed</label>
          <p class="inline-note">It is <?= e(strtolower(Question_model::STATUSES[$q['status']])) ?> now, so students are still being asked it. Untick only if the question is right as it stands.</p>
        <?php else: ?>
          <p class="inline-note">The question is <?= e(strtolower(Question_model::STATUSES[$q['status']])) ?>, so students are not being asked it.</p>
        <?php endif; ?>
        <label class="field"><span class="field__label">Note to the student <span class="amcq-muted">(optional)</span></span>
          <textarea class="field__input" name="note" rows="2" maxlength="255" placeholder="Thank you, you were right."></textarea></label>
        <button class="amcq-btn amcq-btn--primary amcq-btn--block" type="submit"><?= icon('check') ?> Approve</button>
      <?= form_close() ?>
    </section>

    <section class="amcq-card">
      <h2>Do not approve</h2>
      <p class="inline-note">The student reads your reason on their Correct Me page, so write it for them.</p>
      <?= form_open('admin/corrections/' . (int) $r['id'] . '/reject', array('novalidate' => TRUE)) ?>
        <label class="field"><span class="field__label">Reason</span>
          <textarea class="field__input" name="note" rows="3" maxlength="255" required placeholder="e.g. The textbook gives 4/6, so the marked answer is right."></textarea></label>
        <button class="amcq-btn amcq-btn--secondary amcq-btn--block" type="submit">Not approved</button>
      <?= form_close() ?>
    </section>
    <?php else: ?>
    <section class="amcq-card">
      <h2>Decision</h2>
      <dl class="kv">
        <dt>Result</dt><dd><?= status_badge($r['status']) ?></dd>
        <dt>By</dt><dd><?= e($r['reviewer'] ?: 'an admin') ?></dd>
        <dt>When</dt><dd><?= $r['reviewed_at'] ? e(date('j M Y, g:i a', strtotime($r['reviewed_at']))) : '' ?></dd>
        <dt>Note</dt><dd><?= trim((string) $r['review_note']) !== '' ? e($r['review_note']) : '<span class="amcq-muted">none</span>' ?></dd>
      </dl>
      <p class="inline-note">A decision is final. Academic Points never decrease.</p>
    </section>
    <?php endif; ?>
  </aside>
</div>
