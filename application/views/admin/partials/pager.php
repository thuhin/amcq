<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var int $page_no @var int $pages @var int $total @var string $base @var array $query (current filters) */
$url = function ($n) use ($base, $query) {
	return site_url($base . '?' . http_build_query(array_filter($query, function ($v) { return $v !== '' && $v !== NULL; }) + array('page' => $n)));
};
?>
<nav class="pager" aria-label="Pages">
  <span class="amcq-muted"><?= number_format($total) ?> in total · page <?= (int) $page_no ?> of <?= (int) $pages ?></span>
  <span class="admin-actions">
    <?php if ($page_no > 1): ?><a class="amcq-btn amcq-btn--ghost amcq-btn--sm" href="<?= e($url($page_no - 1)) ?>"><?= icon('arrow-left') ?> Previous</a><?php endif; ?>
    <?php if ($page_no < $pages): ?><a class="amcq-btn amcq-btn--ghost amcq-btn--sm" href="<?= e($url($page_no + 1)) ?>">Next <?= icon('arrow-right') ?></a><?php endif; ?>
  </span>
</nav>
