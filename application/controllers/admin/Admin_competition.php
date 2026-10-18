<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Run the national competition: settings, the stage-by-stage steps, results
 * and prize payout records. Steps only move forward (registrations are paid
 * and a sat round cannot be re-opened fairly); each one is checked first.
 * Prize money is paid outside the app; this only records it.
 */
class Admin_competition extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Competition_round_model'));
	}

	private function competition()
	{
		$c = $this->Competition_model->current();
		if ( ! $c) {
			show_404();
		}
		return $c;
	}

	public function index()
	{
		$this->page(array(), NULL);
	}

	private function page(array $errors, $form)
	{
		$c = $this->competition();
		$R = $this->Competition_round_model;
		$after_round1 = in_array($c['status'], array('grading', 'final', 'completed'), TRUE);
		$this->render('admin/competition/index', array(
			'c'         => $c,
			'class'     => $c['class_id'] ? $this->db->get_where('classes', array('id' => $c['class_id']))->row_array() : NULL,
			'form'      => $form ?: $c,
			'errors'    => $errors,
			'editable'  => in_array($c['status'], Competition_model::EDITABLE_STAGES, TRUE),
			'counts'    => $R->counts($c),
			'prizes'    => $this->Competition_model->prizes($c['id']),
			'pool'      => $R->pool($c),
			'mix1'      => $R->mix('round1'),
			'mixf'      => $R->mix('final'),
			'problems'  => array('round1' => $R->round_problems($c, 'round1'), 'final' => $R->round_problems($c, 'final')),
			'max1'      => $R->max_score('round1'),
			'maxf'      => $R->max_score('final'),
			'round1'    => $after_round1 ? $R->results($c, 'round1') : array(),
			'final'     => $c['status'] === 'completed' ? $R->results($c, 'final') : array(),
		), array('title' => 'Competition', 'nav' => 'competition'));
	}

	public function settings()
	{
		$this->require_post();
		$c = $this->competition();
		$in = $this->input->post(NULL, FALSE);
		$in = is_array($in) ? $in : array();
		$errors = $this->Competition_model->update_settings($c, $in);
		if ($errors) {
			return $this->page($errors, array_merge($c, $in));
		}
		$this->flash('success', 'Settings saved.');
		redirect('admin/competition');
	}

	public function advance()
	{
		$this->require_post();
		$c = $this->competition();
		// The stage the admin was looking at: a double click, or two admins at
		// once, must not skip a stage.
		if ((string) $this->input->post('from') !== $c['status']) {
			$this->flash('info', 'The competition had already moved on. Here is where it stands now.');
			redirect('admin/competition');
		}
		list($ok, $messages) = $this->Competition_round_model->advance($c);
		if ($ok) {
			$now = $this->Competition_model->current();
			$this->flash('success', 'Done. The competition is now at: ' . $this->stage_label($now['status']) . '.');
		} else {
			$this->flash('warning', implode(' ', $messages));
		}
		redirect('admin/competition');
	}

	public function paid($registration_id)
	{
		$this->require_post();
		$c = $this->competition();
		$result = $this->Competition_round_model->mark_prize_paid($c, (int) $registration_id, $this->admin['id'], $this->input->post('reference'));
		$messages = array(
			'ok'         => array('success', 'Prize payment recorded. The winner has been told.'),
			'bad_ref'    => array('warning', 'Enter the payment reference (bKash transaction ID or bank reference), 3 to 100 characters.'),
			'already'    => array('info', 'That prize was already recorded as paid.'),
			'not_winner' => array('warning', 'That student has no prize to pay.'),
			'not_found'  => array('warning', 'That registration does not exist.'),
		);
		$this->flash($messages[$result][0], $messages[$result][1]);
		redirect('admin/competition#winners');
	}

	private function stage_label($s)
	{
		$labels = array('draft' => 'Draft', 'registration' => 'Registration open', 'round1' => 'Round 1 open',
			'grading' => 'Round 1 closed', 'final' => 'Final open', 'completed' => 'Completed');
		return $labels[$s];
	}
}
