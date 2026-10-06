<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h1 class="page-title">Think this answer is wrong?</h1>
<div class="card">
  <p class="question__stem"><?= math_text($question['stem']) ?></p>
  <ul class="plain-options"><?php foreach ($options as $o): ?><li><b><?= e($o['label']) ?>.</b> <?= math_text($o['body']) ?></li><?php endforeach; ?></ul>
</div>
<?= form_open('correct-me/' . $question['id'], array('class' => 'card')) ?>
  <label class="field"><span class="field__label">What is wrong?</span>
    <textarea class="field__input" name="what_is_wrong" rows="2" required><?= e(set_value('what_is_wrong')) ?></textarea>
    <?= form_error('what_is_wrong', '<span class="field__error">', '</span>') ?></label>
  <label class="field"><span class="field__label">What do you think is correct?</span>
    <select class="field__input" name="claimed_option_id"><option value="">— Not about the answer choice —</option>
      <?php foreach ($options as $o): ?><option value="<?= (int) $o['id'] ?>" <?= set_select('claimed_option_id', $o['id']) ?>><?= e($o['label']) ?>. <?= e($o['body']) ?></option><?php endforeach; ?>
    </select></label>
  <label class="field"><span class="field__label">Explain why</span>
    <textarea class="field__input" name="explanation" rows="4" required><?= e(set_value('explanation')) ?></textarea>
    <?= form_error('explanation', '<span class="field__error">', '</span>') ?></label>
  <label class="field"><span class="field__label">Source or reference <span class="amcq-muted">(optional)</span></span>
    <input class="field__input" type="text" name="source_ref" value="<?= e(set_value('source_ref')) ?>" placeholder="e.g. NCTB Class 5 Math, page 41"></label>
  <p class="amcq-muted small">A teacher reviews every submission. We can't promise approval, but we read every one.</p>
  <div class="btn-row">
    <?php if ($back): ?><a class="amcq-btn amcq-btn--secondary" href="<?= site_url('quiz/' . (int) $back . '/review') ?>">Cancel</a><?php endif; ?>
    <button class="amcq-btn amcq-btn--primary" type="submit">Submit Correction</button>
  </div>
<?= form_close() ?>
