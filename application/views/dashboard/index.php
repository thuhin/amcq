<?php defined('BASEPATH') OR exit('No direct script access allowed');
$first = explode(' ', trim($user['name']))[0];
$colors = array('green', 'blue', 'orange', 'purple', 'pink', 'amber');
$reg_status = $registration ? $registration['status'] : NULL;
?>
<section class="page-hero">
  <div class="amcq-container">
    <h1 class="greet"><?= greeting() ?>, <?= e($first) ?>!</h1>
    <p class="amcq-muted">Your learning, at a glance. Every question takes you closer to your goal.</p>
  </div>
</section>

<section class="amcq-container">
  <ul class="stat-row stat-row--4">
    <li class="amcq-card stat"><span class="chip chip--orange chip--lg"><?= icon('flame') ?></span><div><small>Active Streak</small><b><?= $streak['count'] ?> / <?= STREAK_REQUIRED_QUIZZES ?></b>
      <em class="amcq-muted"><?= $streak['count'] ? ($streak['hours_left'] ? $streak['hours_left'] . ' h left' : '') : 'Score ' . STREAK_PASS_PERCENTAGE . '%+ to start' ?></em></div></li>
    <li class="amcq-card stat"><span class="chip chip--amber chip--lg"><?= icon('star') ?></span><div><small>Academic Points</small><b class="amcq-points"><?= points($user['total_points']) ?></b><em class="tier-text"><?= e($user['tier_title']) ?></em></div></li>
    <li class="amcq-card stat"><span class="chip chip--amber chip--lg"><?= icon('trophy') ?></span><div><small>National Rank</small><b>#<?= points($rank) ?>
      <?php if ($rank_change): ?><span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?></b>
      <em class="amcq-muted"><?= $rank_change !== NULL ? 'from last week' : 'Bangladesh' ?></em></div></li>
    <li class="amcq-card stat"><span class="chip chip--green chip--lg"><?= icon('target') ?></span><div><small>Accuracy Rate</small><b class="amcq-score"><?= pct($accuracy['accuracy']) ?></b>
      <em class="amcq-muted"><?= (int) $accuracy['quizzes'] ?> quiz<?= $accuracy['quizzes'] == 1 ? '' : 'zes' ?> taken</em></div></li>
  </ul>
</section>

