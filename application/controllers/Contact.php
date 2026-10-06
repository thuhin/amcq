<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Contact Us: details plus a message form saved to contact_messages. */
class Contact extends MY_Controller
{
	public function index()
	{
		$this->load->model(array('Contact_model', 'User_model'));
		$this->load->library('form_validation');
		$error = NULL;

		if ($this->input->method() === 'post') {
			// Honeypot: a field hidden from people. Bots fill every field; a
			// filled one is dropped silently with the normal thank-you, so the
			// bot learns nothing.
			if (trim((string) $this->input->post('website')) !== '') {
				$this->flash('success', 'Thank you. We have received your message.');
				redirect('contact');
			}

			$this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[100]');
			$this->form_validation->set_rules('phone', 'Mobile number', 'trim|max_length[20]|callback__valid_phone');
			$this->form_validation->set_rules('email', 'Email', 'trim|max_length[150]|valid_email');
			$this->form_validation->set_rules('topic', 'Topic', 'required|in_list[' . implode(',', array_keys(Contact_model::TOPICS)) . ']');
			$this->form_validation->set_rules('message', 'Message', 'trim|required|min_length[10]|max_length[2000]');

			$ip = $this->input->ip_address();
			if ($this->form_validation->run()) {
				$phone = $this->input->post('phone') !== '' ? $this->User_model->normalise_phone($this->input->post('phone')) : NULL;
				$email = $this->input->post('email') ?: NULL;
				if ( ! $phone && ! $email) {
					$error = 'Give us a mobile number or an email so we can reply.';
				} elseif ($this->Contact_model->over_limit($ip)) {
					$error = 'You have sent several messages in the last hour. Please try again later.';
				} else {
					$id = $this->Contact_model->save(array(
						'user_id'    => $this->user ? $this->user['id'] : NULL,
						'name'       => $this->input->post('name'),
						'phone'      => $phone,
						'email'      => $email,
						'topic'      => $this->input->post('topic'),
						'message'    => $this->input->post('message'),
						'ip'         => $ip,
						'user_agent' => $this->input->user_agent(),
					));
					$this->session->set_flashdata('contact_ref', Contact_model::reference($id));
					redirect('contact#sent');
				}
			}
		}

		$this->render('contact/index', array(
			'topics' => Contact_model::TOPICS,
			'error'  => $error,
			'ref'    => $this->session->flashdata('contact_ref'),
		), array('title' => 'Contact Us', 'crumbs' => array(array('Home', ''), array('Contact Us', NULL))));
	}

	/** Optional field, but if given it must be a Bangladeshi mobile number. */
	public function _valid_phone($value)
	{
		if ($value === '' || $value === NULL) {
			return TRUE;
		}
		$this->load->model('User_model');
		if ($this->User_model->normalise_phone($value)) {
			return TRUE;
		}
		$this->form_validation->set_message('_valid_phone', 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.');
		return FALSE;
	}
}
