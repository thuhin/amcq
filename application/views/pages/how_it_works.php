<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container narrow">
    <h1>How It Works</h1>
    <p class="amcq-muted">Choose a chapter → take <?= QUIZ_QUESTION_COUNT ?> MCQs → see your result → learn from mistakes → build your rank.</p>
    <ol class="steps steps--big">
      <li><b>1</b><div><strong>Pick a chapter</strong><span class="amcq-muted">Choose your class, subject and chapter, or one topic inside it.</span></div></li>
      <li><b>2</b><div><strong>Answer <?= QUIZ_QUESTION_COUNT ?> MCQs</strong><span class="amcq-muted"><?= QUIZ_MIX_EASY ?> easy, <?= QUIZ_MIX_MEDIUM ?> medium and <?= QUIZ_MIX_HARD ?> hard, in <?= QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60 ?> minutes. Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz; your first <?= GUEST_FREE_QUIZZES ?> are free without an account.</span></div></li>
      <li><b>3</b><div><strong>Learn from mistakes</strong><span class="amcq-muted">Every question has an explanation and a textbook source. Spot an error? Use “Correct Me”.</span></div></li>
      <li><b>4</b><div><strong>Build your rank</strong><span class="amcq-muted">Score <?= STREAK_PASS_PERCENTAGE ?>%+ on <?= STREAK_REQUIRED_QUIZZES ?> quizzes within <?= STREAK_WINDOW_HOURS ?> hours to earn an Academic Point. Master chapters, join the competition, climb the tiers.</span></div></li>
    </ol>
    <div class="amcq-card">
      <h2 class="h3">Three things we keep separate</h2>
      <ul class="three">
        <li><b class="amcq-money">৳ Wallet</b><span>Real money for quizzes and competition entry.</span></li>
        <li><b class="amcq-points">Academic Points</b><span>Your reputation. Never spent, never lost.</span></li>
        <li><b class="amcq-score">Quiz score</b><span>How you did on one quiz.</span></li>
      </ul>
    </div>
    <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="<?= site_url('practice') ?>">Start a Quiz <?= icon('arrow-right') ?></a>
  </div>
</section>