<section class="amcq-container dash">
  <div class="dash__main">
    <div class="amcq-card">
      <div class="section__head"><h2 class="h3"><?= icon('book') ?> Continue Practicing</h2><a class="link-arrow" href="<?= site_url('practice') ?>">View All Chapters <?= icon('arrow-right') ?></a></div>
      <?php if ($continue): $cp = round($continue['best_percentage']); ?>
        <div class="continue">
          <span class="chip chip--green chip--xl"><?= icon('file') ?></span>
          <div class="continue__body">
            <b><?= e($continue['class_name']) ?> - <?= e($continue['subject_name']) ?></b>
            <span>Chapter: <?= e(chapter_label($continue)) ?></span>
            <div class="mini-stat__row"><div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $cp ?>%"></div></div><b><?= $cp ?>%</b></div>
            <small class="amcq-muted">Best score · last <?= pct($continue['last_percentage']) ?> · <?= (int) $continue['attempts'] ?> attempts</small>
          </div>
        </div>
        <?= form_open('quiz/start') ?><?= form_hidden('chapter_id', $continue['chapter_id']) ?>
          <button class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg" type="submit">Continue Practice <?= icon('arrow-right') ?></button>
        <?= form_close() ?>
      <?php else: ?>
        <div class="empty empty--inline">
          <p>You haven't taken a quiz yet. Pick a chapter to begin.</p>
          <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice') ?>">Start Practice <?= icon('arrow-right') ?></a>
        </div>
      <?php endif; ?>
    </div>

    <div class="amcq-card">
      <div class="section__head"><h2 class="h3">Subject Performance</h2><a class="link-arrow" href="<?= site_url('progress?tab=subjects') ?>">View Detailed Report <?= icon('arrow-right') ?></a></div>
      <?php if ($subjects): ?>
      <ul class="grid grid--4 subj-perf">
        <?php foreach ($subjects as $i => $s): $sp = round($s['avg_p']); ?>
        <li class="subj-perf__item">
          <span class="chip chip--<?= $colors[$i % 6] ?>"><?= icon('book') ?></span>
          <span><small><?= e($s['name']) ?></small><b class="amcq-score"><?= $sp ?>%</b></span>
          <div class="amcq-progress"><div class="amcq-progress__bar bar--<?= $colors[$i % 6] ?>" style="width: <?= $sp ?>%"></div></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Subject averages appear after your first quiz.</p><?php endif; ?>
    </div>

    <div class="amcq-card">
      <div class="section__head"><div><h2 class="h3"><?= icon('target') ?> Weak Chapters</h2><small class="amcq-muted">Focus on these chapters to improve</small></div>
        <a class="link-arrow" href="<?= site_url('progress?tab=weak') ?>">View All <?= icon('arrow-right') ?></a></div>
      <?php if ($weak): ?>
      <ol class="weak">
        <?php foreach ($weak as $i => $w): $wp = round($w['avg_percentage']); ?>
        <li>
          <span class="weak__no"><?= $i + 1 ?></span>
          <span class="weak__name"><b><?= e($w['name_bn'] ?: $w['name']) ?></b><small class="amcq-muted"><?= e($w['subject_name']) ?></small></span>
          <span class="amcq-progress weak__bar"><span class="amcq-progress__bar bar--low" style="width: <?= $wp ?>%"></span></span>
          <b class="weak__pct"><?= $wp ?>%</b>
          <a class="amcq-btn amcq-btn--secondary amcq-btn--sm" href="<?= site_url('practice/' . $w['class_slug'] . '/' . $w['subject_slug'] . '?chapter=' . $w['chapter_slug']) ?>">Practice <?= icon('arrow-right') ?></a>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php else: ?><p class="amcq-muted">No weak chapters right now. Every chapter you've practised averages 70% or more.</p><?php endif; ?>
    </div>

    <?php if ($school): ?>
    <div class="amcq-card">
      <div class="section__head"><div><h2 class="h3"><?= icon('school') ?> Your School Performance</h2><small class="amcq-muted"><?= e($school['name']) ?></small></div>
        <a class="link-arrow" href="<?= site_url('profile') ?>">Change School</a></div>
      <ul class="grid grid--3 school">
        <li><small>Your Position</small><b>#<?= (int) $school['position'] ?></b><span class="amcq-muted">Among <?= points($school['students']) ?> students</span></li>
        <li><small>School Average</small><b><?= pct($school['average']) ?></b><span class="amcq-muted">per quiz</span></li>
        <li><small>Top Score</small><b><?= pct($school['top']) ?></b><span class="amcq-muted">single quiz</span></li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <aside class="dash__side">
    <div class="amcq-card">
      <div class="section__head"><h2 class="h3"><?= icon('chart') ?> Recent Activity</h2></div>
      <?php if ($activity): ?>
      <ul class="activity">
        <?php foreach ($activity as $a):
          $ic = array('quiz' => array('check-circle', 'green'), 'points' => array('star', 'amber'), 'rank' => array('chart', 'purple'),
                      'streak' => array('flame', 'orange'), 'tier' => array('medal', 'amber'), 'correction' => array('pencil', 'blue'),
                      'competition' => array('trophy', 'pink'), 'wallet' => array('wallet', 'blue'));
          list($iname, $icol) = isset($ic[$a['type']]) ? $ic[$a['type']] : array('bell', 'blue'); ?>
        <li><span class="chip chip--<?= $icol ?>"><?= icon($iname) ?></span>
          <span class="activity__text"><b><?= e($a['title']) ?></b><?php if ($a['detail']): ?><small class="amcq-muted"><?= e($a['detail']) ?></small><?php endif; ?></span>
          <time class="amcq-muted" datetime="<?= e($a['created_at']) ?>"><?= time_ago($a['created_at']) ?></time></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Your quizzes, points and milestones will show up here.</p><?php endif; ?>
    </div>

    <?php if ($competition): ?>
    <div class="amcq-card">
      <div class="section__head"><h2 class="h3"><?= icon('trophy') ?> Competition</h2></div>
      <div class="comp-card">
        <b><?= e($competition['name']) ?></b><span class="badge badge--pink">Tk <?= number_format($competition['entry_fee']) ?> Entry</span>
      </div>
      <ul class="comp-facts">
        <li><?= icon('calendar') ?><span><small>Final Round</small><b><?= $competition['final_at'] ? date('j F Y', strtotime($competition['final_at'])) : 'Date to be announced' ?></b></span></li>
        <li><?= icon('users') ?><span><small><?= (int) $winners ?> Winners</small><b><?= str_replace('.00', '', taka($competition['prize_pool'])) ?> Prize Pool</b></span></li>
      </ul>
      <p class="comp-status">Status: <b><?= $reg_status ? e(ucwords(str_replace('_', ' ', $reg_status))) : 'Not registered' ?></b></p>
      <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= site_url('competition') ?>">View Details</a>
    </div>
    <?php endif; ?>

    <div class="amcq-card">
      <div class="section__head"><h2 class="h3"><?= icon('medal') ?> Achievements</h2><a class="link-arrow" href="<?= site_url('rank') ?>">View All <?= icon('arrow-right') ?></a></div>
      <?php if ($badges): ?>
      <ul class="badges">
        <?php foreach (array_slice($badges, 0, 4) as $b): ?>
          <li><span class="chip chip--amber chip--lg"><?= icon('medal') ?></span><small><?= e($b['name']) ?></small></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Complete a streak or master a chapter to earn your first badge.</p><?php endif; ?>
    </div>
  </aside>
</section>
