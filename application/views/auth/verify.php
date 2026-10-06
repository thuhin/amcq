<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--blue chip--lg"><?= icon('shield') ?></span>
    <h1>Enter the code</h1>
    <p class="amcq-muted">We sent a <?= OTP_LENGTH ?>-digit code to <strong><?= e(substr($phone, 0, 3) . '••••' . substr($phone, -4)) ?></strong>. It is valid for <?= OTP_TTL_MINUTES ?> minutes.</p>
    <?php if ($dev_otp): ?>
      <!-- Development only: there is no SMS gateway yet. -->
      <p class="amcq-flash amcq-flash--info">Development mode: your code is <strong><?= e($dev_otp) ?></strong></p>
    <?php endif; ?>
    <?= form_open('login/verify', array('novalidate' => TRUE)) ?>
      <label class="field">
        <span class="field__label">Code</span>
        <input class="field__input field__input--otp" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
               maxlength="<?= OTP_LENGTH ?>" pattern="\d{<?= OTP_LENGTH ?>}" required autofocus
               aria-invalid="<?= $error ? 'true' : 'false' ?>"<?= $error ? ' aria-describedby="code-error"' : '' ?>>
        <?php if ($error): ?><span class="field__error" id="code-error"><?= e($error) ?></span><?php endif; ?>
      </label>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Verify <?= icon('arrow-right') ?></button>
    <?= form_close() ?>
    <p class="auth__note"><a href="<?= site_url('login') ?>">Use a different number or resend the code</a></p>
  </div>
</section>
