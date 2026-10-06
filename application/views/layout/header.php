<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $page  @var array|null $user  @var array|null $flash */
$nav = $page['nav'];
$active = function ($key) use ($nav) { return $nav === $key ? ' is-active" aria-current="page' : ''; };
?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page['title']) ?><?= $page['title'] === 'AcademicMCQ' ? ' — Practice. Learn. Compete.' : ' · AcademicMCQ' ?></title>
<meta name="description" content="Chapter-based MCQ practice from Tk 1. Learn from every mistake and build your AcademicMCQ rank.">
<meta name="theme-color" content="#174A7E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= asset('css/brand.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= $user ? 'is-auth' : 'is-guest' ?><?= $page['bare'] ? ' is-bare' : '' ?>">
<?php $this->load->view('partials/icons'); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="amcq-header">
  <div class="amcq-container amcq-header__inner">
    <a class="amcq-logo" href="<?= site_url($user ? 'dashboard' : '') ?>" aria-label="AcademicMCQ home">
      <!-- Answer-box + tick: the logo direction recommended in guideline §8.1. -->
      <span class="amcq-logo__mark" aria-hidden="true"><?= icon('check') ?></span>
      <span>
        <strong class="amcq-logo__word">Academic<span>MCQ</span></strong>
        <small class="amcq-logo__tag">Practice. Learn. Compete.</small>
      </span>
    </a>

    <?php if ( ! $page['bare']): ?>
    <nav class="amcq-nav" aria-label="Main">
      <?php if ($user): ?>
        <a class="amcq-nav__link<?= $active('dashboard') ?>" href="<?= site_url('dashboard') ?>">Home</a>
      <?php endif; ?>
      <a class="amcq-nav__link<?= $active('practice') ?>" href="<?= site_url('practice') ?>">Practice</a>
      <a class="amcq-nav__link<?= $active('competition') ?>" href="<?= site_url('competition') ?>">Competition</a>
      <a class="amcq-nav__link<?= $active('leaderboard') ?>" href="<?= site_url('leaderboard') ?>">Leaderboard</a>
      <?php if ($user): ?>
        <a class="amcq-nav__link<?= $active('progress') ?>" href="<?= site_url('progress') ?>">My Progress</a>
        <a class="amcq-nav__link<?= $active('wallet') ?>" href="<?= site_url('wallet') ?>">Wallet</a>
      <?php else: ?>
        <a class="amcq-nav__link<?= $active('how') ?>" href="<?= site_url('how-it-works') ?>">How It Works</a>
        <a class="amcq-nav__link<?= $active('pricing') ?>" href="<?= site_url('pricing') ?>">Pricing</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>

    <div class="amcq-header__actions">
      <?php if ($page['bare']): ?>
        <!-- Quiz screen: distraction-free (guideline §4.3). -->
      <?php elseif ($user): ?>
        <a class="amcq-me" href="<?= site_url('rank') ?>" title="Your tier and Academic Points">
          <span class="amcq-me__avatar" aria-hidden="true"><?= e(mb_substr($user['name'] ?: '?', 0, 1)) ?></span>
          <span class="amcq-me__text">
            <strong><?= e($user['tier_title'] ?: 'Starter') ?></strong>
            <span class="amcq-points"><?= points($user['total_points']) ?> pts</span>
          </span>
        </a>
        <details class="amcq-menu">
          <summary aria-label="Account menu"><?= icon('menu') ?></summary>
          <div class="amcq-menu__panel">
            <a href="<?= site_url('rank') ?>"><?= icon('medal') ?> My Rank</a>
            <a href="<?= site_url('wallet') ?>"><?= icon('wallet') ?> Wallet</a>
            <a href="<?= site_url('correct-me') ?>"><?= icon('pencil') ?> Correct Me</a>
            <a href="<?= site_url('profile') ?>"><?= icon('user') ?> Profile &amp; Settings</a>
            <?= form_open('logout', array('class' => 'amcq-menu__form')) ?>
              <button type="submit"><?= icon('logout') ?> Sign out</button>
            <?= form_close() ?>
          </div>
        </details>
      <?php else: ?>
        <a class="amcq-btn amcq-btn--secondary amcq-hide-sm" href="<?= site_url('login') ?>">Sign In</a>
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('practice') ?>">Start Quiz</a>
        <details class="amcq-menu amcq-show-sm">
          <summary aria-label="Menu"><?= icon('menu') ?></summary>
          <div class="amcq-menu__panel">
            <a href="<?= site_url('practice') ?>">Practice</a>
            <a href="<?= site_url('competition') ?>">Competition</a>
            <a href="<?= site_url('leaderboard') ?>">Leaderboard</a>
            <a href="<?= site_url('how-it-works') ?>">How It Works</a>
            <a href="<?= site_url('pricing') ?>">Pricing</a>
            <a href="<?= site_url('login') ?>">Sign In</a>
          </div>
        </details>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($page['crumbs']): ?>
<nav class="amcq-crumbs" aria-label="Breadcrumb">
  <ol class="amcq-container">
    <?php foreach ($page['crumbs'] as $i => $c): ?>
      <li><?php if ( ! empty($c[1]) && $i < count($page['crumbs']) - 1): ?><a href="<?= site_url($c[1]) ?>"><?= e($c[0]) ?></a><?php else: ?><span aria-current="page"><?= e($c[0]) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>

<?php if ($flash): ?>
<div class="amcq-container">
  <p class="amcq-flash amcq-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
</div>
<?php endif; ?>

<main id="main">
