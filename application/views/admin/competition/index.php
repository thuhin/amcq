<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $rows */
$stage = array(
	'draft' => 'Draft: registration closed', 'registration' => 'Registration open', 'round1' => 'Round 1',
	'grading' => 'Grading', 'final' => 'Final', 'completed' => 'Completed',
);
$when = function ($ts) { return $ts ? e(date('j M Y, g:i a', strtotime($ts))) : '<span class="amcq-muted">not set</span>'; };
?>
<div class="admin-head"><h1>Competition</h1></div>
<p class="admin-note amcq-flash amcq-flash--info" role="note">
  <?= icon('lock') ?> Read only. The app has no Round 1 or Final yet, so opening registration would let students pay Tk <?= number_format(COMPETITION_FEE_TAKA) ?> for an event that cannot run. Status controls come with the rounds.
</p>

<?php if ($rows): ?>
  <?php foreach ($rows as $c): ?>
  <section class="amcq-card admin-card">
    <div class="admin-card__head">
      <div><h2><?= e($c['name']) ?> <small class="amcq-muted"><?= (int) $c['year'] ?></small></h2>
        <p><span class="badge badge--<?= $c['status'] === 'draft' ? 'muted' : 'primary' ?>"><?= e($stage[$c['status']]) ?></span>
           <?= $c['class_name'] ? ' · ' . e($c['class_name']) . ' only' : ' · all classes' ?></p></div>
      <p class="admin-card__sum"><b><?= (int) $c['registrations'] ?></b> registered · <?= (int) $c['paid'] ?> paid</p>
    </div>
    <dl class="kv">
      <dt>Entry fee</dt><dd><?= taka($c['entry_fee']) ?></dd>
      <dt>Prize pool</dt><dd><?= taka($c['prize_pool']) ?></dd>
      <dt>Qualify</dt><dd>top <?= pct($c['qualify_percent']) ?> go to the final</dd>
      <dt>Registration</dt><dd><?= $when($c['registration_opens_at']) ?> to <?= $when($c['registration_closes_at']) ?></dd>
      <dt>Round 1</dt><dd><?= $when($c['round1_opens_at']) ?> to <?= $when($c['round1_closes_at']) ?> · <?= (int) $c['round1_question_count'] ?> questions, <?= (int) $c['round1_time_limit_minutes'] ?> min</dd>
      <dt>Final</dt><dd><?= $when($c['final_at']) ?> · <?= (int) $c['final_question_count'] ?> questions, <?= (int) $c['final_time_limit_minutes'] ?> min</dd>
    </dl>
  </section>
  <?php endforeach; ?>
<?php else: ?>
  <div class="empty-card"><p>No competition has been set up.</p></div>
<?php endif; ?>
