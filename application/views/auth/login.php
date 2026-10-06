<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--blue chip--lg"><?= icon('user') ?></span>
    <h1><?= $signup ? 'Create your free account' : 'Login' ?></h1>
    <p class="amcq-muted">We will send a 6-digit code to your phone. No password needed.</p>
    <?= form_open($signup ? 'signup' : 'login', array('novalidate' => TRUE)) ?>
      <label class="field">
        <span class="field__label">Mobile number</span>
        <input class="field__input" type="tel" name="phone" inputmode="numeric" autocomplete="tel"
               placeholder="01XXXXXXXXX" value="<?= e($phone) ?>" required
               aria-invalid="<?= $error ? 'true' : 'false' ?>"<?= $error ? ' aria-describedby="phone-error"' : '' ?>>
        <?php if ($error): ?><span class="field__error" id="phone-error"><?= e($error) ?></span><?php endif; ?>
      </label>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Send Code <?= icon('arrow-right') ?></button>
    <?= form_close() ?>
    <p class="auth__note amcq-muted">Free account: save every result, build streaks and Academic Points, and see your national rank.</p>
    <p class="auth__note"><?= $signup ? 'Already have an account? <a href="' . site_url('login') . '">Login</a>' : 'New here? <a href="' . site_url('signup') . '">Sign Up</a>' ?></p>
  </div>
</section>
