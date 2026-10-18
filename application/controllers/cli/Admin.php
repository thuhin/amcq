<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin accounts, from the command line only. There is deliberately no web
 * screen that creates or promotes an admin.
 *
 *   php index.php cli/admin create     # asks for email, name, password
 *   php index.php cli/admin passwd     # asks for email, new password (also clears a lock-out)
 *   php index.php cli/admin list
 *   php index.php cli/admin suspend    # asks for email
 *   php index.php cli/admin enable     # asks for email
 *
 * Values are prompted for, not passed as arguments: CodeIgniter refuses "@" in
 * URI segments, and an argument would show a password in the process list.
 * Piped input works too: one line per prompt.
 */
class Admin extends CI_Controller
{
	/** @var bool a person at a terminal (TRUE) or piped input (FALSE) */
	private $interactive;

	public function __construct()
	{
		parent::__construct();
		if ( ! is_cli()) {
			show_404();
		}
		// Decided before anything is read: asking after a read loses the piped
		// lines PHP has already buffered (and warns about it) on Linux.
		$this->interactive = stream_isatty(STDIN);
		$this->db->query('SET time_zone = ?', array(APP_TZ_OFFSET));
		$this->load->model('Admin_model');
	}

	public function create()
	{
		$email = $this->ask('Email: ');
		$name = $this->ask('Name: ');
		$password = $this->ask_password();
		$result = $this->Admin_model->create($email, $name, $password);
		if (is_string($result)) {
			$this->fail($result);
		}
		echo 'Admin created: ' . Admin_model::normalise_email($email) . ' (id ' . $result . ')' . PHP_EOL;
	}

	public function passwd()
	{
		$email = $this->ask('Email: ');
		$result = $this->Admin_model->set_password($email, $this->ask_password());
		if ($result !== TRUE) {
			$this->fail($result);
		}
		echo 'Password changed for ' . Admin_model::normalise_email($email) . '. Any sign-in lock-out is cleared.' . PHP_EOL;
	}

	public function list()
	{
		$rows = $this->Admin_model->all();
		if ( ! $rows) {
			echo 'No admins yet. Create one with: php index.php cli/admin create' . PHP_EOL;
			return;
		}
		foreach ($rows as $a) {
			printf("%-4d %-32s %-10s %-20s last sign-in: %s\n", $a['id'], $a['email'], $a['status'], $a['name'], $a['last_login_at'] ?: 'never');
		}
	}

	public function suspend()
	{
		$this->set_status('suspended');
	}

	public function enable()
	{
		$this->set_status('active');
	}

	private function set_status($status)
	{
		$email = $this->ask('Email: ');
		$result = $this->Admin_model->set_status($email, $status);
		if ($result !== TRUE) {
			$this->fail($result);
		}
		echo Admin_model::normalise_email($email) . ' is now ' . $status . '.' . PHP_EOL;
	}

	private function ask($label)
	{
		fwrite(STDOUT, $label);
		$line = fgets(STDIN);
		return $line === FALSE ? '' : rtrim($line, "\r\n");
	}

	/** Hidden where the terminal allows it; asked twice when a person is typing. */
	private function ask_password()
	{
		$interactive = $this->interactive;
		$hide = $interactive && DIRECTORY_SEPARATOR === '/';
		if ($interactive && ! $hide) {
			fwrite(STDOUT, '(This terminal cannot hide typing; the password will be visible.)' . PHP_EOL);
		}
		$read = function ($label) use ($hide) {
			fwrite(STDOUT, $label);
			if ($hide) {
				shell_exec('stty -echo');
			}
			$line = fgets(STDIN);
			if ($hide) {
				shell_exec('stty echo');
				fwrite(STDOUT, PHP_EOL);
			}
			return $line === FALSE ? '' : rtrim($line, "\r\n");
		};
		$password = $read('Password (at least ' . ADMIN_PASSWORD_MIN_LENGTH . ' characters): ');
		if ($interactive && $read('Password again: ') !== $password) {
			$this->fail('The two passwords are not the same.');
		}
		return $password;
	}

	private function fail($message)
	{
		fwrite(STDERR, $message . PHP_EOL);
		exit(1);
	}
}
