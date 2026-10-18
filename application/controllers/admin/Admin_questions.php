<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** The question bank: list, write, edit, and move along the workflow. */
class Admin_questions extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Question_model');
	}

	public function index()
	{
		$f = array(
			'q'          => trim((string) $this->input->get('q')),
			'status'     => (string) $this->input->get('status'),
			'difficulty' => (string) $this->input->get('difficulty'),
			'origin'     => (string) $this->input->get('origin'),
			'chapter_id' => (int) $this->input->get('chapter_id') ?: '',
		);
		// Anything that is not a known value is ignored rather than trusted.
		foreach (array('status' => Question_model::STATUSES, 'difficulty' => Question_model::DIFFICULTIES, 'origin' => Question_model::ORIGINS) as $k => $allowed) {
			if ( ! isset($allowed[$f[$k]])) {
				$f[$k] = '';
			}
		}

		$page = $this->page_number();
		list($rows, $total) = $this->Question_model->search($f, $page);
		$this->render('admin/questions/index', array(
			'rows'     => $rows,
			'total'    => $total,
			'filters'  => $f,
			'chapters' => $this->Question_model->chapter_choices(),
			'page_no'  => $page,
			'pages'    => max(1, (int) ceil($total / ADMIN_PAGE_SIZE)),
			'by_status' => $this->Question_model->counts_by_status(),
		), array('title' => 'Questions', 'nav' => 'questions'));
	}

	/** New (no id) or edit. */
	public function edit($id = NULL)
	{
		$question = NULL;
		if ($id !== NULL) {
			$question = $this->Question_model->find($id);
			if ( ! $question) {
				show_404();
			}
		}

		$errors = array();
		$form = $this->form_values($question);
		if ($this->input->method() === 'post') {
			$in = $this->input->post(NULL, FALSE);
			list($saved_id, $errors, $reset) = $this->Question_model->save($question ? (int) $question['id'] : NULL, is_array($in) ? $in : array());
			if ( ! $errors) {
				if ($reset) {
					$this->flash('warning', 'Saved. The question was verified or live, so it is back in review until you verify and publish it again.');
				} else {
					$this->flash('success', $question ? 'Saved.' : 'Question created as a draft.');
				}
				redirect('admin/questions/' . $saved_id);
			}
			$form = $this->form_values(NULL, $in);
		}

		$this->render('admin/questions/form', array(
			'q'        => $question,
			'form'     => $form,
			'errors'   => $errors,
			'chapters' => $this->Question_model->chapter_choices(),
			'topics'   => $this->Question_model->topic_choices(),
			'problems' => $question ? $this->Question_model->completeness($question) : array(),
			'next'     => $question ? Question_model::TRANSITIONS[$question['status']] : array(),
		), array('title' => $question ? 'Question #' . $question['id'] : 'New question', 'nav' => 'questions'));
	}

	public function status($id)
	{
		$this->require_post();
		$to = (string) $this->input->post('to');
		list($ok, $messages) = $this->Question_model->transition((int) $id, $to, $this->admin['id']);
		if ($ok) {
			$this->flash('success', 'Moved to ' . strtolower(Question_model::STATUSES[$to]) . '.');
		} else {
			$this->flash('warning', implode(' ', $messages));
		}
		redirect('admin/questions/' . (int) $id);
	}

	/** Field values for the form: from the saved question, from a failed post, or blank. */
	private function form_values($question, $post = NULL)
	{
		$v = array(
			'chapter_id' => (int) $this->input->get('chapter_id') ?: '', 'topic_id' => '', 'difficulty' => '', 'stem' => '',
			'explanation' => '', 'source_ref' => '', 'correct' => '',
			'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '',
		);
		if ($question) {
			foreach (array('chapter_id', 'topic_id', 'difficulty', 'stem', 'explanation', 'source_ref') as $k) {
				$v[$k] = (string) $question[$k];
			}
			foreach ($question['options'] as $label => $o) {
				$v['option_' . strtolower($label)] = $o['body'];
				if ($o['is_correct']) {
					$v['correct'] = $label;
				}
			}
		}
		if (is_array($post)) {
			foreach ($v as $k => $_) {
				if (isset($post[$k])) {
					$v[$k] = (string) $post[$k];
				}
			}
		}
		return $v;
	}
}
