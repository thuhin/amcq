<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public home page (design 07 desktop, 04 mobile). Guest-first: the
 * four-step selector leads straight to practice without an account.
 */
class Home extends MY_Controller
{
	public function index()
	{
		$this->load->model(array('Curriculum_model', 'Competition_model', 'School_model'));
		$class = $this->Curriculum_model->class_by_slug('class-5');
		$subjects = $this->Curriculum_model->subjects($class['id']);

		// Step 3 -> step 4 of the selector. ?subject= keeps it working without
		// JavaScript; JS swaps step 4 in place.
		$selected = $subjects[0];
		foreach ($subjects as $s) {
			if ($s['slug'] === $this->input->get('subject')) {
				$selected = $s;
			}
		}
		$competition = $this->Competition_model->current();

		$this->render('home/index', array(
			'mediums'      => $this->Curriculum_model->mediums(),
			'classes'      => $this->Curriculum_model->classes(1),
			'class'        => $class,
			'subjects'     => $subjects,
			'selected'     => $selected,
			'stats'        => $this->Curriculum_model->stats(),
			'competition'  => $competition,
			'winners'      => $competition ? $this->Competition_model->winner_count($competition['id']) : 0,
			'top_avg'      => $this->School_model->top_week('average'),
			'top_total'    => $this->School_model->top_week('total'),
			'school_tab'   => $this->input->get('schools') === 'total' ? 'total' : 'average',
			'my_school'    => $this->user ? $this->School_model->school_card($this->user['school_id']) : NULL,
			'testimonials' => $this->School_model->testimonials(3),
		), array('nav' => 'home'));
	}
}
