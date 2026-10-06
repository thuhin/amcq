<?php defined('BASEPATH') OR exit('No direct script access allowed');
$total_chapters = count($chapters);
$subject_pct = $total_chapters ? round($subject_done * 100 / $total_chapters) : 0;
$topic_count = count($topics);
$start_form = function ($topic_id, $label, $class) use ($chapter) {
	return form_open('quiz/start', array('class' => 'inline-form'))
		. form_hidden('chapter_id', $chapter['id'])
		. ($topic_id ? form_hidden('topic_id', $topic_id) : '')
		. '<button type="submit" class="amcq-btn ' . $class . '">' . $label . '</button>'
		. form_close();
};
?>
<section class="page-hero">
  <div class="amcq-container page-hero__inner">
    <div class="page-hero__title">
      <span class="chip chip--green chip--xl"><?= icon('file') ?></span>
      <div>
        <h1><?= e($class['name']) ?> - <?= e($subject['name']) ?></h1>
        <p class="amcq-muted">Complete syllabus practice with step-by-step explanations</p>
      </div>
    </div>
    <?php if ($user): ?>
    <div class="amcq-card progress-card">
      <div class="ring" style="--p: <?= $subject_pct ?>"><span><?= $subject_pct ?>%</span></div>
      <div>
        <small class="amcq-muted">Your Progress</small>
        <strong><?= $subject_done ?> / <?= $total_chapters ?></strong>
        <span class="amcq-muted">Chapters Completed</span>
        <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $subject_pct ?>%"></div></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="amcq-container chapter-layout">
  <aside class="amcq-card chapter-nav" aria-label="Chapters">
    <h2 class="h3">Chapters</h2>
    <ol>
      <?php foreach ($chapters as $c): $current = $c['id'] === $chapter['id']; ?>
      <li>
        <a class="chapter-nav__item<?= $current ? ' is-current' : '' ?><?= $c['state'] === 'locked' ? ' is-locked' : '' ?>"
           href="<?= site_url('practice/' . $class['slug'] . '/' . $subject['slug'] . '?chapter=' . $c['slug']) ?>"<?= $current ? ' aria-current="page"' : '' ?>>
          <span class="chapter-nav__no"><?= (int) $c['chapter_no'] ?></span>
          <span class="chapter-nav__name"><?= e($c['name_bn'] ? $c['name_bn'] : $c['name']) ?></span>
          <?php if ($current): ?><?= icon('arrow-right') ?>
          <?php elseif ($c['state'] === 'done'): ?><span class="state state--done" title="Completed"><?= icon('check') ?><span class="sr-only">Completed</span></span>
          <?php elseif ($c['state'] === 'started'): ?><span class="state state--started" title="In progress"><span class="sr-only">In progress</span></span>
          <?php elseif ($c['state'] === 'locked'): ?><span class="state state--locked" title="Coming soon"><?= icon('lock') ?><span class="sr-only">Coming soon</span></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
    </ol>
  </aside>

  <div class="chapter-main">
    <div class="chapter-head">
      <span class="chapter-head__no"><?= (int) $chapter['chapter_no'] ?></span>
      <div class="chapter-head__title">
        <h2><?= e(chapter_label($chapter)) ?></h2>
        <p class="amcq-muted"><?= e($class['name']) ?> - <?= e($subject['name']) ?></p>
      </div>
      <?php if ($user && $topic_count): ?>
      <div class="amcq-card mini-stat">
        <small>Chapter Progress</small>
        <div class="mini-stat__row"><div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= round($topics_done * 100 / $topic_count) ?>%"></div></div><b><?= $topics_done ?> / <?= $topic_count ?></b></div>
      </div>
      <?php endif; ?>
      <div class="amcq-card mini-stat mini-stat--icon">
        <?= icon('clock') ?><div><small>Time per quiz</small><b><?= QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60 ?> Minutes</b></div>
      </div>
    </div>

    <?php if ( ! $chapter_playable): ?>
      <div class="empty amcq-card">
        <span class="chip chip--blue chip--lg"><?= icon('lock') ?></span>
        <h3>Questions for this chapter are coming soon</h3>
        <p class="amcq-muted">Our teachers are preparing and checking the questions. Try another chapter in the meantime.</p>
      </div>
    <?php else: ?>

      <!-- Whole-chapter quiz: always available when the chapter is playable. -->
      <div class="topic topic--chapter">
        <span class="topic__state"><?= icon('play') ?></span>
        <div class="topic__body">
          <strong>Full chapter quiz</strong>
          <span class="amcq-muted">Mixed questions from the whole chapter · <?= QUIZ_MIX_EASY ?> easy, <?= QUIZ_MIX_MEDIUM ?> medium, <?= QUIZ_MIX_HARD ?> hard</span>
        </div>
        <span class="topic__count"><?= QUIZ_QUESTION_COUNT ?> MCQs</span>
        <?= $start_form(NULL, 'Start Practice ' . icon('arrow-right'), 'amcq-btn--primary') ?>
      </div>

      <?php foreach ($topics as $t): ?>
      <div class="topic<?= $t['playable'] ? '' : ' is-locked' ?>">
        <span class="topic__state<?= $t['best'] !== NULL ? ' is-done' : '' ?>">
          <?= icon($t['playable'] ? ($t['best'] !== NULL ? 'check' : 'play') : 'lock') ?>
        </span>
        <div class="topic__body">
          <strong><span class="topic__code"><?= e($t['code']) ?></span> <?= e($t['name']) ?></strong>
          <span class="amcq-muted"><?= e($t['summary']) ?><?= $t['best'] !== NULL ? ' · Best ' . pct($t['best']) : '' ?></span>
        </div>
        <span class="topic__count"><?= $t['playable'] ? QUIZ_QUESTION_COUNT . ' MCQs' : '' ?></span>
        <?php if ( ! $t['playable']): ?>
          <span class="amcq-btn amcq-btn--muted" aria-disabled="true">Coming Soon</span>
        <?php elseif ($t['best'] !== NULL): ?>
          <?= $start_form($t['id'], icon('refresh') . ' Practice Again', 'amcq-btn--tint') ?>
        <?php else: ?>
          <?= $start_form($t['id'], 'Start Practice ' . icon('arrow-right'), 'amcq-btn--secondary') ?>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <p class="note">
        <?= icon('wallet') ?>
        <?php if ($user): ?>
          Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz attempt, paid from your wallet.
        <?php else: ?>
          You can try <?= GUEST_FREE_QUIZZES ?> quizzes without an account. After that, quizzes are Tk <?= number_format(QUIZ_FEE_TAKA) ?> each.
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <div class="tips">
      <h3><?= icon('bulb') ?> Learning Tips</h3>
      <ul>
        <li><?= icon('check-circle') ?>প্রতি অধ্যায়ের MCQ অনুশীলন করুন</li>
        <li><?= icon('check-circle') ?>ভুল উত্তরগুলোর ব্যাখ্যা অবশ্যই পড়ুন</li>
        <li><?= icon('check-circle') ?>নিয়মিত অনুশীলন করলে আপনার দক্ষতা বাড়বে</li>
        <li><?= icon('check-circle') ?>অধ্যায় শেষ হলে পরবর্তী অধ্যায়ে যান</li>
      </ul>
    </div>
  </div>
</section>
