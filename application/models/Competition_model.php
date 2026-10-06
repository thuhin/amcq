<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * National competition: landing info, prizes, and the Tk 99 registration.
 */
class Competition_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Wallet_model', 'Activity_model'));
	}

	/** The competition to show: the newest one that is not a finished draft. */
	public function current()
	{
		return $this->db->order_by('year', 'DESC')->order_by('id', 'DESC')
			->limit(1)->get('competitions')->row_array();
	}

	public function prizes($competition_id)
	{
		return $this->db->where('competition_id', $competition_id)
			->order_by('rank_from')->get('competition_prizes')->result_array();
	}

	public function winner_count($competition_id)
	{
		$row = $this->db->select_max('rank_to')->where('competition_id', $competition_id)
			->get('competition_prizes')->row_array();
		return (int) $row['rank_to'];
	}

	public function registration($competition_id, $user_id)
	{
		return $this->db->get_where('competition_registrations',
			array('competition_id' => $competition_id, 'user_id' => $user_id))->row_array();
	}

	public function registration_open(array $c)
	{
		return $c['status'] === 'registration';
	}

	/**
	 * Register and pay in one transaction. The UNIQUE (competition, user) key
	 * makes a double submit fail instead of charging Tk 99 twice.
	 *
	 * @return string 'ok' | 'closed' | 'already' | 'insufficient_balance'
	 */
	public function register(array $c, $user_id)
	{
		if ( ! $this->registration_open($c)) {
			return 'closed';
		}
		if ($this->registration($c['id'], $user_id)) {
			return 'already';
		}

		// Manual transaction: the race branch below must roll back the debit.
		$this->db->trans_begin();
		$txn = $this->Wallet_model->debit($user_id, $c['entry_fee'], 'competition_fee', 'Registration: ' . $c['name']);
		if ($txn === FALSE) {
			$this->db->trans_rollback();
			return 'insufficient_balance';
		}
		$this->db->query(
			'INSERT IGNORE INTO competition_registrations (competition_id, user_id, fee_paid, wallet_txn_id) VALUES (?, ?, 1, ?)',
			array($c['id'], $user_id, $txn)
		);
		if ($this->db->affected_rows() !== 1) {
			// Lost a race with a parallel submit: undo the Tk 99 debit.
			$this->db->trans_rollback();
			return 'already';
		}
		$this->Activity_model->log($user_id, 'competition', 'Competition registration confirmed', $c['name']);
		$this->Activity_model->notify($user_id, 'competition', 'You are registered for ' . $c['name']);
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return 'failed';
		}
		$this->db->trans_commit();
		return 'ok';
	}
}
