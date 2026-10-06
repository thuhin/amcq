<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * View helpers shared by every page.
 */

/** Escape for HTML output. Every user- or DB-sourced string goes through this. */
function e($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset($path)
{
	return APP_ASSET_URL . ltrim($path, '/');
}

/**
 * Money. Always "৳" and two decimals, so a wallet amount can never be
 * mistaken for a point total (guideline §6.1).
 */
function taka($amount)
{
	return '৳' . number_format((float) $amount, 2);
}

/** Points are whole numbers with thousands separators, never a currency sign. */
function points($n)
{
	return number_format((int) $n);
}

function pct($value)
{
	return $value === NULL ? '—' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.') . '%';
}

/**
 * Render question text with stacked fractions, as the quiz designs show them.
 * Escapes first, then marks up only digit/digit tokens, so nothing from the
 * question bank can inject HTML.
 */
function math_text($text)
{
	$safe = e($text);
	return preg_replace(
		'~(?<![\d/.])(\d+)/(\d+)(?![\d/])~',
		'<span class="frac" role="math" aria-label="$1 by $2"><span class="frac__n">$1</span><span class="frac__d">$2</span></span>',
		$safe
	);
}

/** "Good evening, Rahim" (guideline §5.1), in Bangla. */
function greeting()
{
	$h = (int) date('G');
	if ($h < 12)  return 'শুভ সকাল';
	if ($h < 17)  return 'শুভ দুপুর';
	return 'শুভ সন্ধ্যা';
}

function time_ago($timestamp)
{
	$diff = time() - strtotime($timestamp);
	if ($diff < 60)     return 'just now';
	if ($diff < 3600)   return floor($diff / 60) . ' min ago';
	if ($diff < 86400)  return floor($diff / 3600) . ' h ago';
	if ($diff < 172800) return 'yesterday';
	return date('j M', strtotime($timestamp));
}

function duration($seconds)
{
	$seconds = (int) $seconds;
	return sprintf('%d:%02d', floor($seconds / 60), $seconds % 60);
}

/** Inline SVG icon from the outline set defined in views/partials/icons.php. */
function icon($name, $class = '')
{
	return '<svg class="icon ' . e($class) . '" aria-hidden="true"><use href="#i-' . e($name) . '"/></svg>';
}

/** Chapter label as the designs print it: "ভগ্নাংশ (Fractions)". */
function chapter_label($chapter)
{
	$c = (array) $chapter;
	return ! empty($c['name_bn']) ? $c['name_bn'] . ' (' . $c['name'] . ')' : $c['name'];
}
