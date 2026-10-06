<?php defined('BASEPATH') OR exit('No direct script access allowed');
$base = 'quiz/' . $attempt['id'] . '/review';
$qs = function ($pos, $f = NULL) use ($filter) { $f = $f ?: $filter; return '?' . http_build_query(array_filter(array('filter' => $f === 'all' ? NULL : $f, 'q' => $pos))); };
$idx = NULL;
foreach ($shown as $i => $s) { if ($current && $s['position'] === $current['position']) { $idx = $i; } }
$prev = $idx !== NULL && $idx > 0 ? $shown[$idx - 1] : NULL;
$next = $idx !== NULL && $idx < count($shown) - 1 ? $shown[$idx + 1] : NULL;
$chapter_url = site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug']);
$p = (float) $attempt['percentage'];
if ($p >= 80)     { $msg = 'মাশা-আল্লাহ! অসাধারণ!'; }
elseif ($p >= 50) { $msg = 'মাশা-আল্লাহ! ভালো চেষ্টা করছেন!'; }
else              { $msg = 'চেষ্টা চালিয়ে যান!'; }
$again = form_open('quiz/start', array('class' => 'inline-form')) . form_hidden('chapter_id', $chapter['id']) . ($attempt['topic_id'] ? form_hidden('topic_id', $attempt['topic_id']) : '');
?>
<section class="page-band">
  <div class="amcq-container page-band__inner page-band__inner--2">
    <div class="page-band__title">
      <span class="tile tile--green"><?= icon('list') ?></span>
      <div><h1><?= e($chapter['class_name']) ?> - <?= e($chapter['subject_name']) ?></h1>
        <p>Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?></p></div>
    </div>
    <img class="page-band__art" src="<?= asset('img/design/study-window.jpg') ?>" width="355" height="204" alt="">
  </div>
</section>

