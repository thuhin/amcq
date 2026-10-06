<?php defined('BASEPATH') OR exit('No direct script access allowed');
$score = (int) $attempt['score']; $total = (int) $attempt['total_questions']; $p = (float) $attempt['percentage'];
$chapter_url = site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug']);
$review_url = site_url('quiz/' . $attempt['id'] . '/review');
$form = function ($mode, $inner, $class) use ($chapter, $attempt) {
	return form_open('quiz/start', array('class' => 'inline-form')) . form_hidden('chapter_id', $chapter['id'])
		. ($mode === 'hard' ? form_hidden('mode', 'hard') : ($attempt['topic_id'] ? form_hidden('topic_id', $attempt['topic_id']) : ''))
		. '<button type="submit" class="' . $class . '">' . $inner . '</button>' . form_close();
};
$earned = (int) $attempt['points_earned'];
$mastered = $user && $progress && (int) $progress['mastery_points_awarded'] > 0 && $earned >= MASTERY_POINTS;
?>
<section class="page-band result-band">
  <div class="amcq-container result-band__inner">
    <div class="result-band__msg">
      <span class="result-check"><?= icon('check') ?></span>
      <div>
        <h1 class="result-title">অভিনন্দন!</h1>
        <p class="result-sub">তুমি এই চ্যাপ্টারের কুইজ সম্পন্ন করেছো।</p>
        <p class="result-chapter"><strong>Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?></strong>
          <span><?= e($chapter['class_name']) ?> · <?= e($chapter['subject_name']) ?><?= $topic ? ' · ' . e($topic['code'] . ' ' . $topic['name']) : '' ?></span></p>
      </div>
    </div>
    <img class="result-band__art" src="<?= asset('img/design/study-reading.jpg') ?>" width="425" height="187" alt="">
    <blockquote class="hadith">“জ্ঞান অর্জন প্রতিটি মুসলিমের জন্য ফরজ।”<cite>— সহিহ ইবনু মাজাহ</cite></blockquote>
  </div>
</section>

