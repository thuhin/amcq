<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for every page.
 *
 * Resolves who is asking, a signed-in student or a guest, once per request,
 * and renders views inside the shared layout.
 */
class MY_Controller extends CI_Controller
{
	/** @var array|null signed-in user row, NULL for guests */
	protected $user;

	public function __construct()
	{
		parent::__construct();
		$this->db->query('SET time_zone = ?', array(APP_TZ_OFFSET));

		$user_id = (int) $this->session->userdata('user_id');
		if ($user_id) {
			$this->user = $this->db->select('u.*, p.total_points, t.title AS tier_title, t.id AS tier_id')
				->from('users u')
				->join('user_lifetime_points p', 'p.user_id = u.id', 'left')
				->join('tiers t', 't.id = COALESCE(p.tier_id, 0)', 'left')
				->where('u.id', $user_id)->where('u.status', 'active')
				->get()->row_array();
			// A deleted or suspended account must not keep a live session.
			if ( ! $this->user) {
				$this->session->unset_userdata('user_id');
			}
		}
	}

	/**
	 * A stable identity for a guest's quiz attempts.
	 *
	 * Not session_id(): CodeIgniter rotates that every few minutes, which
	 * would orphan a guest's in-progress quiz. This token lives in the session
	 * data instead, and on signup the attempts it owns are moved to the new
	 * account, so the result the guest just earned is kept (guideline §4.9).
	 */
	protected function guest_token()
	{
		$token = $this->session->userdata('guest_token');
		if ( ! $token) {
			$token = bin2hex(random_bytes(20));
			$this->session->set_userdata('guest_token', $token);
		}
		return $token;
	}

	protected function require_login()
	{
		if ( ! $this->user) {
			$this->session->set_userdata('after_login', uri_string());
			redirect('login');
		}
	}

	protected function flash($type, $message)
	{
		$this->session->set_flashdata('flash', array('type' => $type, 'message' => $message));
	}

	/**
	 * Render a view inside the layout.
	 *
	 * @param string $view  view path under application/views
	 * @param array  $data  view data
	 * @param array  $page  layout options: title, nav (active item), crumbs,
	 *                      bare (TRUE hides the main nav, for the quiz screen)
	 */
	protected function render($view, array $data = array(), array $page = array())
	{
		$data['user']  = $this->user;
		$data['flash'] = $this->session->flashdata('flash');
		$data['page']  = array_merge(array(
			'title'  => 'AcademicMCQ',
			'nav'    => '',
			'crumbs' => array(),
			'app'    => FALSE,   // TRUE = signed-in page with the dashboard sidebar (design 12)
		), $page);
		$data['unread'] = 0;
		if ($this->user) {
			$data['unread'] = (int) $this->db->where('user_id', $this->user['id'])
				->where('read_at IS NULL', NULL, FALSE)->count_all_results('notifications');
		}

		$this->load->view('layout/header', $data);
		$this->load->view($view, $data);
		$this->load->view('layout/footer', $data);
	}
}
