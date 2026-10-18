<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** "What Students Say": add, edit, publish (only with guardian consent). */
class Admin_testimonials extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Testimonial_model');
	}

	public function index()
	{
		$this->render('admin/testimonials/index', array('rows' => $this->Testimonial_model->all()),
			array('title' => 'Testimonials', 'nav' => 'testimonials'));
	}

	public function edit($id = NULL)
	{
		$t = NULL;
		if ($id !== NULL) {
			$t = $this->Testimonial_model->find($id);
			if ( ! $t) {
				show_404();
			}
		}
		$errors = array();
		$form = $t ?: array('student_name' => '', 'class_label' => 'Class 5', 'school_name' => '', 'quote' => '', 'rating' => 5, 'sort_order' => 0, 'consent_at' => NULL, 'is_published' => 0);

		if ($this->input->method() === 'post') {
			$in = $this->input->post(NULL, FALSE);
			$in = is_array($in) ? $in : array();
			list($saved, $errors, $cleared) = $this->Testimonial_model->save($t ? (int) $t['id'] : NULL, $in);
			if ( ! $errors) {
				$this->flash($cleared ? 'warning' : 'success', $cleared
					? 'Saved. The wording changed, so the recorded consent was cleared and it is unpublished. Tick the consent box once the guardian agrees to these words.'
					: 'Saved.');
				redirect('admin/testimonials/' . $saved);
			}
			$form = array_merge($form, $in);
		}
		$this->render('admin/testimonials/form', array('t' => $t, 'form' => $form, 'errors' => $errors),
			array('title' => $t ? 'Edit testimonial' : 'New testimonial', 'nav' => 'testimonials'));
	}

	public function publish($id)
	{
		$this->require_post();
		$result = $this->Testimonial_model->publish((int) $id);
		$messages = array(
			'ok'         => array('success', 'Published on the homepage.'),
			'no_consent' => array('warning', 'It cannot be published until the guardian\'s consent is recorded.'),
			'not_found'  => array('warning', 'That testimonial no longer exists.'),
		);
		$this->flash($messages[$result][0], $messages[$result][1]);
		redirect('admin/testimonials');
	}

	public function unpublish($id)
	{
		$this->require_post();
		$this->Testimonial_model->unpublish((int) $id);
		$this->flash('success', 'Taken off the homepage.');
		redirect('admin/testimonials');
	}

	public function delete($id)
	{
		$this->require_post();
		if ($this->Testimonial_model->delete((int) $id)) {
			$this->flash('success', 'Deleted.');
		} else {
			$this->flash('warning', 'Only an unpublished testimonial can be deleted. Unpublish it first.');
		}
		redirect('admin/testimonials');
	}
}
