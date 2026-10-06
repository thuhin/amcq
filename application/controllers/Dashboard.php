<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Student dashboard (design 12). */
class Dashboard extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model(array('Points_model', 'Quiz_model', 'Progress_model', 'Activity_model', 'User_model', 'Competition_model'));
		$uid = $this->user['id'];
		$competition = $this->Competition_model->current();
		$continue = $this->Progress_model->continue_chapter($uid);

		// Today's Activity; falls back to the most recent days when today is empty.
		$today = $this->db->where('user_id', $uid)->where('created_at >=', date('Y-m-d 00:00:00'))
			->order_by('id', 'DESC')->limit(5)->get('user_activity')->result_array();

		// Every achievement badge, earned or not, as the design greys out the
		// ones still to earn.
		$badges = $this->db->query(
			"SELECT b.*, ub.awarded_at FROM badges b
			 LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ?
			 WHERE b.code IN ('seven_day_streak', 'chapter_master', 'subject_50_mcqs', 'competition_participant')
			 ORDER BY FIELD(b.code, 'seven_day_streak', 'chapter_master', 'subject_50_mcqs', 'competition_participant')",
			array($uid)
		)->result_array();

		$this->render('dashboard/index', array(
			'rank'         => $this->Points_model->rank($uid),
			'rank_change'  => $this->Points_model->rank_change($uid),
			'day_streak'   => $this->Quiz_model->day_streak($uid),
			'accuracy'     => $this->Progress_model->accuracy($uid),
			'continue'     => $continue,
			'coverage'     => $continue ? $this->Quiz_model->chapter_coverage($uid, $continue['chapter_id']) : NULL,
			'subjects'     => $this->Progress_model->subjects($uid),
			'weak'         => $this->Progress_model->weak_chapters($uid),
			'activity'     => $today ?: $this->Activity_model->recent($uid, 5),
			'activity_today' => (bool) $today,
			'school'       => $this->User_model->school_performance($uid),
			'badges'       => $badges,
			'competition'  => $competition,
			'registration' => $competition ? $this->Competition_model->registration($competition['id'], $uid) : NULL,
			'winners'      => $competition ? $this->Competition_model->winner_count($competition['id']) : 0,
			'open'         => $competition ? $this->Competition_model->registration_open($competition) : FALSE,
		), array('title' => 'Dashboard', 'nav' => 'dashboard', 'app' => TRUE));
	}
}
