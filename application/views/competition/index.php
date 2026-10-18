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
    <?php
    $num = function ($v) { return rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); };
    $st = $registration ? $registration['status'] : NULL;
    ?>
    <?php if ($registration): ?>
      <h2 class="h3">Your competition</h2>
      <?php if ($c['status'] === 'registration' || $c['status'] === 'draft'): ?>
        <p class="amcq-flash amcq-flash--success"><?= icon('check-circle') ?> You are registered.</p>
        <p>Round 1 opens <?= $c['round1_opens_at'] ? 'on <b>' . date('j M Y, g:i a', strtotime($c['round1_opens_at'])) . '</b>' : 'soon' ?>. It is <?= (int) $c['round1_question_count'] ?> questions in <?= (int) $c['round1_time_limit_minutes'] ?> minutes, in one sitting.</p>
      <?php elseif ($c['status'] === 'round1'): ?>
        <?php if ($round1_state === 'ready'): ?>
          <p><b>Round 1 is open.</b> <?= (int) $c['round1_question_count'] ?> questions, <?= (int) $c['round1_time_limit_minutes'] ?> minutes, one sitting. The clock starts when you press Start and does not stop.</p>
          <?= form_open('competition/round1/start') ?><button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit"><?= icon('play') ?> Start Round 1</button><?= form_close() ?>
        <?php elseif (in_array($round1_state, array('in_progress', 'expired'), TRUE)): ?>
          <p><b>Your Round 1 is in progress.</b> The clock is running.</p>
          <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="<?= site_url('competition/round1') ?>"><?= icon('arrow-right') ?> Continue Round 1</a>
        <?php else: ?>
          <p class="amcq-flash amcq-flash--success"><?= icon('check-circle') ?> Round 1 submitted. Results are announced when the round closes.</p>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($registration['round1_submitted_at']): ?>
          <p>Round 1: <b><?= $num($registration['round1_score']) ?> / <?= $num($max1) ?></b><?= $registration['round1_rank'] ? ', rank <b>' . (int) $registration['round1_rank'] . '</b>' : '' ?>.</p>
        <?php else: ?>
          <p>You did not sit Round 1.</p>
        <?php endif; ?>
        <?php if ($c['status'] === 'grading'): ?>
          <?php if ($st === 'qualified'): ?><p class="amcq-flash amcq-flash--success"><?= icon('trophy') ?> You qualified for the final. It will be announced here.</p>
          <?php else: ?><p>You did not qualify for the final this time. Your participation certificate is in <a href="<?= site_url('certificates') ?>">Certificates</a>.</p><?php endif; ?>
        <?php elseif ($c['status'] === 'final'): ?>
          <?php if ($final_state === 'ready'): ?>
            <p><b>The final is open.</b> <?= (int) $c['final_question_count'] ?> questions, <?= (int) $c['final_time_limit_minutes'] ?> minutes, one question at a time. You cannot go back to a question.</p>
            <?= form_open('competition/final/start') ?><button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit"><?= icon('play') ?> Start the final</button><?= form_close() ?>
          <?php elseif (in_array($final_state, array('in_progress', 'expired'), TRUE)): ?>
            <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="<?= site_url('competition/final') ?>"><?= icon('arrow-right') ?> Continue the final</a>
          <?php elseif ($final_state === 'submitted'): ?>
            <p class="amcq-flash amcq-flash--success"><?= icon('check-circle') ?> Final submitted. Results are announced when the final closes.</p>
          <?php else: ?>
            <p>The final is under way for the qualifiers.</p>
          <?php endif; ?>
        <?php elseif ($c['status'] === 'completed'): ?>
          <?php if ($registration['final_rank']): ?>
            <p>Final: <b><?= $num($registration['final_score']) ?> / <?= $num($maxf) ?></b>, <b><?= e(Competition_round_model::ordinal($registration['final_rank'])) ?> place</b>.</p>
          <?php endif; ?>
          <?php if ($st === 'winner'): ?>
            <p class="amcq-flash amcq-flash--success"><?= icon('trophy') ?> Congratulations! Prize: <b class="amcq-money"><?= taka($registration['prize_amount']) ?></b><?= $registration['prize_paid_at'] ? ' (paid)' : '. We will contact you about payment.' ?></p>
          <?php endif; ?>
          <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('certificates') ?>"><?= icon('certificate') ?> My certificates</a>
        <?php endif; ?>
      <?php endif; ?>
    <?php elseif ( ! $open): ?>
      <h2 class="h3">Register</h2>
      <p><b><?= in_array($c['status'], array('draft'), TRUE) ? 'Registration opens soon.' : 'Registration is closed.' ?></b> Read the rules above in the meantime.</p>
    <?php elseif ( ! $user): ?>
      <h2 class="h3">Register</h2>
      <p>Sign in to register.</p><a class="amcq-btn amcq-btn--primary" href="<?= site_url('login') ?>">Sign In</a>
    <?php else: ?>
      <h2 class="h3">Register</h2>
      <?= form_open('competition/register') ?>
        <p>You pay <b class="amcq-money"><?= $fee ?></b> from your wallet, once. You get Round 1 and, if you qualify, the final.</p>
        <label class="check"><input type="checkbox" name="confirm" value="1" required> I have read the rules and the refund policy.</label>
        <button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit">Register for <?= $fee ?></button>
      <?= form_close() ?>
    <?php endif; ?>
  </div>
</section>
