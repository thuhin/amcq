<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Profile & settings (guideline §5.8). */
class Profile extends MY_Controller
{
	public function index()
	{
		$this->require_login();
		$this->load->model(array('User_model', 'Curriculum_model'));
		$this->load->library('form_validation');
		$uid = $this->user['id'];

		$this->form_validation->set_rules('name', 'Name', 'trim|required|min_length[2]|max_length[100]');
		$this->form_validation->set_rules('display_name', 'Display name', 'trim|required|min_length[2]|max_length[50]');
		$this->form_validation->set_rules('school_id', 'School', 'trim|integer');
		$this->form_validation->set_rules('language', 'Language', 'in_list[bn,en]');

		if ($this->form_validation->run()) {
			$school_id = (int) $this->input->post('school_id');
			$this->User_model->update_profile($uid, array(
				'name'         => $this->input->post('name'),
				'display_name' => $this->input->post('display_name'),
				'school_id'    => $school_id && $this->db->where('id', $school_id)->count_all_results('schools') ? $school_id : NULL,
			));
			$this->User_model->update_settings($uid, array(
				'language'            => $this->input->post('language'),
				'show_on_leaderboard' => $this->input->post('show_on_leaderboard') ? 1 : 0,
				'notify_streak'       => $this->input->post('notify_streak') ? 1 : 0,
				'notify_competition'  => $this->input->post('notify_competition') ? 1 : 0,
			));
			$this->flash('success', 'Profile saved.');
			redirect('profile');
		}
		$this->render('profile/index', array(
			'settings' => $this->User_model->settings($uid),
			'schools'  => $this->User_model->schools(),
			'class'    => $this->db->get_where('classes', array('id' => $this->user['class_id']))->row_array(),
		), array('title' => 'Profile & Settings', 'nav' => 'profile'));
	}
}
