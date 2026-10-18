<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin accounts and sign-in.
 *
 * An admin is a `users` row with user_type = 'admin', an email and a password
 * hash. Admins never use the student phone/OTP flow, and a student can never
 * become an admin through any screen: accounts are created from the command
 * line only (cli/admin).
 */
class Admin_model extends CI_Model
{
	/**
	 * Checked when the email is unknown, so a wrong email and a wrong password
	 * cost the same time and the response does not reveal which emails exist.
	 * Hash of a random string nobody knows.
	 */
	const DUMMY_HASH = '$2y$10$zy6ilzWtFZkT39CvIXnyVOotIjiN6HegbSKoooadAejF1riOvxJMq';

	public static function normalise_email($email)
	{
		return mb_strtolower(trim((string) $email));
	}

	/** Active admin by id, or NULL. Re-read every request, so disabling takes effect at once. */
	public function find($id)
	{
		return $this->db->get_where('users', array('id' => (int) $id, 'user_type' => 'admin', 'status' => 'active'))->row_array();
	}

	public function find_by_email($email)
	{
		return $this->db->get_where('users', array('email' => self::normalise_email($email), 'user_type' => 'admin'))->row_array();
	}

	public function all()
	{
		return $this->db->select('id, name, email, status, last_login_at, created_at')
			->where('user_type', 'admin')->order_by('id')->get('users')->result_array();
	}

	/**
	 * Locked after ADMIN_LOGIN_MAX_FAILS wrong passwords for one email (counted
	 * since its last successful sign-in), or ADMIN_LOGIN_MAX_FAILS_IP from one
	 * address, within ADMIN_LOGIN_LOCK_MINUTES.
	 */
	public function locked_out($email, $ip)
	{
		$email = mb_substr(self::normalise_email($email), 0, 100);
		$by_email = (int) $this->db->query(
			'SELECT COUNT(*) AS n FROM admin_login_attempts
			 WHERE email = ? AND succeeded = 0 AND created_at > NOW() - INTERVAL ? MINUTE
			   AND id > COALESCE((SELECT MAX(id) FROM admin_login_attempts WHERE email = ? AND succeeded = 1), 0)',
			array($email, ADMIN_LOGIN_LOCK_MINUTES, $email)
		)->row()->n;
		if ($by_email >= ADMIN_LOGIN_MAX_FAILS) {
			return TRUE;
		}
		$by_ip = (int) $this->db->query(
			'SELECT COUNT(*) AS n FROM admin_login_attempts
			 WHERE ip_address = ? AND succeeded = 0 AND created_at > NOW() - INTERVAL ? MINUTE',
			array($ip, ADMIN_LOGIN_LOCK_MINUTES)
		)->row()->n;
		return $by_ip >= ADMIN_LOGIN_MAX_FAILS_IP;
	}

	/**
	 * @return array|string the admin row, or 'locked' | 'invalid'
	 */
	public function authenticate($email, $password, $ip)
	{
		$email = self::normalise_email($email);
		if ($this->locked_out($email, $ip)) {
			return 'locked';   // not recorded: waiting must not extend the lock
		}

		$admin = $this->find_by_email($email);
		$active = $admin && $admin['status'] === 'active' && $admin['password_hash'];
		// Always run one password_verify, so timing is the same either way.
		$matches = password_verify((string) $password, $active ? $admin['password_hash'] : self::DUMMY_HASH);

		if ( ! ($active && $matches)) {
			$this->record($email, $ip, FALSE);
			return 'invalid';
		}

		$this->record($email, $ip, TRUE);
		$update = array('last_login_at' => date('Y-m-d H:i:s'));
		if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
			$update['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
		}
		$this->db->update('users', $update, array('id' => $admin['id']));
		// Keep the log small: only recent history matters for the lock-out.
		$this->db->query('DELETE FROM admin_login_attempts WHERE created_at < NOW() - INTERVAL 30 DAY');
		return $admin;
	}

	private function record($email, $ip, $succeeded)
	{
		$this->db->insert('admin_login_attempts', array(
			'email'      => mb_substr($email, 0, 100),
			'ip_address' => $ip,
			'succeeded'  => $succeeded ? 1 : 0,
		));
	}

