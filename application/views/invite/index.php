<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $code @var string $link @var array $friends */
?>
<h1 class="page-title">Invite Friends</h1>
<p class="amcq-muted">Share your link. When a friend creates an account with it, they appear below.</p>

<div class="card invite-card">
  <h2><?= icon('users') ?> Your invite link</h2>
  <?php if ($code): ?>
    <div class="invite-link">
      <input class="field__input" type="text" id="invite-link" value="<?= e($link) ?>" readonly aria-label="Your invite link">
      <button class="amcq-btn amcq-btn--primary" type="button" data-copy="#invite-link"><?= icon('check') ?> <span>Copy link</span></button>
    </div>
    <p class="amcq-muted small">Your invite code: <b><?= e($code) ?></b></p>
  <?php else: ?>
    <p class="amcq-muted">Your account has no invite code yet. Please contact us.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Friends who joined · <?= count($friends) ?></h2>
  <?php if ($friends): ?>
    <ul class="card-list">
      <?php foreach ($friends as $f): ?>
        <li class="card-list__item"><span class="round round--blue"><?= icon('user') ?></span>
          <div><strong><?= e($f['display_name'] ?: 'A new student') ?></strong><p class="amcq-muted small">Joined <?= e(date('j M Y', strtotime($f['created_at']))) ?></p></div></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="amcq-muted">Nobody has joined with your link yet.</p>
  <?php endif; ?>
</div>