<section class="amcq-container">
  <ul class="stat-cards<?= $user ? '' : ' stat-cards--4' ?>">
    <li class="stat-card"><span class="sq sq--green"><?= icon('check') ?></span><div><b><?= $score ?> / <?= $total ?></b><small>Correct Answers</small></div></li>
    <li class="stat-card"><span class="sq sq--red"><?= icon('x-circle') ?></span><div><b><?= $wrong ?></b><small>Incorrect Answers</small></div></li>
    <li class="stat-card"><span class="sq sq--blue"><?= icon('clock') ?></span><div><b><?= duration($attempt['duration_seconds']) ?></b><small>Time Taken</small></div></li>
    <li class="stat-card"><span class="sq sq--purple"><?= icon('target') ?></span><div><b><?= pct($p) ?></b><small>Your Score</small></div></li>
    <?php if ($user): ?>
    <li class="stat-card"><span class="sq sq--amber"><?= icon('trophy') ?></span><div><b class="amcq-points">+<?= $earned ?></b><small>Points Earned</small></div></li>
    <li class="stat-card"><span class="sq sq--blue"><?= icon('bar-up') ?></span><div><b>#<?= points($rank) ?><?php if ($rank_change): ?> <span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?></b><small>Your Rank</small></div></li>
    <?php endif; ?>
  </ul>

  <?php if ( ! $user): ?>
  <div class="save-banner">
    <span class="round round--blue"><?= icon('shield') ?></span>
    <div><strong>Save this result and start building your national rank.</strong><span>Create a free account with your phone number. This result will be saved to it.</span></div>
    <a class="amcq-btn amcq-btn--primary" href="<?= site_url('signup') ?>">Create Free Account</a>
  </div>
  <?php endif; ?>

  <div class="result-grid-3">
    <div class="card">
      <h2>Performance by <?= $breakdown['by'] === 'topic' ? 'Topic' : 'Difficulty' ?></h2>
      <ul class="perf">
        <?php foreach ($breakdown['groups'] as $g): $gp = $g['total'] ? $g['correct'] / $g['total'] : 0;
          $cls = $g['correct'] === 0 ? 'none' : ($gp >= .8 ? 'good' : ($gp >= .5 ? 'mid' : 'low')); ?>
        <li><span class="perf__label"><?= e($g['label']) ?></span>
          <span class="perf__bar"><span class="perf__fill perf__fill--<?= $cls ?>" style="width: <?= round($gp * 100) ?>%"></span></span>
          <b><?= $g['correct'] ?> / <?= $g['total'] ?></b></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card">
      <div class="card__head"><h2>Your Answer Review</h2><a class="link-arrow" href="<?= $review_url ?>">View All Questions <?= icon('arrow-right') ?></a></div>
      <table class="ans-table">
        <thead><tr><th>Q</th><th>Your Answer</th><th>Correct Answer</th><th>Result</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($items, 0, 5) as $it): ?>
          <tr class="<?= $it['is_correct'] ? '' : 'is-wrong' ?>">
            <td><?= (int) $it['position'] ?></td>
            <td><?= isset($it['selected_label']) ? e($it['selected_label']) : '—' ?></td>
            <td><?= e($it['correct_label']) ?></td>
            <td><?= $it['is_correct'] ? '<span class="res res--ok">' . icon('check') . '<span class="sr-only">Correct</span></span>' : '<span class="res res--bad">' . icon('x-circle') . '<span class="sr-only">Incorrect</span></span>' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <a class="link-center" href="<?= $review_url ?>">View Full Answer Review <?= icon('arrow-right') ?></a>
    </div>

    <div class="card">
      <h2>Next Steps</h2>
      <ul class="steps-list">
        <li><?= $form('again', '<span class="sq sq--blue">' . icon('play') . '</span><span><b>Practice Again</b><small>Try another set of questions from this ' . ($topic ? 'topic' : 'chapter') . '</small></span>' . icon('arrow-right'), 'step-link') ?></li>
        <li><a class="step-link" href="<?= $review_url . ($wrong ? '?filter=incorrect' : '') ?>"><span class="sq sq--blue"><?= icon('book-open') ?></span><span><b>Learn Concepts</b><small>Read explanations and notes</small></span><?= icon('arrow-right') ?></a></li>
        <?php if ($can_harder): ?>
        <li><?= $form('hard', '<span class="sq sq--blue">' . icon('file') . '</span><span><b>Try Harder Quiz</b><small>Challenge yourself with more hard questions</small></span>' . icon('arrow-right'), 'step-link') ?></li>
        <?php endif; ?>
        <li><a class="step-link" href="<?= $chapter_url ?>"><span class="sq sq--blue"><?= icon('grid') ?></span><span><b>Go to Chapter List</b><small>Practice other chapters</small></span><?= icon('arrow-right') ?></a></li>
      </ul>
    </div>
  </div>

  <div class="result-grid-3">
    <div class="card">
      <h2>Your Progress</h2>
      <div class="progress-tile__row">
        <div class="ring ring--lg" style="--p: <?= $coverage['pct'] ?>"><span><?= $coverage['pct'] ?>%</span></div>
        <div><b class="big-num">Chapter <?= (int) $chapter['chapter_no'] ?> Progress</b>
          <span class="amcq-muted"><?= $coverage['done'] ?> / <?= $coverage['total'] ?> questions completed</span>
          <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $coverage['pct'] ?>%"></div></div></div>
      </div>
    </div>

    <div class="card">
      <h2>Your Achievement</h2>
      <div class="achieve">
        <span class="achieve__medal"><?= icon('medal') ?></span>
        <div>
          <?php if ($mastered): ?>
            <b>Chapter Mastered!</b><span>You have mastered Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e($chapter['name_bn'] ?: $chapter['name']) ?></span>
          <?php elseif ($user && $attempt['counts_for_streak']): ?>
            <b>Quiz Completed!</b><span>This quiz counts toward your streak: <?= (int) $streak['count'] ?: STREAK_REQUIRED_QUIZZES ?> / <?= STREAK_REQUIRED_QUIZZES ?></span>
          <?php else: ?>
            <b>Quiz Completed!</b><span>You have completed Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e($chapter['name_bn'] ?: $chapter['name']) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($user): ?><span class="achieve__pts"><?= icon('trophy') ?><b>+<?= $earned ?></b><small>Points</small></span><?php endif; ?>
      </div>
    </div>

    <div class="card keep">
      <h2>Keep Going!</h2>
      <div class="keep__row"><span class="sq sq--blue"><?= icon('bar-up') ?></span>
        <div><b><?= $p >= 80 ? 'You are doing great!' : ($p >= STREAK_PASS_PERCENTAGE ? 'Good work!' : 'Every attempt helps!') ?></b>
          <span><?= $wrong ? 'Review the ' . $wrong . ' question' . ($wrong > 1 ? 's' : '') . ' you missed, then practise again to improve your rank.' : 'Continue practising to improve your rank and earn more points.' ?></span></div></div>
    </div>
  </div>

  <div class="quote-banner2">
    <img src="<?= asset('img/design/books-plant.jpg') ?>" width="325" height="109" alt="">
    <p>“অনুশীলনই দক্ষতার চাবিকাঠি।”<small>নিয়মিত অনুশীলন করলে তুমি আরও ভালো করতে পারবে।</small></p>
    <div class="quote-banner2__actions">
      <a class="amcq-btn amcq-btn--secondary" href="<?= $chapter_url ?>"><?= icon('arrow-left') ?> Back to Chapter</a>
      <?php if ($next_chapter): ?>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $next_chapter['slug']) ?>">Continue to Next Chapter <?= icon('arrow-right') ?></a>
      <?php else: ?>
        <a class="amcq-btn amcq-btn--primary" href="<?= $review_url ?>">Review Answers <?= icon('arrow-right') ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
