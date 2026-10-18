<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Students: search, look, suspend. Read only otherwise: no admin screen changes
 * a wallet balance or Academic Points (docs/admin-panel.md).
 */
class Admin_students extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Student_model');
	}

	public function index()
	{
		$f = array('q' => trim((string) $this->input->get('q')), 'status' => (string) $this->input->get('status'));
		if ( ! in_array($f['status'], array('active', 'suspended'), TRUE)) {
			$f['status'] = '';
		}
		$page = $this->page_number();
		list($rows, $total) = $this->Student_model->search($f, $page);
		$this->render('admin/students/index', array(
			'rows' => $rows, 'total' => $total, 'filters' => $f,
			'page_no' => $page, 'pages' => max(1, (int) ceil($total / ADMIN_PAGE_SIZE)),
		), array('title' => 'Students', 'nav' => 'students'));
	}

	public function show($id)
	{
		$s = $this->Student_model->find($id);
		if ( ! $s) {
			show_404();
		}
		$this->load->model(array('Wallet_model', 'Points_model', 'Correction_model', 'Referral_model'));
		$this->render('admin/students/show', array(
			's'            => $s,
			'sums'         => $this->Student_model->ledger_sums($s['id']),
			'wallet'       => $this->Wallet_model->transactions($s['id'], 50),
			'points'       => $this->Points_model->history($s['id'], 50),
			'rank'         => $this->Points_model->rank($s['id']),
			'attempts'     => $this->Student_model->attempts($s['id']),
			'corrections'  => $this->Correction_model->mine($s['id']),
			'registrations' => $this->Student_model->registrations($s['id']),
			'invited_by'   => $this->Referral_model->invited_by($s['id']),
			'invited'      => $this->Referral_model->invited($s['id']),
		), array('title' => $s['name'] ?: 'Student #' . $s['id'], 'nav' => 'students'));
	}

	public function status($id)
	{
		$this->require_post();
		$to = (string) $this->input->post('to');
		if ($this->Student_model->set_status((int) $id, $to)) {
			$this->flash('success', $to === 'suspended'
				? 'Suspended. The student is signed out and cannot sign in until reactivated.'
				: 'Reactivated. The student can sign in again.');
		} else {
			$this->flash('info', 'Nothing changed.');
		}
		redirect('admin/students/' . (int) $id);
	}
}
