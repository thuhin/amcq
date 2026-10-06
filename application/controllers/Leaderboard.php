<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Public leaderboard (guideline §4.6). Display names only, never contact details. */
class Leaderboard extends MY_Controller
{
	public function index()
	{
		$this->load->model('Points_model');
		$data = array('leaders' => $this->Points_model->leaderboard(LEADERBOARD_PUBLIC_SIZE));
		if ($this->user) {
			$data['rank']   = $this->Points_model->rank($this->user['id']);
			$data['nearby'] = $this->Points_model->nearby($this->user['id']);
		}
		$this->render('leaderboard/index', $data, array('title' => 'Leaderboard', 'nav' => 'leaderboard'));
	}
}