<section class="amcq-container">
  <div class="review-top">
    <div class="card score-card">
      <div class="ring ring--xl" style="--p: <?= round($p) ?>"><span><b><?= $correct ?> / <?= count($items) ?></b><small>Correct Answers</small><em><?= pct($p) ?></em></span></div>
      <div class="score-card__body">
        <h2 class="score-card__msg"><?= $msg ?></h2>
        <p>আরও অনুশীলন করলে আপনি আরও ভালো করতে পারেন।</p>
        <ul class="score-facts">
          <li><span class="sq sq--purple"><?= icon('file') ?></span><b><?= count($items) ?></b><small>Total MCQs</small></li>
          <li><span class="sq sq--green"><?= icon('check') ?></span><b><?= $correct ?></b><small>Correct</small></li>
          <li><span class="sq sq--red"><?= icon('x-circle') ?></span><b><?= $incorrect ?></b><small>Incorrect</small></li>
          <li><span class="sq sq--blue"><?= icon('clock') ?></span><b><?= duration($attempt['duration_seconds']) ?></b><small>Time Taken</small></li>
        </ul>
      </div>
    </div>
    <?php if ($user): ?>
    <div class="card points-card">
      <div class="points-card__row"><span class="round round--amber"><?= icon('trophy') ?></span><div><b class="amcq-points">+<?= (int) $attempt['points_earned'] ?></b><small>Academic Points Earned</small></div></div>
      <div class="points-card__row"><span class="round round--blue"><?= icon('bar-up') ?></span><div><small>Your Rank</small><b>#<?= points($rank) ?>
        <?php if ($rank_change): ?><span class="delta delta--<?= $rank_change > 0 ? 'up' : 'down' ?>"><?= $rank_change > 0 ? '↑' : '↓' ?> <?= abs($rank_change) ?></span><?php endif; ?></b>
        <small>Bangladesh<?= $rank_change !== NULL ? ' · from last week' : '' ?></small></div></div>
    </div>
    <?php else: ?>
    <div class="card points-card">
      <p><strong>Save this result</strong><br><span class="amcq-muted">Create a free account to keep your results and build your national rank.</span></p>
      <a class="amcq-btn amcq-btn--primary amcq-btn--block" href="<?= site_url('signup') ?>">Create Free Account</a>
    </div>
    <?php endif; ?>
  </div>

  <div class="review-grid">
    <div class="card review-list">
      <nav class="review-tabs" aria-label="Filter questions">
        <a class="<?= $filter === 'all' ? 'is-active' : '' ?>" href="<?= site_url($base) ?>">All Questions (<?= count($items) ?>)</a>
        <a class="<?= $filter === 'correct' ? 'is-active' : '' ?>" href="<?= site_url($base . '?filter=correct') ?>">Correct (<?= $correct ?>)</a>
        <a class="<?= $filter === 'incorrect' ? 'is-active' : '' ?>" href="<?= site_url($base . '?filter=incorrect') ?>">Incorrect (<?= $incorrect ?>)</a>
      </nav>
      <ol class="review-items">
        <?php foreach ($shown as $it): $is = $current && $it['position'] === $current['position']; ?>
        <li><a class="ritem<?= $is ? ' is-current' : '' ?><?= $it['is_correct'] ? '' : ' is-wrong' ?>" href="<?= site_url($base . $qs($it['position'])) ?>"<?= $is ? ' aria-current="true"' : '' ?>>
          <span class="ritem__no"><?= (int) $it['position'] ?></span>
          <span class="res res--<?= $it['is_correct'] ? 'ok' : 'bad' ?>"><?= icon($it['is_correct'] ? 'check' : 'x-circle') ?><span class="sr-only"><?= $it['is_correct'] ? 'Correct' : 'Incorrect' ?></span></span>
          <span class="ritem__text"><span class="ritem__stem"><?= math_text($it['stem']) ?></span>
            <small>Your answer: <?= isset($it['selected_label']) ? e($it['selected_label']) : '—' ?><?= $it['is_correct'] ? '' : ' | <span class="bad">Correct: ' . e($it['correct_label']) . '</span>' ?></small></span>
        </a></li>
        <?php endforeach; ?>
        <?php if ( ! $shown): ?><li class="amcq-muted ritem__empty">No questions in this list.</li><?php endif; ?>
      </ol>
    </div>

    <?php if ($current): ?>
    <div class="card review-detail">
      <div class="review-detail__head">
        <b>Question <?= (int) $current['position'] ?> of <?= count($items) ?></b>
        <?php if ($current['is_correct']): ?><span class="pill-ok"><?= icon('check') ?> Correct</span>
        <?php else: ?><span class="pill-bad"><?= icon('x-circle') ?> <?= isset($current['selected_label']) ? 'Incorrect' : 'Not answered' ?></span><?php endif; ?>
      </div>
      <p class="question__stem"><?= math_text($current['stem']) ?></p>
      <div class="options">
        <?php foreach ($current['options'] as $o):
          $ok = (bool) $o['is_correct']; $picked = (int) $o['id'] === (int) $current['selected_option_id'];
          $cls = $ok ? ' is-correct' : ($picked ? ' is-wrong' : ''); ?>
        <div class="opt opt--static<?= $cls ?>">
          <span class="opt__letter"><?= e($o['label']) ?></span>
          <span class="opt__body"><?= math_text($o['body']) ?></span>
          <?php if ($ok): ?><span class="opt__mark ok"><?= icon('check') ?><span class="opt__tag"><?= $picked ? 'Your answer · Correct' : 'Correct answer' ?></span></span>
          <?php elseif ($picked): ?><span class="opt__mark bad"><?= icon('x-circle') ?><span class="opt__tag">Your answer</span></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="explain-box">
        <h3><?= icon('book-open') ?> ব্যাখ্যা (Explanation)</h3>
        <p><strong>সঠিক উত্তর: <?= e($current['correct_label']) ?></strong></p>
        <p><?= $current['explanation'] ? math_text($current['explanation']) : '<span class="amcq-muted">Explanation coming soon.</span>' ?></p>
        <?php if ($current['source_ref']): ?><p class="explain-box__src"><?= icon('flag') ?> Source: <?= e($current['source_ref']) ?></p><?php endif; ?>
        <a class="explain-box__report" href="<?= site_url('correct-me/' . $current['question_id'] . '?back=' . $attempt['id']) ?>"><?= icon('pencil') ?> ভুল মনে হলে জানাও (Correct Me)</a>
      </div>
      <div class="review-actions">
        <?php if ($prev): ?><a class="amcq-btn amcq-btn--secondary" href="<?= site_url($base . $qs($prev['position'])) ?>"><?= icon('arrow-left') ?> Previous Question</a><?php else: ?><span></span><?php endif; ?>
        <?= $again ?><button class="amcq-btn amcq-btn--secondary" type="submit"><?= icon('refresh') ?> Practice Again</button><?= form_close() ?>
        <?php if ($next): ?><a class="amcq-btn amcq-btn--primary" href="<?= site_url($base . $qs($next['position'])) ?>">Next Question <?= icon('arrow-right') ?></a><?php else: ?><span></span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <aside class="review-side">
      <div class="card">
        <div class="card__head"><h2>Chapter Progress</h2></div>
        <div class="progress-tile__row">
          <div class="ring" style="--p: <?= $coverage['pct'] ?>"><span><?= $coverage['pct'] ?>%</span></div>
          <div><b class="big-num"><?= $coverage['done'] ?> / <?= $coverage['total'] ?></b><span class="amcq-muted small">Questions Completed</span>
            <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $coverage['pct'] ?>%"></div></div></div>
        </div>
        <div class="stack">
          <?= form_open('quiz/start', array('class' => 'inline-form')) ?><?= form_hidden('chapter_id', $chapter['id']) ?>
            <button class="amcq-btn amcq-btn--primary amcq-btn--block" type="submit">Continue Practice <?= icon('arrow-right') ?></button><?= form_close() ?>
          <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= $chapter_url ?>"><?= icon('file') ?> View Chapter Details</a>
          <a class="amcq-btn amcq-btn--ghost amcq-btn--block" href="<?= $chapter_url ?>"><?= icon('arrow-left') ?> Back to Chapter</a>
        </div>
      </div>
      <div class="card tips-card">
        <h2><span class="tips-banner__bulb"><?= icon('bulb') ?></span> Tips</h2>
        <ul>
          <li><?= icon('check') ?>ভুল উত্তরের ব্যাখ্যা মনোযোগ দিয়ে পড়ুন</li>
          <li><?= icon('check') ?>প্রশ্ন ভালোভাবে পড়ুন</li>
          <li><?= icon('check') ?>বারবার অনুশীলন করুন</li>
        </ul>
      </div>
    </aside>
  </div>
</section>
