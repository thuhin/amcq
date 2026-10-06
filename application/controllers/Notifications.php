<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Bell icon in the signed-in header (designs 08-12). */
class Notifications extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->render('notifications/index', array(
			'items' => $this->db->where('user_id', $this->user['id'])->order_by('id', 'DESC')->limit(50)->get('notifications')->result_array(),
		), array('title' => 'Notifications', 'nav' => 'notifications', 'app' => TRUE));
	}

	public function read()
	{
		$this->require_login();
		if ($this->input->method() === 'post') {
			$this->db->where('user_id', $this->user['id'])->where('read_at IS NULL', NULL, FALSE)
				->update('notifications', array('read_at' => date('Y-m-d H:i:s')));
		}
		redirect('notifications');
	}
}
