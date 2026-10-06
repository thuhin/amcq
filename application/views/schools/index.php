<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="amcq-container page-pad narrow">
  <h1>Top Schools This Week</h1>
  <p class="amcq-muted"><?= $week ? 'Week of ' . date('j F Y', strtotime($week)) . '. ' : '' ?>Average score is per quiz, so schools are compared on quality, not size.</p>
  <div class="seg seg--inline">
    <a class="seg__btn<?= $by === 'average' ? ' is-active' : '' ?>" href="<?= site_url('schools') ?>">Average Score</a>
    <a class="seg__btn<?= $by === 'total' ? ' is-active' : '' ?>" href="<?= site_url('schools?by=total') ?>">Total Score</a>
  </div>
  <div class="schools-card">
    <table class="mini-table">
      <thead><tr><th>#</th><th>School Name</th><th class="num">Quizzes</th><th class="num"><?= $by === 'total' ? 'Total Score' : 'Average Score' ?></th></tr></thead>
      <tbody>
      <?php foreach ($schools as $i => $r): ?>
        <tr<?= $mine && $mine['name'] === $r['name'] ? ' class="is-me"' : '' ?>><td><?= $i < 3 ? '<span class="medal medal--' . ($i + 1) . '">' . icon('medal') . '</span>' : $i + 1 ?></td><td><?= e($r['name']) ?></td>
          <td class="num"><?= number_format($r['quizzes']) ?></td>
          <td class="num"><?= $by === 'total' ? number_format($r['total_score']) : number_format($r['average_score'], 3) ?></td></tr>
      <?php endforeach; ?>
      <?php if ( ! $schools): ?><tr><td colspan="4" class="amcq-muted">Rankings appear after the first week of practice.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
