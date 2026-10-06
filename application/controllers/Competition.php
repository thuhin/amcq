<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Competition landing (guideline §4.7). Rules, prizes and eligibility are
 * all on the page before the Register button: "Never place vague rules
 * behind a payment button."
 */
class Competition extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Competition_model');
	}

	public function index()
	{
		$c = $this->Competition_model->current();
		if ( ! $c) {
			show_404();
		}
		$this->render('competition/index', array(
			'c'            => $c,
			'prizes'       => $this->Competition_model->prizes($c['id']),
			'winners'      => $this->Competition_model->winner_count($c['id']),
			'open'         => $this->Competition_model->registration_open($c),
			'registration' => $this->user ? $this->Competition_model->registration($c['id'], $this->user['id']) : NULL,
			'class'        => $c['class_id'] ? $this->db->get_where('classes', array('id' => $c['class_id']))->row_array() : NULL,
		), array('title' => 'National Competition', 'nav' => 'competition'));
	}

	public function register()
	{
		$this->require_login();
		if ($this->input->method() !== 'post') {
			redirect('competition');
		}
		$c = $this->Competition_model->current();
		// Explicit confirmation step for a paid action (guideline §17).
		if ( ! $this->input->post('confirm')) {
			$this->flash('warning', 'Please confirm that you have read the rules.');
			redirect('competition#register');
		}
		$messages = array(
			'ok'                   => array('success', 'You are registered. Good luck!'),
			'closed'               => array('info', 'Registration is not open yet.'),
			'already'              => array('info', 'You are already registered.'),
			'insufficient_balance' => array('warning', 'Registration costs ' . taka($c['entry_fee']) . '. Add balance to your wallet first.'),
			'failed'               => array('warning', 'Something went wrong. You were not charged.'),
		);
		$result = $this->Competition_model->register($c, $this->user['id']);
		$this->flash($messages[$result][0], $messages[$result][1]);
		redirect($result === 'insufficient_balance' ? 'wallet' : 'competition');
	}
}
