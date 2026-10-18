<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $rows @var int $total @var array $filters @var array $chapters @var int $page_no @var int $pages @var array $by_status */
?>
<div class="admin-head">
  <h1>Questions</h1>
  <div class="admin-head__actions">
    <a class="amcq-btn amcq-btn--primary" href="<?= site_url('admin/questions/new' . ($filters['chapter_id'] ? '?chapter_id=' . (int) $filters['chapter_id'] : '')) ?>"><?= icon('plus') ?> New question</a>
  </div>
</div>

<nav class="tabs admin-tabs-sub" aria-label="Question status">
  <a class="tab<?= $filters['status'] === '' ? ' is-active' : '' ?>" href="<?= site_url('admin/questions') ?>">All <span class="amcq-muted"><?= (int) array_sum($by_status) ?></span></a>
  <?php foreach (Question_model::STATUSES as $key => $label): ?>
    <a class="tab<?= $filters['status'] === $key ? ' is-active' : '' ?>" href="<?= site_url('admin/questions?status=' . $key) ?>"><?= e($label) ?> <span class="amcq-muted"><?= (int) $by_status[$key] ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="admin-filters" method="get" action="<?= site_url('admin/questions') ?>">
  <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
  <label class="field"><span class="field__label">Search</span>
    <input class="field__input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Words, or a number"></label>
  <label class="field"><span class="field__label">Chapter</span>
    <select class="field__input" name="chapter_id"><option value="">All chapters</option>
      <?php foreach ($chapters as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $filters['chapter_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['class_name'] . ' › ' . $c['subject_name'] . ' › ' . $c['name']) ?></option><?php endforeach; ?>
    </select></label>
  <label class="field"><span class="field__label">Difficulty</span>
    <select class="field__input" name="difficulty"><option value="">Any</option>
      <?php foreach (Question_model::DIFFICULTIES as $k => $l): ?><option value="<?= e($k) ?>"<?= $filters['difficulty'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select></label>
  <label class="field"><span class="field__label">Origin</span>
    <select class="field__input" name="origin"><option value="">Any</option>
      <?php foreach (Question_model::ORIGINS as $k => $l): ?><option value="<?= e($k) ?>"<?= $filters['origin'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select></label>
  <button class="amcq-btn amcq-btn--secondary" type="submit"><?= icon('search') ?> Filter</button>
</form>

<?php if ($rows): ?>
<section class="amcq-card">
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only">Questions</caption>
      <thead><tr><th scope="col">#</th><th scope="col">Question</th><th scope="col">Chapter</th><th scope="col">Level</th><th scope="col">Status</th><th scope="col" class="num">Served</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="num"><?= (int) $r['id'] ?></td>
          <th scope="row" class="admin-table__main stem"><a href="<?= site_url('admin/questions/' . (int) $r['id']) ?>"><?= math_text(mb_strimwidth($r['stem'], 0, 110, '…')) ?></a>
            <small class="amcq-muted"><?= e(Question_model::ORIGINS[$r['origin']]) ?> · updated <?= e(time_ago($r['updated_at'])) ?></small></th>
          <td><?= e($r['chapter_name']) ?><small class="amcq-muted"><?= e($r['class_name']) ?> · <?= e($r['subject_name']) ?></small></td>
          <td><?= e(Question_model::DIFFICULTIES[$r['difficulty']]) ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="num"><?= (int) $r['times_served'] ?><?php if ($r['times_served']): ?><small class="amcq-muted"><?= pct(100 * $r['times_correct'] / $r['times_served']) ?> right</small><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('admin/partials/pager', array('page_no' => $page_no, 'pages' => $pages, 'total' => $total, 'base' => 'admin/questions', 'query' => $filters)); ?>
</section>
<?php else: ?>
<div class="empty-card"><p>No questions match these filters.</p>
  <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('admin/questions') ?>">Clear filters</a></div>
<?php endif; ?>
