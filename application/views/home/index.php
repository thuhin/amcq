<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<section class="hero">
  <div class="amcq-container hero__inner">
    <div class="hero__copy">
      <h1 class="hero__title">Better Practice<br><span>Brighter Future</span></h1>
      <p class="hero__bangla">অনুশীলন • শেখা • প্রতিযোগিতা</p>
      <p class="hero__lede">Curriculum-based MCQ practice for Class 5 to SSC. Learn with instant explanations, build your rank, and join the national competition.</p>
      <div class="hero__cta">
        <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="<?= site_url('practice') ?>">Start Practicing <?= icon('arrow-right') ?></a>
        <a class="amcq-btn amcq-btn--secondary amcq-btn--lg" href="<?= site_url('how-it-works') ?>"><?= icon('play') ?> See How It Works</a>
      </div>
      <ul class="hero__features">
        <li><span class="chip chip--green"><?= icon('file') ?></span>Only Tk <?= number_format(QUIZ_FEE_TAKA) ?><br>per quiz</li>
        <li><span class="chip chip--purple"><?= icon('bulb') ?></span>Learn with<br>explanations</li>
        <li><span class="chip chip--amber"><?= icon('trophy') ?></span>Build your<br>lifetime rank</li>
        <li><span class="chip chip--pink"><?= icon('users') ?></span>Join national<br>competition</li>
      </ul>
    </div>

    <!-- Product visual, not a photo (guideline §4.1, §13): what a student builds. -->
    <div class="hero__visual" aria-hidden="true">
      <div class="float-card float-card--score"><span class="chip chip--green"><?= icon('target') ?></span><div><small>Mathematics</small><strong class="amcq-score">85%</strong><em class="ok">Excellent!</em></div></div>
      <div class="float-card float-card--rank"><span class="chip chip--amber"><?= icon('trophy') ?></span><div><small>Your Rank</small><strong>#1,250</strong><em>Bangladesh</em></div></div>
      <div class="float-card float-card--streak"><span class="chip chip--orange"><?= icon('flame') ?></span><div><small>Streak</small><strong>12</strong><em>quizzes</em></div></div>
      <div class="float-card float-card--points"><span class="chip chip--pink"><?= icon('shield') ?></span><div><small>Academic Points</small><strong class="amcq-points">530</strong><em>Silver Scholar</em></div></div>
      <div class="mcq-card">
        <p class="mcq-card__q">নিচের ভগ্নাংশগুলোর মধ্যে কোনটি বৃহত্তম?</p>
        <span class="mcq-card__opt is-picked"><b>A</b><?= math_text('3/4') ?></span>
        <span class="mcq-card__opt"><b>B</b><?= math_text('5/8') ?></span>
        <span class="mcq-card__opt"><b>C</b><?= math_text('2/3') ?></span>
      </div>
    </div>
  </div>
</section>

<section class="amcq-container">
  <ul class="stats-strip">
    <li><span class="chip chip--blue"><?= icon('users') ?></span><div><strong><?= points($stats['questions']) ?>+</strong><small>Quality MCQs (Class 5 launch)</small></div></li>
    <li><span class="chip chip--blue"><?= icon('book') ?></span><div><strong><?= (int) $stats['subjects'] ?></strong><small>Subjects</small></div></li>
    <li><span class="chip chip--blue"><?= icon('school') ?></span><div><strong><?= e($class['name']) ?> (Launch)</strong><small>More classes coming soon</small></div></li>
    <?php if ($competition): ?>
    <li><span class="chip chip--blue"><?= icon('trophy') ?></span><div><strong><?= (int) $winners ?> Winners · <?= str_replace('.00', '', taka($competition['prize_pool'])) ?></strong><small>National Competition Prize Pool</small></div></li>
    <?php endif; ?>
  </ul>
</section>

<section class="section">
  <div class="amcq-container">
    <h2>Choose Your Curriculum / Medium</h2>
    <p class="amcq-muted">Select your education medium to see relevant classes, subjects and chapters.</p>
    <ul class="grid grid--3">
      <?php foreach ($mediums as $m): ?>
      <li class="amcq-card pick <?= $m['is_active'] ? 'pick--active' : 'pick--soon' ?>">
        <span class="chip chip--blue chip--lg"><?= icon($m['is_active'] ? 'book' : 'lock') ?></span>
        <div>
          <strong class="pick__title"><?= e($m['name']) ?></strong>
          <span class="amcq-muted pick__desc"><?= e($m['description']) ?></span>
          <span class="badge <?= $m['is_active'] ? 'badge--primary' : 'badge--muted' ?>"><?= $m['is_active'] ? 'Available Now' : 'Coming Soon' ?></span>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section section--tight">
  <div class="amcq-container">
    <h2>Choose Your Class and Start Practicing</h2>
    <p class="amcq-muted">Based on the NCTB curriculum (Bangla medium).</p>
    <ul class="grid grid--6">
      <?php foreach ($classes as $c): ?>
      <li>
        <?php if ($c['is_active']): ?>
          <a class="amcq-card pick pick--active pick--row" href="<?= site_url('practice?class=' . $c['slug']) ?>">
            <strong><?= e($c['name']) ?></strong><span class="pick__meta">Start Now <?= icon('arrow-right') ?></span>
          </a>
        <?php else: ?>
          <!-- Not a link: a disabled link is still focusable and announced as one. -->
          <div class="amcq-card pick pick--soon pick--row" aria-disabled="true">
            <strong><?= e($c['name']) ?></strong><span class="pick__meta"><?= icon('lock') ?> Coming Soon</span>
          </div>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section section--tight">
  <div class="amcq-container">
    <div class="section__head">
      <div><h2>Popular Subjects (<?= e($class['name']) ?>)</h2><p class="amcq-muted">Select a subject to see chapters and start practicing.</p></div>
      <a class="link-arrow" href="<?= site_url('practice') ?>">View All Subjects <?= icon('arrow-right') ?></a>
    </div>
    <ul class="grid grid--3">
      <?php foreach ($subjects as $i => $s): ?>
      <li>
        <a class="amcq-card subject" href="<?= site_url('practice/' . $class['slug'] . '/' . $s['slug']) ?>">
          <span class="chip chip--<?= array('green','blue','orange','purple','pink','amber')[$i % 6] ?>"><?= icon('book') ?></span>
          <span><strong><?= e($s['name']) ?></strong><small class="amcq-muted">Chapters: <?= (int) $s['chapter_count'] ?></small></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if ($competition): ?>
