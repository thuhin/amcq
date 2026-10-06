<?php defined('BASEPATH') OR exit('No direct script access allowed');
$total_chapters = count($chapters);
$subject_pct = $total_chapters ? (int) round($subject_done * 100 / $total_chapters) : 0;
$topic_count = count($topics);
$playable_topics = count(array_filter($topics, function ($t) { return $t['playable']; }));
$next_id = NULL;
foreach ($topics as $t) { if ($t['playable'] && $t['best'] === NULL) { $next_id = $t['id']; break; } }
$start = function ($topic_id, $label, $class) use ($chapter) {
	return form_open('quiz/start', array('class' => 'inline-form')) . form_hidden('chapter_id', $chapter['id'])
		. form_hidden('topic_id', $topic_id) . '<button type="submit" class="amcq-btn ' . $class . '">' . $label . '</button>' . form_close();
};
$chapter_url = function ($c) use ($class, $subject) { return site_url('practice/' . $class['slug'] . '/' . $subject['slug'] . '?chapter=' . $c['slug']); };
?>
<section class="page-band">
  <div class="amcq-container page-band__inner">
    <div class="page-band__title">
      <span class="tile tile--green"><?= icon('list') ?></span>
      <div><h1><?= e($class['name']) ?> - <?= e($subject['name']) ?></h1>
        <p>Complete syllabus practice with step-by-step explanations</p></div>
    </div>
    <div class="progress-tile">
      <?php if ($user): ?>
        <small>Your Progress</small>
        <div class="progress-tile__row">
          <div class="ring" style="--p: <?= $subject_pct ?>"><span><?= $subject_pct ?>%</span></div>
          <div><b><?= $subject_done ?> / <?= $total_chapters ?></b><span>Chapters Completed</span>
            <div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= $subject_pct ?>%"></div></div></div>
        </div>
      <?php else: ?>
        <small>Your Progress</small>
        <p class="amcq-muted">Create a free account to track chapters you complete.</p>
        <a class="link-arrow" href="<?= site_url('signup') ?>">Sign Up <?= icon('arrow-right') ?></a>
      <?php endif; ?>
    </div>
    <img class="page-band__art" src="<?= asset('img/design/study-window.jpg') ?>" width="355" height="204" alt="">
  </div>
</section>

