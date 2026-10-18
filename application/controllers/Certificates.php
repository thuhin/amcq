<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Certificates (dashboard sidebar, design 12; guideline §5.6).
 *
 * A certificate has a public page at /certificate/{code}: the code is printed
 * on it, so a school or employer can check it is genuine. Codes are random and
 * 12 characters long, so they cannot be guessed or enumerated.
 */
class Certificates extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Certificate_model');
	}

	public function index()
	{
		$this->require_login();
		$this->render('certificates/index', array(
			'certificates' => $this->Certificate_model->for_user($this->user['id']),
		), array('title' => 'Certificates', 'nav' => 'certificates', 'app' => TRUE));
	}

	/** Public, printable. */
	public function show($code)
	{
		$cert = $this->Certificate_model->by_code($code);
		if ( ! $cert) {
			show_404();
		}
		$this->render('certificates/show', array('cert' => $cert),
			array('title' => 'Certificate ' . $cert['verification_code']));
	}

	/** "Check a certificate": type the code printed on it. */
	public function verify()
	{
		$code = (string) $this->input->get('code');
		if ($code !== '') {
			if ($this->Certificate_model->by_code($code)) {
				redirect('certificate/' . Certificate_model::normalise($code));
			}
			$this->output->set_status_header(404);
		}
		$this->render('certificates/verify', array('code' => $code), array('title' => 'Verify a certificate'));
	}
}
