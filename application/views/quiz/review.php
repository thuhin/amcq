<?php defined('BASEPATH') OR exit('No direct script access allowed');
$base = 'quiz/' . $attempt['id'] . '/review';
$qs = function ($pos, $f = NULL) use ($filter) { $f = $f ?: $filter; return '?' . http_build_query(array_filter(array('filter' => $f === 'all' ? NULL : $f, 'q' => $pos))); };
$idx = NULL;
foreach ($shown as $i => $s) { if ($current && $s['position'] === $current['position']) { $idx = $i; } }
$prev = $idx !== NULL && $idx > 0 ? $shown[$idx - 1] : NULL;
$next = $idx !== NULL && $idx < count($shown) - 1 ? $shown[$idx + 1] : NULL;
$chapter_url = 'practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug'];
?>
<section class="page-hero page-hero--slim">
  <div class="amcq-container page-hero__inner">
    <div class="page-hero__title">
      <span class="chip chip--green chip--xl"><?= icon('file') ?></span>
      <div><h1><?= e($chapter['class_name']) ?> - <?= e($chapter['subject_name']) ?></h1>
        <p class="amcq-muted">Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?></p></div>
    </div>
    <div class="review-score">
      <div class="ring ring--lg" style="--p: <?= round($attempt['percentage']) ?>"><span><b><?= $correct ?> / <?= count($items) ?></b><small>Correct</small></span></div>
      <ul class="review-score__facts">
        <li><b><?= count($items) ?></b><small>Total MCQs</small></li>
        <li><b class="ok"><?= $correct ?></b><small>Correct</small></li>
        <li><b class="bad"><?= $incorrect ?></b><small>Incorrect</small></li>
        <li><b><?= duration($attempt['duration_seconds']) ?></b><small>Time Taken</small></li>
      </ul>
    </div>
  </div>
</section>

