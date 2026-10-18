<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * The admin sections, drawn once and used twice: as the sidebar on wide screens
 * ($variant 'side') and inside the menu button on narrow ones ('menu').
 *
 * @var array  $page      layout options; $page['nav'] is the active section
 * @var array  $attention counts of what is waiting
 * @var string $variant   'side' | 'menu'
 */
$items = array(
	'dashboard'    => array('Dashboard',    'admin',              'grid',      0),
	'questions'    => array('Questions',    'admin/questions',    'checklist', isset($attention['review']) ? $attention['review'] : 0),
	'corrections'  => array('Corrections',  'admin/corrections',  'pencil',    isset($attention['corrections']) ? $attention['corrections'] : 0),
	'messages'     => array('Messages',     'admin/messages',     'mail',      isset($attention['messages']) ? $attention['messages'] : 0),
	'testimonials' => array('Testimonials', 'admin/testimonials', 'star',      0),
	'competition'  => array('Competition',  'admin/competition',  'trophy',    0),
);
foreach ($items as $key => $it):
	$on = $page['nav'] === $key;
	$class = trim(($variant === 'side' ? 'sidebar__link' : '') . ($on ? ' is-active' : ''));
?>
<a<?= $class !== '' ? ' class="' . $class . '"' : '' ?> href="<?= site_url($it[1]) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= icon($it[2]) ?><span><?= e($it[0]) ?></span><?php if ($it[3]): ?><span class="count" aria-label="<?= (int) $it[3] ?> waiting"><?= (int) $it[3] ?></span><?php endif; ?></a>
<?php endforeach; ?>
