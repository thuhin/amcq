<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Admin sign-in, sign-out and own-password change. */
class Admin_auth extends Admin_Controller
{
	protected $requires_admin = FALSE;

	public function login()
	{
		if ($this->admin) {
			redirect('admin');
		}
		$error = NULL;
		$email = '';
		if ($this->input->method() === 'post') {
			$email = (string) $this->input->post('email');
			$result = $this->Admin_model->authenticate($email, (string) $this->input->post('password'), $this->input->ip_address());
			if (is_array($result)) {
				$this->sign_in_admin($result['id']);
				$next = (string) $this->session->userdata('admin_after_login');
				$this->session->unset_userdata('admin_after_login');
				// Only ever back to an admin page, never to a URL someone else chose.
				redirect(preg_match('~^admin(/[A-Za-z0-9_\-/]*)?$~', $next) && $next !== 'admin/login' ? $next : 'admin');
			}
			// One message for a wrong email and a wrong password.
			$error = $result === 'locked'
				? 'Too many wrong attempts. Try again in ' . ADMIN_LOGIN_LOCK_MINUTES . ' minutes.'
				: 'The email or password is not right.';
		}
		$this->render('admin/auth/login', array('error' => $error, 'email' => $email, 'dev_login' => $this->dev_login()), array('title' => 'Admin sign in'));
	}

	/**
	 * The click-to-fill login for local testing, or NULL.
	 *
	 * Three things must all be true, because this prints a password into the
	 * page: the app runs in development, the server name is a local one, and
	 * the machine's own env.php (never committed) defines the credentials.
	 * A server that has none of that renders nothing.
	 */
	private function dev_login()
	{
		if (ENVIRONMENT !== 'development' || ! defined('ADMIN_DEV_LOGIN_EMAIL') || ! defined('ADMIN_DEV_LOGIN_PASSWORD')) {
			return NULL;
		}
		if (ADMIN_DEV_LOGIN_EMAIL === '' || ADMIN_DEV_LOGIN_PASSWORD === '') {
			return NULL;
		}
		$host = strtolower((string) $this->input->server('SERVER_NAME'));
		if ( ! preg_match('~(^|\.)(test|localhost)$|^127\.0\.0\.1$~', $host)) {
			return NULL;
		}
		return array('email' => ADMIN_DEV_LOGIN_EMAIL, 'password' => ADMIN_DEV_LOGIN_PASSWORD);
	}

	public function logout()
	{
		$this->require_post();
		$this->forget_admin();
		$this->flash('info', 'You are signed out.');
		redirect('admin/login');
	}

	public function password()
	{
		if ( ! $this->admin) {
			redirect('admin/login');
		}
		$error = NULL;
		if ($this->input->method() === 'post') {
			$new = (string) $this->input->post('new_password');
			if ($new !== (string) $this->input->post('confirm_password')) {
				$error = 'The two new passwords are not the same.';
			} else {
				$result = $this->Admin_model->change_password($this->admin['id'], (string) $this->input->post('current_password'), $new);
				if ($result === TRUE) {
					$this->sign_in_admin($this->admin['id']);   // new session id after a credential change
					$this->flash('success', 'Your password is changed.');
					redirect('admin/password');
				}
				$error = $result;
			}
		}
		$this->render('admin/auth/password', array('error' => $error), array('title' => 'Change password'));
	}
}
