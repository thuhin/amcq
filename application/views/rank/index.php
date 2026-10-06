<?php defined('BASEPATH') OR exit('No direct script access allowed');
$to_next = $next ? $next['min_points'] - $total : 0;
$span = $next ? $next['min_points'] - $tier['min_points'] : 1;
$tp = $next ? round(($total - $tier['min_points']) * 100 / $span) : 100; ?>
<section class="rank-hero">
  <div class="amcq-container rank-hero__inner">
    <div class="rank-hero__main">
      <small>National Rank</small>
      <b class="rank-hero__rank">#<?= points($rank) ?>
        <?php if ($rank_change): ?><span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?></b>
    </div>
    <div><small>Academic Points</small><b class="amcq-points rank-hero__pts"><?= points($total) ?></b></div>
    <div><small>Tier</small><b class="rank-hero__tier"><?= icon('medal') ?> <?= e($tier['title']) ?></b></div>
  </div>
</section>

<section class="amcq-container rank-grid">
  <div class="amcq-card">
    <h2 class="h3">Tier Progress</h2>
    <?php if ($next): ?>
      <p><b class="amcq-points"><?= points($to_next) ?> points</b> to <?= e($next['title']) ?></p>
      <div class="amcq-progress amcq-progress--lg"><div class="amcq-progress__bar" style="width: <?= $tp ?>%"></div></div>
    <?php else: ?><p>You are at the highest tier. Remarkable.</p><?php endif; ?>
    <ol class="ladder">
      <?php foreach ($tiers as $t): $reached = $total >= $t['min_points']; ?>
        <li class="<?= $reached ? 'is-reached' : '' ?><?= $t['id'] === $tier['id'] ? ' is-current' : '' ?>">
          <span class="ladder__dot"><?= $reached ? icon('check') : '' ?></span>
          <b><?= e($t['title']) ?></b><small class="amcq-muted"><?= points($t['min_points']) ?> pts</small>
        </li>
      <?php endforeach; ?>
    </ol>
    <p class="note"><?= icon('shield') ?> Academic Points never decrease and are not spendable.</p>
  </div>

  <div class="amcq-card">
    <h2 class="h3">You and Nearby</h2>
    <table class="table">
      <tbody>
      <?php foreach ($nearby as $n): ?>
        <tr class="<?= $n['is_me'] ? 'is-me' : '' ?>"><td>#<?= points($n['rank']) ?></td><td><?= $n['is_me'] ? '<b>You</b>' : e($n['display_name']) ?></td><td class="num amcq-points"><?= points($n['total_points']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <a class="link-arrow" href="<?= site_url('leaderboard') ?>">Full Leaderboard <?= icon('arrow-right') ?></a>
  </div>

  <div class="amcq-card rank-grid__wide">
    <h2 class="h3">Point History</h2>
    <?php if ($history): ?>
    <table class="table">
      <thead><tr><th>Date</th><th>For</th><th class="num">Points</th></tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr><td><?= date('j M Y', strtotime($h['created_at'])) ?></td><td><?= e($h['description']) ?></td><td class="num amcq-points">+<?= points($h['points']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><p class="amcq-muted">Complete a <?= STREAK_REQUIRED_QUIZZES ?>-quiz streak, master a chapter or get a correction approved to earn your first points.</p><?php endif; ?>
  </div>
</section>
