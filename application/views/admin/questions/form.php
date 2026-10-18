<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array|null $q @var array $form @var array $errors @var array $chapters @var array $topics @var array $problems @var array $next */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<span class="field__error">' . e($errors[$k]) . '</span>' : ''; };
$bad = function ($k) use ($errors) { return isset($errors[$k]) ? 'true' : 'false'; };
$label_for = function ($from, $to) {
	$map = array(
		'draft>in_review'     => 'Send to review',
		'in_review>verified'  => 'Verify',
		'in_review>published' => 'Verify and publish',
		'in_review>draft'     => 'Back to draft',
		'verified>published'  => 'Publish',
		'verified>in_review'  => 'Pull back to review',
		'published>in_review' => 'Pull back to review',
		'retired>draft'       => 'Restore as draft',
	);
	return isset($map[$from . '>' . $to]) ? $map[$from . '>' . $to] : 'Retire';
};
$by_chapter = array();
foreach ($topics as $t) { $by_chapter[$t['chapter_id']][] = $t; }
?>
<div class="admin-head">
  <h1><?= $q ? 'Question #' . (int) $q['id'] . ' ' . status_badge($q['status']) : 'New question' ?></h1>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('admin/questions') ?>"><?= icon('arrow-left') ?> All questions</a></div>
</div>
<?php if (isset($errors['_'])): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($errors['_']) ?></p><?php endif; ?>

