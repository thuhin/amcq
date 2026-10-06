<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape(isset($page_title) ? $page_title : 'AcademicMCQ') ?></title>
<meta name="description" content="Chapter-based MCQ practice from Tk 1. Learn from every mistake and build your AcademicMCQ rank.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Noto+Sans+Bengali:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= APP_ASSET_URL ?>css/brand.css">
<link rel="stylesheet" href="<?= APP_ASSET_URL ?>css/home.css">
</head>
<body>

<header class="amcq-header">
  <div class="amcq-container amcq-header__inner">
    <a class="amcq-logo" href="<?= base_url() ?>">
      <span class="amcq-logo__mark" aria-hidden="true">&#10003;</span>
      <span>
        <strong class="amcq-logo__word">AcademicMCQ</strong>
        <small class="amcq-logo__tag">Practice. Learn. Compete.</small>
      </span>
    </a>

    <nav class="amcq-nav" aria-label="Main">
      <a href="<?= base_url('practice') ?>">Practice</a>
      <a href="<?= base_url('competition') ?>">Competition</a>
      <a href="<?= base_url('leaderboard') ?>">Leaderboard</a>
      <a href="<?= base_url('pricing') ?>">Pricing</a>
      <a href="<?= base_url('how-it-works') ?>">How It Works</a>
    </nav>

    <div class="amcq-header__actions">
      <a class="amcq-btn amcq-btn--secondary" href="<?= base_url('login') ?>">Sign In</a>
      <a class="amcq-btn amcq-btn--primary" href="<?= base_url('practice') ?>">Start Quiz</a>
    </div>
  </div>
</header>
