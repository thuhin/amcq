<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * @var array $c @var array|null $class @var array $form @var array $errors @var bool $editable @var array $counts @var array $prizes
 * @var array $pool @var array $mix1 @var array $mixf @var array $problems @var float $max1 @var float $maxf
 * @var array $round1 @var array $final
 */
$stages = array(
	'draft'        => 'Draft',
	'registration' => 'Registration',
	'round1'       => 'Round 1',
	'grading'      => 'Round 1 closed',
	'final'        => 'Final',
	'completed'    => 'Completed',
);
$keys = array_keys($stages);
$at = array_search($c['status'], $keys, TRUE);
$next = array(
	'draft'        => array('Open registration', 'Students can register and pay ' . taka($c['entry_fee']) . ' from their wallet, once. The full rules below must be published first: the competition page promises them before registration opens.', array()),
	'registration' => array('Close registration and open Round 1', 'Registration closes. Each registered student can sit Round 1: ' . (int) $c['round1_question_count'] . ' questions, ' . (int) $c['round1_time_limit_minutes'] . ' minutes, their own random set.', $problems['round1']),
	'round1'       => array('Close Round 1 and grade it', 'Anyone still answering is submitted as they stand. Students are ranked by score, then time taken; the top ' . rtrim(rtrim($c['qualify_percent'], '0'), '.') . '% qualify (anyone tied at the cut goes through). Participation certificates are issued and every student is told the result.', array()),
	'grading'      => array('Open the final', 'Qualified students can sit the final: ' . (int) $c['final_question_count'] . ' questions, ' . (int) $c['final_time_limit_minutes'] . ' minutes, one at a time, no going back. Arrange proctoring before you open it.', $problems['final']),
	'final'        => array('Close the final and announce results', 'Anyone still answering is submitted. Finalists are ranked by score, then time. Prize places get their prize recorded and their winner Academic Points; finalist and winner certificates are issued; everyone is told. You pay the prize money outside the app and record each payment below.', array()),
);
$num = function ($v) { return rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); };
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<span class="field__error">' . e($errors[$k]) . '</span>' : ''; };
$dt = function ($v) { return $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; };
?>
<div class="admin-head">
  <div><h1><?= e($c['name']) ?> <?= (int) $c['year'] ?></h1>
    <p class="admin-head__sub"><?= $class ? e($class['name']) . ' only · ' : 'All classes · ' ?>Entry <?= taka($c['entry_fee']) ?> · Prize pool <?= taka($c['prize_pool']) ?></p></div>
  <div class="admin-head__actions"><a class="amcq-btn amcq-btn--ghost" href="<?= site_url('competition') ?>" target="_blank" rel="noopener">Student page <?= icon('arrow-right') ?></a></div>
</div>
<?php if (isset($errors['_'])): ?><p class="amcq-flash amcq-flash--warning" role="alert"><?= e($errors['_']) ?></p><?php endif; ?>

<ol class="stages" aria-label="Competition stages">
  <?php foreach ($stages as $k => $label): $i = array_search($k, $keys, TRUE); ?>
    <li class="<?= $i < $at ? 'is-done' : ($i === $at ? 'is-current' : '') ?>"<?= $i === $at ? ' aria-current="step"' : '' ?>><span><?= $i < $at ? icon('check') : $i + 1 ?></span><?= e($label) ?></li>
  <?php endforeach; ?>
</ol>

<section class="amcq-card admin-card admin-next">
  <?php if (isset($next[$c['status']])): list($title, $what, $blocks) = $next[$c['status']]; ?>
    <h2>Next step: <?= e($title) ?></h2>
    <p><?= e($what) ?></p>
    <?php if ($blocks): ?>
      <p class="inline-note"><strong>Not possible yet:</strong></p>
      <ul class="admin-problems"><?php foreach ($blocks as $b): ?><li><?= e($b) ?></li><?php endforeach; ?></ul>
    <?php else: ?>
      <?= form_open('admin/competition/advance', array('class' => 'admin-actions')) ?>
        <input type="hidden" name="from" value="<?= e($c['status']) ?>">
        <button class="amcq-btn amcq-btn--primary" type="submit" onclick="return confirm(<?= e(json_encode($title . '? This cannot be undone.')) ?>)"><?= e($title) ?> <?= icon('arrow-right') ?></button>
        <span class="inline-note">Stages only move forward.</span>
      <?= form_close() ?>
    <?php endif; ?>
  <?php else: ?>
    <h2>The competition is complete</h2>
    <p>Results are published to students. Record prize payments below as you make them.</p>
  <?php endif; ?>
