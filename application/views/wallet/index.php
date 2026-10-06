<?php defined('BASEPATH') OR exit('No direct script access allowed');
$labels = array('topup' => 'Top-up', 'quiz_fee' => 'Quiz', 'competition_fee' => 'Competition', 'refund' => 'Refund', 'prize' => 'Prize', 'adjustment' => 'Adjustment'); ?>
<section class="section section--tight">
  <div class="amcq-container wallet">
    <div class="wallet__card">
      <small><?= icon('wallet') ?> Wallet Balance</small>
      <b class="wallet__balance"><?= taka($balance) ?></b>
      <span>≈ <?= floor($balance / QUIZ_FEE_TAKA) ?> quizzes at Tk <?= number_format(QUIZ_FEE_TAKA) ?> each</span>
    </div>

    <div class="amcq-card" id="add">
      <h2 class="h3">Add Balance</h2>
      <?php if ($simulated): ?>
        <p class="amcq-flash amcq-flash--info">Development mode: top-ups are simulated. No real money is charged.</p>
        <?= form_open('wallet/topup', array('class' => 'topup')) ?>
          <div class="topup__presets" role="radiogroup" aria-label="Amount">
            <?php foreach ($presets as $i => $p): ?>
              <label class="topup__preset"><input type="radio" name="amount" value="<?= $p ?>"<?= $i === 1 ? ' checked' : '' ?>><span><?= taka($p) ?></span></label>
            <?php endforeach; ?>
          </div>
          <label class="field field--inline"><span class="field__label">Custom amount (৳)</span>
            <input class="field__input" type="number" name="custom" min="10" max="1000" step="1" inputmode="numeric" placeholder="e.g. 30"></label>
          <!-- Paid action: exact amount, what you get, refundability, one confirm (§17). -->
          <p class="amcq-muted">Wallet balance is used for quizzes (Tk <?= number_format(QUIZ_FEE_TAKA) ?>) and competition registration. See the <a href="<?= site_url('refund-policy') ?>">refund policy</a>.</p>
          <button class="amcq-btn amcq-btn--primary amcq-btn--lg" type="submit">Add Balance</button>
        <?= form_close() ?>
      <?php else: ?>
        <p>Top up with bKash or Nagad, coming soon.</p>
      <?php endif; ?>
    </div>

    <div class="amcq-card wallet__history">
      <h2 class="h3">Transactions</h2>
      <?php if ($transactions): ?>
      <ul class="txns">
        <?php foreach ($transactions as $t): $in = $t['amount'] > 0; ?>
        <li>
          <span class="chip chip--<?= $in ? 'green' : 'blue' ?>"><?= icon($in ? 'wallet' : 'file') ?></span>
          <span class="txns__what"><b><?= e($t['description'] ?: $labels[$t['type']]) ?></b>
            <small class="amcq-muted"><?= $labels[$t['type']] ?><?= $t['gateway'] && $t['gateway'] !== 'system' ? ' · ' . e(ucfirst($t['gateway'])) : '' ?> · <?= date('j M Y, g:i a', strtotime($t['created_at'])) ?></small></span>
          <b class="txns__amt <?= $in ? 'is-in' : 'is-out' ?>"><?= $in ? '+' : '−' ?><?= taka(abs($t['amount'])) ?></b>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">No transactions yet.</p><?php endif; ?>
    </div>
  </div>
</section>
