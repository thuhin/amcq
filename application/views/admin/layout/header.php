<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $page  @var array|null $admin  @var array|null $flash  @var array $attention */
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
<body class="admin">
<?php $this->load->view('partials/icons'); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="admin-bar">
  <div class="admin-container admin-bar__inner">
    <?php if ($admin): ?>
      <!-- Narrow screens: the side menu becomes this button. -->
      <details class="mobile-menu admin-mobile-menu">
        <summary aria-label="Menu"><?= icon('menu') ?></summary>
        <nav class="menu-panel" aria-label="Admin sections">
          <?php $this->load->view('admin/partials/nav', array('variant' => 'menu')); ?>
          <a href="<?= site_url('') ?>" target="_blank" rel="noopener"><?= icon('arrow-right') ?> View site</a>
        </nav>
      </details>
    <?php endif; ?>
    <a class="brand" href="<?= site_url('admin') ?>" aria-label="AcademicMCQ admin home">
      <?php $this->load->view('partials/logo'); ?>
      <span class="brand__text"><strong class="brand__word">Academic<span>MCQ</span></strong></span>
    </a>
    <span class="admin-tag"><?= icon('shield') ?> Admin</span>
    <?php if ($admin): ?>
      <div class="admin-bar__actions">
        <a class="admin-bar__link" href="<?= site_url('') ?>" target="_blank" rel="noopener">View site <?= icon('arrow-right') ?></a>
        <details class="user-menu">
          <summary aria-label="Account menu">
            <span class="avatar" aria-hidden="true"><?= icon('user') ?></span>
            <span class="user-menu__text"><strong><?= e($admin['name']) ?></strong><small><?= e($admin['email']) ?></small></span>
            <?= icon('chevron-down', 'user-menu__chev') ?>
          </summary>
          <div class="menu-panel">
            <p class="menu-panel__who"><strong><?= e($admin['name']) ?></strong><span><?= e($admin['email']) ?></span></p>
            <a href="<?= site_url('admin/password') ?>"><?= icon('lock') ?> Change password</a>
            <?= form_open('admin/logout', array('class' => 'menu-panel__form')) ?><button type="submit"><?= icon('logout') ?> Sign out</button><?= form_close() ?>
          </div>
        </details>
      </div>
    <?php endif; ?>
  </div>
</header>

<div class="admin-container admin-shell<?= $admin ? '' : ' admin-shell--bare' ?>">
  <?php if ($admin): ?>
  <aside class="admin-sidebar" aria-label="Admin sections">
    <nav><?php $this->load->view('admin/partials/nav', array('variant' => 'side')); ?></nav>
  </aside>
  <?php endif; ?>
  <main id="main" class="admin-main">
    <?php if ($flash): ?><p class="amcq-flash amcq-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
