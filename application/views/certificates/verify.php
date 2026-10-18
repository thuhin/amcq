<?php defined('BASEPATH') OR exit('No direct script access allowed'); /** @var string $code */ ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--lg"><?= icon('certificate') ?></span>
    <h1>Verify a certificate</h1>
    <p class="amcq-muted">Type the Verification ID printed on an AcademicMCQ certificate.</p>
    <?php if ($code !== ''): ?><p class="amcq-flash amcq-flash--warning" role="alert">No certificate has the ID <?= e($code) ?>. Check it and try again.</p><?php endif; ?>
    <form method="get" action="<?= site_url('certificates/verify') ?>">
      <label class="field"><span class="field__label">Verification ID</span>
        <input class="field__input" type="text" name="code" value="<?= e($code) ?>" maxlength="20" autocomplete="off" spellcheck="false" required placeholder="e.g. 7K2M9QHX4TNA"></label>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Check</button>
    </form>
  </div>
</section>
