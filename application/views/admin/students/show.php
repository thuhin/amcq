<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $s @var array $sums @var array $wallet @var array $points @var int $rank @var array $attempts @var array $corrections @var array $registrations @var array|null $invited_by @var array $invited */
$wallet_ok = abs($sums['wallet'] - (float) $s['balance']) < 0.005;
$points_ok = $sums['points'] === (int) $s['total_points'];
?>
<div class="admin-head">
  <div>
    <h1><?= e($s['name'] ?: 'Student #' . $s['id']) ?> <?= $s['status'] === 'active' ? '<span class="badge badge--ok">Active</span>' : '<span class="badge badge--bad">Suspended</span>' ?></h1>
    <p class="admin-head__sub">#<?= (int) $s['id'] ?> · <?= e($s['phone']) ?> · joined <?= e(date('j M Y', strtotime($s['created_at']))) ?></p>
  </div>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('admin/students') ?>"><?= icon('arrow-left') ?> All students</a></div>
</div>

<ul class="kpis">
  <li><div class="kpi"><span class="kpi__icon"><?= icon('wallet') ?></span><span class="kpi__body"><b class="amcq-money"><?= taka($s['balance']) ?></b><span>Wallet <?= $wallet_ok ? '· matches its ledger' : '· DOES NOT match its ledger (' . taka($sums['wallet']) . ')' ?></span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('star') ?></span><span class="kpi__body"><b><?= points($s['total_points']) ?></b><span>Academic Points · <?= e($s['tier']) ?> <?= $points_ok ? '' : '· DOES NOT match its ledger (' . points($sums['points']) . ')' ?></span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('bar-up') ?></span><span class="kpi__body"><b>#<?= (int) $rank ?></b><span>National rank</span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('checklist') ?></span><span class="kpi__body"><b><?= count($attempts) ?></b><span>Recent quizzes shown</span></span></div></li>
</ul>

