<?php defined('BASEPATH') OR exit('No direct script access allowed');
$tabs = array('overview' => 'Overview', 'subjects' => 'Subjects', 'history' => 'Quiz History', 'weak' => 'Weak Areas'); ?>
<section class="section section--tight">
  <div class="amcq-container">
    <h1>My Progress</h1>
    <nav class="tabs" aria-label="Progress sections">
      <?php foreach ($tabs as $k => $label): ?>
        <a class="tab<?= $tab === $k ? ' is-active' : '' ?>" href="<?= site_url('progress' . ($k === 'overview' ? '' : '?tab=' . $k)) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($tab === 'overview'):
      $best = $subjects ? array_reduce($subjects, function ($a, $s) { return ! $a || $s['avg_p'] > $a['avg_p'] ? $s : $a; }) : NULL;
      $worst = $subjects ? array_reduce($subjects, function ($a, $s) { return ! $a || $s['avg_p'] < $a['avg_p'] ? $s : $a; }) : NULL; ?>
      <ul class="stat-row stat-row--3">
        <li class="amcq-card stat"><span class="chip chip--blue"><?= icon('file') ?></span><div><small>Quizzes Completed</small><b><?= (int) $accuracy['quizzes'] ?></b></div></li>
        <li class="amcq-card stat"><span class="chip chip--green"><?= icon('target') ?></span><div><small>Average Score</small><b class="amcq-score"><?= pct($accuracy['accuracy']) ?></b></div></li>
        <li class="amcq-card stat"><span class="chip chip--orange"><?= icon('flame') ?></span><div><small>Qualifying Quizzes</small><b><?= (int) $qualifying ?></b><em class="amcq-muted"><?= STREAK_PASS_PERCENTAGE ?>%+ each</em></div></li>
        <li class="amcq-card stat"><span class="chip chip--amber"><?= icon('star') ?></span><div><small>Streak Completions</small><b><?= (int) $streak['completed'] ?></b><em class="amcq-muted">current <?= $streak['count'] ?> / <?= STREAK_REQUIRED_QUIZZES ?></em></div></li>
        <li class="amcq-card stat"><span class="chip chip--green"><?= icon('check') ?></span><div><small>Best Subject</small><b><?= $best ? e($best['name']) : '—' ?></b><em class="amcq-muted"><?= $best ? pct($best['avg_p']) : '' ?></em></div></li>
        <li class="amcq-card stat"><span class="chip chip--red"><?= icon('target') ?></span><div><small>Needs Work</small><b><?= $worst && $worst !== $best ? e($worst['name']) : '—' ?></b><em class="amcq-muted"><?= $worst && $worst !== $best ? pct($worst['avg_p']) : '' ?></em></div></li>
      </ul>

    <?php elseif ($tab === 'subjects'): ?>
      <?php if ($subjects): ?>
      <ul class="grid grid--3">
        <?php foreach ($subjects as $s): $sp = round($s['avg_p']); ?>
        <li class="amcq-card">
          <h2 class="h3"><?= e($s['name']) ?></h2>
          <div class="mini-stat__row"><div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $sp ?>%"></div></div><b><?= $sp ?>%</b></div>
          <p class="amcq-muted"><?= (int) $s['quizzes'] ?> quizzes · <?= (int) $s['chapters'] ?> chapters practised</p>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="amcq-muted">Take a quiz to see your subjects here.</p><?php endif; ?>

    <?php elseif ($tab === 'history'): ?>
      <?php if ($history): ?>
      <div class="amcq-card table-wrap">
        <table class="table">
          <thead><tr><th>Date</th><th>Chapter</th><th class="num">Score</th><th>Result</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= date('j M, g:i a', strtotime($h['completed_at'])) ?></td>
              <td><b><?= e($h['chapter_name_bn'] ?: $h['chapter_name']) ?></b><?= $h['topic_name'] ? '<br><small class="amcq-muted">' . e($h['topic_name']) . '</small>' : '' ?><br><small class="amcq-muted"><?= e($h['subject_name']) ?></small></td>
              <td class="num amcq-score"><?= (int) $h['score'] ?>/<?= (int) $h['total_questions'] ?> · <?= pct($h['percentage']) ?></td>
              <td><?= $h['counts_for_streak'] ? '<span class="badge badge--ok">' . icon('check') . ' Counts for streak</span>' : '<span class="badge badge--muted">Below ' . STREAK_PASS_PERCENTAGE . '%</span>' ?></td>
              <td><a class="link-arrow" href="<?= site_url('quiz/' . $h['id'] . '/review') ?>">Review <?= icon('arrow-right') ?></a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?><p class="amcq-muted">No quizzes yet.</p><?php endif; ?>

    <?php else: ?>
      <?php if ($weak): ?>
      <ol class="amcq-card weak">
        <?php foreach ($weak as $i => $w): $wp = round($w['avg_percentage']); ?>
        <li><span class="weak__no"><?= $i + 1 ?></span><span class="weak__name"><b><?= e($w['name_bn'] ?: $w['name']) ?></b><small class="amcq-muted"><?= e($w['subject_name']) ?> · <?= (int) $w['attempts'] ?> attempts</small></span>
          <span class="amcq-progress weak__bar"><span class="amcq-progress__bar bar--low" style="width: <?= $wp ?>%"></span></span><b class="weak__pct"><?= $wp ?>%</b>
          <a class="amcq-btn amcq-btn--secondary amcq-btn--sm" href="<?= site_url('practice/' . $w['class_slug'] . '/' . $w['subject_slug'] . '?chapter=' . $w['chapter_slug']) ?>">Practice <?= icon('arrow-right') ?></a></li>
        <?php endforeach; ?>
      </ol>
      <?php else: ?><p class="amcq-muted">No weak areas. Every chapter you've practised averages 70% or more.</p><?php endif; ?>
    <?php endif; ?>
  </div>
</section>
