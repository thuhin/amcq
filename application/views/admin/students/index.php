<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $rows @var int $total @var array $filters @var int $page_no @var int $pages */
?>
<div class="admin-head">
  <div><h1>Students</h1><p class="admin-head__sub">Look up an account, its wallet, points and quizzes.</p></div>
</div>

<nav class="tabs admin-tabs-sub" aria-label="Account status">
  <?php foreach (array('' => 'All', 'active' => 'Active', 'suspended' => 'Suspended') as $k => $l): ?>
    <a class="tab<?= $filters['status'] === $k ? ' is-active' : '' ?>" href="<?= site_url('admin/students' . ($k ? '?status=' . $k : '')) ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</nav>

<form class="admin-filters" method="get" action="<?= site_url('admin/students') ?>">
  <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
  <label class="field"><span class="field__label">Search</span>
    <input class="field__input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, mobile number or ID"></label>
  <button class="amcq-btn amcq-btn--secondary" type="submit"><?= icon('search') ?> Search</button>
</form>

<?php if ($rows): ?>
<section class="amcq-card">
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only">Students</caption>
      <thead><tr><th scope="col">Student</th><th scope="col">Mobile</th><th scope="col">Class · School</th><th scope="col" class="num">Wallet</th><th scope="col" class="num">Points</th><th scope="col">Last sign-in</th><th scope="col">Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/students/' . (int) $r['id']) ?>"><?= e($r['name'] ?: 'Student #' . $r['id']) ?></a><small class="amcq-muted">#<?= (int) $r['id'] ?> · <?= e($r['display_name']) ?></small></th>
          <td><?= e($r['phone']) ?></td>
          <td><?= e($r['class_name'] ?: '—') ?><small class="amcq-muted"><?= e($r['school'] ?: 'No school') ?></small></td>
          <td class="num amcq-money"><?= taka($r['balance']) ?></td>
          <td class="num"><?= points($r['total_points']) ?></td>
          <td><?= $r['last_login_at'] ? e(time_ago($r['last_login_at'])) : '<span class="amcq-muted">never</span>' ?></td>
          <td><?= $r['status'] === 'active' ? '<span class="badge badge--ok">Active</span>' : '<span class="badge badge--bad">Suspended</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('admin/partials/pager', array('page_no' => $page_no, 'pages' => $pages, 'total' => $total, 'base' => 'admin/students', 'query' => $filters)); ?>
</section>
<?php else: ?>
<div class="empty-card"><p>No students match.</p></div>
<?php endif; ?>