<div class="admin-two admin-two--spaced">
  <div class="admin-side">
    <section class="amcq-card">
      <h2>Wallet ledger <small class="amcq-muted">latest <?= count($wallet) ?></small></h2>
      <?php if ($wallet): ?>
      <div class="table-wrap"><table class="table admin-table">
        <thead><tr><th scope="col">When</th><th scope="col">What</th><th scope="col" class="num">Amount</th><th scope="col" class="num">Balance</th></tr></thead>
        <tbody><?php foreach ($wallet as $t): ?>
          <tr><td><?= e(date('j M Y, g:i a', strtotime($t['created_at']))) ?></td><td><?= e(ucwords(str_replace('_', ' ', $t['type']))) ?><small class="amcq-muted"><?= e($t['description']) ?></small></td>
            <td class="num amcq-money"><?= ($t['amount'] > 0 ? '+' : '') . e(number_format((float) $t['amount'], 2)) ?></td><td class="num"><?= taka($t['balance_after']) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php else: ?><p class="amcq-muted">No wallet activity.</p><?php endif; ?>
      <p class="inline-note">Read only. Admins cannot change a balance.</p>
    </section>

    <section class="amcq-card">
      <h2>Recent quizzes</h2>
      <?php if ($attempts): ?>
      <div class="table-wrap"><table class="table admin-table">
        <thead><tr><th scope="col">Started</th><th scope="col">Chapter</th><th scope="col" class="num">Score</th><th scope="col">Status</th></tr></thead>
        <tbody><?php foreach ($attempts as $a): ?>
          <tr><td><?= e(date('j M, g:i a', strtotime($a['started_at']))) ?></td><td><?= e($a['chapter']) ?></td>
            <td class="num"><?= $a['status'] === 'completed' ? (int) $a['score'] . ' / ' . (int) $a['total_questions'] : '—' ?></td><td><?= e(ucwords(str_replace('_', ' ', $a['status']))) ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <?php else: ?><p class="amcq-muted">No quizzes yet.</p><?php endif; ?>
    </section>
  </div>

  <aside class="admin-side">
    <section class="amcq-card">
      <h2>Account</h2>
      <dl class="kv">
        <dt>Display name</dt><dd><?= e($s['display_name']) ?></dd>
        <dt>Class</dt><dd><?= e($s['class_name'] ?: '—') ?></dd>
        <dt>School</dt><dd><?= e($s['school'] ?: '—') ?></dd>
        <dt>Last sign-in</dt><dd><?= $s['last_login_at'] ? e(date('j M Y, g:i a', strtotime($s['last_login_at']))) : 'never' ?></dd>
        <dt>Invited by</dt><dd><?= $invited_by ? '<a href="' . site_url('admin/students/' . (int) $invited_by['id']) . '">' . e($invited_by['name']) . '</a>' : '—' ?></dd>
        <dt>Invited</dt><dd><?= count($invited) ?> friend<?= count($invited) === 1 ? '' : 's' ?></dd>
      </dl>
      <?= form_open('admin/students/' . (int) $s['id'] . '/status', array('class' => 'admin-actions admin-actions--spaced')) ?>
        <?php if ($s['status'] === 'active'): ?>
          <input type="hidden" name="to" value="suspended">
          <button class="amcq-btn amcq-btn--secondary amcq-btn--sm" type="submit" onclick="return confirm('Suspend this student? They are signed out at once.')">Suspend account</button>
        <?php else: ?>
          <input type="hidden" name="to" value="active">
          <button class="amcq-btn amcq-btn--primary amcq-btn--sm" type="submit">Reactivate account</button>
        <?php endif; ?>
      <?= form_close() ?>
    </section>

    <section class="amcq-card">
      <h2>Academic Points</h2>
      <?php if ($points): ?>
        <ul class="admin-opts"><?php foreach ($points as $p): ?>
          <li><b class="amcq-points">+<?= (int) $p['points'] ?></b><span><?= e($p['description'] ?: $p['source']) ?></span><span class="tagline amcq-muted"><?= e(date('j M Y', strtotime($p['created_at']))) ?></span></li>
        <?php endforeach; ?></ul>
      <?php else: ?><p class="amcq-muted">No points yet.</p><?php endif; ?>
    </section>

    <?php if ($registrations): ?>
    <section class="amcq-card">
      <h2>Competition</h2>
      <?php foreach ($registrations as $r): ?>
        <dl class="kv">
          <dt>Competition</dt><dd><?= e($r['competition']) ?> <?= (int) $r['year'] ?></dd>
          <dt>Status</dt><dd><?= e(ucwords(str_replace('_', ' ', $r['status']))) ?></dd>
          <?php if ($r['round1_score'] !== NULL): ?><dt>Round 1</dt><dd><?= e($r['round1_score']) ?><?= $r['round1_rank'] ? ', rank ' . (int) $r['round1_rank'] : '' ?></dd><?php endif; ?>
          <?php if ($r['final_rank']): ?><dt>Final</dt><dd><?= e($r['final_score']) ?>, rank <?= (int) $r['final_rank'] ?></dd><?php endif; ?>
          <?php if ($r['prize_amount']): ?><dt>Prize</dt><dd class="amcq-money"><?= taka($r['prize_amount']) ?> <?= $r['prize_paid_at'] ? '(paid, ref ' . e($r['prize_payout_ref']) . ')' : '(not yet paid)' ?></dd><?php endif; ?>
        </dl>
      <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($corrections): ?>
    <section class="amcq-card">
      <h2>Correct Me reports</h2>
      <ul class="admin-opts"><?php foreach (array_slice($corrections, 0, 10) as $r): ?>
        <li><span><?= math_text(mb_strimwidth($r['stem'], 0, 60, '…')) ?></span><span class="tagline"><?= status_badge($r['status']) ?></span></li>
      <?php endforeach; ?></ul>
    </section>
    <?php endif; ?>
  </aside>
</div>
