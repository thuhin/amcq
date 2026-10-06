<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="amcq-container page-pad">
  <h1>What Students Say</h1>
  <?php if ($items): ?>
  <ul class="testimonials testimonials--grid">
    <?php foreach ($items as $t): ?>
    <li class="testimonial">
      <?php if ($t['photo']): ?><img class="testimonial__photo" src="<?= asset($t['photo']) ?>" width="74" height="74" alt=""><?php endif; ?>
      <div><p class="testimonial__quote">“<?= e($t['quote']) ?>”</p>
        <span class="stars" aria-label="<?= (int) $t['rating'] ?> out of 5"><?= str_repeat(icon('star'), (int) $t['rating']) ?></span>
        <p class="testimonial__who"><strong><?= e($t['student_name']) ?></strong>, <?= e($t['class_label']) ?><?php if ($t['school_name']): ?><br><span><?= e($t['school_name']) ?></span><?php endif; ?></p></div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><p class="amcq-muted">Student stories are coming soon.</p><?php endif; ?>
</section>
