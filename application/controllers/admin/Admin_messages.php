<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Contact Us messages. Replies happen by phone or email, outside the system. */
class Admin_messages extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Contact_model');
	}

	public function index()
	{
		$f = array('status' => (string) $this->input->get('status'), 'topic' => (string) $this->input->get('topic'));
		if ( ! isset(Contact_model::STATUSES[$f['status']])) {
			$f['status'] = '';
		}
		if ( ! isset(Contact_model::TOPICS[$f['topic']])) {
			$f['topic'] = '';
		}
		$page = $this->page_number();
		list($rows, $total) = $this->Contact_model->inbox($f, $page);
		$this->render('admin/messages/index', array(
			'rows'    => $rows,
			'total'   => $total,
			'filters' => $f,
			'by_status' => $this->Contact_model->counts(),
			'page_no' => $page,
			'pages'   => max(1, (int) ceil($total / ADMIN_PAGE_SIZE)),
		), array('title' => 'Messages', 'nav' => 'messages'));
	}

	public function show($id)
	{
		$m = $this->Contact_model->find($id);
		if ( ! $m) {
			show_404();
		}
		// Opening a new message marks it read, except right after an admin
		// chose "Mark as new": that choice must stick.
		if ($m['status'] === 'new' && ! $this->session->flashdata('keep_unread')) {
			$this->Contact_model->mark_read($m['id']);
			$m['status'] = 'read';
		}
		$this->render('admin/messages/show', array('m' => $m), array('title' => 'Message ' . Contact_model::reference($m['id']), 'nav' => 'messages'));
	}

	public function status($id)
	{
		$this->require_post();
		$to = (string) $this->input->post('to');
		if ( ! $this->Contact_model->find($id) || ! $this->Contact_model->set_status((int) $id, $to)) {
			show_404();
		}
		$this->flash('success', 'Marked ' . strtolower(Contact_model::STATUSES[$to]) . '.');
		$this->session->set_flashdata('keep_unread', TRUE);   // read by show() on the redirect that follows
		redirect('admin/messages/' . (int) $id);
	}
}
