<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="amcq-container page-pad narrow">
  <h1>Search</h1>
  <form class="search-box" action="<?= site_url('search') ?>" method="get" role="search">
    <?= icon('search') ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search chapters and topics, e.g. ভগ্নাংশ or Fractions" aria-label="Search" autofocus>
    <button class="amcq-btn amcq-btn--primary" type="submit">Search</button>
  </form>
  <?php if ($q !== '' && mb_strlen($q) < 2): ?><p class="amcq-muted">Type at least 2 characters.</p>
  <?php elseif ($q !== '' && ! $results): ?><p class="amcq-muted">Nothing found for “<?= e($q) ?>”.</p>
  <?php elseif ($results): ?>
    <ul class="result-list">
      <?php foreach ($results as $r):
        $url = 'practice/' . $r['class_slug'] . '/' . $r['subject_slug'] . ($r['chapter_slug'] ? '?chapter=' . $r['chapter_slug'] : ''); ?>
        <li><a href="<?= site_url($url) ?>">
          <span class="badge badge--muted"><?= ucfirst($r['kind']) ?></span>
          <strong><?= e(($r['code'] ? $r['code'] . ' ' : '') . ($r['name_bn'] ? $r['name_bn'] . ' (' . $r['name'] . ')' : $r['name'])) ?></strong>
          <small class="amcq-muted"><?= e($r['subject']) ?></small><?= icon('arrow-right') ?></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
