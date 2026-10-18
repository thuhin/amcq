<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $attention @var array $by_status @var array $stats @var int $playable @var int $total @var array $coverage @var bool $show_all */
$need = array('easy' => QUIZ_MIX_EASY, 'medium' => QUIZ_MIX_MEDIUM, 'hard' => QUIZ_MIX_HARD);
?>
<div class="admin-head"><h1>Dashboard</h1></div>

<h2 class="admin-h2">Waiting for you</h2>
<ul class="admin-stats">
  <li><a class="admin-stat<?= $attention['corrections'] ? ' is-hot' : '' ?>" href="<?= site_url('admin/corrections') ?>">
    <b><?= (int) $attention['corrections'] ?></b><span>Correct Me reports to decide</span></a></li>
  <li><a class="admin-stat<?= $attention['messages'] ? ' is-hot' : '' ?>" href="<?= site_url('admin/messages?status=new') ?>">
    <b><?= (int) $attention['messages'] ?></b><span>New contact messages</span></a></li>
  <li><a class="admin-stat<?= $attention['review'] ? ' is-hot' : '' ?>" href="<?= site_url('admin/questions?status=in_review') ?>">
    <b><?= (int) $attention['review'] ?></b><span>Questions in review</span></a></li>
  <li><a class="admin-stat" href="<?= site_url('admin/questions?status=verified') ?>">
    <b><?= (int) $by_status['verified'] ?></b><span>Verified, not yet published</span></a></li>
</ul>

<h2 class="admin-h2">The site</h2>
<ul class="admin-stats">
  <li><a class="admin-stat" href="<?= site_url('admin/questions?status=published') ?>"><b><?= (int) $by_status['published'] ?></b><span>Published questions</span></a></li>
  <li><a class="admin-stat" href="<?= site_url('admin/questions?status=draft') ?>"><b><?= (int) $by_status['draft'] ?></b><span>Draft questions</span></a></li>
  <li><div class="admin-stat"><b><?= (int) $stats['students'] ?></b><span>Students</span></div></li>
  <li><div class="admin-stat"><b><?= (int) $stats['quizzes_today'] ?></b><span>Quizzes completed today</span></div></li>
</ul>

<section class="amcq-card admin-card">
  <div class="admin-card__head">
    <div>
      <h2>Quiz coverage</h2>
      <p class="amcq-muted">A chapter is playable with <?= $need['easy'] ?> easy, <?= $need['medium'] ?> medium and <?= $need['hard'] ?> hard <strong>published</strong> questions. Until then students see “Coming Soon”.</p>
    </div>
    <p class="admin-card__sum"><b><?= (int) $playable ?></b> of <?= (int) $total ?> chapters playable</p>
  </div>

  <?php if ($coverage): ?>
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only"><?= $show_all ? 'All active chapters' : 'Chapters that are not playable yet' ?></caption>
      <thead><tr><th scope="col">Chapter</th><th scope="col" class="num">Easy</th><th scope="col" class="num">Medium</th><th scope="col" class="num">Hard</th><th scope="col" class="num col-wide">Waiting</th><th scope="col" class="col-wide">Status</th></tr></thead>
      <tbody>
      <?php foreach ($coverage as $c): ?>
        <tr>
          <?php $lack = implode(', ', array_filter(array(
            $c['short']['easy']   ? $c['short']['easy']   . ' easy'   : '',
            $c['short']['medium'] ? $c['short']['medium'] . ' medium' : '',
            $c['short']['hard']   ? $c['short']['hard']   . ' hard'   : '',
          )));
          $state = $c['playable'] ? '<span class="badge badge--ok">' . icon('check') . ' Playable</span>' : '<span class="badge badge--warn">Needs ' . e($lack) . '</span>'; ?>
          <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/questions?chapter_id=' . (int) $c['id']) ?>"><?= e(chapter_label($c)) ?></a><small class="amcq-muted"><?= e($c['class_name']) ?> · <?= e($c['subject_name']) ?></small><span class="only-narrow"><?= $state ?></span></th>
          <?php foreach ($need as $d => $n): ?>
            <td class="num"><span class="<?= $c['mix'][$d] >= $n ? 'ok' : 'bad' ?>"><?= (int) $c['mix'][$d] ?></span><small class="amcq-muted"> / <?= $n ?></small></td>
          <?php endforeach; ?>
          <td class="num col-wide" title="Drafts, in review and verified"><?= (int) $c['in_pipeline'] ?></td>
          <td class="col-wide"><?= $state ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <p class="amcq-muted">Every active chapter is playable.</p>
  <?php endif; ?>
  <p class="admin-card__foot"><a href="<?= site_url($show_all ? 'admin' : 'admin?show=all') ?>"><?= $show_all ? 'Show only chapters that need questions' : 'Show all chapters' ?></a></p>
</section>
