<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** "Invite a friend": the student's link and who joined with it (tracking only). */
class Invite extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model('Referral_model');
		$code = $this->user['referral_code'];
		$this->render('invite/index', array(
			'code'    => $code,
			'link'    => site_url('signup?ref=' . rawurlencode((string) $code)),
			'friends' => $this->Referral_model->invited($this->user['id']),
		), array('title' => 'Invite Friends', 'nav' => 'invite', 'app' => TRUE));
	}
}
