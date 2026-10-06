<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="section-head">
  <h1>Notifications</h1>
  <?php if ($unread): ?><?= form_open('notifications/read') ?><button class="amcq-btn amcq-btn--secondary amcq-btn--sm" type="submit">Mark all as read</button><?= form_close() ?><?php endif; ?>
</div>
<?php if ($items): ?>
<ul class="card-list">
  <?php foreach ($items as $n): ?>
  <li class="card-list__item<?= $n['read_at'] ? '' : ' is-unread' ?>">
    <span class="round round--blue"><?= icon('bell') ?></span>
    <div><strong><?= e($n['title']) ?></strong><?php if ($n['body']): ?><p class="amcq-muted"><?= e($n['body']) ?></p><?php endif; ?>
      <small class="amcq-muted"><?= time_ago($n['created_at']) ?></small></div>
    <?php if ($n['link']): ?><a class="link-arrow" href="<?= site_url($n['link']) ?>">View <?= icon('arrow-right') ?></a><?php endif; ?>
  </li>
  <?php endforeach; ?>
</ul>
<?php else: ?><div class="empty-card"><span class="round round--blue"><?= icon('bell') ?></span><p>No notifications yet. Streaks, points and competition news will show up here.</p></div><?php endif; ?>
