<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Mostly-static pages: how it works, pricing, and the legal/policy pages. */
class Pages extends MY_Controller
{
	const TITLES = array(
		'how-it-works'      => array('How It Works', 'how'),
		'pricing'           => array('Pricing', 'pricing'),
		'faq'               => array('Frequently Asked Questions', 'faq'),
		'about'             => array('About', ''),
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
		$view = in_array($slug, array('how-it-works', 'pricing', 'faq'), TRUE) ? 'pages/' . str_replace('-', '_', $slug) : 'pages/policy';
		$data = array('slug' => $slug, 'heading' => self::TITLES[$slug][0]);
		$title = self::TITLES[$slug][0];
		if ($slug === 'faq') {
			// The answers restate live rules, so they read them rather than retype them.
			$this->load->model(array('Competition_model', 'Points_model', 'Curriculum_model'));
			$c = $this->Competition_model->current();
			$data['lang']    = $this->content_lang();
			$data['comp']    = $c;
			$data['winners'] = $c ? $this->Competition_model->winner_count($c['id']) : 0;
			$data['tiers']   = $this->Points_model->tiers();
			$data['classes'] = array_values(array_filter($this->Curriculum_model->classes(1), function ($k) { return $k['is_active']; }));
			if ($data['lang'] === 'bn') {
				$title = 'প্রায়ই জিজ্ঞাসিত প্রশ্ন';
			}
		}
		$this->render($view, $data, array('title' => $title, 'nav' => self::TITLES[$slug][1]));
	}
}
