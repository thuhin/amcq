<?php defined('BASEPATH') OR exit('No direct script access allowed');
$acc = $accuracy['accuracy'];
$acc_label = $acc === NULL ? 'Take a quiz to start' : ($acc >= 80 ? 'Excellent!' : ($acc >= 60 ? 'Good' : 'Keep practising'));
$act_icons = array('quiz' => array('check', 'green'), 'points' => array('trophy', 'amber'), 'rank' => array('bar-up', 'purple'),
	'streak' => array('flame', 'orange'), 'tier' => array('medal', 'amber'), 'correction' => array('pencil', 'blue'),
	'competition' => array('trophy', 'pink'), 'wallet' => array('wallet', 'blue'));
$badge_style = array('seven_day_streak' => array('flame', 'orange'), 'chapter_master' => array('book-open', 'blue'),
	'subject_50_mcqs' => array('list', 'green'), 'competition_participant' => array('trophy', 'amber'));
?>
<section class="greet-card">
  <div class="greet-card__msg">
    <span class="tile tile--blue tile--sm"><?= icon('users') ?></span>
    <div><h1>আসসালামু আলাইকুম!</h1><p>Keep practicing. Every question takes you closer to your goal.</p></div>
  </div>
  <img class="greet-card__art" src="<?= asset('img/design/study-mosque.jpg') ?>" width="333" height="144" alt="">
  <blockquote class="hadith hadith--sm">“জ্ঞান অর্জন প্রতিটি মুসলিমের জন্য ফরজ।”<cite>— সহিহ ইবনু মাজাহ</cite></blockquote>
</section>

<ul class="dash-stats">
  <li class="stat-card"><span class="sq sq--purple sq--lg"><?= icon('book-open') ?></span><div><small>Practice Streak</small><b><?= (int) $day_streak ?> day<?= $day_streak == 1 ? '' : 's' ?></b><em class="amcq-muted"><?= $day_streak ? 'Keep it going!' : 'Practise today to start' ?></em></div></li>
  <li class="stat-card"><span class="sq sq--amber sq--lg"><?= icon('star') ?></span><div><small>Academic Points</small><b><?= points($user['total_points']) ?></b><em class="tier-gold"><?= e($user['tier_title']) ?></em></div></li>
  <li class="stat-card"><span class="sq sq--amber sq--lg"><?= icon('trophy') ?></span><div><small>Your Rank</small><b>#<?= points($rank) ?><?php if ($rank_change): ?> <span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?></b><em class="amcq-muted">Bangladesh<?= $rank_change !== NULL ? ' · from last week' : '' ?></em></div></li>
  <li class="stat-card"><span class="sq sq--green sq--lg"><?= icon('target') ?></span><div><small>Accuracy Rate</small><b><?= pct($acc) ?></b><em class="<?= $acc !== NULL && $acc >= 60 ? 'ok' : 'amcq-muted' ?>"><?= $acc_label ?></em></div></li>
</ul>

