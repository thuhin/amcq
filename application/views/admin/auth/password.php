<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--lg"><?= icon('lock') ?></span>
    <h1>Change password</h1>
    <p class="amcq-muted">At least <?= (int) ADMIN_PASSWORD_MIN_LENGTH ?> characters. Use one you do not use anywhere else.</p>
    <?php if ($error): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?= form_open('admin/password', array('novalidate' => TRUE, 'autocomplete' => 'off')) ?>
      <label class="field"><span class="field__label">Current password</span>
        <input class="field__input" type="password" name="current_password" autocomplete="current-password" required></label>
      <label class="field"><span class="field__label">New password</span>
        <input class="field__input" type="password" name="new_password" autocomplete="new-password" minlength="<?= (int) ADMIN_PASSWORD_MIN_LENGTH ?>" required></label>
      <label class="field"><span class="field__label">New password again</span>
        <input class="field__input" type="password" name="confirm_password" autocomplete="new-password" required></label>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Save password</button>
    <?= form_close() ?>
  </div>
</section>
