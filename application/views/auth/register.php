<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="auth">
  <div class="amcq-card auth__card">
    <span class="chip chip--green chip--lg"><?= icon('check') ?></span>
    <h1>Almost done</h1>
    <p class="amcq-muted">Just two things, so we can show the right chapters.</p>
    <?= form_open('register') ?>
      <label class="field">
        <span class="field__label">Your name</span>
        <input class="field__input" type="text" name="name" value="<?= e(set_value('name')) ?>" autocomplete="name" required
               aria-invalid="<?= form_error('name') ? 'true' : 'false' ?>">
        <?= form_error('name', '<span class="field__error">', '</span>') ?>
      </label>
      <label class="field">
        <span class="field__label">Class</span>
        <select class="field__input" name="class_id" required>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= set_select('class_id', $c['id']) ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?= form_error('class_id', '<span class="field__error">', '</span>') ?>
      </label>
      <label class="field">
        <span class="field__label">School <span class="amcq-muted">(optional)</span></span>
        <select class="field__input" name="school_id">
          <option value="">— Skip for now —</option>
          <?php foreach ($schools as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= set_select('school_id', $s['id']) ?>><?= e($s['name']) ?><?= $s['district'] ? ', ' . e($s['district']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <p class="amcq-muted field__hint">On leaderboards we show a short name like “Rahim A.”, never your phone number. You can change it in your profile.</p>
      <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Create Account <?= icon('arrow-right') ?></button>
    <?= form_close() ?>
  </div>
</section>
