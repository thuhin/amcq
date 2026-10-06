<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallet: real money in Taka. Nothing here reads or writes Academic Points.
 *
 * Every change locks the wallet row (SELECT ... FOR UPDATE), writes the new
 * balance and a ledger row, all inside the caller's transaction. Without the
 * lock, two quiz starts in two tabs could both read ৳1 and both spend it.
 * The CHECK (balance >= 0) constraint is the backstop if that ever slips.
 */
class Wallet_model extends CI_Model
{
	public function ensure($user_id)
	{
		$this->db->query('INSERT IGNORE INTO wallets (user_id, balance) VALUES (?, 0.00)', array($user_id));
	}

	public function balance($user_id)
	{
		$row = $this->db->get_where('wallets', array('user_id' => $user_id))->row_array();
		return $row ? (float) $row['balance'] : 0.0;
	}

	/**
	 * Take money out. Must run inside a transaction.
	 *
	 * @return int|false wallet_transactions id, or FALSE if the balance is short
	 */
	public function debit($user_id, $amount, $type, $description)
	{
		return $this->apply($user_id, -abs($amount), $type, NULL, NULL, $description);
	}

	/**
	 * Put money in. Must run inside a transaction.
	 *
	 * $gateway_txn_id is UNIQUE in the ledger, so replaying the same gateway
	 * callback fails instead of crediting twice.
	 *
	 * @return int|false wallet_transactions id
	 */
	public function credit($user_id, $amount, $type, $gateway, $gateway_txn_id, $description)
	{
		return $this->apply($user_id, abs($amount), $type, $gateway, $gateway_txn_id, $description);
	}

	private function apply($user_id, $delta, $type, $gateway, $gateway_txn_id, $description)
	{
		$this->ensure($user_id);
		$row = $this->db->query('SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE', array($user_id))->row_array();

		// Work in paisa (integers) so Tk 0.10 + Tk 0.20 is exactly Tk 0.30.
		$new_paisa = (int) round($row['balance'] * 100) + (int) round($delta * 100);
		if ($new_paisa < 0) {
			return FALSE;
		}
		$new_balance = number_format($new_paisa / 100, 2, '.', '');

		$this->db->update('wallets', array('balance' => $new_balance), array('user_id' => $user_id));
		$this->db->insert('wallet_transactions', array(
			'user_id'        => $user_id,
			'amount'         => number_format($delta, 2, '.', ''),
			'balance_after'  => $new_balance,
			'type'           => $type,
			'gateway'        => $gateway ?: 'system',
			'gateway_txn_id' => $gateway_txn_id,
			'description'    => $description,
		));
		return (int) $this->db->insert_id();
	}

	public function transactions($user_id, $limit = 30)
	{
		return $this->db->where('user_id', $user_id)->order_by('id', 'DESC')
			->limit($limit)->get('wallet_transactions')->result_array();
	}
}
