<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Student dashboard (design 12, guideline §5.1). */
class Dashboard extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model(array('Points_model', 'Quiz_model', 'Progress_model', 'Activity_model', 'User_model', 'Competition_model'));
		$uid = $this->user['id'];
		$competition = $this->Competition_model->current();

		$this->render('dashboard/index', array(
			'rank'        => $this->Points_model->rank($uid),
			'rank_change' => $this->Points_model->rank_change($uid),
			'streak'      => $this->Quiz_model->streak($uid),
			'accuracy'    => $this->Progress_model->accuracy($uid),
			'continue'    => $this->Progress_model->continue_chapter($uid),
			'subjects'    => $this->Progress_model->subjects($uid),
			'weak'        => $this->Progress_model->weak_chapters($uid),
			'activity'    => $this->Activity_model->recent($uid),
			'school'      => $this->User_model->school_performance($uid),
			'badges'      => $this->Progress_model->badges($uid),
			'competition' => $competition,
			'registration'=> $competition ? $this->Competition_model->registration($competition['id'], $uid) : NULL,
			'winners'     => $competition ? $this->Competition_model->winner_count($competition['id']) : 0,
		), array('title' => 'Dashboard', 'nav' => 'dashboard'));
	}
}