	/** @return string|NULL a message when the password is not acceptable */
	public static function password_problem($password, $email = '')
	{
		if (mb_strlen($password) < ADMIN_PASSWORD_MIN_LENGTH) {
			return 'Use at least ' . ADMIN_PASSWORD_MIN_LENGTH . ' characters.';
		}
		if ($email !== '' && mb_strtolower($password) === self::normalise_email($email)) {
			return 'The password must not be the email address.';
		}
		return NULL;
	}

	/**
	 * Create an admin (command line only).
	 *
	 * @return int|string the new id, or an error message
	 */
	public function create($email, $name, $password)
	{
		$email = self::normalise_email($email);
		$name = trim((string) $name);
		if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
			return 'That is not a valid email address.';
		}
		if ($name === '' || mb_strlen($name) > 100) {
			return 'Give the admin a name (up to 100 characters).';
		}
		if ($problem = self::password_problem($password, $email)) {
			return $problem;
		}
		if ($this->find_by_email($email)) {
			return 'An admin with that email already exists.';
		}
		$this->db->insert('users', array(
			'name'          => $name,
			'email'         => $email,
			'password_hash' => password_hash($password, PASSWORD_DEFAULT),
			'user_type'     => 'admin',
			'status'        => 'active',
		));
		return (int) $this->db->insert_id();
	}

	/** Set a new password without asking for the old one (command line recovery). */
	public function set_password($email, $password)
	{
		$admin = $this->find_by_email($email);
		if ( ! $admin) {
			return 'No admin with that email.';
		}
		if ($problem = self::password_problem($password, $admin['email'])) {
			return $problem;
		}
		$this->db->update('users', array('password_hash' => password_hash($password, PASSWORD_DEFAULT)), array('id' => $admin['id']));
		// A password reset also clears the lock-out, so the owner is not locked out of their own recovery.
		$this->db->delete('admin_login_attempts', array('email' => $admin['email']));
		return TRUE;
	}

	/** Change your own password: the current one is required. @return TRUE|string */
	public function change_password($admin_id, $current, $new)
	{
		$admin = $this->db->get_where('users', array('id' => (int) $admin_id, 'user_type' => 'admin'))->row_array();
		if ( ! $admin || ! password_verify((string) $current, (string) $admin['password_hash'])) {
			return 'Your current password is not right.';
		}
		if ($problem = self::password_problem($new, $admin['email'])) {
			return $problem;
		}
		if (password_verify((string) $new, $admin['password_hash'])) {
			return 'Choose a password you are not already using.';
		}
		$this->db->update('users', array('password_hash' => password_hash($new, PASSWORD_DEFAULT)), array('id' => $admin['id']));
		return TRUE;
	}

	/** Suspend or re-enable an admin. A suspended admin is signed out on their next request. */
	public function set_status($email, $status)
	{
		$admin = $this->find_by_email($email);
		if ( ! $admin) {
			return 'No admin with that email.';
		}
		if ($status === 'suspended' && $this->active_count() <= 1 && $admin['status'] === 'active') {
			return 'That is the only active admin; create another one first.';
		}
		$this->db->update('users', array('status' => $status), array('id' => $admin['id']));
		return TRUE;
	}

	/** What is waiting for an admin: the counts on the navigation tabs. */
	public function attention_counts()
	{
		$row = $this->db->query(
			"SELECT (SELECT COUNT(*) FROM correction_requests WHERE status = 'pending') AS corrections,
			        (SELECT COUNT(*) FROM contact_messages WHERE status = 'new')         AS messages,
			        (SELECT COUNT(*) FROM questions WHERE status = 'in_review')          AS review"
		)->row_array();
		return array_map('intval', $row);
	}

	/** Headline numbers for the dashboard. */
	public function dashboard_stats()
	{
		$row = $this->db->query(
			"SELECT (SELECT COUNT(*) FROM users WHERE user_type = 'student') AS students,
			        (SELECT COUNT(*) FROM quiz_attempts WHERE status = 'completed' AND completed_at >= CURDATE()) AS quizzes_today"
		)->row_array();
		return array_map('intval', $row);
	}

	public function active_count()
	{
		return (int) $this->db->where('user_type', 'admin')->where('status', 'active')->count_all_results('users');
	}
}