<section class="amcq-container review">
  <div class="amcq-card review__list">
    <nav class="review__tabs" aria-label="Filter questions">
      <a class="<?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= site_url($base) ?>">All Questions (<?= count($items) ?>)</a>
      <a class="<?= $filter === 'correct' ? 'is-active' : '' ?>" href="<?= site_url($base . '?filter=correct') ?>">Correct (<?= $correct ?>)</a>
      <a class="<?= $filter === 'incorrect' ? 'is-active' : '' ?>" href="<?= site_url($base . '?filter=incorrect') ?>">Incorrect (<?= $incorrect ?>)</a>
    </nav>
    <ol class="review__items">
      <?php foreach ($shown as $it): $is = $current && $it['position'] === $current['position']; ?>
      <li>
        <a class="review__item<?= $is ? ' is-current' : '' ?><?= $it['is_correct'] ? '' : ' is-wrong' ?>" href="<?= site_url($base . $qs($it['position'])) ?>"<?= $is ? ' aria-current="true"' : '' ?>>
          <span class="review__no"><?= (int) $it['position'] ?></span>
          <span class="mark mark--<?= $it['is_correct'] ? 'ok' : 'bad' ?>"><?= icon($it['is_correct'] ? 'check-circle' : 'x-circle') ?><span class="sr-only"><?= $it['is_correct'] ? 'Correct' : 'Incorrect' ?></span></span>
          <span class="review__text">
            <span class="review__stem"><?= math_text($it['stem']) ?></span>
            <small>Your answer: <?= isset($it['selected_label']) ? e($it['selected_label']) : 'Not answered' ?><?= $it['is_correct'] ? '' : ' | <span class="ok">Correct: ' . e($it['correct_label']) . '</span>' ?></small>
          </span>
        </a>
      </li>
      <?php endforeach; ?>
      <?php if ( ! $shown): ?><li class="amcq-muted review__empty">No questions in this list.</li><?php endif; ?>
    </ol>
  </div>

  <?php if ($current): ?>
  <div class="amcq-card review__detail">
    <div class="review__detail-head">
      <b>Question <?= (int) $current['position'] ?> of <?= count($items) ?></b>
      <?php if ($current['is_correct']): ?>
        <span class="badge badge--ok"><?= icon('check-circle') ?> Correct</span>
      <?php else: ?>
        <span class="badge badge--bad"><?= icon('x-circle') ?> <?= isset($current['selected_label']) ? 'Incorrect' : 'Not answered' ?></span>
      <?php endif; ?>
    </div>
    <p class="question__stem"><?= math_text($current['stem']) ?></p>
    <div class="options">
      <?php foreach ($current['options'] as $o):
        $is_correct = (bool) $o['is_correct'];
        $is_picked  = (int) $o['id'] === (int) $current['selected_option_id'];
        $cls = $is_correct ? ' amcq-option--correct' : ($is_picked ? ' amcq-option--incorrect' : '');
      ?>
      <div class="amcq-option<?= $cls ?>">
        <span class="amcq-option__label"><?= e($o['label']) ?></span>
        <span class="amcq-option__body"><?= math_text($o['body']) ?></span>
        <?php if ($is_correct): ?>
          <span class="amcq-option__tag ok"><?= icon('check-circle') ?> <?= $is_picked ? 'Your answer · Correct' : 'Correct answer' ?></span>
        <?php elseif ($is_picked): ?>
          <span class="amcq-option__tag bad"><?= icon('x-circle') ?> Your answer</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="explain">
      <h3><?= icon('book') ?> ব্যাখ্যা (Explanation)</h3>
      <p><strong>সঠিক উত্তর: <?= e($current['correct_label']) ?></strong></p>
      <p><?= $current['explanation'] ? math_text($current['explanation']) : '<span class="amcq-muted">Explanation coming soon.</span>' ?></p>
      <?php if ($current['source_ref']): ?>
        <p class="explain__source"><?= icon('flag') ?> Source: <?= e($current['source_ref']) ?></p>
      <?php endif; ?>
    </div>

    <div class="review__actions">
      <?php if ($prev): ?><a class="amcq-btn amcq-btn--secondary" href="<?= site_url($base . $qs($prev['position'])) ?>"><?= icon('arrow-left') ?> Previous Question</a>
      <?php else: ?><span></span><?php endif; ?>
      <a class="amcq-btn amcq-btn--ghost" href="<?= site_url('correct-me/' . $current['question_id'] . '?back=' . $attempt['id']) ?>"><?= icon('pencil') ?> Think this is wrong? Correct Me</a>
      <?php if ($next): ?><a class="amcq-btn amcq-btn--primary" href="<?= site_url($base . $qs($next['position'])) ?>">Next Question <?= icon('arrow-right') ?></a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <aside class="review__side">
    <div class="amcq-card">
      <h2 class="h3">Score</h2>
      <div class="progress-card progress-card--flat">
        <div class="ring" style="--p: <?= round($attempt['percentage']) ?>"><span><?= pct($attempt['percentage']) ?></span></div>
        <div><b><?= $correct ?> / <?= count($items) ?></b><span class="amcq-muted">Questions correct</span></div>
      </div>
      <?= form_open('quiz/start') ?><?= form_hidden('chapter_id', $chapter['id']) ?><?= $attempt['topic_id'] ? form_hidden('topic_id', $attempt['topic_id']) : '' ?>
        <button class="amcq-btn amcq-btn--primary amcq-btn--block" type="submit"><?= icon('refresh') ?> Practice Again</button>
      <?= form_close() ?>
      <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= site_url('quiz/' . $attempt['id'] . '/result') ?>"><?= icon('chart') ?> Back to Result</a>
      <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= site_url($chapter_url) ?>"><?= icon('arrow-left') ?> Back to Chapter</a>
    </div>
    <div class="tips tips--small">
      <h3><?= icon('bulb') ?> Tips</h3>
      <ul>
        <li><?= icon('check-circle') ?>ভুল উত্তরের ব্যাখ্যা মনোযোগ দিয়ে পড়ো</li>
        <li><?= icon('check-circle') ?>প্রশ্ন ভালোভাবে পড়ে উত্তর দাও</li>
        <li><?= icon('check-circle') ?>বারবার অনুশীলন করো</li>
      </ul>
    </div>
  </aside>
</section>
