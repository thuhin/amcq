<?php defined('BASEPATH') OR exit('No direct script access allowed');
$score = (int) $attempt['score']; $total = (int) $attempt['total_questions'];
$p = (float) $attempt['percentage'];
if ($p >= 80)     { $head = 'অভিনন্দন!';     $tone = 'great'; }
elseif ($p >= 60) { $head = 'ভালো চেষ্টা!';   $tone = 'good'; }
else              { $head = 'চালিয়ে যাও!';    $tone = 'keep'; }
$chapter_url = 'practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug'];
$again = form_open('quiz/start', array('class' => 'inline-form')) . form_hidden('chapter_id', $chapter['id'])
	. ($attempt['topic_id'] ? form_hidden('topic_id', $attempt['topic_id']) : '');
?>
<section class="result-hero result-hero--<?= $tone ?>">
  <div class="amcq-container result-hero__inner">
    <span class="result-hero__icon"><?= icon($p >= STREAK_PASS_PERCENTAGE ? 'check' : 'refresh') ?></span>
    <div>
      <h1 class="result-hero__title"><?= $head ?></h1>
      <p class="result-hero__msg">
        You scored <?= $score ?>/<?= $total ?>.
        <?= $wrong ? 'Review the ' . $wrong . ' question' . ($wrong > 1 ? 's' : '') . ' you missed.' : 'Every answer correct!' ?>
      </p>
      <p class="result-hero__chapter"><strong>Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?></strong>
        <span class="amcq-muted"><?= e($chapter['class_name']) ?> · <?= e($chapter['subject_name']) ?><?= $topic ? ' · ' . e($topic['code'] . ' ' . $topic['name']) : '' ?></span></p>
    </div>
  </div>
</section>

<section class="amcq-container">
  <ul class="stat-row">
    <li class="amcq-card stat"><span class="chip chip--green"><?= icon('check') ?></span><div><b class="amcq-score"><?= $score ?> / <?= $total ?></b><small>Correct Answers</small></div></li>
    <li class="amcq-card stat"><span class="chip chip--red"><?= icon('x-circle') ?></span><div><b class="amcq-score"><?= $wrong ?></b><small>Incorrect Answers</small></div></li>
    <li class="amcq-card stat"><span class="chip chip--blue"><?= icon('clock') ?></span><div><b><?= duration($attempt['duration_seconds']) ?></b><small>Time Taken</small></div></li>
    <li class="amcq-card stat"><span class="chip chip--purple"><?= icon('target') ?></span><div><b class="amcq-score"><?= pct($p) ?></b><small>Your Score</small></div></li>
    <?php if ($user): ?>
    <li class="amcq-card stat"><span class="chip chip--amber"><?= icon('trophy') ?></span><div><b class="amcq-points">+<?= (int) $attempt['points_earned'] ?></b><small>Points Earned</small></div></li>
    <li class="amcq-card stat"><span class="chip chip--blue"><?= icon('chart') ?></span><div><b>#<?= points($rank) ?></b>
      <?php if ($rank_change): ?><span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?>
      <small>Your Rank</small></div></li>
    <?php endif; ?>
  </ul>
</section>

<?php if ( ! $user): ?>
<section class="amcq-container">
  <div class="save-cta">
    <span class="chip chip--blue chip--lg"><?= icon('shield') ?></span>
    <div><h2>Save this result and start building your national rank.</h2>
      <p class="amcq-muted">Create a free account with your phone number. This result will be saved to it.</p></div>
    <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="<?= site_url('login') ?>">Create Free Account</a>
  </div>
</section>
<?php endif; ?>

