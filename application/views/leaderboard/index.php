<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container narrow">
    <h1>National Academic Ranking</h1>
    <p class="amcq-muted">Lifetime Academic Points. Points never decrease.</p>
    <nav class="tabs" aria-label="Leaderboard"><span class="tab is-active">Lifetime</span><span class="tab is-soon">Competition · soon</span><span class="tab is-soon">Weekly · soon</span></nav>

    <div class="amcq-card table-wrap">
      <?php if ($leaders): ?>
      <table class="table leaderboard">
        <thead><tr><th>Rank</th><th>Student</th><th class="num">Academic Points</th><th>Tier</th></tr></thead>
        <tbody>
        <?php foreach ($leaders as $i => $l): $is_me = $user && (int) $l['id'] === (int) $user['id']; ?>
          <tr class="<?= $i < 3 ? 'is-top' : '' ?><?= $is_me ? ' is-me' : '' ?>">
            <td><span class="rank-no rank-no--<?= $i + 1 ?>"><?= $i + 1 ?></span></td>
            <td><b><?= e($l['display_name']) ?></b><?= $is_me ? ' <small>(you)</small>' : '' ?><?php if ($l['school']): ?><br><small class="amcq-muted"><?= e($l['school']) ?></small><?php endif; ?></td>
            <td class="num amcq-points"><?= points($l['total_points']) ?></td>
            <td><?= e($l['tier']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?><p class="amcq-muted">No one on the board yet.</p><?php endif; ?>
    </div>

    <?php if ($user): ?>
      <div class="amcq-card">
        <h2 class="h3">Your position: #<?= points($rank) ?></h2>
        <table class="table"><tbody>
          <?php foreach ($nearby as $n): ?><tr class="<?= $n['is_me'] ? 'is-me' : '' ?>"><td>#<?= points($n['rank']) ?></td><td><?= $n['is_me'] ? '<b>You</b>' : e($n['display_name']) ?></td><td class="num amcq-points"><?= points($n['total_points']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
      </div>
    <?php else: ?>
      <div class="save-cta"><span class="chip chip--blue chip--lg"><?= icon('trophy') ?></span>
        <div><h2>Create an account and start building your rank</h2><p class="amcq-muted">Free. Your phone number is never shown publicly.</p></div>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('login') ?>">Create Free Account</a></div>
    <?php endif; ?>
  </div>
</section>
