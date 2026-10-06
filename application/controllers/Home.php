<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public home page (design 01). Guest-first: everything here works without an
 * account, and Start Practicing goes straight to a quiz (guideline §6.2).
 */
class Home extends MY_Controller
{
	public function index()
	{
		$this->load->model(array('Curriculum_model', 'Points_model', 'Competition_model'));
		$class = $this->Curriculum_model->class_by_slug('class-5');
		$competition = $this->Competition_model->current();

		$this->render('home/index', array(
			'mediums'     => $this->Curriculum_model->mediums(),
			'classes'     => $this->Curriculum_model->classes(1),
			'class'       => $class,
			'subjects'    => $this->Curriculum_model->subjects($class['id']),
			'stats'       => $this->Curriculum_model->stats(),
			'leaders'     => $this->Points_model->leaderboard(5),
			'competition' => $competition,
			'winners'     => $competition ? $this->Competition_model->winner_count($competition['id']) : 0,
		));
	}
}
