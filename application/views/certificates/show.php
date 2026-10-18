<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $cert */
$titles = array('participation' => 'Certificate of Participation', 'finalist' => 'Certificate of Achievement: Finalist', 'winner' => 'Certificate of Excellence: Winner');
$lines = array(
	'participation' => 'took part in Round 1 of',
	'finalist'      => 'qualified for the final of',
	'winner'        => 'won a prize place in',
);
?>
<section class="amcq-container cert-page">
  <article class="cert-sheet" aria-label="<?= e($titles[$cert['type']]) ?>">
    <div class="cert-sheet__brand"><?php $this->load->view('partials/logo'); ?><strong>Academic<span>MCQ</span></strong></div>
    <p class="cert-sheet__kind"><?= e($titles[$cert['type']]) ?></p>
    <p class="cert-sheet__lead">This certifies that</p>
    <h1 class="cert-sheet__name"><?= e($cert['student_name']) ?></h1>
    <p class="cert-sheet__what"><?= e($lines[$cert['type']]) ?> the <strong><?= e($cert['competition']) ?> <?= (int) $cert['year'] ?></strong><?= $cert['result_label'] && $cert['type'] === 'winner' ? ', finishing in <strong>' . e($cert['result_label']) . '</strong>' : '' ?>.</p>
    <div class="cert-sheet__foot">
      <span><small>Issued</small><b><?= e(date('j F Y', strtotime($cert['issued_at']))) ?></b></span>
      <span><small>Verification ID</small><b class="cert-sheet__code"><?= e($cert['verification_code']) ?></b></span>
    </div>
    <p class="cert-sheet__check">Check this certificate at <?= e(site_url('certificate/' . $cert['verification_code'])) ?></p>
  </article>
  <p class="cert-page__actions no-print">
    <span class="badge badge--ok"><?= icon('check') ?> Genuine: issued by AcademicMCQ</span>
    <button class="amcq-btn amcq-btn--secondary" type="button" onclick="window.print()"><?= icon('file') ?> Print or save as PDF</button>
  </p>
</section>
