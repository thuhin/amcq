<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<section class="amcq-hero">
  <div class="amcq-container amcq-hero__inner">
    <div>
      <h1 class="amcq-hero__title">
        Better Practice<br>
        <span class="amcq-hero__title--accent">Brighter Future</span>
      </h1>
      <p class="amcq-hero__bangla">অনুশীলন • শেখা • প্রতিযোগিতা</p>
      <p class="amcq-hero__lede">
        Curriculum-based MCQ practice for Class 5 to SSC. Learn with instant
        explanations, build your rank, and join the national competition.
      </p>
      <div class="amcq-hero__cta">
        <a class="amcq-btn amcq-btn--primary" href="<?= base_url('practice') ?>">Start Practicing</a>
        <a class="amcq-btn amcq-btn--secondary" href="<?= base_url('how-it-works') ?>">See How It Works</a>
      </div>
      <p class="amcq-muted amcq-hero__note">
        No account needed to try. Tk <?= number_format(QUIZ_FEE_TAKA, 0) ?> per quiz.
      </p>
    </div>

    <!-- The learning loop, shown rather than described: score, streak, rank. -->
    <aside class="amcq-hero__stats" aria-label="What you build">
      <div class="amcq-card amcq-stat">
        <span class="amcq-muted">Mathematics</span>
        <strong class="amcq-score amcq-stat__value">85%</strong>
        <span class="amcq-stat__meta" style="color:var(--amcq-success)">Excellent</span>
      </div>
      <div class="amcq-card amcq-stat">
        <span class="amcq-muted">Your Rank</span>
        <strong class="amcq-points amcq-stat__value">#1,250</strong>
        <span class="amcq-stat__meta amcq-muted">Bangladesh</span>
      </div>
      <div class="amcq-card amcq-stat">
        <span class="amcq-muted">Streak</span>
        <strong class="amcq-score amcq-stat__value">12</strong>
        <span class="amcq-stat__meta amcq-muted">of <?= STREAK_REQUIRED_QUIZZES ?> quizzes</span>
      </div>
      <div class="amcq-card amcq-stat">
        <span class="amcq-muted">Academic Points</span>
        <strong class="amcq-points amcq-stat__value">530</strong>
        <span class="amcq-stat__meta amcq-muted">Silver Scholar</span>
      </div>
    </aside>
  </div>
</section>

<section class="amcq-section">
  <div class="amcq-container">
    <h2>Choose Your Class and Start Practicing</h2>
    <p class="amcq-muted">Based on the NCTB curriculum (Bangla medium).</p>

    <ul class="amcq-grid amcq-grid--6">
      <?php foreach ($classes as $class): ?>
        <li>
          <?php if ($class['active']): ?>
            <a class="amcq-card amcq-pick amcq-pick--active"
               href="<?= base_url('practice/' . $class['slug']) ?>">
              <strong><?= html_escape($class['name']) ?></strong>
              <span class="amcq-pick__meta">Start now &rarr;</span>
            </a>
          <?php else: ?>
            <!-- Not a link: a disabled anchor is still focusable and still
                 announced as a link, which misleads keyboard and screen
                 reader users about where they can go. -->
            <div class="amcq-card amcq-pick amcq-pick--soon" aria-disabled="true">
              <strong><?= html_escape($class['name']) ?></strong>
              <span class="amcq-pick__meta">Coming soon</span>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="amcq-section amcq-section--tint">
  <div class="amcq-container">
    <h2>Popular Subjects (Class 5)</h2>
    <p class="amcq-muted">Select a subject to see chapters and start practicing.</p>

    <ul class="amcq-grid amcq-grid--3">
      <?php foreach ($subjects as $subject): ?>
        <li class="amcq-card amcq-subject">
          <strong><?= html_escape($subject['name']) ?></strong>
          <span class="amcq-muted">Chapters: <?= (int) $subject['chapters'] ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="amcq-section">
  <div class="amcq-container">
    <h2>Why AcademicMCQ?</h2>
    <p class="amcq-muted">More than just MCQs — a complete learning ecosystem.</p>

    <ul class="amcq-grid amcq-grid--4">
      <li class="amcq-card">
        <h3>Tk <?= number_format(QUIZ_FEE_TAKA, 0) ?> Practice</h3>
        <p class="amcq-muted">Pay only when you practice. No subscription.</p>
      </li>
      <li class="amcq-card">
        <h3>Instant Explanation</h3>
        <p class="amcq-muted">Every wrong answer comes with a reason and a textbook source.</p>
      </li>
      <li class="amcq-card">
        <h3>Academic Points &amp; Rank</h3>
        <p class="amcq-muted">Build a lifetime record. Points never decrease.</p>
      </li>
      <li class="amcq-card">
        <h3>National Competition</h3>
        <p class="amcq-muted">Compete with students across Bangladesh.</p>
      </li>
    </ul>
  </div>
</section>

<section class="amcq-section">
  <div class="amcq-container">
    <div class="amcq-card amcq-promo">
      <div>
        <h2 class="amcq-promo__title">AcademicMCQ National Competition</h2>
        <p>Test your knowledge. Compete with students across Bangladesh.</p>
        <p class="amcq-promo__facts">
          Tk <?= number_format(COMPETITION_FEE_TAKA, 0) ?> entry &middot;
          Top 5% qualify for the final round &middot; 20 winners
        </p>
      </div>
      <a class="amcq-btn amcq-btn--secondary" href="<?= base_url('competition') ?>">Learn More</a>
    </div>
  </div>
</section>
