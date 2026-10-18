<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for every /admin page.
 *
 * Admin identity lives in its own session keys (admin_id, admin_seen), never
 * in the student's user_id: a student session is not an admin session, and an
 * admin signing in does not sign them in as a student. The account is read
 * from the database on every request, so a suspended admin is out at once, and
 * a request more than ADMIN_SESSION_IDLE_MINUTES after the last one signs out.
 */
class Admin_Controller extends MY_Controller
{
	/** @var array|null the signed-in admin row */
	protected $admin;

	/** Set FALSE in the controller that serves the sign-in page. */
	protected $requires_admin = TRUE;

	public function __construct()
	{
		parent::__construct();
		// Admin pages show private data and must not linger in a browser cache,
		// a proxy, a search index or somebody else's frame.
		$this->output->set_header('Cache-Control: no-store, max-age=0');
		$this->output->set_header('X-Frame-Options: DENY');
		$this->output->set_header('X-Robots-Tag: noindex, nofollow');

		$this->load->model('Admin_model');
		$expired = $this->resolve_admin();
		if ($this->requires_admin && ! $this->admin) {
			if ($expired) {
				$this->flash('info', 'You were signed out after ' . ADMIN_SESSION_IDLE_MINUTES . ' minutes without activity.');
			}
			if ($this->input->method() === 'get') {
				$this->session->set_userdata('admin_after_login', uri_string());
			}
			redirect('admin/login');
		}
	}

	/** @return bool TRUE when an existing session had just gone idle */
	private function resolve_admin()
	{
		$id = (int) $this->session->userdata('admin_id');
		if ( ! $id) {
			return FALSE;
		}
		$seen = (int) $this->session->userdata('admin_seen');
		if ($seen && time() - $seen > ADMIN_SESSION_IDLE_MINUTES * 60) {
			$this->forget_admin();
			return TRUE;
		}
		$this->admin = $this->Admin_model->find($id);
		if ( ! $this->admin) {
			$this->forget_admin();
			return FALSE;
		}
		$this->session->set_userdata('admin_seen', time());
		return FALSE;
	}

	protected function sign_in_admin($admin_id)
	{
		$this->session->sess_regenerate(TRUE);   // a new session id at the moment of privilege
		$this->session->set_userdata(array('admin_id' => (int) $admin_id, 'admin_seen' => time()));
	}

	protected function forget_admin()
	{
		$this->session->unset_userdata(array('admin_id', 'admin_seen'));
		$this->admin = NULL;
	}

	/** Every change is a POST. A GET to a change URL is refused, not obeyed. */
	protected function require_post()
	{
		if ($this->input->method() !== 'post') {
			show_error('Method not allowed', 405);
		}
	}

	/** Current page number of a list, 1 or more. */
	protected function page_number()
	{
		return max(1, (int) $this->input->get('page'));
	}

	/**
	 * Render a view inside the admin layout.
	 *
	 * @param array $page layout options: title, nav (active tab)
	 */
	protected function render($view, array $data = array(), array $page = array())
	{
		$data['admin']  = $this->admin;
		$data['flash']  = $this->session->flashdata('flash');
		$data['page']   = array_merge(array('title' => 'Admin', 'nav' => ''), $page);
		$data['attention'] = $this->admin ? $this->Admin_model->attention_counts() : array();

		$this->load->view('admin/layout/header', $data);
		$this->load->view($view, $data);
		$this->load->view('admin/layout/footer', $data);
	}
}
