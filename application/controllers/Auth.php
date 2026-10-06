<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Phone -> OTP -> name -> class -> optional school (guideline §4.9).
 * Only what is needed up front; everything else lives in the profile.
 */
class Auth extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('User_model', 'Quiz_model'));
		$this->load->library('form_validation');
	}

	public function login($mode = 'login')
	{
		if ($this->user) {
			redirect('dashboard');
		}
		$error = NULL;
		if ($this->input->method() === 'post') {
			$phone = $this->User_model->normalise_phone($this->input->post('phone'));
			if ( ! $phone) {
				$error = 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.';
			} else {
				$otp = $this->User_model->request_otp($phone, $this->input->ip_address());
				if ( ! $otp['ok']) {
					$error = $otp['error'];
				} else {
					$this->session->set_userdata('otp_phone', $phone);
					// No SMS gateway yet. Development shows the code on the next
					// screen; any other environment must never reveal it.
					if (ENVIRONMENT === 'development') {
						$this->session->set_flashdata('dev_otp', $otp['code']);
					}
					redirect('login/verify');
				}
			}
		}
		$signup = $mode === 'signup';
		$this->render('auth/login', array('error' => $error, 'phone' => $this->input->post('phone'), 'signup' => $signup),
			array('title' => $signup ? 'Sign Up' : 'Login'));
	}

	public function verify()
	{
		$phone = $this->session->userdata('otp_phone');
		if ( ! $phone) {
			redirect('login');
		}
		$error = NULL;
		if ($this->input->method() === 'post') {
			if ($this->User_model->verify_otp($phone, $this->input->post('code'))) {
				$this->session->unset_userdata('otp_phone');
				$existing = $this->User_model->by_phone($phone);
				if ($existing) {
					$this->sign_in($existing['id']);
				}
				$this->session->set_userdata('verified_phone', $phone);
				redirect('register');
			}
			$error = 'That code is not right, or it has expired. Check the SMS and try again.';
		}
		$this->render('auth/verify', array(
			'phone'   => $phone,
			'error'   => $error,
			'dev_otp' => ENVIRONMENT === 'development' ? $this->session->flashdata('dev_otp') : NULL,
		), array('title' => 'Enter Code'));
	}

	public function register()
	{
		$phone = $this->session->userdata('verified_phone');
		if ( ! $phone) {
			redirect('login');
		}
		$this->load->model('Curriculum_model');
		$classes = array_filter($this->Curriculum_model->classes(1), function ($c) { return $c['is_active']; });

		$this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[100]');
		$this->form_validation->set_rules('class_id', 'Class', 'required|in_list[' . implode(',', array_column($classes, 'id')) . ']');
		$this->form_validation->set_rules('school_id', 'School', 'trim|integer');

		if ($this->form_validation->run()) {
			$school_id = (int) $this->input->post('school_id');
			if ($school_id && ! $this->db->where('id', $school_id)->count_all_results('schools')) {
				$school_id = NULL;
			}
			$id = $this->User_model->create($phone, $this->input->post('name'), (int) $this->input->post('class_id'), $school_id);
			if ($id) {
				$this->session->unset_userdata('verified_phone');
				$this->sign_in($id, TRUE);
			}
		}
		$this->render('auth/register', array(
			'classes' => $classes,
			'schools' => $this->User_model->schools(),
		), array('title' => 'Create Your Account'));
	}

	public function logout()
	{
		if ($this->input->method() === 'post') {
			$this->session->sess_destroy();
		}
		redirect('');
	}

	/**
	 * Start a signed-in session. The session id is regenerated so an id
	 * planted before login (session fixation) is worthless afterwards.
	 */
	private function sign_in($user_id, $is_new = FALSE)
	{
		$this->session->sess_regenerate(TRUE);
		$this->session->set_userdata('user_id', (int) $user_id);
		$this->User_model->touch_login($user_id);

		$token = $this->session->userdata('guest_token');
		$claimed = $token ? $this->Quiz_model->claim_guest_attempts($token, $user_id) : 0;
		$this->session->unset_userdata('guest_token');

		if ($claimed) {
			$this->flash('success', 'Your previous result has been saved.');
		} elseif ($is_new) {
			$this->flash('success', 'Welcome to AcademicMCQ! Add balance to your wallet to start practising.');
		}
		$after = $this->session->userdata('after_login');
		$this->session->unset_userdata('after_login');
		redirect($after ?: 'dashboard');
	}
}
