<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Invite a friend": tracking only.
 *
 * A visitor arriving with ?ref=CODE keeps the code in their session; if they
 * create an account, the friend who invited them is recorded (once, ever:
 * referrals.referred_id is UNIQUE). There is no reward yet: Academic Points
 * come only from streak, mastery, competition and corrections, and a Taka
 * bonus is a business decision still to be made. referrals.status stays
 * 'signed_up' until one exists.
 */
class Referral_model extends CI_Model
{
	public static function valid_code($code)
	{
		return is_string($code) && preg_match('/^[A-Z0-9]{4,12}$/', $code) === 1;
	}

	/** Called right after a new student account is created. @return bool recorded */
	public function record($code, $new_user_id)
	{
		$code = strtoupper(trim((string) $code));
		if ( ! self::valid_code($code)) {
			return FALSE;
		}
		$referrer = $this->db->get_where('users', array('referral_code' => $code, 'user_type' => 'student', 'status' => 'active'))->row_array();
		if ( ! $referrer || (int) $referrer['id'] === (int) $new_user_id) {
			return FALSE;
		}
		$this->db->query('INSERT IGNORE INTO referrals (referrer_id, referred_id) VALUES (?, ?)', array($referrer['id'], $new_user_id));
		if ($this->db->affected_rows() !== 1) {
			return FALSE;
		}
		$this->db->query('UPDATE users SET referred_by = ? WHERE id = ? AND referred_by IS NULL', array($referrer['id'], $new_user_id));
		return TRUE;
	}

	/** Friends this student invited: display names only, never phone numbers. */
	public function invited($user_id)
	{
		return $this->db->select('r.created_at, r.status, u.display_name, u.name')
			->from('referrals r')->join('users u', 'u.id = r.referred_id')
			->where('r.referrer_id', (int) $user_id)->order_by('r.id', 'DESC')->get()->result_array();
	}

	public function invited_by($user_id)
	{
		return $this->db->select('u.id, u.name, u.display_name')->from('referrals r')->join('users u', 'u.id = r.referrer_id')
			->where('r.referred_id', (int) $user_id)->get()->row_array();
	}
}
