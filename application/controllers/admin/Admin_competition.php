<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Competitions, read only.
 *
 * There is deliberately no way to change a status here: the app has no Round 1
 * or Final code, and opening registration would let students pay Tk 99 for an
 * event that cannot run. Add controls once the rounds exist.
 */
class Admin_competition extends Admin_Controller
{
	public function index()
	{
		$this->load->model('Competition_model');
		$this->render('admin/competition/index', array('rows' => $this->Competition_model->overview()),
			array('title' => 'Competition', 'nav' => 'competition'));
	}
}
