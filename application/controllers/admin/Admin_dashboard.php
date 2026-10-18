<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** What needs attention, and which chapters students cannot play yet. */
class Admin_dashboard extends Admin_Controller
{
	public function index()
	{
		$this->load->model('Question_model');
		$coverage = $this->Question_model->coverage();
		$show_all = $this->input->get('show') === 'all';

		$this->render('admin/dashboard', array(
			'by_status' => $this->Question_model->counts_by_status(),
			'stats'     => $this->Admin_model->dashboard_stats(),
			'playable'  => count(array_filter($coverage, function ($c) { return $c['playable']; })),
			'total'     => count($coverage),
			'coverage'  => $show_all ? $coverage : array_values(array_filter($coverage, function ($c) { return ! $c['playable']; })),
			'show_all'  => $show_all,
		), array('title' => 'Dashboard', 'nav' => 'dashboard'));
	}
}
