<?php defined('BASEPATH') OR exit('No direct script access allowed');
$total = count($items);
$pos_pct = (int) round($q * 100 / $total);   // design: "Question 4 of 20 ... 20%"
$is_last = $q === $total;
$unanswered = $total - $answered;
$chapter_url = site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug']);
?>
<section class="page-band page-band--quiz">
  <div class="amcq-container quiz-band">
    <div class="quiz-band__main">
      <div class="page-band__title">
        <span class="tile tile--green"><?= icon('list') ?></span>
        <div><h1><?= e($chapter['class_name']) ?> - <?= e($chapter['subject_name']) ?></h1>
          <p>Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?><?= $topic ? ' · ' . e($topic['code'] . ' ' . $topic['name']) : '' ?><?= $attempt['mode'] === 'hard' ? ' · Harder quiz' : '' ?></p></div>
        <a class="amcq-btn amcq-btn--secondary amcq-btn--sm quiz-band__change" href="<?= $chapter_url ?>"><?= icon('swap') ?> Change</a>
      </div>
      <ul class="fact-cards">
        <li><span class="sq sq--purple"><?= icon('file') ?></span><span><small>Questions</small><b><?= $total ?> MCQs</b></span></li>
        <li><span class="sq sq--red"><?= icon('clock') ?></span><span><small>Time</small><b><?= round($attempt['time_limit_seconds'] / 60) ?> Minutes</b></span></li>
        <li><span class="sq sq--amber"><?= icon('trophy') ?></span><span><small>Passing Mark</small><b><?= STREAK_PASS_PERCENTAGE ?>%</b></span></li>
        <li><span class="sq sq--blue"><?= icon('bar-up') ?></span><span><small>Attempts</small><b><?= (int) $attempts ?>/∞</b></span></li>
      </ul>
    </div>
    <img class="page-band__art quiz-band__art" src="<?= asset('img/design/study-desk.jpg') ?>" width="360" height="232" alt="">
  </div>
</section>

