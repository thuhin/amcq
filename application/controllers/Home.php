<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public home page.
 *
 * Guest-first (brand guideline §6.2): everything here is reachable without an
 * account, and the quick-start selector hands a visitor straight to a quiz.
 */
class Home extends CI_Controller
{
	public function index()
	{
		$data = array(
			'page_title' => 'AcademicMCQ — Practice. Learn. Compete.',
			// Hard-coded for the first commit. These become queries against
			// classes/subjects once the curriculum tables hold real rows;
			// the view already reads them as if they came from the database.
			'classes' => array(
				array('name' => 'Class 5',  'slug' => 'class-5',  'active' => TRUE),
				array('name' => 'Class 6',  'slug' => 'class-6',  'active' => FALSE),
				array('name' => 'Class 7',  'slug' => 'class-7',  'active' => FALSE),
				array('name' => 'Class 8',  'slug' => 'class-8',  'active' => FALSE),
				array('name' => 'Class 9',  'slug' => 'class-9',  'active' => FALSE),
				array('name' => 'Class 10', 'slug' => 'class-10', 'active' => FALSE),
			),
			'subjects' => array(
				array('name' => 'Mathematics',  'chapters' => 12),
				array('name' => 'Science',      'chapters' => 14),
				array('name' => 'Bangla',       'chapters' => 12),
				array('name' => 'English',      'chapters' => 12),
				array('name' => 'Islam & Moral','chapters' => 10),
				array('name' => 'Bangladesh & Global Studies', 'chapters' => 12),
			),
		);

		$this->load->view('layout/header', $data);
		$this->load->view('home/index', $data);
		$this->load->view('layout/footer');
	}
}
