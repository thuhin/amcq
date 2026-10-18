<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $rows @var int $total @var array $filters @var array $by_status @var int $page_no @var int $pages */
?>
<div class="admin-head"><h1>Contact messages</h1></div>

<nav class="tabs admin-tabs-sub" aria-label="Message status">
  <a class="tab<?= $filters['status'] === '' ? ' is-active' : '' ?>" href="<?= site_url('admin/messages' . ($filters['topic'] ? '?topic=' . $filters['topic'] : '')) ?>">All <span class="amcq-muted"><?= (int) array_sum($by_status) ?></span></a>
  <?php foreach (Contact_model::STATUSES as $key => $label): ?>
    <a class="tab<?= $filters['status'] === $key ? ' is-active' : '' ?>" href="<?= site_url('admin/messages?status=' . $key . ($filters['topic'] ? '&topic=' . $filters['topic'] : '')) ?>"><?= e($label) ?> <span class="amcq-muted"><?= (int) $by_status[$key] ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="admin-filters" method="get" action="<?= site_url('admin/messages') ?>">
  <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
  <label class="field"><span class="field__label">Topic</span>
    <select class="field__input" name="topic"><option value="">Any topic</option>
      <?php foreach (Contact_model::TOPICS as $k => $l): ?><option value="<?= e($k) ?>"<?= $filters['topic'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
    </select></label>
  <button class="amcq-btn amcq-btn--secondary" type="submit"><?= icon('search') ?> Filter</button>
</form>

<?php if ($rows): ?>
<section class="amcq-card">
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only">Messages, newest first</caption>
      <thead><tr><th scope="col">Reference</th><th scope="col">From</th><th scope="col">Topic</th><th scope="col">Message</th><th scope="col">Status</th><th scope="col">Received</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $m): ?>
        <tr class="<?= $m['status'] === 'new' ? 'is-new' : '' ?>">
          <th scope="row"><a href="<?= site_url('admin/messages/' . (int) $m['id']) ?>"><?= e(Contact_model::reference($m['id'])) ?></a></th>
          <td><?= e($m['name']) ?><small class="amcq-muted"><?= e($m['phone'] ?: $m['email']) ?></small></td>
          <td><?= e(Contact_model::TOPICS[$m['topic']]) ?></td>
          <td class="stem"><?= e(mb_strimwidth($m['message'], 0, 100, '…')) ?></td>
          <td><?= status_badge($m['status']) ?></td>
          <td><?= e(time_ago($m['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $this->load->view('admin/partials/pager', array('page_no' => $page_no, 'pages' => $pages, 'total' => $total, 'base' => 'admin/messages', 'query' => $filters)); ?>
</section>
<?php else: ?>
<div class="empty-card"><p>No messages match.</p></div>
<?php endif; ?>
