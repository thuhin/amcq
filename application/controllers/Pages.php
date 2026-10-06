<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mostly-static pages: how it works, pricing, and the legal/policy pages. */
class Pages extends MY_Controller
{
	const TITLES = array(
		'how-it-works'      => array('How It Works', 'how'),
		'pricing'           => array('Pricing', 'pricing'),
		'about'             => array('About', ''),
		'contact'           => array('Contact', ''),
		'terms'             => array('Terms of Use', ''),
		'privacy'           => array('Privacy Policy', ''),
		'refund-policy'     => array('Refund & Payment Policy', ''),
		'correct-me-policy' => array('Correct Me Policy', ''),
	);

	public function show($slug)
	{
		if ( ! isset(self::TITLES[$slug])) {
			show_404();
		}
		$view = in_array($slug, array('how-it-works', 'pricing'), TRUE) ? 'pages/' . str_replace('-', '_', $slug) : 'pages/policy';
		$this->render($view, array('slug' => $slug, 'heading' => self::TITLES[$slug][0]),
			array('title' => self::TITLES[$slug][0], 'nav' => self::TITLES[$slug][1]));
	}
}
