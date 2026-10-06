<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container narrow">
    <h1>Profile &amp; Settings</h1>
    <?= form_open('profile', array('class' => 'amcq-card form-card')) ?>
      <h2 class="h3">Profile</h2>
      <label class="field"><span class="field__label">Name</span>
        <input class="field__input" type="text" name="name" value="<?= e(set_value('name', $user['name'])) ?>" required>
        <?= form_error('name', '<span class="field__error">', '</span>') ?></label>
      <label class="field"><span class="field__label">Display name <span class="amcq-muted">(shown on leaderboards)</span></span>
        <input class="field__input" type="text" name="display_name" value="<?= e(set_value('display_name', $user['display_name'])) ?>" required>
        <?= form_error('display_name', '<span class="field__error">', '</span>') ?></label>
      <div class="field"><span class="field__label">Phone</span><span class="field__static"><?= e($user['phone']) ?></span></div>
      <div class="field"><span class="field__label">Class</span><span class="field__static"><?= e($class ? $class['name'] : '—') ?></span></div>
      <label class="field"><span class="field__label">School</span>
        <select class="field__input" name="school_id"><option value="">— None —</option>
          <?php foreach ($schools as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $user['school_id'] === (int) $s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
        </select></label>

      <h2 class="h3">Preferences</h2>
      <label class="field"><span class="field__label">Language</span>
        <select class="field__input" name="language">
          <option value="bn"<?= $settings['language'] === 'bn' ? ' selected' : '' ?>>বাংলা</option>
          <option value="en"<?= $settings['language'] === 'en' ? ' selected' : '' ?>>English</option>
        </select></label>
      <label class="check"><input type="checkbox" name="show_on_leaderboard" value="1"<?= $settings['show_on_leaderboard'] ? ' checked' : '' ?>> Show me on the public leaderboard</label>
      <label class="check"><input type="checkbox" name="notify_streak" value="1"<?= $settings['notify_streak'] ? ' checked' : '' ?>> Streak reminders</label>
      <label class="check"><input type="checkbox" name="notify_competition" value="1"<?= $settings['notify_competition'] ? ' checked' : '' ?>> Competition updates</label>
      <button class="amcq-btn amcq-btn--primary" type="submit">Save</button>
    <?= form_close() ?>
  </div>
</section>
