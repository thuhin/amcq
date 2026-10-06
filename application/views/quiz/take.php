<?php defined('BASEPATH') OR exit('No direct script access allowed');
$total = count($items);
$pct = round($answered * 100 / $total);
$is_last = $q === $total;
$unanswered = $total - $answered;
?>
<div class="quiz">
  <div class="amcq-container quiz__head">
    <div class="quiz__title">
      <span class="chip chip--green chip--xl"><?= icon('file') ?></span>
      <div>
        <h1><?= e($chapter['class_name']) ?> - <?= e($chapter['subject_name']) ?></h1>
        <p class="amcq-muted">Chapter <?= (int) $chapter['chapter_no'] ?>: <?= e(chapter_label($chapter)) ?><?= $topic ? ' · ' . e($topic['code'] . ' ' . $topic['name']) : '' ?></p>
      </div>
    </div>
    <ul class="quiz__facts">
      <li><span class="chip chip--purple"><?= icon('file') ?></span><span><small>Questions</small><b><?= $total ?> MCQs</b></span></li>
      <li><span class="chip chip--pink"><?= icon('clock') ?></span><span><small>Time</small><b><?= round($attempt['time_limit_seconds'] / 60) ?> Minutes</b></span></li>
      <li><span class="chip chip--amber"><?= icon('trophy') ?></span><span><small>Passing Mark</small><b><?= STREAK_PASS_PERCENTAGE ?>%</b></span></li>
    </ul>
    <a class="quiz__exit" href="<?= site_url('practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug']) ?>">Exit</a>
  </div>

  <?= form_open('quiz/' . $attempt['id'] . '/answer', array('class' => 'amcq-container quiz__body', 'id' => 'quiz-form', 'data-unanswered' => $unanswered)) ?>
    <?= form_hidden('position', $q) ?>

    <section class="amcq-card quiz__card" aria-labelledby="question-text">
      <div class="quiz__bar">
        <span class="quiz__count">Question <?= $q ?> of <?= $total ?></span>
        <div class="amcq-progress quiz__progress" role="progressbar" aria-valuenow="<?= $answered ?>" aria-valuemin="0" aria-valuemax="<?= $total ?>" aria-label="Answered">
          <div class="amcq-progress__bar" style="width: <?= $pct ?>%"></div>
        </div>
        <span class="amcq-muted"><?= $pct ?>%</span>
        <?php if ($seconds_left !== NULL): ?>
        <span class="timer" id="timer" data-seconds-left="<?= (int) $seconds_left ?>" role="timer" aria-live="off">
          <?= icon('clock') ?><b><?= duration($seconds_left) ?></b>
        </span>
        <?php endif; ?>
      </div>

      <fieldset class="question">
        <legend id="question-text" class="question__stem"><?= math_text($current['stem']) ?></legend>
        <div class="options">
          <?php foreach ($current['options'] as $o): $checked = (int) $o['id'] === (int) $current['selected_option_id']; ?>
          <!-- The whole row is the tap target (guideline §4.3: no tiny radio buttons). -->
          <label class="amcq-option<?= $checked ? ' amcq-option--selected' : '' ?>">
            <input type="radio" name="option_id" value="<?= (int) $o['id'] ?>"<?= $checked ? ' checked' : '' ?>>
            <span class="amcq-option__label"><?= e($o['label']) ?></span>
            <span class="amcq-option__body"><?= math_text($o['body']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="quiz__actions">
        <button class="amcq-btn amcq-btn--secondary" type="submit" name="go" value="prev"<?= $q === 1 ? ' disabled' : '' ?>><?= icon('arrow-left') ?> Previous</button>
        <label class="amcq-btn amcq-btn--ghost review-toggle">
          <input type="checkbox" name="review" value="1"<?= $current['marked_for_review'] ? ' checked' : '' ?>>
          <?= icon('bookmark') ?> Mark for Review
        </label>
        <?php if ($is_last): ?>
          <button class="amcq-btn amcq-btn--primary js-submit" type="submit" name="go" value="submit">Submit Quiz <?= icon('arrow-right') ?></button>
        <?php else: ?>
          <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="next">Next <?= icon('arrow-right') ?></button>
        <?php endif; ?>
      </div>
    </section>

    <aside class="quiz__side">
      <div class="amcq-card navigator">
        <h2 class="h3">Question Navigator</h2>
        <ul class="navigator__legend">
          <li><i class="dot dot--answered"></i>Answered</li>
          <li><i class="dot dot--current"></i>Current</li>
          <li><i class="dot dot--review"></i>Review</li>
          <li><i class="dot"></i>Not Answered</li>
        </ul>
        <div class="navigator__grid">
          <?php foreach ($items as $it):
            $p = (int) $it['position'];
            $cls = $p === $q ? 'is-current' : ($it['marked_for_review'] ? 'is-review' : ($it['selected_option_id'] ? 'is-answered' : ''));
            $state = $p === $q ? 'current' : ($it['marked_for_review'] ? 'marked for review' : ($it['selected_option_id'] ? 'answered' : 'not answered'));
          ?>
            <button type="submit" name="go" value="<?= $p ?>" class="navigator__cell <?= $cls ?>" aria-label="Question <?= $p ?>, <?= $state ?>"<?= $p === $q ? ' aria-current="step"' : '' ?>><?= $p ?></button>
          <?php endforeach; ?>
        </div>
        <button class="amcq-btn amcq-btn--primary amcq-btn--block js-submit" type="submit" name="go" value="submit">Submit Quiz <?= icon('arrow-right') ?></button>
        <p class="amcq-muted navigator__note"><?= $answered ?> of <?= $total ?> answered</p>
      </div>

      <div class="tips tips--small">
        <h3><?= icon('bulb') ?> Tips</h3>
        <ul>
          <li><?= icon('check-circle') ?>প্রশ্নটি ভালোভাবে পড়ো</li>
          <li><?= icon('check-circle') ?>সময় ব্যবস্থাপনার দিকে খেয়াল রাখো</li>
          <li><?= icon('check-circle') ?>অনিশ্চিত হলে Mark for Review করো</li>
        </ul>
      </div>
    </aside>
  <?= form_close() ?>
</div>
