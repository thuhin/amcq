<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Competition certificates: participation (sat Round 1), finalist (qualified)
 * and winner (a prize place). One of each type per student per competition
 * (UNIQUE user, competition, type), so issuing twice is harmless.
 *
 * The student's name is copied in at issue time: a certificate keeps the name
 * it was issued under even if the profile changes later.
 */
class Certificate_model extends CI_Model
{
	/** No 0/O, 1/I/L: the code is read off paper and typed in. */
	const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

	/** @return string|NULL the verification code (new or existing), NULL if the user is gone */
	public function issue($user_id, $competition_id, $type, $result_label = NULL)
	{
		$have = $this->db->get_where('certificates', array('user_id' => $user_id, 'competition_id' => $competition_id, 'type' => $type))->row_array();
		if ($have) {
			return $have['verification_code'];
		}
		$user = $this->db->get_where('users', array('id' => $user_id))->row_array();
		if ( ! $user) {
			return NULL;
		}
		for ($try = 0; $try < 5; $try++) {
			$code = self::new_code();
			$this->db->query(
				'INSERT IGNORE INTO certificates (verification_code, user_id, competition_id, type, student_name, result_label) VALUES (?, ?, ?, ?, ?, ?)',
				array($code, $user_id, $competition_id, $type, $user['name'] ?: 'Student', $result_label)
			);
			if ($this->db->affected_rows() === 1) {
				return $code;
			}
		}
		return NULL;
	}

	public static function new_code()
	{
		$code = '';
		for ($i = 0; $i < CERTIFICATE_CODE_LENGTH; $i++) {
			$code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
		}
		return $code;
	}

	public static function normalise($code)
	{
		return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
	}

	/** A certificate with its competition, or NULL. */
	public function by_code($code)
	{
		$code = self::normalise($code);
		if (strlen($code) !== CERTIFICATE_CODE_LENGTH) {
			return NULL;
		}
		return $this->db->select('c.*, k.name AS competition, k.year')->from('certificates c')
			->join('competitions k', 'k.id = c.competition_id')
			->where('c.verification_code', $code)->get()->row_array();
	}

	public function for_user($user_id)
	{
		return $this->db->select('c.*, k.name AS competition, k.year')->from('certificates c')
			->join('competitions k', 'k.id = c.competition_id')->where('c.user_id', (int) $user_id)
			->order_by('c.issued_at', 'DESC')->order_by('c.id', 'DESC')->get()->result_array();
	}
}
