<?php defined('BASEPATH') OR exit('No direct script access allowed');
$tabs = array('overview' => 'Overview', 'subjects' => 'Subjects', 'history' => 'Quiz History', 'weak' => 'Weak Areas'); ?>
<h1 class="page-title">My Progress</h1>
<nav class="tabs2" aria-label="Progress sections">
  <?php foreach ($tabs as $k => $label): ?>
    <a class="<?= $tab === $k ? 'is-active' : '' ?>" href="<?= site_url('progress' . ($k === 'overview' ? '' : '?tab=' . $k)) ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'):
  $best = $subjects ? array_reduce($subjects, function ($a, $s) { return ! $a || $s['avg_p'] > $a['avg_p'] ? $s : $a; }) : NULL;
  $worst = $subjects ? array_reduce($subjects, function ($a, $s) { return ! $a || $s['avg_p'] < $a['avg_p'] ? $s : $a; }) : NULL; ?>
  <ul class="stat-cards stat-cards--3">
    <li class="stat-card"><span class="sq sq--blue"><?= icon('file') ?></span><div><b><?= (int) $accuracy['quizzes'] ?></b><small>Quizzes Completed</small></div></li>
    <li class="stat-card"><span class="sq sq--green"><?= icon('target') ?></span><div><b><?= pct($accuracy['accuracy']) ?></b><small>Average Score</small></div></li>
    <li class="stat-card"><span class="sq sq--orange"><?= icon('flame') ?></span><div><b><?= $streak['count'] ?> / <?= STREAK_REQUIRED_QUIZZES ?></b><small>Current streak (<?= STREAK_PASS_PERCENTAGE ?>%+ quizzes)</small></div></li>
    <li class="stat-card"><span class="sq sq--amber"><?= icon('star') ?></span><div><b><?= (int) $streak['completed'] ?></b><small>Streak Completions</small></div></li>
    <li class="stat-card"><span class="sq sq--green"><?= icon('check') ?></span><div><b><?= $best ? e($best['name']) : '—' ?></b><small>Best Subject<?= $best ? ' · ' . pct($best['avg_p']) : '' ?></small></div></li>
    <li class="stat-card"><span class="sq sq--red"><?= icon('target') ?></span><div><b><?= (int) $qualifying ?></b><small>Qualifying quizzes (all time)</small></div></li>
  </ul>
  <?php if ($worst && $worst !== $best): ?><p class="note"><?= icon('bulb') ?> <?= e($worst['name']) ?> is your lowest subject at <?= pct($worst['avg_p']) ?>.</p><?php endif; ?>

<?php elseif ($tab === 'subjects'): ?>
  <?php if ($subjects): ?>
  <ul class="subj-tiles subj-tiles--wide">
    <?php foreach ($subjects as $s): list($ic, $col) = subject_style($s['slug']); $sp = (int) round($s['avg_p']); ?>
    <li class="subj-tile card">
      <div class="subj-tile__top"><span class="pick-row__icon pick-row__icon--<?= $col ?>"><?= icon($ic) ?></span><span><small><?= e($s['name']) ?></small><b><?= $sp ?>%</b></span></div>
      <span class="perf__bar"><span class="perf__fill subj-fill--<?= $col ?>" style="width: <?= $sp ?>%"></span></span>
      <small class="amcq-muted"><?= (int) $s['quizzes'] ?> quizzes · <?= (int) $s['chapters'] ?> chapters practised</small>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><div class="empty-card"><p>Take a quiz to see your subjects here.</p></div><?php endif; ?>

<?php elseif ($tab === 'history'): ?>
  <?php if ($history): ?>
  <div class="card table-wrap">
    <table class="mini-table">
      <thead><tr><th>Date</th><th>Chapter</th><th class="num">Score</th><th>Streak</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr>
          <td><?= date('j M, g:i a', strtotime($h['completed_at'])) ?></td>
          <td><b><?= e($h['chapter_name_bn'] ?: $h['chapter_name']) ?></b><?= $h['topic_name'] ? '<br><small class="amcq-muted">' . e($h['topic_name']) . '</small>' : '' ?></td>
          <td class="num"><b><?= (int) $h['score'] ?>/<?= (int) $h['total_questions'] ?></b> · <?= pct($h['percentage']) ?></td>
          <td><?= $h['counts_for_streak'] ? '<span class="pill-ok">' . icon('check') . ' Counts</span>' : '<span class="amcq-muted small">Below ' . STREAK_PASS_PERCENTAGE . '%</span>' ?></td>
          <td><a class="link-arrow" href="<?= site_url('quiz/' . $h['id'] . '/review') ?>">Review <?= icon('arrow-right') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?><div class="empty-card"><p>No quizzes yet.</p></div><?php endif; ?>

<?php else: ?>
  <?php if ($weak): ?>
  <div class="card"><ol class="weak2">
    <?php foreach ($weak as $i => $w): $wp = (int) round($w['avg_percentage']); ?>
    <li><span class="weak2__no"><?= $i + 1 ?></span><span class="weak2__name"><b><?= e(chapter_label($w)) ?></b><small><?= e($w['subject_name']) ?> · <?= (int) $w['attempts'] ?> attempts</small></span>
      <span class="perf__bar weak2__bar"><span class="perf__fill perf__fill--low" style="width: <?= $wp ?>%"></span></span><b class="weak2__pct"><?= $wp ?>%</b>
      <a class="amcq-btn amcq-btn--secondary amcq-btn--sm" href="<?= site_url('practice/' . $w['class_slug'] . '/' . $w['subject_slug'] . '?chapter=' . $w['chapter_slug']) ?>">Practice <?= icon('arrow-right') ?></a></li>
    <?php endforeach; ?>
  </ol></div>
  <?php else: ?><div class="empty-card"><p>No weak areas. Every chapter you've practised averages 70% or more.</p></div><?php endif; ?>
<?php endif; ?>
