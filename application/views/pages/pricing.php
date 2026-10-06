<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="section section--tight">
  <div class="amcq-container">
    <h1>Pricing</h1>
    <p class="amcq-muted">Pay only when you practise. No subscription.</p>
    <ul class="grid grid--3 pricing">
      <li class="amcq-card pricing__card pricing__card--main">
        <h2 class="h3">Daily Practice</h2>
        <b class="pricing__price">Tk <?= number_format(QUIZ_FEE_TAKA) ?></b><span class="amcq-muted">per quiz attempt</span>
        <ul><li><?= icon('check') ?><?= QUIZ_QUESTION_COUNT ?> questions</li><li><?= icon('check') ?>Instant scoring</li><li><?= icon('check') ?>Explanation for every answer</li><li><?= icon('check') ?>Progress saved for registered users</li></ul>
        <a class="amcq-btn amcq-btn--primary amcq-btn--block" href="<?= site_url('practice') ?>">Start a Quiz</a>
      </li>
      <li class="amcq-card pricing__card">
        <h2 class="h3">Competition</h2>
        <b class="pricing__price">Tk <?= number_format(COMPETITION_FEE_TAKA) ?></b><span class="amcq-muted">one registration</span>
        <ul><li><?= icon('check') ?>Round 1</li><li><?= icon('check') ?>Final included if you qualify</li><li><?= icon('check') ?>Certificate for every participant</li></ul>
        <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= site_url('competition') ?>">View Competition</a>
      </li>
      <li class="amcq-card pricing__card">
        <h2 class="h3">School / Teacher</h2>
        <b class="pricing__price pricing__price--sm">Custom</b><span class="amcq-muted">bulk access and analytics</span>
        <ul><li><?= icon('check') ?>Class-wide practice</li><li><?= icon('check') ?>Printable quizzes</li><li><?= icon('check') ?>Progress reports</li></ul>
        <a class="amcq-btn amcq-btn--secondary amcq-btn--block" href="<?= site_url('contact') ?>">Contact Us</a>
      </li>
    </ul>
  </div>
</section>