</section>

<h2 class="admin-h2">Numbers</h2>
<ul class="kpis">
  <li><div class="kpi"><span class="kpi__icon"><?= icon('users') ?></span><span class="kpi__body"><b><?= (int) $counts['registered'] ?></b><span>Registered (<?= (int) $counts['paid'] ?> paid)</span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('file') ?></span><span class="kpi__body"><b><?= (int) $counts['round1_done'] ?></b><span>Sat Round 1 (<?= (int) $counts['round1_started'] ?> started)</span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('trophy') ?></span><span class="kpi__body"><b><?= (int) $counts['qualified'] ?></b><span>Qualified for the final</span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('medal') ?></span><span class="kpi__body"><b><?= (int) $counts['winners'] ?></b><span>Winners</span></span></div></li>
</ul>

<div class="admin-two admin-two--spaced">
  <section class="amcq-card">
    <h2>Settings</h2>
    <?php if ($editable): ?>
      <?= form_open('admin/competition/settings', array('class' => 'admin-form', 'novalidate' => TRUE)) ?>
        <div class="form-grid">
          <?php foreach (array('registration_opens_at' => 'Registration opens', 'registration_closes_at' => 'Registration closes', 'round1_opens_at' => 'Round 1 opens', 'round1_closes_at' => 'Round 1 closes', 'final_at' => 'Final') as $f => $label): ?>
            <label class="field"><span class="field__label"><?= e($label) ?></span>
              <input class="field__input" type="datetime-local" name="<?= $f ?>" value="<?= e(isset($form[$f]) && strpos((string) $form[$f], 'T') !== FALSE ? $form[$f] : $dt($form[$f])) ?>"><?= $err($f) ?></label>
          <?php endforeach; ?>
        </div>
        <p class="inline-note">Dates are announced to students; the stages themselves move only when you press the next step.</p>
        <label class="field"><span class="field__label">Full rules <span class="amcq-muted">(shown on the competition page; HTML allowed)</span></span>
          <textarea class="field__input" name="rules_html" rows="8" placeholder="&lt;h3&gt;Proctoring&lt;/h3&gt;&lt;p&gt;...&lt;/p&gt; &lt;h3&gt;Disputes&lt;/h3&gt; ... &lt;h3&gt;Prize payment&lt;/h3&gt; ..."><?= e($form['rules_html']) ?></textarea>
          <span class="field__hint amcq-muted">Cover how the final is proctored, how to dispute a result, and how and when prizes are paid.</span><?= $err('rules_html') ?></label>
        <div class="admin-actions"><button class="amcq-btn amcq-btn--primary" type="submit"><?= icon('check') ?> Save settings</button></div>
      <?= form_close() ?>
    <?php else: ?>
      <p class="inline-note"><?= icon('lock') ?> Locked once Round 1 has opened.</p>
      <dl class="kv">
        <dt>Round 1 opened</dt><dd><?= $c['round1_opens_at'] ? e(date('j M Y, g:i a', strtotime($c['round1_opens_at']))) : 'not announced' ?></dd>
        <dt>Final</dt><dd><?= $c['final_at'] ? e(date('j M Y, g:i a', strtotime($c['final_at']))) : 'not announced' ?></dd>
        <dt>Rules</dt><dd><?= trim(strip_tags((string) $c['rules_html'])) !== '' ? 'Published' : 'None' ?></dd>
      </dl>
    <?php endif; ?>
  </section>

  <aside class="admin-side">
    <section class="amcq-card">
      <h2>Question pool</h2>
      <p class="inline-note">Published questions<?= $c['class_id'] ? ' for this class' : '' ?>, against what each student's set needs.</p>
      <div class="table-wrap"><table class="table admin-table">
        <thead><tr><th scope="col">Level</th><th scope="col" class="num">Published</th><th scope="col" class="num">Round 1</th><th scope="col" class="num">Final</th></tr></thead>
        <tbody><?php foreach ($pool as $d => $n): ?>
          <tr><th scope="row"><?= e(ucfirst($d)) ?></th><td class="num"><?= (int) $n ?></td>
            <td class="num"><?= (int) $mix1[$d] ?> <?= $n >= $mix1[$d] ? icon('check', 'ok') : '<span class="badge badge--warn">short ' . ($mix1[$d] - $n) . '</span>' ?></td>
            <td class="num"><?= (int) $mixf[$d] ?> <?= $n >= $mixf[$d] ? icon('check', 'ok') : '<span class="badge badge--warn">short ' . ($mixf[$d] - $n) . '</span>' ?></td></tr>
        <?php endforeach; ?></tbody></table></div>
      <p class="inline-note">Maximum score: Round 1 <?= $num($max1) ?>, final <?= $num($maxf) ?> (each correct answer earns its level's weight).</p>
    </section>
    <section class="amcq-card">
      <h2>Prizes</h2>
      <ul class="admin-opts"><?php foreach ($prizes as $p): ?>
        <li><b><?= $p['rank_from'] == $p['rank_to'] ? e(Competition_round_model::ordinal($p['rank_from'])) : e(Competition_round_model::ordinal($p['rank_from']) . '–' . Competition_round_model::ordinal($p['rank_to'])) ?></b>
          <span class="amcq-money"><?= taka($p['prize_amount']) ?><?= $p['rank_from'] != $p['rank_to'] ? ' each' : '' ?></span><span class="tagline amcq-points">+<?= (int) $p['points'] ?> pts</span></li>
      <?php endforeach; ?></ul>
    </section>
  </aside>
</div>

<?php if ($final): ?>
<section class="amcq-card admin-card" id="winners">
  <div class="admin-card__head"><div><h2>Final results</h2><p class="amcq-muted">Out of <?= $num($maxf) ?>. Ties are broken by time taken; equal score and equal time share a place.</p></div></div>
  <div class="table-wrap"><table class="table admin-table">
    <thead><tr><th scope="col">Place</th><th scope="col">Student</th><th scope="col" class="num">Score</th><th scope="col" class="num">Time</th><th scope="col">Prize</th><th scope="col">Payment</th></tr></thead>
    <tbody><?php $places = array_count_values(array_column($final, 'pos')); foreach ($final as $r): ?>
      <tr>
        <td><?= e(Competition_round_model::ordinal($r['pos'])) ?><?= $places[$r['pos']] > 1 ? ' <span class="badge badge--warn">tie</span>' : '' ?></td>
        <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/students/' . (int) $r['user_id']) ?>"><?= e($r['name']) ?></a><small class="amcq-muted"><?= e($r['phone']) ?><?= $r['school'] ? ' · ' . e($r['school']) : '' ?></small></th>
        <td class="num"><?= $num($r['score']) ?></td><td class="num"><?= duration($r['seconds']) ?></td>
        <td><?= $r['prize_amount'] ? '<span class="amcq-money">' . taka($r['prize_amount']) . '</span>' : '—' ?></td>
        <td>
          <?php if ( ! $r['prize_amount']): ?>—
          <?php elseif ($r['prize_paid_at']): ?><span class="badge badge--ok"><?= icon('check') ?> Paid</span><small class="amcq-muted">Ref <?= e($r['prize_payout_ref']) ?> · <?= e(date('j M Y', strtotime($r['prize_paid_at']))) ?></small>
          <?php else: ?>
            <?= form_open('admin/competition/paid/' . (int) $r['id'], array('class' => 'admin-actions')) ?>
              <input class="field__input field__input--sm" type="text" name="reference" maxlength="100" placeholder="bKash TrxID / bank ref" aria-label="Payment reference for <?= e($r['name']) ?>" required>
              <button class="amcq-btn amcq-btn--secondary amcq-btn--sm" type="submit">Mark paid</button>
            <?= form_close() ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
</section>
<?php endif; ?>

<?php if ($round1): ?>
<section class="amcq-card admin-card">
  <div class="admin-card__head"><div><h2>Round 1 results</h2><p class="amcq-muted">Out of <?= $num($max1) ?>. Top <?= count($round1) ?> shown.</p></div></div>
  <div class="table-wrap"><table class="table admin-table">
    <thead><tr><th scope="col" class="num">Rank</th><th scope="col">Student</th><th scope="col" class="num">Score</th><th scope="col" class="num">Time</th><th scope="col">Result</th></tr></thead>
    <tbody><?php foreach ($round1 as $r): ?>
      <tr><td class="num"><?= (int) $r['pos'] ?></td>
        <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/students/' . (int) $r['user_id']) ?>"><?= e($r['name']) ?></a><small class="amcq-muted"><?= e($r['school'] ?: '') ?></small></th>
        <td class="num"><?= $num($r['score']) ?></td><td class="num"><?= duration($r['seconds']) ?></td>
        <td><?= in_array($r['status'], array('qualified', 'finalist', 'winner'), TRUE) ? '<span class="badge badge--ok">Qualified</span>' : '<span class="badge badge--muted">Not qualified</span>' ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
</section>
<?php endif; ?>
