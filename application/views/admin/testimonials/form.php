<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array|null $t @var array $form @var array $errors */
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<span class="field__error">' . e($errors[$k]) . '</span>' : ''; };
$bad = function ($k) use ($errors) { return isset($errors[$k]) ? 'true' : 'false'; };
$has_consent = $t && $t['consent_at'];
?>
<div class="admin-head">
  <h1><?= $t ? 'Edit testimonial' : 'Add testimonial' ?></h1>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('admin/testimonials') ?>"><?= icon('arrow-left') ?> All testimonials</a></div>
</div>
<?php if (isset($errors['_'])): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($errors['_']) ?></p><?php endif; ?>

<?= form_open($t ? 'admin/testimonials/' . (int) $t['id'] : 'admin/testimonials/new', array('class' => 'admin-form narrow', 'novalidate' => TRUE)) ?>
  <section class="amcq-card">
    <div class="form-grid">
      <label class="field"><span class="field__label">Student's name</span>
        <input class="field__input" type="text" name="student_name" maxlength="100" required value="<?= e($form['student_name']) ?>" aria-invalid="<?= $bad('student_name') ?>"><?= $err('student_name') ?></label>
      <label class="field"><span class="field__label">Class</span>
        <input class="field__input" type="text" name="class_label" maxlength="30" required value="<?= e($form['class_label']) ?>" aria-invalid="<?= $bad('class_label') ?>"><?= $err('class_label') ?></label>
    </div>
    <label class="field"><span class="field__label">School <span class="amcq-muted">(optional)</span></span>
      <input class="field__input" type="text" name="school_name" maxlength="200" value="<?= e($form['school_name']) ?>" aria-invalid="<?= $bad('school_name') ?>"><?= $err('school_name') ?></label>
    <label class="field"><span class="field__label">Quote</span>
      <textarea class="field__input" name="quote" rows="4" maxlength="500" required aria-invalid="<?= $bad('quote') ?>"><?= e($form['quote']) ?></textarea>
      <span class="field__hint amcq-muted">The student's own words, up to 500 characters. Do not improve them.</span><?= $err('quote') ?></label>
    <div class="form-grid">
      <label class="field"><span class="field__label">Rating</span>
        <select class="field__input" name="rating" aria-invalid="<?= $bad('rating') ?>">
          <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"<?= (int) $form['rating'] === $i ? ' selected' : '' ?>><?= $i ?> / 5</option><?php endfor; ?>
        </select><?= $err('rating') ?></label>
      <label class="field"><span class="field__label">Position <span class="amcq-muted">(lower shows first)</span></span>
        <input class="field__input" type="number" name="sort_order" min="0" max="999" value="<?= (int) $form['sort_order'] ?>" aria-invalid="<?= $bad('sort_order') ?>"><?= $err('sort_order') ?></label>
    </div>
  </section>

  <section class="amcq-card">
    <h2>Guardian consent</h2>
    <?php if ($has_consent): ?>
      <p><span class="badge badge--ok"><?= icon('check') ?> Recorded <?= e(date('j M Y', strtotime($t['consent_at']))) ?></span></p>
      <p class="inline-note">Consent covers these exact words. If you change the name or the quote, the consent is cleared and the testimonial is unpublished until it is recorded again.</p>
    <?php else: ?>
      <label class="check"><input type="checkbox" name="consent" value="1"<?= ! empty($form['consent']) ? ' checked' : '' ?>> The student's parent or guardian has agreed to this being shown publicly</label>
      <p class="inline-note">Tick only when you have it. Without it the testimonial cannot be published.</p>
    <?php endif; ?>
  </section>

  <div class="admin-actions"><button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit"><?= icon('check') ?> Save</button></div>
<?= form_close() ?>
