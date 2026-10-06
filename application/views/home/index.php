<?php defined('BASEPATH') OR exit('No direct script access allowed');
$pool = $competition ? bd_number($competition['prize_pool']) : '';
list($sel_icon, $sel_color) = subject_style($selected['slug']);
$sel_ready = $selected['playable_chapters'] > 0;
$medium_icons = array('bangla-medium' => 'book-open', 'english-version' => 'ab', 'english-medium' => 'globe');
?>
<!-- ============ Hero (design 07) ============ -->
<section class="home-hero">
  <div class="amcq-container home-hero__inner">
    <div class="home-hero__copy">
      <p class="pill"><span class="pill__star"><?= icon('star') ?></span> Bangladesh's First Curriculum-Based MCQ Platform</p>
      <h1 class="home-hero__title">Better Practice<br><span>Brighter Future</span></h1>
      <p class="home-hero__bangla">অনুশীলন • শেখা • প্রতিযোগিতা</p>
      <p class="home-hero__lede">Curriculum-based MCQ practice for Class 5 to SSC. Learn with instant explanations, build your rank, and join the national competition.</p>
      <div class="home-hero__cta">
        <a class="amcq-btn amcq-btn--primary amcq-btn--lg" href="#start">Start Practicing Now <?= icon('arrow-right') ?></a>
        <a class="amcq-btn amcq-btn--secondary amcq-btn--lg" href="<?= site_url('how-it-works#video') ?>"><span class="play-dot"><?= icon('play') ?></span> Watch Video</a>
      </div>
      <ul class="feature-row">
        <li><span class="round round--green"><?= icon('calculator') ?></span>Only<br>Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz</li>
        <li><span class="round round--purple"><?= icon('bulb') ?></span>Learn with<br>explanations</li>
        <li><span class="round round--amber"><?= icon('trophy') ?></span>Build your<br>lifetime rank</li>
        <li><span class="round round--pink"><?= icon('users') ?></span>Join national<br>competition</li>
      </ul>
    </div>
    <div class="home-hero__art">
      <img src="<?= asset('img/design/hero.jpg') ?>" width="570" height="385"
           alt="A student practising on a laptop, with score, streak, rank and points cards">
    </div>
  </div>
</section>

<!-- ============ Four-step selector ============ -->
<section class="amcq-container" id="start">
  <div class="selector">
    <div class="selector__head">
      <span class="chip chip--blue chip--lg"><?= icon('book-open') ?></span>
      <div>
        <h2>Start Practicing — Select Your Curriculum, Class and Subject</h2>
        <p class="amcq-muted">Follow the NCTB syllabus and practice chapter-wise MCQs with instant explanations.</p>
      </div>
    </div>

    <div class="selector__steps">
      <div class="step step--curriculum">
        <h3 class="step__title step__title--ribbon"><b>1</b> Choose Your Curriculum</h3>
        <ul class="medium-cards">
          <?php foreach ($mediums as $m): ?>
          <li class="medium-card<?= $m['is_active'] ? ' is-selected' : ' is-locked' ?>">
            <?php if ($m['is_active']): ?><span class="medium-card__check" aria-label="Selected"><?= icon('check') ?></span>
            <?php else: ?><span class="medium-card__lock" aria-hidden="true"><?= icon('lock') ?></span><?php endif; ?>
            <span class="medium-card__icon"><?= icon(isset($medium_icons[$m['slug']]) ? $medium_icons[$m['slug']] : 'book') ?></span>
            <strong><?= e($m['name']) ?></strong>
            <small><?= e($m['description']) ?></small>
            <?php if ( ! $m['is_active']): ?><span class="soon-tag">Coming Soon</span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="step">
        <h3 class="step__title"><b>2</b> Select Your Class</h3>
        <ul class="pick-list">
          <?php foreach ($classes as $c): ?>
            <li>
              <?php if ($c['is_active']): ?>
                <a class="pick-row is-selected" href="<?= site_url('?subject=' . $selected['slug'] . '#start') ?>" aria-current="true">
                  <span class="pick-row__icon pick-row__icon--blue"><?= icon('file') ?></span><span><?= e($c['name']) ?></span><?= icon('arrow-right', 'pick-row__go') ?></a>
              <?php else: ?>
                <span class="pick-row is-locked" aria-disabled="true"><span><?= e($c['name']) ?></span><?= icon('lock', 'pick-row__go') ?><span class="sr-only">Coming soon</span></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="step">
        <h3 class="step__title"><b>3</b> Choose a Subject</h3>
        <ul class="pick-list" data-subjects>
          <?php foreach ($subjects as $s): list($ic, $col) = subject_style($s['slug']); $on = $s['id'] === $selected['id']; ?>
            <li>
              <a class="pick-row<?= $on ? ' is-selected' : '' ?>" href="<?= site_url('?subject=' . $s['slug'] . '#start') ?>"
                 data-subject="<?= e($s['slug']) ?>" data-name="<?= e($s['name']) ?>" data-icon="<?= $ic ?>" data-color="<?= $col ?>"
                 data-ready="<?= $s['playable_chapters'] ? 1 : 0 ?>" data-url="<?= site_url('practice/' . $class['slug'] . '/' . $s['slug']) ?>"<?= $on ? ' aria-current="true"' : '' ?>>
                <span class="pick-row__icon pick-row__icon--<?= $col ?>"><?= icon($ic) ?></span><span><?= e($s['name']) ?></span><?= icon('arrow-right', 'pick-row__go') ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="step step--go" data-go>
        <h3 class="step__title"><b>4</b> Start Practicing</h3>
        <div class="go-card">
          <span class="go-card__art" aria-hidden="true"><?= icon('checklist') ?><?= icon('clock', 'go-card__clock') ?></span>
          <strong data-go-title><?= e($class['name']) ?> • <?= e($selected['name']) ?></strong>
          <span data-go-sub><?= $sel_ready ? 'Chapter-wise MCQs' : 'Questions coming soon' ?></span>
          <span>Only Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz</span>
          <a class="amcq-btn amcq-btn--primary" data-go-link href="<?= site_url('practice/' . $class['slug'] . '/' . $selected['slug']) ?>">Start Practicing Now <?= icon('arrow-right') ?></a>
        </div>
      </div>
    </div>
    <!-- Mobile (design 04): one full-width call to action under the steps. -->
    <a class="amcq-btn amcq-btn--primary amcq-btn--block amcq-btn--lg selector__mobile-cta" data-go-link href="<?= site_url('practice/' . $class['slug'] . '/' . $selected['slug']) ?>">
      <span>Start Practice for <?= e($class['name']) ?> - <span data-go-name><?= e($selected['name']) ?></span></span> <?= icon('arrow-right') ?></a>
  </div>