<section class="section section--tight">
  <div class="amcq-container">
    <div class="promo">
      <div class="promo__main">
        <h2 class="promo__title"><?= e($competition['name']) ?> <span class="badge badge--pink">Tk <?= number_format($competition['entry_fee']) ?> Entry</span></h2>
        <p>Test your knowledge. Compete with students across Bangladesh.</p>
      </div>
      <ul class="promo__facts">
        <li><?= icon('trophy') ?><span><strong>Top <?= rtrim(rtrim($competition['qualify_percent'], '0'), '.') ?>%</strong>Qualify for Final Round</span></li>
        <li><?= icon('star') ?><span><strong><?= (int) $winners ?> Winners</strong><?= str_replace('.00', '', taka($competition['prize_pool'])) ?> Prize Pool</span></li>
        <li><?= icon('calendar') ?><span><strong><?= $competition['round1_opens_at'] ? date('j M Y', strtotime($competition['round1_opens_at'])) : 'Coming Soon' ?></strong>Register and be ready</span></li>
      </ul>
      <a class="amcq-btn amcq-btn--light" href="<?= site_url('competition') ?>">Learn More <?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="amcq-container">
    <h2>Why AcademicMCQ?</h2>
    <p class="amcq-muted">More than just MCQs — a complete learning ecosystem.</p>
    <ul class="grid grid--5 why">
      <li class="why__card why__card--green"><span class="chip chip--green"><?= icon('flame') ?></span><div><strong>Practice</strong><p>Only Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz. No subscription.</p></div></li>
      <li class="why__card why__card--blue"><span class="chip chip--blue"><?= icon('bulb') ?></span><div><strong>Learn</strong><p>Detailed explanations with source references.</p></div></li>
      <li class="why__card why__card--purple"><span class="chip chip--purple"><?= icon('chart') ?></span><div><strong>Track Progress</strong><p>Streaks, points, tiers and lifetime ranking.</p></div></li>
      <li class="why__card why__card--pink"><span class="chip chip--pink"><?= icon('trophy') ?></span><div><strong>Compete</strong><p>Join the national competition and win real prizes.</p></div></li>
      <li class="why__card why__card--amber"><span class="chip chip--amber"><?= icon('users') ?></span><div><strong>Contribute</strong><p>Use “Correct Me” to improve questions and build your profile.</p></div></li>
    </ul>
  </div>
</section>

<section class="section section--tint">
  <div class="amcq-container two-col">
    <div>
      <h2>How It Works</h2>
      <ol class="steps">
        <li><b>1</b><div><strong>Pick a chapter</strong><span class="amcq-muted">Choose class, subject and chapter.</span></div></li>
        <li><b>2</b><div><strong>Answer <?= QUIZ_QUESTION_COUNT ?> MCQs</strong><span class="amcq-muted"><?= QUIZ_MIX_EASY ?> easy, <?= QUIZ_MIX_MEDIUM ?> medium, <?= QUIZ_MIX_HARD ?> hard.</span></div></li>
        <li><b>3</b><div><strong>Learn from mistakes</strong><span class="amcq-muted">Every answer comes with an explanation.</span></div></li>
        <li><b>4</b><div><strong>Save progress and build rank</strong><span class="amcq-muted">Earn Academic Points and climb the tiers.</span></div></li>
      </ol>
    </div>
    <div class="amcq-card">
      <div class="section__head"><h3>National Academic Ranking</h3><a class="link-arrow" href="<?= site_url('leaderboard') ?>">View Full Leaderboard <?= icon('arrow-right') ?></a></div>
      <?php if ($leaders): ?>
      <table class="table">
        <thead><tr><th>Rank</th><th>Student</th><th class="num">Points</th><th>Tier</th></tr></thead>
        <tbody>
        <?php foreach ($leaders as $i => $l): ?>
          <tr><td>#<?= $i + 1 ?></td><td><?= e($l['display_name']) ?></td><td class="num amcq-points"><?= points($l['total_points']) ?></td><td><?= e($l['tier']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <p class="amcq-muted">Be the first on the board.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="amcq-container trust">
    <span class="chip chip--blue chip--lg"><?= icon('shield') ?></span>
    <div>
      <h2>Affordable practice. Visible progress. No subscription pressure.</h2>
      <p class="amcq-muted">See where your child is improving and which chapters need more practice.</p>
    </div>
    <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice') ?>">Start a Quiz</a>
  </div>
</section>
