<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fee = taka($c['entry_fee']); $pool = str_replace('.00', '', taka($c['prize_pool'])); ?>
<section class="comp-hero">
  <div class="amcq-container">
    <span class="badge badge--pink">Tk <?= number_format($c['entry_fee']) ?> Entry</span>
    <h1><?= e($c['name']) ?> <?= (int) $c['year'] ?></h1>
    <p>Test your knowledge, qualify for the final and earn national recognition.</p>
    <a class="amcq-btn amcq-btn--light amcq-btn--lg" href="#register">Register for Competition</a>
  </div>
</section>

<section class="amcq-container">
  <ul class="stat-row stat-row--3">
    <li class="amcq-card stat"><span class="chip chip--pink"><?= icon('wallet') ?></span><div><small>Entry</small><b><?= $fee ?></b><em class="amcq-muted">one-time, final included</em></div></li>
    <li class="amcq-card stat"><span class="chip chip--blue"><?= icon('file') ?></span><div><small>Round 1</small><b><?= (int) $c['round1_question_count'] ?> questions</b><em class="amcq-muted"><?= (int) $c['round1_time_limit_minutes'] ?> minutes</em></div></li>
    <li class="amcq-card stat"><span class="chip chip--amber"><?= icon('trophy') ?></span><div><small>Qualify</small><b>Top <?= rtrim(rtrim($c['qualify_percent'], '0'), '.') ?>%</b><em class="amcq-muted">go to the final</em></div></li>
    <li class="amcq-card stat"><span class="chip chip--purple"><?= icon('target') ?></span><div><small>Final</small><b><?= (int) $c['final_question_count'] ?> questions</b><em class="amcq-muted">one by one, proctored</em></div></li>
    <li class="amcq-card stat"><span class="chip chip--green"><?= icon('medal') ?></span><div><small>Winners</small><b><?= (int) $winners ?></b><em class="amcq-muted"><?= $pool ?> prize pool</em></div></li>
    <li class="amcq-card stat"><span class="chip chip--orange"><?= icon('calendar') ?></span><div><small>Round 1 opens</small><b><?= $c['round1_opens_at'] ? date('j M Y', strtotime($c['round1_opens_at'])) : 'To be announced' ?></b></div></li>
  </ul>
</section>

<section class="amcq-container comp-body">
  <div class="amcq-card">
    <h2 class="h3">How it works</h2>
    <ol class="steps">
      <li><b>1</b><div><strong>Register</strong><span class="amcq-muted">Pay <?= $fee ?> once from your wallet.</span></div></li>
      <li><b>2</b><div><strong>Round 1</strong><span class="amcq-muted"><?= (int) $c['round1_question_count'] ?> questions (10 easy, 10 medium, 10 hard) in <?= (int) $c['round1_time_limit_minutes'] ?> minutes. Each student gets a randomised set. Scored 1 / 1.5 / 2 points by difficulty, maximum 50.</span></div></li>
      <li><b>3</b><div><strong>Final</strong><span class="amcq-muted">The top <?= rtrim(rtrim($c['qualify_percent'], '0'), '.') ?>% sit a proctored final of <?= (int) $c['final_question_count'] ?> questions, one at a time, with no going back.</span></div></li>
      <li><b>4</b><div><strong>Results</strong><span class="amcq-muted">Ties are broken by time taken. <?= (int) $winners ?> winners; every participant gets a certificate.</span></div></li>
    </ol>
  </div>

  <div class="amcq-card">
    <h2 class="h3">Prize structure</h2>
    <table class="table">
      <thead><tr><th>Position</th><th class="num">Prize</th><th class="num">Academic Points</th></tr></thead>
      <tbody>
      <?php foreach ($prizes as $p): ?>
        <tr><td><?= $p['rank_from'] === $p['rank_to'] ? '#' . $p['rank_from'] : '#' . $p['rank_from'] . '–' . $p['rank_to'] ?></td>
          <td class="num amcq-money"><?= str_replace('.00', '', taka($p['prize_amount'])) ?><?= $p['rank_from'] !== $p['rank_to'] ? ' each' : '' ?></td>
          <td class="num amcq-points">+<?= (int) $p['points'] ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="amcq-card" id="rules">
    <h2 class="h3">Eligibility &amp; rules</h2>
    <ul class="rules">
      <li>Open to <?= $class ? e($class['name']) : 'enrolled' ?> students with a registered AcademicMCQ account.</li>
      <li>One registration per student. The <?= $fee ?> fee covers Round 1 and, if you qualify, the final.</li>
      <li>Round 1 must be completed in one sitting within <?= (int) $c['round1_time_limit_minutes'] ?> minutes.</li>
      <li>The final round is proctored. What proctoring involves will be stated here before registration opens.</li>
    </ul>
    <!-- Dispute window, prize payout method and full proctoring terms are
         business decisions not yet made. They belong in rules_html, set by an
         admin, rather than invented here. -->
    <p class="note"><?= icon('bulb') ?> Full rules, the result dispute process and prize payment details will be published before registration opens.</p>
    </ul>
    <?php if ($c['rules_html']): ?><!-- Admin-authored HTML, trusted. --><div class="rules-extra"><?= $c['rules_html'] ?></div><?php endif; ?>
    <p class="amcq-muted"><a href="<?= site_url('refund-policy') ?>">Refund &amp; payment policy</a></p>
  </div>

  <div class="amcq-card register-box" id="register">
    <h2 class="h3">Register</h2>
    <?php if ($registration): ?>
      <p class="amcq-flash amcq-flash--success"><?= icon('check-circle') ?> You are registered. Status: <b><?= e(ucwords(str_replace('_', ' ', $registration['status']))) ?></b></p>
    <?php elseif ( ! $open): ?>
      <p><b>Registration opens soon.</b> Read the rules above in the meantime. We'll announce the dates here.</p>
    <?php elseif ( ! $user): ?>
      <p>Sign in to register.</p><a class="amcq-btn amcq-btn--primary" href="<?= site_url('login') ?>">Sign In</a>
    <?php else: ?>
      <?= form_open('competition/register') ?>
        <p>You pay <b class="amcq-money"><?= $fee ?></b> from your wallet, once. You get Round 1 and, if you qualify, the final.</p>
        <label class="check"><input type="checkbox" name="confirm" value="1" required> I have read the rules and the refund policy.</label>
        <button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit">Register for <?= $fee ?></button>
      <?= form_close() ?>
    <?php endif; ?>
  </div>
</section>
