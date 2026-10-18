<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $c @var string $round @var array $items @var array $current @var int $q @var int $seconds_left @var int $answered @var array $weights */
$total = count($items);
$final = $round === 'final';
$pos_pct = (int) round($q * 100 / $total);
$unanswered = $total - $answered;
?>
<section class="page-band page-band--quiz">
  <div class="amcq-container quiz-band">
    <div class="quiz-band__main">
      <div class="page-band__title">
        <span class="tile tile--blue"><?= icon('trophy') ?></span>
        <div><h1><?= e($c['name']) ?></h1>
          <p><?= $final ? 'Final round: one question at a time, no going back.' : 'Round 1: one sitting; you may move between questions.' ?></p></div>
      </div>
      <ul class="fact-cards">
        <li><span class="sq sq--purple"><?= icon('file') ?></span><span><small>Questions</small><b><?= $total ?> MCQs</b></span></li>
        <li><span class="sq sq--red"><?= icon('clock') ?></span><span><small>Time</small><b><?= (int) $c[$final ? 'final_time_limit_minutes' : 'round1_time_limit_minutes'] ?> Minutes</b></span></li>
        <li><span class="sq sq--amber"><?= icon('target') ?></span><span><small>Points: <?= e(implode(' · ', array_column($weights, 'label'))) ?></small><b><?= e(implode(' · ', array_map(function ($d) { return rtrim(rtrim($d['weight'], '0'), '.'); }, $weights))) ?></b></span></li>
        <li><span class="sq sq--blue"><?= icon('check') ?></span><span><small><?= $final ? 'Done' : 'Answered' ?></small><b><?= (int) $answered ?> / <?= $total ?></b></span></li>
      </ul>
    </div>
  </div>
</section>

<div class="amcq-container quiz-layout">
  <?= form_open('competition/' . $round . '/answer', array('class' => 'quiz-form', 'id' => 'quiz-form', 'data-unanswered' => $final ? 0 : $unanswered)) ?>
    <?= form_hidden('position', $q) ?>
    <div class="card qcard">
      <div class="qcard__bar">
        <span class="qcard__count">Question <?= $q ?> of <?= $total ?></span>
        <div class="amcq-progress qcard__progress" role="progressbar" aria-valuenow="<?= $q ?>" aria-valuemin="1" aria-valuemax="<?= $total ?>" aria-label="Progress">
          <div class="amcq-progress__bar" style="width: <?= $pos_pct ?>%"></div></div>
        <span class="qcard__pct"><?= $pos_pct ?>%</span>
        <div class="qcard__timer">
          <!-- The clock is the server's. This countdown only displays it; at 0 it submits. -->
          <span class="timer-pill" id="timer" data-seconds-left="<?= (int) $seconds_left ?>" data-paused="0" role="timer" aria-live="off"><?= icon('clock') ?><b><?= duration($seconds_left) ?></b></span>
        </div>
      </div>

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
        <?php if ($final): ?>
          <span class="amcq-muted small">After this you cannot come back to question <?= $q ?>.</span>
          <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="next"><?= $q === $total ? 'Save and submit the final' : 'Save and next' ?> <?= icon('arrow-right') ?></button>
        <?php else: ?>
          <button class="amcq-btn amcq-btn--secondary" type="submit" name="go" value="prev"<?= $q === 1 ? ' disabled' : '' ?>><?= icon('arrow-left') ?> Previous</button>
          <?php if ($q === $total): ?>
            <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="submit">Submit Round 1 <?= icon('arrow-right') ?></button>
          <?php else: ?>
            <button class="amcq-btn amcq-btn--primary" type="submit" name="go" value="next">Next <?= icon('arrow-right') ?></button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <aside class="qside">
      <div class="card nav-card">
        <h2><?= $final ? 'Progress' : 'Question Navigator' ?></h2>
        <div class="nav-grid">
          <?php foreach ($items as $it):
            $p = (int) $it['position'];
            $done = $final ? (bool) $it['answered_at'] : (bool) $it['selected_option_id'];
            $cls = $p === $q ? 'is-current' : ($done ? 'is-answered' : ''); ?>
            <?php if ($final): ?>
              <span class="nav-cell <?= $cls ?>" aria-label="Question <?= $p ?>, <?= $p === $q ? 'current' : ($done ? 'done' : 'to come') ?>"><?= $p ?></span>
            <?php else: ?>
              <button type="submit" name="go" value="<?= $p ?>" class="nav-cell <?= $cls ?>" aria-label="Question <?= $p ?>, <?= $p === $q ? 'current' : ($done ? 'answered' : 'not answered') ?>"<?= $p === $q ? ' aria-current="step"' : '' ?>><?= $p ?></button>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php if ( ! $final): ?>
          <button class="amcq-btn amcq-btn--primary amcq-btn--block" type="submit" name="go" value="submit">Submit Round 1 <?= icon('arrow-right') ?></button>
        <?php endif; ?>
      </div>
      <div class="card">
        <h2>Rules</h2>
        <ul class="rules">
          <li>The clock does not stop. At 0 your answers are submitted as they stand.</li>
          <?php if ($final): ?><li>Leaving a question blank and moving on skips it for good.</li><?php else: ?><li>You may change an answer until you submit.</li><?php endif; ?>
          <li>Ties are broken by time taken.</li>
        </ul>
      </div>
    </aside>
  <?= form_close() ?>
</div>
