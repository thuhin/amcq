<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Correct Me" (guideline §5.5). Reports a question as wrong; an approval
 * earns 1 Academic Point. The career copy says "eligible for assessment",
 * never a job promise (blueprint v2.0).
 */
class Correct_me extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->require_login();
		$this->load->model('Correction_model');
	}

	public function index()
	{
		$uid = $this->user['id'];
		$this->render('correct_me/index', array(
			'requests'   => $this->Correction_model->mine($uid),
			'approved'   => $this->Correction_model->approved_count($uid),
			'milestones' => $this->Correction_model->milestones(),
		), array('title' => 'Correct Me', 'nav' => 'correct', 'app' => TRUE));
	}

	public function question($question_id)
	{
		$question = $this->db->get_where('questions', array('id' => (int) $question_id, 'status' => 'published'))->row_array();
		if ( ! $question) {
			show_404();
		}
		$options = $this->db->where('question_id', $question['id'])->order_by('label')->get('question_options')->result_array();

		$this->load->library('form_validation');
		$this->form_validation->set_rules('what_is_wrong', 'What is wrong', 'trim|required|min_length[5]|max_length[1000]');
		$this->form_validation->set_rules('explanation', 'Why', 'trim|required|min_length[10]|max_length[2000]');
		$this->form_validation->set_rules('source_ref', 'Source', 'trim|max_length[255]');
		$this->form_validation->set_rules('claimed_option_id', 'Correct answer', 'trim|integer');

		if ($this->form_validation->run()) {
			$result = $this->Correction_model->submit($this->user['id'], $question['id'], array(
				'what_is_wrong'     => $this->input->post('what_is_wrong'),
				'explanation'       => $this->input->post('explanation'),
				'source_ref'        => $this->input->post('source_ref'),
				'claimed_option_id' => $this->input->post('claimed_option_id'),
			));
			$this->flash($result === 'ok' ? 'success' : 'info', $result === 'ok'
				? 'Thank you. Your correction is pending review.'
				: 'You already have a pending report for this question.');
			redirect('correct-me');
		}
		$this->render('correct_me/question', array(
			'question' => $question, 'options' => $options,
			'back'     => $this->input->get('back'),
		), array('title' => 'Correct Me', 'nav' => 'correct', 'app' => TRUE));
	}
}