<section class="amcq-container result-grid">
  <div class="amcq-card">
    <h2 class="h3">Performance by <?= $breakdown['by'] === 'topic' ? 'Topic' : 'Difficulty' ?></h2>
    <ul class="bars">
      <?php foreach ($breakdown['groups'] as $g): $gp = $g['total'] ? $g['correct'] / $g['total'] : 0; ?>
      <li>
        <span class="bars__label"><?= e($g['label']) ?></span>
        <span class="amcq-progress"><span class="amcq-progress__bar bar--<?= $gp >= .8 ? 'good' : ($gp >= .5 ? 'mid' : 'low') ?>" style="width: <?= round($gp * 100) ?>%"></span></span>
        <b><?= $g['correct'] ?> / <?= $g['total'] ?></b>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="amcq-card">
    <div class="section__head"><h2 class="h3">Your Answer Review</h2><a class="link-arrow" href="<?= site_url('quiz/' . $attempt['id'] . '/review') ?>">View All Questions <?= icon('arrow-right') ?></a></div>
    <table class="table table--compact">
      <thead><tr><th>Q</th><th>Your Answer</th><th>Correct Answer</th><th>Result</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr class="<?= $it['is_correct'] ? '' : 'is-wrong' ?>">
          <td><a href="<?= site_url('quiz/' . $attempt['id'] . '/review?q=' . $it['position']) ?>"><?= (int) $it['position'] ?></a></td>
          <td><?= isset($it['selected_label']) ? e($it['selected_label']) : '<span class="amcq-muted">—</span>' ?></td>
          <td><?= e($it['correct_label']) ?></td>
          <td><?= $it['is_correct']
            ? '<span class="mark mark--ok">' . icon('check-circle') . '<span class="sr-only">Correct</span></span>'
            : '<span class="mark mark--bad">' . icon('x-circle') . '<span class="sr-only">Incorrect</span></span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="amcq-card">
    <h2 class="h3">Next Steps</h2>
    <ul class="next-steps">
      <li><?= $again ?><button type="submit" class="next-step"><span class="chip chip--blue"><?= icon('play') ?></span><span><b>Practice Again</b><small>Another set of questions from this <?= $topic ? 'topic' : 'chapter' ?></small></span><?= icon('arrow-right') ?></button><?= form_close() ?></li>
      <li><a class="next-step" href="<?= site_url('quiz/' . $attempt['id'] . '/review' . ($wrong ? '?filter=incorrect' : '')) ?>"><span class="chip chip--blue"><?= icon('book') ?></span><span><b>Learn Concepts</b><small>Read explanations for each answer</small></span><?= icon('arrow-right') ?></a></li>
      <li><a class="next-step" href="<?= site_url($chapter_url) ?>"><span class="chip chip--blue"><?= icon('grid') ?></span><span><b>Go to Chapter List</b><small>Practice other topics and chapters</small></span><?= icon('arrow-right') ?></a></li>
    </ul>
  </div>

  <?php if ($user): ?>
  <div class="amcq-card">
    <h2 class="h3">Your Streak</h2>
    <div class="streak">
      <span class="chip chip--orange chip--lg"><?= icon('flame') ?></span>
      <div>
        <b class="streak__count"><?= $streak['count'] ?> / <?= STREAK_REQUIRED_QUIZZES ?></b>
        <small class="amcq-muted">qualifying quizzes (<?= STREAK_PASS_PERCENTAGE ?>%+ each)</small>
      </div>
    </div>
    <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= round($streak['count'] * 100 / STREAK_REQUIRED_QUIZZES) ?>%"></div></div>
    <p class="amcq-muted streak__note">
      <?php if ( ! $attempt['counts_for_streak']): ?>
        This quiz scored under <?= STREAK_PASS_PERCENTAGE ?>%, so it doesn't count toward your streak. It doesn't break it either.
      <?php elseif ($streak['count'] === 0): ?>
        Streak complete! A new one starts with your next qualifying quiz.
      <?php else: ?>
        <?= STREAK_REQUIRED_QUIZZES - $streak['count'] ?> more qualifying quizzes<?= $streak['hours_left'] ? ' within ' . $streak['hours_left'] . ' hours' : '' ?> to earn 1 Academic Point.
      <?php endif; ?>
    </p>
  </div>

  <?php if ($progress): ?>
  <div class="amcq-card">
    <h2 class="h3">Chapter Progress</h2>
    <div class="progress-card progress-card--flat">
      <div class="ring" style="--p: <?= round($progress['best_percentage']) ?>"><span><?= round($progress['best_percentage']) ?>%</span></div>
      <div>
        <b>Best score in this chapter</b>
        <span class="amcq-muted"><?= (int) $progress['attempts'] ?> quiz<?= $progress['attempts'] > 1 ? 'zes' : '' ?> · average <?= pct($progress['avg_percentage']) ?></span>
        <?php if ((int) $progress['mastery_points_awarded'] === 0): ?>
          <small class="amcq-muted"><?= max(0, MASTERY_RUN - (int) $progress['consecutive_mastery']) ?> more <?= MASTERY_PERCENTAGE ?>%+ quizzes in a row to master this chapter (+<?= MASTERY_POINTS ?> points).</small>
        <?php else: ?>
          <small class="ok"><?= icon('check') ?> Chapter mastered</small>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</section>

<section class="amcq-container">
  <div class="quote-banner">
    <p>“অনুশীলনই দক্ষতার চাবিকাঠি।”<small>নিয়মিত অনুশীলন করলে তুমি আরও ভালো করতে পারবে।</small></p>
    <div class="quote-banner__actions">
      <a class="amcq-btn amcq-btn--secondary" href="<?= site_url($chapter_url) ?>"><?= icon('arrow-left') ?> Back to Chapter</a>
      <?php if ($next_chapter): ?>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $next_chapter['slug']) ?>">Continue to Next Chapter <?= icon('arrow-right') ?></a>
      <?php else: ?>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('quiz/' . $attempt['id'] . '/review') ?>">Review Answers <?= icon('arrow-right') ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
