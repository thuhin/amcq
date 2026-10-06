<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Certificates (dashboard sidebar, design 12; guideline §5.6). */
class Certificates extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->render('certificates/index', array(
			'certificates' => $this->db->select('c.*, k.name AS competition, k.year')->from('certificates c')
				->join('competitions k', 'k.id = c.competition_id')->where('c.user_id', $this->user['id'])
				->order_by('c.issued_at', 'DESC')->get()->result_array(),
		), array('title' => 'Certificates', 'nav' => 'certificates', 'app' => TRUE));
	}
}
