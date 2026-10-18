<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $rows @var int $total @var string $status @var array $counts @var int $page_no @var int $pages */
$tabs = array('pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Not approved');
?>
<div class="admin-head"><h1>Correct Me reports</h1></div>
<p class="amcq-muted">Students report questions they think are wrong. Approving earns them <?= (int) CORRECTION_APPROVED_POINTS ?> Academic Point<?= CORRECTION_APPROVED_POINTS === 1 ? '' : 's' ?>.</p>

<nav class="tabs admin-tabs-sub" aria-label="Report status">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="tab<?= $status === $key ? ' is-active' : '' ?>" href="<?= site_url('admin/corrections?status=' . $key) ?>"><?= e($label) ?> <span class="amcq-muted"><?= (int) $counts[$key] ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if ($rows): ?>
<section class="amcq-card">
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only"><?= e($tabs[$status]) ?> reports</caption>
      <thead><tr><th scope="col">Question</th><th scope="col">What the student says</th><th scope="col">From</th><th scope="col"><?= $status === 'pending' ? 'Waiting' : 'Decided' ?></th><th scope="col"><span class="sr-only">Open</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <th scope="row" class="admin-table__main stem"><a href="<?= site_url('admin/corrections/' . (int) $r['id']) ?>"><?= math_text(mb_strimwidth($r['stem'], 0, 90, '…')) ?></a>
            <small class="amcq-muted">Question #<?= (int) $r['question_id'] ?> · <?= status_badge($r['question_status']) ?></small></th>
          <td class="stem"><?= e(mb_strimwidth((string) $r['what_is_wrong'], 0, 100, '…')) ?></td>
          <td><?= e($r['student']) ?></td>
          <td><?= e(time_ago($status === 'pending' ? $r['created_at'] : $r['reviewed_at'])) ?></td>
          <td><a class="amcq-btn amcq-btn--secondary amcq-btn--sm" href="<?= site_url('admin/corrections/' . (int) $r['id']) ?>"><?= $status === 'pending' ? 'Review' : 'View' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('admin/partials/pager', array('page_no' => $page_no, 'pages' => $pages, 'total' => $total, 'base' => 'admin/corrections', 'query' => array('status' => $status))); ?>
</section>
<?php else: ?>
<div class="empty-card"><?= icon('check-circle', 'ok') ?><p><?= $status === 'pending' ? 'No reports are waiting. Nice.' : 'Nothing here yet.' ?></p></div>
<?php endif; ?>