</section>

<!-- ============ Stats strip (real figures) ============ -->
<section class="amcq-container">
  <ul class="stats-bar">
    <li><span class="chip chip--blue chip--lg"><?= icon('users') ?></span><div><strong><?= number_format($stats['questions']) ?>+</strong><span>High-quality MCQs</span><span>(<?= e($class['name']) ?> - initial launch)</span></div></li>
    <li><span class="chip chip--blue chip--lg"><?= icon('book-open') ?></span><div><strong><?= (int) $stats['subjects'] ?></strong><span>Subjects</span></div></li>
    <li><span class="chip chip--blue chip--lg"><?= icon('users') ?></span><div><small>For</small><strong><?= e($class['name']) ?> (Launch)</strong><span>More classes coming soon</span></div></li>
    <?php if ($competition): ?>
    <li><span class="chip chip--blue chip--lg"><?= icon('trophy') ?></span><div><strong><?= (int) $winners ?> Winners</strong><strong>Tk <?= $pool ?></strong><span>National Competition Prize Pool</span></div></li>
    <?php endif; ?>
  </ul>
</section>

<!-- ============ Competition + Top Schools ============ -->
<section class="amcq-container comp-schools">
  <?php if ($competition): ?>
  <div class="comp-banner">
    <div class="comp-banner__body">
      <h2><?= e($competition['name']) ?> <span class="badge-pink">Tk <?= number_format($competition['entry_fee']) ?> Entry</span></h2>
      <p>Test your knowledge. Compete with students across Bangladesh.</p>
      <ul class="comp-banner__facts">
        <li><?= icon('trophy', 'gold') ?><span><strong>Top <?= rtrim(rtrim($competition['qualify_percent'], '0'), '.') ?>%</strong>Qualify for Final Round</span></li>
        <li><?= icon('star', 'gold') ?><span><strong><?= (int) $winners ?> Winners</strong>Tk <?= $pool ?> Prize Pool</span></li>
        <li><?= icon('calendar') ?><span><strong><?= $competition['round1_opens_at'] ? date('j M Y', strtotime($competition['round1_opens_at'])) : 'Coming Soon' ?></strong>Register and be ready</span></li>
      </ul>
      <a class="amcq-btn amcq-btn--light" href="<?= site_url('competition') ?>">Learn More <?= icon('arrow-right') ?></a>
    </div>
    <img class="comp-banner__art" src="<?= asset('img/design/trophy.jpg') ?>" width="140" height="240" alt="">
  </div>
  <?php endif; ?>

  <div class="schools-card" data-tabs>
    <div class="schools-card__head">
      <h2><?= icon('school') ?> Top Schools This Week</h2>
      <a class="link-arrow" href="<?= site_url('schools') ?>">View All Schools <?= icon('arrow-right') ?></a>
    </div>
    <div class="schools-card__body">
      <div class="schools-card__table">
        <div class="seg" role="tablist">
          <a role="tab" class="seg__btn<?= $school_tab === 'average' ? ' is-active' : '' ?>" href="<?= site_url('?schools=average#schools') ?>" data-tab="average" aria-selected="<?= $school_tab === 'average' ? 'true' : 'false' ?>">Average Score</a>
          <a role="tab" class="seg__btn<?= $school_tab === 'total' ? ' is-active' : '' ?>" href="<?= site_url('?schools=total#schools') ?>" data-tab="total" aria-selected="<?= $school_tab === 'total' ? 'true' : 'false' ?>">Total Score</a>
        </div>
        <?php foreach (array('average' => $top_avg, 'total' => $top_total) as $tab => $rows): ?>
        <table class="mini-table" data-panel="<?= $tab ?>" id="<?= $tab === 'average' ? 'schools' : 'schools-total' ?>"<?= $school_tab === $tab ? '' : ' hidden' ?>>
          <thead><tr><th>#</th><th>School Name</th><th class="num"><?= $tab === 'average' ? 'Average Score' : 'Total Score' ?></th></tr></thead>
          <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr><td><?= $i < 3 ? '<span class="medal medal--' . ($i + 1) . '" aria-label="Rank ' . ($i + 1) . '">' . icon('medal') . '</span>' : $i + 1 ?></td>
              <td><?= e($r['name']) ?></td>
              <td class="num"><?= $tab === 'average' ? number_format($r['average_score'], 3) : number_format($r['total_score']) ?></td></tr>
          <?php endforeach; ?>
          <?php if ( ! $rows): ?><tr><td colspan="3" class="amcq-muted">Rankings appear after the first week of practice.</td></tr><?php endif; ?>
          </tbody>
        </table>
        <?php endforeach; ?>
      </div>
      <div class="your-school">
        <span class="your-school__icon"><?= icon('school') ?></span>
        <strong>Your School</strong>
        <?php if ($my_school): ?>
          <b class="your-school__rank">#<?= (int) $my_school['position'] ?></b>
          <small>Among <?= number_format($my_school['of']) ?> schools</small>
          <small>Average Score</small><b><?= number_format($my_school['average'], 3) ?></b>
          <small>Total Score</small><b><?= number_format($my_school['total']) ?></b>
        <?php elseif ($user): ?>
          <small>Add your school in your profile to see its rank.</small>
          <a class="link-arrow" href="<?= site_url('profile') ?>">Add School <?= icon('arrow-right') ?></a>
        <?php else: ?>
          <small>Sign in and add your school to see where it ranks.</small>
          <a class="link-arrow" href="<?= site_url('signup') ?>">Sign Up <?= icon('arrow-right') ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ============ Why AcademicMCQ ============ -->
