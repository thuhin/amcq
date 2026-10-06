<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** "View All Schools" from the homepage Top Schools card. */
class Schools_board extends MY_Controller
{
	public function index()
	{
		$this->load->model('School_model');
		$by = $this->input->get('by') === 'total' ? 'total' : 'average';
		$this->render('schools/index', array(
			'by'      => $by,
			'schools' => $this->School_model->top_week($by, 100),
			'week'    => $this->School_model->latest_week(),
			'mine'    => $this->user ? $this->School_model->school_card($this->user['school_id']) : NULL,
		), array('title' => 'Top Schools This Week', 'nav' => 'leaderboard'));
	}
}
