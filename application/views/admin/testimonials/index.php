<?php defined('BASEPATH') OR exit('No direct script access allowed'); /** @var array $rows */ ?>
<div class="admin-head">
  <h1>Testimonials</h1>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--primary" href="<?= site_url('admin/testimonials/new') ?>"><?= icon('plus') ?> Add testimonial</a></div>
</div>
<p class="amcq-muted">These appear in “What Students Say” on the homepage. Only real students, and only with a guardian's consent: a testimonial cannot be published until consent is recorded.</p>

<?php if ($rows): ?>
<section class="amcq-card">
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only">Testimonials</caption>
      <thead><tr><th scope="col">Student</th><th scope="col">Quote</th><th scope="col">Consent</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $t): ?>
        <tr>
          <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/testimonials/' . (int) $t['id']) ?>"><?= e($t['student_name']) ?></a>
            <small class="amcq-muted"><?= e($t['class_label']) ?><?= $t['school_name'] ? ' · ' . e($t['school_name']) : '' ?></small></th>
          <td class="stem"><?= e(mb_strimwidth($t['quote'], 0, 110, '…')) ?><small class="amcq-muted"><?= (int) $t['rating'] ?> / 5</small></td>
          <td><?= $t['consent_at'] ? '<span class="badge badge--ok">' . icon('check') . ' Recorded ' . e(date('j M Y', strtotime($t['consent_at']))) . '</span>' : '<span class="badge badge--warn">Not recorded</span>' ?></td>
          <td><?= $t['is_published'] ? '<span class="badge badge--ok">Published</span>' : '<span class="badge badge--muted">Not published</span>' ?></td>
          <td>
            <div class="admin-actions">
              <?php if ($t['is_published']): ?>
                <?= form_open('admin/testimonials/' . (int) $t['id'] . '/unpublish', array('class' => 'inline-form')) ?><button class="amcq-btn amcq-btn--ghost amcq-btn--sm" type="submit">Unpublish</button><?= form_close() ?>
              <?php else: ?>
                <?= form_open('admin/testimonials/' . (int) $t['id'] . '/publish', array('class' => 'inline-form')) ?><button class="amcq-btn amcq-btn--secondary amcq-btn--sm" type="submit"<?= $t['consent_at'] ? '' : ' disabled title="Record the guardian\'s consent first"' ?>>Publish</button><?= form_close() ?>
                <?= form_open('admin/testimonials/' . (int) $t['id'] . '/delete', array('class' => 'inline-form')) ?><button class="amcq-btn amcq-btn--ghost amcq-btn--sm" type="submit" onclick="return confirm('Delete this testimonial?')">Delete</button><?= form_close() ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php else: ?>
<div class="empty-card"><p>No testimonials yet. The homepage hides the section until there is a published one.</p>
  <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('admin/testimonials/new') ?>">Add the first one</a></div>
<?php endif; ?>
