<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** @var array $admin @var array $attention @var array $by_status @var array $stats @var int $playable @var int $total @var array $coverage @var bool $show_all */
$need = array('easy' => QUIZ_MIX_EASY, 'medium' => QUIZ_MIX_MEDIUM, 'hard' => QUIZ_MIX_HARD);
$h = (int) date('G');
$hello = $h < 12 ? 'Good morning' : ($h < 17 ? 'Good afternoon' : 'Good evening');
$first = strtok(trim($admin['name']), ' ');
$kpi = function ($href, $icon, $n, $label, $go, $hot = FALSE) {
	return '<li><a class="kpi' . ($hot ? ' is-hot' : '') . '" href="' . site_url($href) . '">'
		. '<span class="kpi__icon">' . icon($icon) . '</span>'
		. '<span class="kpi__body"><b>' . (int) $n . '</b><span>' . e($label) . '</span></span>'
		. '<span class="kpi__go">' . e($go) . ' ' . icon('arrow-right') . '</span></a></li>';
};
$pct_playable = $total ? round(100 * $playable / $total) : 0;
?>
<div class="admin-head">
  <div>
    <h1>Dashboard</h1>
    <p class="admin-head__sub"><?= e($hello . ', ' . $first) ?>. Here is what needs you today.</p>
  </div>
  <div class="admin-head__actions">
    <a class="amcq-btn amcq-btn--primary" href="<?= site_url('admin/questions/new') ?>"><?= icon('plus') ?> New question</a>
  </div>
</div>

<h2 class="admin-h2">Waiting for you</h2>
<ul class="kpis">
  <?= $kpi('admin/corrections', 'pencil', $attention['corrections'], 'Correct Me reports to decide', 'Review', $attention['corrections'] > 0) ?>
  <?= $kpi('admin/messages?status=new', 'mail', $attention['messages'], 'New contact messages', 'Open inbox', $attention['messages'] > 0) ?>
  <?= $kpi('admin/questions?status=in_review', 'checklist', $attention['review'], 'Questions in review', 'Verify', $attention['review'] > 0) ?>
  <?= $kpi('admin/questions?status=verified', 'check-circle', $by_status['verified'], 'Verified, not yet published', 'Publish') ?>
</ul>

<h2 class="admin-h2">The site</h2>
<ul class="kpis">
  <?= $kpi('admin/questions?status=published', 'book-open', $by_status['published'], 'Published questions', 'View') ?>
  <?= $kpi('admin/questions?status=draft', 'file', $by_status['draft'], 'Draft questions', 'View') ?>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('users') ?></span><span class="kpi__body"><b><?= (int) $stats['students'] ?></b><span>Students</span></span></div></li>
  <li><div class="kpi"><span class="kpi__icon"><?= icon('bolt') ?></span><span class="kpi__body"><b><?= (int) $stats['quizzes_today'] ?></b><span>Quizzes completed today</span></span></div></li>
</ul>

<section class="amcq-card admin-card">
  <div class="admin-card__head">
    <div>
      <h2>Quiz coverage</h2>
      <p class="amcq-muted">A chapter is playable with <?= $need['easy'] ?> easy, <?= $need['medium'] ?> medium and <?= $need['hard'] ?> hard <strong>published</strong> questions. Until then students see “Coming Soon”.</p>
    </div>
    <div class="cov-total">
      <p><b><?= (int) $playable ?></b> of <?= (int) $total ?> chapters playable</p>
      <span class="mini-bar mini-bar--lg" role="img" aria-label="<?= $pct_playable ?>% of chapters playable"><i style="width: <?= $pct_playable ?>%"></i></span>
    </div>
  </div>

  <?php if ($coverage): ?>
  <div class="table-wrap">
    <table class="table admin-table">
      <caption class="sr-only"><?= $show_all ? 'All active chapters' : 'Chapters that are not playable yet' ?></caption>
      <thead><tr><th scope="col">Chapter</th><th scope="col">Easy</th><th scope="col">Medium</th><th scope="col">Hard</th><th scope="col" class="num col-wide">Waiting</th><th scope="col" class="col-wide">Status</th></tr></thead>
      <tbody>
      <?php foreach ($coverage as $c): ?>
        <?php $lack = implode(', ', array_filter(array(
          $c['short']['easy']   ? $c['short']['easy']   . ' easy'   : '',
          $c['short']['medium'] ? $c['short']['medium'] . ' medium' : '',
          $c['short']['hard']   ? $c['short']['hard']   . ' hard'   : '',
        )));
        $state = $c['playable'] ? '<span class="badge badge--ok">' . icon('check') . ' Playable</span>' : '<span class="badge badge--warn">Needs ' . e($lack) . '</span>'; ?>
        <tr>
          <th scope="row" class="admin-table__main"><a href="<?= site_url('admin/questions?chapter_id=' . (int) $c['id']) ?>"><?= e(chapter_label($c)) ?></a><small class="amcq-muted"><?= e($c['class_name']) ?> · <?= e($c['subject_name']) ?></small><span class="only-narrow"><?= $state ?></span></th>
          <?php foreach ($need as $d => $req): $have = (int) $c['mix'][$d]; $full = $have >= $req; ?>
            <td class="cov-cell">
              <span class="cov-cell__n"><b><?= $have ?></b><small> / <?= $req ?></small></span>
              <span class="mini-bar<?= $full ? ' is-full' : '' ?>"><i style="width: <?= min(100, round(100 * $have / $req)) ?>%"></i></span>
            </td>
          <?php endforeach; ?>
          <td class="num col-wide" title="Drafts, in review and verified"><?= (int) $c['in_pipeline'] ?></td>
          <td class="col-wide"><?= $state ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="empty-card"><?= icon('check-circle', 'ok') ?><p>Every active chapter is playable.</p></div>
  <?php endif; ?>
  <p class="admin-card__foot"><a class="link-arrow" href="<?= site_url($show_all ? 'admin' : 'admin?show=all') ?>"><?= $show_all ? 'Show only chapters that need questions' : 'Show all chapters' ?> <?= icon('arrow-right') ?></a></p>
</section>
