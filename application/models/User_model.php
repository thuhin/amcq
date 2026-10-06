<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounts and phone-OTP sign-in.
 */
class User_model extends CI_Model
{
	/**
	 * Normalise a Bangladeshi mobile number to 01XXXXXXXXX, or NULL if it is
	 * not one. Accepts +880 / 880 prefixes and spaces or dashes.
	 */
	public function normalise_phone($raw)
	{
		$digits = preg_replace('/\D+/', '', (string) $raw);
		if (strpos($digits, '880') === 0) {
			$digits = substr($digits, 2);
		}
		return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : NULL;
	}

	public function by_phone($phone)
	{
		return $this->db->get_where('users', array('phone' => $phone))->row_array();
	}

	/**
	 * Issue a one-time code.
	 *
	 * The code is stored hashed: it is a live credential for a few minutes,
	 * and a leaked table should not hand out working logins.
	 *
	 * @return array ['ok' => TRUE, 'code' => string] or ['ok' => FALSE, 'error' => string]
	 */
	public function request_otp($phone, $ip)
	{
		$recent = $this->db->where('phone', $phone)
			->where('created_at >', date('Y-m-d H:i:s', time() - OTP_RESEND_SECONDS))
			->count_all_results('otp_verifications');
		if ($recent) {
			return array('ok' => FALSE, 'error' => 'Please wait a minute before asking for another code.');
		}
		// Each SMS costs money; cap how many one address can trigger.
		$from_ip = $this->db->where('ip_address', $ip)
			->where('created_at >', date('Y-m-d H:i:s', time() - 3600))
			->count_all_results('otp_verifications');
		if ($from_ip >= 10) {
			return array('ok' => FALSE, 'error' => 'Too many codes requested. Try again later.');
		}

		$code = str_pad((string) random_int(0, pow(10, OTP_LENGTH) - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
		$this->db->insert('otp_verifications', array(
			'phone'      => $phone,
			'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
			'purpose'    => 'login',
			'expires_at' => date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60),
			'ip_address' => $ip,
		));
		return array('ok' => TRUE, 'code' => $code);
	}

	/**
	 * Check a code. The newest unexpired code is the only one accepted, and it
	 * allows OTP_MAX_ATTEMPTS guesses before it is dead.
	 */
	public function verify_otp($phone, $code)
	{
		$otp = $this->db->where('phone', $phone)->where('consumed_at IS NULL', NULL, FALSE)
			->where('expires_at >', date('Y-m-d H:i:s'))
			->order_by('id', 'DESC')->limit(1)->get('otp_verifications')->row_array();
		if ( ! $otp || $otp['attempts'] >= OTP_MAX_ATTEMPTS) {
			return FALSE;
		}
		$this->db->set('attempts', 'attempts + 1', FALSE)->where('id', $otp['id'])->update('otp_verifications');
		if ( ! password_verify(trim((string) $code), $otp['code_hash'])) {
			return FALSE;
		}
		$this->db->update('otp_verifications', array('consumed_at' => date('Y-m-d H:i:s')), array('id' => $otp['id']));
		return TRUE;
	}

	/**
	 * Create a student with every per-user row the app expects to exist, so
	 * no page has to cope with a missing wallet or points row.
	 */
	public function create($phone, $name, $class_id, $school_id, $display_name = NULL)
	{
		$this->db->trans_start();
		$this->db->insert('users', array(
			'name'              => $name,
			'phone'             => $phone,
			'display_name'      => $display_name ?: $this->default_display_name($name),
			'class_id'          => $class_id,
			'school_id'         => $school_id ?: NULL,
			'referral_code'     => $this->new_referral_code(),
			'phone_verified_at' => date('Y-m-d H:i:s'),
			'last_login_at'     => date('Y-m-d H:i:s'),
		));
		$id = (int) $this->db->insert_id();
		$this->db->insert('wallets', array('user_id' => $id, 'balance' => '0.00'));
		$this->db->insert('user_lifetime_points', array('user_id' => $id, 'total_points' => 0, 'tier_id' => 0));
		$this->db->insert('user_quiz_streaks', array('user_id' => $id));
		$this->db->insert('user_settings', array('user_id' => $id));
		$this->db->trans_complete();
		return $this->db->trans_status() ? $id : FALSE;
	}

	/**
	 * Leaderboards show "Rahim A.", not a full name (guideline §4.6: allow a
	 * masked name). The student can change it in their profile.
	 */
	public function default_display_name($name)
	{
		$parts = preg_split('/\s+/u', trim($name));
		$first = array_shift($parts);
		$last = $parts ? array_pop($parts) : '';
		return $last === '' ? $first : $first . ' ' . mb_substr($last, 0, 1) . '.';
	}

	private function new_referral_code()
	{
		do {
			$code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
		} while ($this->db->where('referral_code', $code)->count_all_results('users'));
		return $code;
	}

	public function touch_login($user_id)
	{
		$this->db->update('users', array('last_login_at' => date('Y-m-d H:i:s')), array('id' => $user_id));
	}

	public function update_profile($user_id, array $data)
	{
		$allowed = array_intersect_key($data, array_flip(array('name', 'display_name', 'school_id', 'class_id')));
		if ($allowed) {
			$this->db->update('users', $allowed, array('id' => $user_id));
		}
	}

	public function settings($user_id)
	{
		$this->db->query('INSERT IGNORE INTO user_settings (user_id) VALUES (?)', array($user_id));
		return $this->db->get_where('user_settings', array('user_id' => $user_id))->row_array();
	}

	public function update_settings($user_id, array $data)
	{
		$allowed = array_intersect_key($data, array_flip(array('language', 'show_on_leaderboard', 'notify_streak', 'notify_competition', 'notify_sms')));
		if ($allowed) {
			$this->db->update('user_settings', $allowed, array('user_id' => $user_id));
		}
	}

	public function schools()
	{
		return $this->db->order_by('name')->get('schools')->result_array();
	}

	/**
	 * School card on the dashboard (design 12): this student's position among
	 * schoolmates by points, and the school's average quiz score. The average
	 * divides by quizzes taken, not student count (blueprint v2.0 fix).
	 */
	public function school_performance($user_id)
	{
		$user = $this->db->get_where('users', array('id' => $user_id))->row_array();
		if ( ! $user || ! $user['school_id']) {
			return NULL;
		}
		$school = $this->db->get_where('schools', array('id' => $user['school_id']))->row_array();
		$students = (int) $this->db->where('school_id', $user['school_id'])->count_all_results('users');

		$mine = (int) $this->db->select('COALESCE(p.total_points, 0) AS pts', FALSE)
			->from('users u')->join('user_lifetime_points p', 'p.user_id = u.id', 'left')
			->where('u.id', $user_id)->get()->row()->pts;
		$ahead = (int) $this->db->from('users u')->join('user_lifetime_points p', 'p.user_id = u.id')
			->where('u.school_id', $user['school_id'])->where('p.total_points >', $mine)->count_all_results();

		$scores = $this->db->query(
			"SELECT AVG(a.percentage) avg_p, MAX(a.percentage) max_p, COUNT(*) n
			 FROM quiz_attempts a JOIN users u ON u.id = a.user_id
			 WHERE u.school_id = ? AND a.status = 'completed'",
			array($user['school_id'])
		)->row_array();

		return array(
			'name'     => $school['name'],
			'position' => $ahead + 1,
			'students' => $students,
			'average'  => $scores['n'] ? (float) $scores['avg_p'] : NULL,
			'top'      => $scores['n'] ? (float) $scores['max_p'] : NULL,
		);
	}
}
