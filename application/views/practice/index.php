<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container">
    <h1>Start Practice</h1>
    <p class="amcq-muted">Choose your class, subject and chapter.</p>

    <ul class="tabs" role="list">
      <?php foreach ($classes as $c): ?>
        <li><a class="tab<?= $c['id'] === $class['id'] ? ' is-active' : '' ?><?= $c['is_active'] ? '' : ' is-soon' ?>"
               href="<?= site_url('practice?class=' . $c['slug']) ?>"<?= $c['id'] === $class['id'] ? ' aria-current="page"' : '' ?>>
          <?= e($c['name']) ?><?= $c['is_active'] ? '' : ' ' . icon('lock') ?></a></li>
      <?php endforeach; ?>
    </ul>

    <?php if ( ! $class['is_active']): ?>
      <div class="empty">
        <span class="chip chip--blue chip--lg"><?= icon('lock') ?></span>
        <h2><?= e($class['name']) ?> is coming soon</h2>
        <p class="amcq-muted">We are launching with Class 5. More classes will follow.</p>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice?class=class-5') ?>">Practice Class 5</a>
      </div>
    <?php else: ?>
      <ul class="grid grid--3">
        <?php foreach ($subjects as $i => $s): list($ic, $col) = subject_style($s['slug']); ?>
        <li>
          <a class="amcq-card subject subject--lg" href="<?= site_url('practice/' . $class['slug'] . '/' . $s['slug']) ?>">
            <span class="round round--<?= $col ?>"><?= icon($ic) ?></span>
            <span class="subject__body">
              <strong><?= e($s['name']) ?></strong>
              <?php if ($s['name_bn']): ?><span class="amcq-muted"><?= e($s['name_bn']) ?></span><?php endif; ?>
              <small class="amcq-muted"><?= (int) $s['chapter_count'] ?> chapters ·
                <?= $s['playable_chapters'] ? (int) $s['playable_chapters'] . ' ready to practise' : 'coming soon' ?></small>
            </span>
            <?= icon('arrow-right', 'subject__go') ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if ( ! $user): ?>
        <p class="note"><?= icon('bulb') ?> You can try <?= GUEST_FREE_QUIZZES ?> quizzes without creating an account.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
