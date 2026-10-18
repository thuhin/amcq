<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $m */
?>
<div class="admin-head">
  <h1><?= e(Contact_model::reference($m['id'])) ?> <?= status_badge($m['status']) ?></h1>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('admin/messages') ?>"><?= icon('arrow-left') ?> All messages</a></div>
</div>

<div class="admin-two">
  <section class="amcq-card">
    <h2><?= e(Contact_model::TOPICS[$m['topic']]) ?></h2>
    <p class="admin-quote"><?= e($m['message']) ?></p>
  </section>

  <aside class="admin-side">
    <section class="amcq-card">
      <h2>Reply to</h2>
      <dl class="kv">
        <dt>Name</dt><dd><?= e($m['name']) ?></dd>
        <?php if ($m['phone']): ?><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $m['phone'])) ?>"><?= e($m['phone']) ?></a></dd><?php endif; ?>
        <?php if ($m['email']): ?><dt>Email</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd><?php endif; ?>
        <dt>Student</dt><dd><?= $m['student_name'] ? e($m['student_name']) . ' (signed in)' : '<span class="amcq-muted">not signed in</span>' ?></dd>
        <dt>Received</dt><dd><?= e(date('j M Y, g:i a', strtotime($m['created_at']))) ?></dd>
      </dl>
      <p class="inline-note">Nothing is sent from here. Reply by phone or email, then mark it below.</p>
    </section>

    <section class="amcq-card">
      <h2>Status</h2>
      <div class="admin-actions">
        <?php foreach (Contact_model::STATUSES as $key => $label): if ($key === $m['status']) { continue; } ?>
          <?= form_open('admin/messages/' . (int) $m['id'] . '/status', array('class' => 'inline-form')) ?>
            <input type="hidden" name="to" value="<?= e($key) ?>">
            <button class="amcq-btn amcq-btn--sm <?= $key === 'replied' ? 'amcq-btn--primary' : 'amcq-btn--secondary' ?>" type="submit"><?= $key === 'new' ? 'Mark as new' : 'Mark as ' . e(strtolower($label)) ?></button>
          <?= form_close() ?>
        <?php endforeach; ?>
      </div>
    </section>
  </aside>
</div>
