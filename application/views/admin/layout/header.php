<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $page  @var array|null $admin  @var array|null $flash  @var array $attention */
$initials = '';
if ($admin) {
	foreach (preg_split('/\s+/', trim($admin['name'])) as $w) {
		$initials .= mb_strtoupper(mb_substr($w, 0, 1));
	}
	$initials = mb_substr($initials, 0, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($page['title']) ?> · AcademicMCQ Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('css/brand.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="<?= asset('css/design.css') ?>">
</head>
<body class="admin<?= $admin ? ' admin--in' : '' ?>">
<?php $this->load->view('partials/icons'); ?>
<a class="skip-link" href="#main">Skip to content</a>

<?php if ($admin): ?>
<aside class="admin-sidebar" aria-label="Admin sections">
  <a class="admin-sidebar__brand" href="<?= site_url('admin') ?>" aria-label="AcademicMCQ admin home">
    <span class="admin-sidebar__logo"><?php $this->load->view('partials/logo'); ?></span>
    <span><strong>Academic<span>MCQ</span></strong><small>Admin panel</small></span>
  </a>
  <nav class="admin-sidebar__nav"><?php $this->load->view('admin/partials/nav', array('variant' => 'side')); ?></nav>
  <div class="admin-sidebar__foot">
    <a class="admin-nav__link" href="<?= site_url('') ?>" target="_blank" rel="noopener"><?= icon('globe') ?><span>View site</span><?= icon('arrow-right', 'admin-nav__ext') ?></a>
  </div>
</aside>
<?php endif; ?>

<div class="admin-frame">
  <header class="admin-bar">
    <?php if ($admin): ?>
      <!-- Narrow screens: the sidebar becomes this button. -->
      <details class="mobile-menu admin-mobile-menu">
        <summary aria-label="Menu"><?= icon('menu') ?></summary>
        <nav class="menu-panel" aria-label="Admin sections">
          <?php $this->load->view('admin/partials/nav', array('variant' => 'menu')); ?>
          <a href="<?= site_url('') ?>" target="_blank" rel="noopener"><?= icon('globe') ?> View site</a>
        </nav>
      </details>
    <?php endif; ?>
    <a class="brand admin-bar__brand" href="<?= site_url('admin') ?>" aria-label="AcademicMCQ admin home">
      <?php $this->load->view('partials/logo'); ?>
      <span class="brand__text"><strong class="brand__word">Academic<span>MCQ</span></strong></span>
    </a>
    <span class="admin-tag"><?= icon('shield') ?> Admin</span>
    <?php if ($admin): ?>
      <p class="admin-bar__date"><?= icon('calendar') ?> <?= e(date('l, j F Y')) ?></p>
      <details class="user-menu admin-user">
        <summary aria-label="Account menu">
          <span class="admin-avatar" aria-hidden="true"><?= e($initials) ?></span>
          <span class="user-menu__text"><strong><?= e($admin['name']) ?></strong><small><?= e($admin['email']) ?></small></span>
          <?= icon('chevron-down', 'user-menu__chev') ?>
        </summary>
        <div class="menu-panel">
          <p class="menu-panel__who"><strong><?= e($admin['name']) ?></strong><span><?= e($admin['email']) ?></span></p>
          <a href="<?= site_url('admin/password') ?>"><?= icon('lock') ?> Change password</a>
          <?= form_open('admin/logout', array('class' => 'menu-panel__form')) ?><button type="submit"><?= icon('logout') ?> Sign out</button><?= form_close() ?>
        </div>
      </details>
    <?php endif; ?>
  </header>

  <main id="main" class="admin-main">
    <?php if ($flash): ?><p class="amcq-flash amcq-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
