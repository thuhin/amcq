<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
<?php if ($dev_login): ?>
    <!-- Development only: see Admin_auth::dev_login(). Never rendered on a server. -->
    <button type="button" class="chip chip--lg admin-devfill" data-dev-login
            data-email="<?= e($dev_login['email']) ?>" data-password="<?= e($dev_login['password']) ?>"
            title="Development: fill in the test login" aria-label="Development: fill in the test login"><?= icon('shield') ?></button>
<?php else: ?>
    <span class="chip chip--lg"><?= icon('shield') ?></span>
<?php endif; ?>
    <h1>Admin sign in</h1>
    <p class="amcq-muted">For AcademicMCQ staff. Students sign in on the main site.</p>
    <?php if ($dev_login): ?><p class="inline-note">Development: click the shield to fill in the test login.</p><?php endif; ?>
    <?php if ($error): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?= form_open('admin/login', array('novalidate' => TRUE, 'autocomplete' => 'off')) ?>
      <label class="field">
        <span class="field__label">Email</span>
        <input class="field__input" type="email" name="email" autocomplete="username" required autofocus value="<?= e($email) ?>">
      </label>
      <label class="field">
        <span class="field__label">Password</span>
        <input class="field__input" type="password" name="password" autocomplete="current-password" required>
      </label>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Sign in <?= icon('arrow-right') ?></button>
    <?= form_close() ?>
  </div>
</section>
