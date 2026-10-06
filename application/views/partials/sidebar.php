<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Dashboard sidebar (design 12).
$items = array(
	'dashboard'    => array('Dashboard', 'dashboard', 'grid'),
	'practice'     => array('Practice', 'practice', 'file'),
	'progress'     => array('My Progress', 'progress', 'chart'),
	'rank'         => array('My Rank', 'rank', 'bar-up'),
	'competition'  => array('Competitions', 'competition', 'trophy'),
	'wallet'       => array('Wallet', 'wallet', 'wallet'),
	'correct'      => array('Correct Me', 'correct-me', 'pencil'),
	'certificates' => array('Certificates', 'certificates', 'certificate'),
	'profile'      => array('Profile & Settings', 'profile', 'user'),
);
?>
<aside class="sidebar" aria-label="Your account">
  <nav>
    <?php foreach ($items as $key => $it): ?>
      <a class="sidebar__link<?= $nav === $key ? ' is-active' : '' ?>" href="<?= site_url($it[1]) ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= icon($it[2]) ?><span><?= e($it[0]) ?></span></a>
    <?php endforeach; ?>
  </nav>
</aside>
