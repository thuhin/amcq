<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** My Progress: overview, subjects, quiz history, weak areas (guideline §5.2). */
class Progress extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model(array('Progress_model', 'Quiz_model'));
		$uid = $this->user['id'];
		$tab = in_array($this->input->get('tab'), array('subjects', 'history', 'weak'), TRUE) ? $this->input->get('tab') : 'overview';

		$this->render('progress/index', array(
			'tab'      => $tab,
			'accuracy' => $this->Progress_model->accuracy($uid),
			'subjects' => $this->Progress_model->subjects($uid),
			'history'  => $this->Quiz_model->history($uid, 50),
			'weak'     => $this->Progress_model->weak_chapters($uid, 10),
			'streak'   => $this->Quiz_model->streak($uid),
			'qualifying' => (int) $this->db->where('user_id', $uid)->where('counts_for_streak', 1)->count_all_results('quiz_attempts'),
		), array('title' => 'My Progress', 'nav' => 'progress', 'app' => TRUE));
	}
}