<section class="amcq-container home-section">
  <h2>Why AcademicMCQ?</h2>
  <p class="amcq-muted">More than just MCQs — a complete learning ecosystem</p>
  <ul class="why-grid">
    <li class="why why--green"><span class="round round--green"><?= icon('bolt') ?></span><div><strong>Practice</strong><p>Only Tk <?= number_format(QUIZ_FEE_TAKA) ?> per quiz. No subscription.</p></div></li>
    <li class="why why--blue"><span class="round round--blue"><?= icon('bulb') ?></span><div><strong>Learn</strong><p>Detailed explanations with source references.</p></div></li>
    <li class="why why--purple"><span class="round round--purple"><?= icon('bar-up') ?></span><div><strong>Track Progress</strong><p>Streaks, points, tiers and lifetime ranking.</p></div></li>
    <li class="why why--pink"><span class="round round--pink"><?= icon('trophy') ?></span><div><strong>Compete</strong><p>Join national competition and win real prizes.</p></div></li>
    <li class="why why--amber"><span class="round round--amber"><?= icon('users') ?></span><div><strong>Contribute</strong><p>Use “Correct Me” to improve questions and build your profile.</p></div></li>
  </ul>
</section>

<!-- ============ What Students Say ============ -->
<?php if ($testimonials): ?>
<section class="amcq-container home-section">
  <div class="section-head">
    <h2>What Students Say</h2>
    <a class="link-arrow" href="<?= site_url('testimonials') ?>">View More Testimonials <?= icon('arrow-right') ?></a>
  </div>
  <ul class="testimonials">
    <?php foreach ($testimonials as $t): ?>
    <li class="testimonial">
      <?php if ($t['photo']): ?><img class="testimonial__photo" src="<?= asset($t['photo']) ?>" width="74" height="74" alt=""><?php endif; ?>
      <div>
        <p class="testimonial__quote">“<?= e($t['quote']) ?>”</p>
        <span class="stars" aria-label="<?= (int) $t['rating'] ?> out of 5"><?= str_repeat(icon('star'), (int) $t['rating']) ?></span>
        <p class="testimonial__who"><strong><?= e($t['student_name']) ?></strong>, <?= e($t['class_label']) ?><?php if ($t['school_name']): ?><br><span><?= e($t['school_name']) ?></span><?php endif; ?></p>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
