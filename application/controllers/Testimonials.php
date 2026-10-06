<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** "View More Testimonials" from the homepage. */
class Testimonials extends MY_Controller
{
	public function index()
	{
		$this->load->model('School_model');
		$this->render('testimonials/index', array('items' => $this->School_model->testimonials(60)),
			array('title' => 'What Students Say'));
	}
}
