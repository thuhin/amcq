<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $page  @var array|null $user  @var array|null $flash  @var int $unread */
$nav = $page['nav'];
$active = function ($key) use ($nav) { return $nav === $key ? ' is-active" aria-current="page' : ''; };
$links = array(
	'home'        => array('Home', $user ? 'dashboard' : ''),
	'practice'    => array('Practice', 'practice'),
	'competition' => array('Competition', 'competition'),
	'leaderboard' => array('Leaderboard', 'leaderboard'),
	'pricing'     => array('Pricing', 'pricing'),
	'how'         => array('How It Works', 'how-it-works'),
	'faq'         => array('FAQ', 'faq'),
);
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?><?= $page['title'] === 'AcademicMCQ' ? ' — Practice. Learn. Compete.' : ' · AcademicMCQ' ?></title>
<meta name="description" content="Chapter-based MCQ practice from Tk 1. Learn from every mistake and build your AcademicMCQ rank.">
<meta name="theme-color" content="#0665FC">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('css/brand.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="<?= asset('css/design.css') ?>">
</head>
<body class="<?= $user ? 'is-auth' : 'is-guest' ?><?= $page['app'] ? ' has-sidebar' : '' ?>">
<?php $this->load->view('partials/icons'); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="amcq-container site-header__inner">
    <a class="brand" href="<?= site_url($user ? 'dashboard' : '') ?>" aria-label="AcademicMCQ home">
      <?php $this->load->view('partials/logo'); ?>
      <span class="brand__text">
        <strong class="brand__word">Academic<span>MCQ</span></strong>
        <small class="brand__tag">Practice. Learn. Compete.</small>
      </span>
    </a>

    <nav class="site-nav" aria-label="Main">
      <?php foreach ($links as $key => $l): ?>
        <a class="site-nav__link<?= $active($key) ?>" href="<?= site_url($l[1]) ?>"><?= $l[0] ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="site-header__actions">
      <a class="icon-btn" href="<?= site_url('search') ?>" aria-label="Search"><?= icon('search') ?></a>
      <?php if ($user): ?>
        <a class="icon-btn icon-btn--bell" href="<?= site_url('notifications') ?>" aria-label="Notifications<?= $unread ? ', ' . $unread . ' unread' : '' ?>">
          <?= icon('bell') ?><?php if ($unread): ?><span class="icon-btn__dot" aria-hidden="true"></span><?php endif; ?>
        </a>
        <details class="user-menu">
          <summary aria-label="Account menu">
            <span class="avatar" aria-hidden="true"><?= icon('user') ?></span>
            <span class="user-menu__text"><strong>Level <?= (int) $user['tier_id'] ?></strong><span><?= points($user['total_points']) ?> <small>pts</small></span></span>
            <?= icon('chevron-down', 'user-menu__chev') ?>
          </summary>
          <div class="menu-panel">
            <p class="menu-panel__who"><strong><?= e($user['name']) ?></strong><span><?= e($user['tier_title']) ?></span></p>
            <a href="<?= site_url('dashboard') ?>"><?= icon('grid') ?> Dashboard</a>
            <a href="<?= site_url('progress') ?>"><?= icon('chart') ?> My Progress</a>
            <a href="<?= site_url('rank') ?>"><?= icon('medal') ?> My Rank</a>
            <a href="<?= site_url('wallet') ?>"><?= icon('wallet') ?> Wallet</a>
            <a href="<?= site_url('correct-me') ?>"><?= icon('pencil') ?> Correct Me</a>
            <a href="<?= site_url('certificates') ?>"><?= icon('certificate') ?> Certificates</a>
            <a href="<?= site_url('profile') ?>"><?= icon('user') ?> Profile &amp; Settings</a>
            <?= form_open('logout', array('class' => 'menu-panel__form')) ?><button type="submit"><?= icon('logout') ?> Sign out</button><?= form_close() ?>
          </div>
        </details>
      <?php else: ?>
        <a class="amcq-btn amcq-btn--secondary hide-md" href="<?= site_url('login') ?>">Login</a>
        <a class="amcq-btn amcq-btn--primary hide-md" href="<?= site_url('signup') ?>">Sign Up</a>
      <?php endif; ?>
      <details class="mobile-menu">
        <summary aria-label="Menu"><?= icon('menu') ?></summary>
        <div class="menu-panel">
          <?php foreach ($links as $key => $l): ?><a href="<?= site_url($l[1]) ?>"><?= $l[0] ?></a><?php endforeach; ?>
          <?php if ( ! $user): ?>
            <a href="<?= site_url('login') ?>"><?= icon('user') ?> Login</a>
            <a class="menu-panel__cta" href="<?= site_url('signup') ?>">Sign Up</a>
          <?php endif; ?>
        </div>
      </details>
    </div>
  </div>
</header>

<?php if ($page['crumbs']): ?>
<nav class="crumbs" aria-label="Breadcrumb">
  <ol class="amcq-container">
    <?php foreach ($page['crumbs'] as $i => $c): $last = $i === count($page['crumbs']) - 1; ?>
      <li><?php if ( ! $last && isset($c[1])): ?><a href="<?= site_url($c[1]) ?>"><?= e($c[0]) ?></a><?php else: ?><span<?= $last ? ' aria-current="page"' : '' ?>><?= e($c[0]) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>

<?php if ($page['app'] && $user): ?>
<div class="app-shell amcq-container">
  <?php $this->load->view('partials/sidebar', array('nav' => $nav)); ?>
  <div class="app-shell__main">
<?php endif; ?>

<?php if ($flash): ?>
  <div class="<?= $page['app'] && $user ? '' : 'amcq-container' ?>"><p class="amcq-flash amcq-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p></div>
<?php endif; ?>

<main id="main">
