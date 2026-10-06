<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Academic Rank (guideline §5.3). */
class Rank extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model('Points_model');
		$uid = $this->user['id'];
		$total = $this->Points_model->total($uid);

		$this->render('rank/index', array(
			'total'       => $total,
			'rank'        => $this->Points_model->rank($uid),
			'rank_change' => $this->Points_model->rank_change($uid),
			'tier'        => $this->Points_model->tier_for($total),
			'next'        => $this->Points_model->next_tier($total),
			'tiers'       => $this->Points_model->tiers(),
			'history'     => $this->Points_model->history($uid),
			'nearby'      => $this->Points_model->nearby($uid),
		), array('title' => 'My Rank', 'nav' => 'leaderboard'));
	}
}
