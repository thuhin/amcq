<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * The admin sections, drawn once and used twice: the dark sidebar on wide
 * screens ($variant 'side') and the menu button on narrow ones ('menu').
 *
 * @var array  $page      layout options; $page['nav'] is the active section
 * @var array  $attention counts of what is waiting
 * @var string $variant   'side' | 'menu'
 */
$n = function ($k) use ($attention) { return isset($attention[$k]) ? (int) $attention[$k] : 0; };
$groups = array(
	'Overview' => array(
		'dashboard'    => array('Dashboard',    'admin',              'grid',      0),
	),
	'Content' => array(
		'questions'    => array('Questions',    'admin/questions',    'checklist', $n('review')),
		'testimonials' => array('Testimonials', 'admin/testimonials', 'star',      0),
	),
	'Inbox' => array(
		'corrections'  => array('Corrections',  'admin/corrections',  'pencil',    $n('corrections')),
		'messages'     => array('Messages',     'admin/messages',     'mail',      $n('messages')),
	),
	'Events' => array(
		'competition'  => array('Competition',  'admin/competition',  'trophy',    0),
	),
);
foreach ($groups as $group => $items):
	if ($variant === 'side'): ?><p class="admin-nav__group"><?= e($group) ?></p><?php endif;
	foreach ($items as $key => $it):
		$on = $page['nav'] === $key;
		$class = trim(($variant === 'side' ? 'admin-nav__link' : '') . ($on ? ' is-active' : ''));
?>
<a<?= $class !== '' ? ' class="' . $class . '"' : '' ?> href="<?= site_url($it[1]) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= icon($it[2]) ?><span><?= e($it[0]) ?></span><?php if ($it[3]): ?><span class="count" aria-label="<?= $it[3] ?> waiting"><?= $it[3] ?></span><?php endif; ?></a>
<?php endforeach; endforeach; ?>
