<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--blue chip--lg"><?= icon('user') ?></span>
    <h1>Sign in or create a free account</h1>
    <p class="amcq-muted">We will send a 6-digit code to your phone. No password needed.</p>
    <?= form_open('login', array('novalidate' => TRUE)) ?>
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
  </div>
</section>