<div class="dash-grid">
  <div class="dash-col">
    <div class="card">
      <div class="card__head"><h2><?= icon('book-open') ?> Continue Practicing</h2><a class="link-arrow" href="<?= site_url('practice') ?>">View All Chapters <?= icon('arrow-right') ?></a></div>
      <?php if ($continue): ?>
        <div class="continue2">
          <span class="tile tile--green"><?= icon('list') ?></span>
          <div class="continue2__body">
            <b><?= e($continue['class_name']) ?> - <?= e($continue['subject_name']) ?></b>
            <span>Chapter <?= (int) $continue['chapter_no'] ?>: <?= e(chapter_label($continue)) ?></span>
            <div class="mini-card__row"><div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $coverage['pct'] ?>%"></div></div><b><?= $coverage['pct'] ?>%</b></div>
            <small class="amcq-muted"><?= $coverage['done'] ?> / <?= $coverage['total'] ?> questions completed</small>
          </div>
        </div>
        <?= form_open('quiz/start') ?><?= form_hidden('chapter_id', $continue['chapter_id']) ?>
          <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Continue Practice <?= icon('arrow-right') ?></button><?= form_close() ?>
      <?php else: ?>
        <div class="empty-card"><p>You haven't taken a quiz yet. Pick a chapter to begin.</p><a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice') ?>">Start Practice <?= icon('arrow-right') ?></a></div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card__head"><h2>Subject Performance</h2><a class="link-arrow" href="<?= site_url('progress?tab=subjects') ?>">View Detailed Report <?= icon('arrow-right') ?></a></div>
      <?php if ($subjects): ?>
      <ul class="subj-tiles">
        <?php foreach ($subjects as $s): list($ic, $col) = subject_style($s['slug']); $sp = (int) round($s['avg_p']); ?>
        <li class="subj-tile">
          <div class="subj-tile__top"><span class="pick-row__icon pick-row__icon--<?= $col ?>"><?= icon($ic) ?></span><span><small><?= e($s['name']) ?></small><b><?= $sp ?>%</b></span></div>
          <span class="perf__bar"><span class="perf__fill subj-fill--<?= $col ?>" style="width: <?= $sp ?>%"></span></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Subject averages appear after your first quiz.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card__head"><div class="card__title-sub"><h2><span class="sq sq--red sq--sm"><?= icon('target') ?></span> Weak Chapters</h2><small>Focus on these chapters to improve</small></div>
        <a class="link-arrow" href="<?= site_url('progress?tab=weak') ?>">View All <?= icon('arrow-right') ?></a></div>
      <?php if ($weak): ?>
      <ol class="weak2">
        <?php foreach ($weak as $i => $w): $wp = (int) round($w['avg_percentage']); ?>
        <li>
          <span class="weak2__no"><?= $i + 1 ?></span>
          <span class="weak2__name"><b><?= e(chapter_label($w)) ?></b><small><?= e($w['subject_name']) ?></small></span>
          <span class="perf__bar weak2__bar"><span class="perf__fill perf__fill--low" style="width: <?= $wp ?>%"></span></span>
          <b class="weak2__pct"><?= $wp ?>%</b>
          <a class="amcq-btn amcq-btn--secondary amcq-btn--sm" href="<?= site_url('practice/' . $w['class_slug'] . '/' . $w['subject_slug'] . '?chapter=' . $w['chapter_slug']) ?>">Practice <?= icon('arrow-right') ?></a>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php else: ?><p class="amcq-muted">No weak chapters right now. Every chapter you've practised averages 70% or more.</p><?php endif; ?>
    </div>

    <div class="card">
      <div class="card__head"><div class="card__title-sub"><h2><?= icon('school') ?> Your School Performance</h2><small><?= $school ? e($school['name']) : 'No school added' ?></small></div>
        <a class="link-arrow" href="<?= site_url('profile') ?>"><?= $school ? 'Change School' : 'Add School' ?></a></div>
      <?php if ($school): ?>
      <ul class="school3">
        <li><small>Your Position</small><b>#<?= (int) $school['position'] ?></b><span>Among <?= number_format($school['students']) ?> students</span></li>
        <li><small>School Average</small><b><?= pct($school['average']) ?></b></li>
        <li><small>Top Score</small><b><?= pct($school['top']) ?></b></li>
      </ul>
      <?php else: ?><p class="amcq-muted">Add your school in your profile to compare with your schoolmates.</p><?php endif; ?>
    </div>
  </div>

  <div class="dash-col">
    <div class="card">
      <div class="card__head"><h2><span class="sq sq--pink sq--sm"><?= icon('bar-up') ?></span> <?= $activity_today ? "Today's Activity" : 'Recent Activity' ?></h2><a class="link-arrow" href="<?= site_url('progress?tab=history') ?>">View All <?= icon('arrow-right') ?></a></div>
      <?php if ($activity): ?>
      <ul class="timeline">
        <?php foreach ($activity as $a): list($ic, $col) = isset($act_icons[$a['type']]) ? $act_icons[$a['type']] : array('bell', 'blue'); ?>
        <li><span class="timeline__dot round round--<?= $col ?>"><?= icon($ic) ?></span>
          <span class="timeline__text"><b><?= e($a['title']) ?></b><?php if ($a['detail']): ?><small><?= e($a['detail']) ?></small><?php endif; ?></span>
          <time datetime="<?= e($a['created_at']) ?>"><?= $activity_today ? date('g:i A', strtotime($a['created_at'])) : time_ago($a['created_at']) ?></time></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Your quizzes, points and milestones will show up here.</p><?php endif; ?>
    </div>

    <?php if ($competition): ?>
    <div class="card">
      <div class="card__head"><h2><span class="gold"><?= icon('trophy') ?></span> Upcoming Competitions</h2><a class="link-arrow" href="<?= site_url('competition') ?>">View All <?= icon('arrow-right') ?></a></div>
      <div class="comp-strip"><b><?= e($competition['name']) ?></b><span class="badge-pink">Tk <?= number_format($competition['entry_fee']) ?> Entry</span></div>
      <ul class="comp-facts2">
        <li><span class="sq sq--blue sq--sm"><?= icon('calendar') ?></span><span><b>Final Round</b><small><?= $competition['final_at'] ? date('j F Y', strtotime($competition['final_at'])) : 'Date to be announced' ?></small></span></li>
        <li><span class="sq sq--blue sq--sm"><?= icon('users') ?></span><span><b><?= (int) $winners ?> Winners</b><small>Tk <?= bd_number($competition['prize_pool']) ?> Prize Pool</small></span></li>
      </ul>
      <div class="btn-pair">
        <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('competition') ?>">View Details</a>
        <?php if ($registration): ?>
          <span class="amcq-btn amcq-btn--muted" aria-disabled="true"><?= icon('check') ?> Registered</span>
        <?php else: ?>
          <a class="amcq-btn amcq-btn--primary" href="<?= site_url('competition#register') ?>">Register Now <?= icon('arrow-right') ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card__head"><h2><span class="sq sq--red sq--sm"><?= icon('medal') ?></span> Achievements</h2><a class="link-arrow" href="<?= site_url('rank') ?>">View All <?= icon('arrow-right') ?></a></div>
      <ul class="achievements">
        <?php foreach ($badges as $b): list($ic, $col) = $badge_style[$b['code']]; $got = (bool) $b['awarded_at'];
          $label = $b['code'] === 'subject_50_mcqs' ? '50 MCQs' : $b['name']; ?>
        <li class="<?= $got ? '' : 'is-locked' ?>" title="<?= e($b['description']) ?><?= $got ? '' : ' (not earned yet)' ?>">
          <span class="round round--<?= $got ? $col : 'gray' ?>"><?= icon($ic) ?></span><small><?= e($label) ?></small><?php if ( ! $got): ?><span class="sr-only">Not earned yet</span><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