<div class="amcq-container quiz-layout">
  <?= form_open('quiz/' . $attempt['id'] . '/answer', array('class' => 'quiz-form', 'id' => 'quiz-form', 'data-unanswered' => $unanswered)) ?>
    <?= form_hidden('position', $q) ?>
    <div class="card qcard">
    <div class="qcard__bar">
      <span class="qcard__count">Question <?= $q ?> of <?= $total ?></span>
      <div class="amcq-progress qcard__progress" role="progressbar" aria-valuenow="<?= $q ?>" aria-valuemin="1" aria-valuemax="<?= $total ?>" aria-label="Progress">
        <div class="amcq-progress__bar" style="width: <?= $pos_pct ?>%"></div></div>
      <span class="qcard__pct"><?= $pos_pct ?>%</span>
      <div class="qcard__timer">
        <span class="timer-pill<?= $paused ? ' is-paused' : '' ?>" id="timer" data-seconds-left="<?= (int) $seconds_left ?>" data-paused="<?= $paused ? 1 : 0 ?>" role="timer" aria-live="off">
          <?= icon('clock') ?><b><?= duration($seconds_left) ?></b></span>
        <button class="pause-btn" type="submit" form="pause-form" aria-label="<?= $paused ? 'Resume timer' : 'Pause timer' ?>" title="<?= $paused ? 'Resume' : 'Pause' ?>"><?= icon($paused ? 'play' : 'pause') ?></button>
      </div>
    </div>

    <?php if ($paused): ?>
      <div class="paused-card">
        <span class="round round--blue"><?= icon('pause') ?></span>
        <strong>Quiz paused</strong>
        <p class="amcq-muted">The timer is stopped. The question is hidden until you resume.</p>
        <button class="amcq-btn amcq-btn--primary" type="submit" form="pause-form"><?= icon('play') ?> Resume</button>
      </div>
    <?php else: ?>
    <fieldset class="question">
      <legend class="question__stem"><?= math_text($current['stem']) ?></legend>
      <div class="options">
        <?php foreach ($current['options'] as $o): $checked = (int) $o['id'] === (int) $current['selected_option_id']; ?>
        <label class="opt<?= $checked ? ' is-selected' : '' ?>">
          <input type="radio" name="option_id" value="<?= (int) $o['id'] ?>"<?= $checked ? ' checked' : '' ?>>
          <span class="opt__letter"><?= e($o['label']) ?></span>
          <span class="opt__body"><?= math_text($o['body']) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="qcard__actions">
      <button class="amcq-btn amcq-btn--secondary" type="submit" name="go" value="prev"<?= $q === 1 ? ' disabled' : '' ?>><?= icon('arrow-left') ?> Previous</button>
      <label class="amcq-btn amcq-btn--ghost review-toggle">
        <input type="checkbox" name="review" value="1"<?= $current['marked_for_review'] ? ' checked' : '' ?>><?= icon('bookmark') ?> Mark for Review
      </label>
      <?php if ($is_last): ?>
        <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="submit">Submit Quiz <?= icon('arrow-right') ?></button>
      <?php else: ?>
        <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="next">Next <?= icon('arrow-right') ?></button>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    </div>

    <aside class="qside">
      <div class="card nav-card">
        <h2>Question Navigator</h2>
        <ul class="legend">
          <li><i class="dot dot--answered"></i>Answered</li><li><i class="dot dot--current"></i>Current</li>
          <li><i class="dot dot--review"></i>Review</li><li><i class="dot"></i>Not Answered</li>
        </ul>
        <div class="nav-grid">
          <?php foreach ($items as $it):
            $p = (int) $it['position'];
            $cls = $p === $q ? 'is-current' : ($it['marked_for_review'] ? 'is-review' : ($it['selected_option_id'] ? 'is-answered' : ''));
            $state = $p === $q ? 'current' : ($it['marked_for_review'] ? 'marked for review' : ($it['selected_option_id'] ? 'answered' : 'not answered')); ?>
            <button type="submit" name="go" value="<?= $p ?>" class="nav-cell <?= $cls ?>" aria-label="Question <?= $p ?>, <?= $state ?>"<?= $p === $q ? ' aria-current="step"' : '' ?><?= $paused ? ' disabled' : '' ?>><?= $p ?></button>
          <?php endforeach; ?>
        </div>
        <button class="amcq-btn amcq-btn--primary amcq-btn--block" type="submit" name="go" value="submit"<?= $paused ? ' disabled' : '' ?>>Submit Quiz <?= icon('arrow-right') ?></button>
      </div>
      <div class="card">
        <h2>Chapter Progress</h2>
        <div class="progress-tile__row">
          <div class="ring" style="--p: <?= $coverage['pct'] ?>"><span><?= $coverage['pct'] ?>%</span></div>
          <div><b class="big-num"><?= $coverage['done'] ?> / <?= $coverage['total'] ?></b><span class="amcq-muted small">Questions Completed</span>
            <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $coverage['pct'] ?>%"></div></div></div>
        </div>
        <a class="link-row" href="<?= $chapter_url ?>"><?= icon('file') ?> View Chapter Details <?= icon('arrow-right') ?></a>
      </div>
    </aside>
  <?= form_close() ?>
  <?= form_open('quiz/' . $attempt['id'] . '/' . ($paused ? 'resume' : 'pause'), array('id' => 'pause-form', 'hidden' => 'hidden')) ?><?= form_hidden('position', $q) ?><?= form_close() ?>

  <div class="tips-banner tips-banner--quiz">
    <div>
      <h3><span class="tips-banner__bulb"><?= icon('bulb') ?></span> Tips</h3>
      <ul class="tips-2col">
        <li><?= icon('check') ?>প্রশ্নটি ভালোভাবে পড়ো</li><li><?= icon('check') ?>সময় ব্যবস্থাপনার দিকে খেয়াল রাখো</li>
        <li><?= icon('check') ?>প্রয়োজনে খাতায় হিসাব করো</li><li><?= icon('check') ?>অনিশ্চিত হলে Mark for Review করো</li>
      </ul>
    </div>
    <img src="<?= asset('img/design/books-desk.jpg') ?>" width="405" height="125" alt="">
  </div>
</div>
