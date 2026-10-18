<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Competition landing (guideline §4.7), registration, and the two rounds.
 *
 * Rules, prizes and eligibility are all on the page before the Register
 * button: "Never place vague rules behind a payment button." The rounds work
 * without JavaScript; the server owns the clock and, in the final, the order.
 */
class Competition extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Competition_model', 'Competition_round_model'));
	}

	public function index()
	{
		$c = $this->Competition_model->current();
		if ( ! $c) {
			show_404();
		}
		$registration = $this->user ? $this->Competition_model->registration($c['id'], $this->user['id']) : NULL;
		$this->render('competition/index', array(
			'c'            => $c,
			'prizes'       => $this->Competition_model->prizes($c['id']),
			'winners'      => $this->Competition_model->winner_count($c['id']),
			'open'         => $this->Competition_model->registration_open($c),
			'registration' => $registration,
			'round1_state' => $this->Competition_round_model->state($c, $registration, 'round1'),
			'final_state'  => $this->Competition_round_model->state($c, $registration, 'final'),
			'max1'         => $this->Competition_round_model->max_score('round1'),
			'maxf'         => $this->Competition_round_model->max_score('final'),
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

	// ------------------------------------------------------------ the rounds

	/** @return array [competition, registration] for a signed-in student, or redirect */
	private function context($round)
	{
		$this->require_login();
		$c = $this->Competition_model->current();
		if ( ! $c || ! in_array($round, array('round1', 'final'), TRUE)) {
			show_404();
		}
		return array($c, $this->Competition_model->registration($c['id'], $this->user['id']));
	}

	private function require_post()
	{
		if ($this->input->method() !== 'post') {
			show_error('Method not allowed', 405);
		}
	}

	private function label($round)
	{
		return $round === 'final' ? 'The final' : 'Round 1';
	}

	public function start($round)
	{
		$this->require_post();
		list($c, $reg) = $this->context($round);
		$result = $this->Competition_round_model->start($c, $reg ?: array(), $round);
		if ($result === 'ok') {
			redirect('competition/' . $round);
		}
		$this->flash('warning', $result === 'pool'
			? $this->label($round) . ' could not be prepared. Please tell us through the Contact page.'
			: $this->label($round) . ' is not open for you right now.');
		redirect('competition');
	}

	public function play($round)
	{
		list($c, $reg) = $this->context($round);
		$state = $this->Competition_round_model->state($c, $reg, $round);
		if ($state === 'expired') {
			$this->Competition_round_model->submit($c, $reg, $round);
			$this->flash('info', 'Time is up. Your answers were submitted.');
			redirect('competition');
		}
		if ($state !== 'in_progress') {
			redirect('competition');
		}

		$items = $this->Competition_round_model->items($reg['id'], $round);
		if ($round === 'final') {
			$q = $this->Competition_round_model->current_final_position($items);
			if ($q === NULL) {   // every question answered or skipped
				$this->Competition_round_model->submit($c, $reg, $round);
				$this->flash('success', 'The final is submitted. Results will be announced on this page.');
				redirect('competition');
			}
		} else {
			$q = (int) $this->input->get('q');
			if ($q < 1 || $q > count($items)) {
				$q = 1;
				foreach ($items as $it) {
					if ( ! $it['selected_option_id']) {
						$q = (int) $it['position'];
						break;
					}
				}
			}
		}

		$this->render('competition/play', array(
			'c'            => $c,
			'round'        => $round,
			'items'        => $items,
			'current'      => $items[$q - 1],
			'q'            => $q,
			'seconds_left' => $this->Competition_round_model->seconds_left($c, $reg, $round),
			'answered'     => count(array_filter(array_column($items, $round === 'final' ? 'answered_at' : 'selected_option_id'))),
			'weights'      => $this->db->order_by('weight')->get('difficulties')->result_array(),
		), array('title' => $this->label($round) . ': question ' . $q, 'nav' => 'competition', 'bare' => TRUE));
	}

	public function answer($round)
	{
		$this->require_post();
		list($c, $reg) = $this->context($round);
		$state = $this->Competition_round_model->state($c, $reg, $round);
		if ($state === 'expired') {
			$this->Competition_round_model->submit($c, $reg, $round);
			$this->flash('info', 'Time is up. Your answers were submitted.');
			redirect('competition');
		}
		if ($state !== 'in_progress') {
			redirect('competition');
		}

		$position = (int) $this->input->post('position');
		$ok = $this->Competition_round_model->save_answer($c, $reg, $round, $position, (int) $this->input->post('option_id'));
		$go = (string) $this->input->post('go');

		if ($round === 'final') {
			if ( ! $ok) {
				$this->flash('warning', 'In the final you answer the questions in order, once each. Here is your current question.');
			}
			redirect('competition/final');   // the next unanswered question, or the submit
		}
		if ($go === 'submit') {
			$this->Competition_round_model->submit($c, $reg, $round);
			$this->flash('success', 'Round 1 is submitted. Results will be announced when the round closes.');
			redirect('competition');
		}
		$total = count($this->Competition_round_model->items($reg['id'], $round));
		if ($go === 'prev') {
			$target = max(1, $position - 1);
		} elseif ($go === 'next') {
			$target = min($total, $position + 1);
		} else {
			$target = min($total, max(1, (int) $go));
		}
		redirect('competition/round1?q=' . $target);
	}

	public function submit($round)
	{
		$this->require_post();
		list($c, $reg) = $this->context($round);
		if ($reg && in_array($this->Competition_round_model->state($c, $reg, $round), array('in_progress', 'expired'), TRUE)) {
			$this->Competition_round_model->submit($c, $reg, $round);
			$this->flash('success', $this->label($round) . ' is submitted. Results will be announced on this page.');
		}
		redirect('competition');
	}
}