<section class="amcq-container chapter-wrap" data-tabs>
  <!-- Mobile tabs (design 11, phone): Chapters | About Subject -->
  <div class="seg chapter-wrap__tabs" role="tablist">
    <a role="tab" class="seg__btn is-active" href="#chapters" data-tab="chapters" aria-selected="true"><?= icon('book') ?> Chapters</a>
    <a role="tab" class="seg__btn" href="#about" data-tab="about" aria-selected="false"><?= icon('list') ?> About Subject</a>
  </div>

  <aside class="chapters-card" id="chapters" data-panel="chapters" aria-label="Chapters">
    <h2>Chapters</h2>
    <ol>
      <?php foreach ($chapters as $c): $cur = $c['id'] === $chapter['id']; ?>
      <li><a class="ch-row<?= $cur ? ' is-current' : '' ?><?= $c['state'] === 'locked' ? ' is-locked' : '' ?>" href="<?= $chapter_url($c) ?>"<?= $cur ? ' aria-current="page"' : '' ?>>
        <span class="ch-row__no"><?= (int) $c['chapter_no'] ?></span>
        <span class="ch-row__name"><?= e($c['name_bn'] ?: $c['name']) ?><?= $cur && $c['name_bn'] ? ' (' . e($c['name']) . ')' : '' ?></span>
        <?php if ($cur): ?><?= icon('arrow-right') ?>
        <?php elseif ($c['state'] === 'done'): ?><span class="st st--done" title="Completed"><?= icon('check') ?></span><span class="sr-only">Completed</span>
        <?php elseif ($c['state'] === 'started'): ?><span class="st st--started" title="In progress"></span><span class="sr-only">In progress</span>
        <?php elseif ($c['state'] === 'locked'): ?><span class="st st--locked" title="Coming soon"><?= icon('lock') ?></span><span class="sr-only">Coming soon</span>
        <?php endif; ?>
      </a></li>
      <?php endforeach; ?>
    </ol>
  </aside>

  <div class="about-card" id="about" data-panel="about" hidden>
    <h2>About <?= e($subject['name']) ?></h2>
    <p><?= e($subject['description'] ?: 'NCTB curriculum, ' . $class['name'] . '.') ?></p>
    <p class="amcq-muted"><?= $total_chapters ?> chapters · <?= QUIZ_QUESTION_COUNT ?> MCQs per quiz · Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz</p>
  </div>

  <div class="chapter-main" data-panel="chapters">
    <div class="ch-head">
      <span class="ch-head__no"><?= (int) $chapter['chapter_no'] ?></span>
      <div class="ch-head__title"><h2><?= e(chapter_label($chapter)) ?></h2><p><?= e($class['name']) ?> - <?= e($subject['name']) ?></p></div>
      <?php if ($topic_count): ?>
      <div class="mini-card">
        <small>Chapter Progress</small>
        <div class="mini-card__row"><div class="amcq-progress"><div class="amcq-progress__bar" style="width: <?= round($topics_done * 100 / $topic_count) ?>%"></div></div><b><?= $topics_done ?> / <?= $topic_count ?></b></div>
      </div>
      <?php endif; ?>
      <div class="mini-card mini-card--icon"><?= icon('clock') ?><div><small>Estimated Time</small><b><?= max(1, $playable_topics) * QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60 ?> Minutes</b></div></div>
    </div>

    <?php if ( ! $topics): ?>
      <div class="empty-card"><span class="round round--blue"><?= icon('lock') ?></span>
        <p><strong>Questions for this chapter are coming soon.</strong><br>Our teachers are preparing and checking them. Try another chapter in the meantime.</p></div>
    <?php endif; ?>

    <?php foreach ($topics as $t): $done = $t['best'] !== NULL; $is_next = $t['id'] === $next_id; ?>
    <div class="topic-row<?= $is_next ? ' is-next' : '' ?><?= $t['playable'] ? '' : ' is-locked' ?>">
      <span class="topic-row__state<?= $done ? ' is-done' : '' ?>"><?= icon( ! $t['playable'] ? 'lock' : ($done ? 'check' : 'play')) ?></span>
      <div class="topic-row__body">
        <strong><span class="topic-row__code"><?= e($t['code']) ?></span> <?= e($t['name']) ?></strong>
        <span><?= e($t['summary']) ?><?= $done ? ' · Best ' . pct($t['best']) : '' ?></span>
      </div>
      <span class="topic-row__count"><?= QUIZ_QUESTION_COUNT ?> MCQs</span>
      <?php if ( ! $t['playable']): ?>
        <span class="amcq-btn amcq-btn--muted topic-row__btn" aria-disabled="true">Coming Soon</span>
      <?php elseif ($done): ?>
        <?= $start($t['id'], icon('refresh') . ' Practice Again', 'amcq-btn--tint topic-row__btn') ?>
      <?php else: ?>
        <?= $start($t['id'], 'Start Practice ' . icon('arrow-right'), 'amcq-btn--primary topic-row__btn') ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if ( ! $user && $topics): ?>
      <p class="note"><?= icon('bulb') ?> You can try <?= GUEST_FREE_QUIZZES ?> quizzes without an account. After that, quizzes are Tk <?= number_format(QUIZ_FEE_TAKA) ?> each.</p>
    <?php endif; ?>

    <div class="tips-banner">
      <div>
        <h3><span class="tips-banner__bulb"><?= icon('bulb') ?></span> Learning Tips</h3>
        <ul>
          <li><?= icon('check') ?>প্রতি অধ্যায়ের MCQ অনুশীলন করুন</li>
          <li><?= icon('check') ?>ভুল উত্তরগুলোর ব্যাখ্যা অবশ্যই পড়ুন</li>
          <li><?= icon('check') ?>নিয়মিত অনুশীলন করলে আপনার দক্ষতা বাড়বে</li>
          <li><?= icon('check') ?>অধ্যায় শেষ হলে পরবর্তী অধ্যায়ে যান</li>
        </ul>
      </div>
      <img src="<?= asset('img/design/books-tips.jpg') ?>" width="405" height="192" alt="">
    </div>
  </div>
</section>
