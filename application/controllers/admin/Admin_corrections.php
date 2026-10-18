<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Correct Me: students report questions they think are wrong; an admin decides. */
class Admin_corrections extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Correction_model');
	}

	public function index()
	{
		$status = (string) $this->input->get('status');
		if ( ! in_array($status, array('pending', 'approved', 'rejected'), TRUE)) {
			$status = 'pending';
		}
		$page = $this->page_number();
		list($rows, $total) = $this->Correction_model->queue($status, $page);
		$this->render('admin/corrections/index', array(
			'rows'    => $rows,
			'total'   => $total,
			'status'  => $status,
			'counts'  => $this->Correction_model->counts(),
			'page_no' => $page,
			'pages'   => max(1, (int) ceil($total / ADMIN_PAGE_SIZE)),
		), array('title' => 'Corrections', 'nav' => 'corrections'));
	}

	public function review($id)
	{
		$r = $this->Correction_model->find_full($id);
		if ( ! $r) {
			show_404();
		}
		$this->render('admin/corrections/review', array('r' => $r), array('title' => 'Report #' . (int) $r['id'], 'nav' => 'corrections'));
	}

	public function approve($id)
	{
		$this->require_post();
		$note = trim((string) $this->input->post('note'));
		if (mb_strlen($note) > 255) {
			$this->flash('warning', 'Keep the note under 255 characters.');
			redirect('admin/corrections/' . (int) $id);
		}
		$result = $this->Correction_model->approve((int) $id, $this->admin['id'], $note, $this->input->post('pull_question') === '1');
		$this->report($result, 'Approved. The student earned ' . CORRECTION_APPROVED_POINTS . ' Academic Point' . (CORRECTION_APPROVED_POINTS === 1 ? '' : 's') . ' and has been told.');
	}

	public function reject($id)
	{
		$this->require_post();
		$note = trim((string) $this->input->post('note'));
		if (mb_strlen($note) < 3 || mb_strlen($note) > 255) {
			$this->flash('warning', 'Write a short reason (3 to 255 characters). The student reads it.');
			redirect('admin/corrections/' . (int) $id);
		}
		$result = $this->Correction_model->reject((int) $id, $this->admin['id'], $note);
		$this->report($result, 'Not approved. The student has been told why.');
	}

	private function report($result, $success)
	{
		$messages = array(
			'ok'        => array('success', $success),
			'decided'   => array('info', 'That report was already decided.'),
			'not_found' => array('warning', 'That report no longer exists.'),
			'failed'    => array('warning', 'It could not be saved. Please try again.'),
		);
		list($type, $text) = $messages[$result];
		$this->flash($type, $text);
		redirect($result === 'failed' && $this->uri->segment(3) ? 'admin/corrections/' . (int) $this->uri->segment(3) : 'admin/corrections');
	}
}