<div class="admin-two">
  <?= form_open($q ? 'admin/questions/' . (int) $q['id'] : 'admin/questions/new', array('class' => 'admin-form', 'novalidate' => TRUE)) ?>
    <section class="amcq-card">
      <h2>The question</h2>
      <div class="form-grid">
        <label class="field"><span class="field__label">Chapter</span>
          <select class="field__input" name="chapter_id" data-filters-topics required aria-invalid="<?= $bad('chapter_id') ?>">
            <option value="">Choose…</option>
            <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (string) $form['chapter_id'] === (string) $c['id'] ? ' selected' : '' ?>><?= e($c['class_name'] . ' › ' . $c['subject_name'] . ' › ' . $c['name']) ?></option><?php endforeach; ?>
          </select><?= $err('chapter_id') ?></label>
        <label class="field"><span class="field__label">Topic <span class="amcq-muted">(optional)</span></span>
          <select class="field__input" name="topic_id" aria-invalid="<?= $bad('topic_id') ?>">
            <option value="">No topic</option>
            <?php foreach ($by_chapter as $chapter_id => $list): foreach ($list as $t): ?>
              <option value="<?= (int) $t['id'] ?>" data-chapter="<?= (int) $chapter_id ?>"<?= (string) $form['topic_id'] === (string) $t['id'] ? ' selected' : '' ?>><?= e($t['code'] . ' ' . $t['name']) ?></option>
            <?php endforeach; endforeach; ?>
          </select><?= $err('topic_id') ?></label>
      </div>
      <fieldset class="field">
        <legend class="field__label">Difficulty</legend>
        <div class="admin-actions">
          <?php foreach (Question_model::DIFFICULTIES as $k => $l): ?>
            <label class="check"><input type="radio" name="difficulty" value="<?= e($k) ?>"<?= $form['difficulty'] === $k ? ' checked' : '' ?>> <?= e($l) ?></label>
          <?php endforeach; ?>
        </div><?= $err('difficulty') ?>
      </fieldset>
      <label class="field"><span class="field__label">Question</span>
        <textarea class="field__input" name="stem" rows="4" maxlength="2000" required aria-invalid="<?= $bad('stem') ?>"><?= e($form['stem']) ?></textarea>
        <span class="field__hint amcq-muted">Write a fraction as 3/7 and students see it stacked.</span><?= $err('stem') ?></label>
    </section>

    <section class="amcq-card">
      <h2>Options</h2>
      <p class="amcq-muted inline-note">Select the round button beside the correct option. All four options must be different.</p>
      <?php foreach (Question_model::LABELS as $label): $k = 'option_' . strtolower($label); ?>
        <div class="opt-row">
          <label class="opt-row__pick" title="Tick if <?= $label ?> is the correct answer">
            <span aria-hidden="true"><?= $label ?></span>
            <input type="radio" name="correct" value="<?= $label ?>"<?= $form['correct'] === $label ? ' checked' : '' ?> aria-label="Option <?= $label ?> is the correct answer"></label>
          <label class="field"><span class="sr-only">Option <?= $label ?></span>
            <input class="field__input" type="text" name="<?= $k ?>" maxlength="500" value="<?= e($form[$k]) ?>" aria-invalid="<?= $bad($k) ?>"><?= $err($k) ?></label>
        </div>
      <?php endforeach; ?>
      <?= $err('correct') ?>
    </section>

    <section class="amcq-card">
      <h2>For students and reviewers</h2>
      <label class="field"><span class="field__label">Explanation <span class="amcq-muted">(shown when a student reviews a mistake)</span></span>
        <textarea class="field__input" name="explanation" rows="3" maxlength="2000" aria-invalid="<?= $bad('explanation') ?>"><?= e($form['explanation']) ?></textarea><?= $err('explanation') ?></label>
      <label class="field"><span class="field__label">Source <span class="amcq-muted">(required before it can be verified)</span></span>
        <input class="field__input" type="text" name="source_ref" maxlength="255" value="<?= e($form['source_ref']) ?>" placeholder="NCTB Class 5 Math, Ch 6, p.41" aria-invalid="<?= $bad('source_ref') ?>"><?= $err('source_ref') ?></label>
    </section>

    <div class="admin-actions">
      <button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit"><?= icon('check') ?> <?= $q ? 'Save changes' : 'Create draft' ?></button>
      <?php if ($q && in_array($q['status'], array('verified', 'published'), TRUE)): ?>
        <span class="inline-note">Changing the question, an option, the answer, the level or the chapter sends it back to review.</span>
      <?php endif; ?>
    </div>
  <?= form_close() ?>

  <?php if ($q): ?>
  <aside class="admin-side">
    <section class="amcq-card">
      <h2>Status</h2>
      <p><?= status_badge($q['status']) ?>
        <?php if ($q['verified_at']): ?><span class="amcq-muted"> verified by <?= e($q['verified_by_name'] ?: 'an admin') ?>, <?= e(date('j M Y', strtotime($q['verified_at']))) ?></span><?php endif; ?></p>
      <?php if ($q['status'] !== 'retired'): ?>
        <?php if ($problems): ?>
          <p class="inline-note"><strong>Not ready to verify or publish yet:</strong></p>
          <ul class="admin-problems"><?php foreach ($problems as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
        <?php else: ?><p class="inline-note"><?= icon('check-circle', 'ok') ?> Complete: ready to verify and publish.</p><?php endif; ?>
      <?php endif; ?>
      <p class="inline-note">Save your changes first; these buttons act on what is saved.</p>
      <div class="admin-actions">
        <?php foreach ($next as $to): $retire = $to === 'retired'; ?>
          <?= form_open('admin/questions/' . (int) $q['id'] . '/status', array('class' => 'inline-form')) ?>
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <button class="amcq-btn amcq-btn--sm <?= $retire ? 'amcq-btn--ghost' : ($to === 'published' ? 'amcq-btn--primary' : 'amcq-btn--secondary') ?>" type="submit"<?= $retire ? ' onclick="return confirm(\'Retire this question? Students will no longer see it.\')"' : '' ?>><?= e($label_for($q['status'], $to)) ?></button>
          <?= form_close() ?>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="amcq-card">
      <h2>How students see it</h2>
      <p class="admin-q"><?= math_text($q['stem']) ?></p>
      <ul class="admin-opts">
        <?php foreach ($q['options'] as $label => $o): ?>
          <li class="<?= $o['is_correct'] ? 'is-right' : '' ?>"><b><?= e($label) ?></b> <span><?= math_text($o['body']) ?></span><?php if ($o['is_correct']): ?><span class="tagline"><?= icon('check') ?> Correct</span><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if (trim((string) $q['explanation']) !== ''): ?><p class="inline-note"><strong>ব্যাখ্যা:</strong> <?= math_text($q['explanation']) ?></p><?php endif; ?>
    </section>

    <section class="amcq-card">
      <h2>Details</h2>
      <dl class="kv">
        <dt>Origin</dt><dd><?= e(Question_model::ORIGINS[$q['origin']]) ?></dd>
        <dt>Served</dt><dd><?= (int) $q['times_served'] ?><?= $q['times_served'] ? ' · ' . pct(100 * $q['times_correct'] / $q['times_served']) . ' answered right' : '' ?></dd>
        <dt>Created</dt><dd><?= e(date('j M Y, g:i a', strtotime($q['created_at']))) ?></dd>
        <dt>Updated</dt><dd><?= e(date('j M Y, g:i a', strtotime($q['updated_at']))) ?></dd>
      </dl>
    </section>
  </aside>
  <?php endif; ?>
</div>
