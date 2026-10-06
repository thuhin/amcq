<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Take a quiz (design 08), see the result (design 10), review answers
 * (design 09).
 *
 * The quiz works without JavaScript: each answer is a form post and the
 * server decides the next question. JS only adds the countdown and
 * auto-submit, which keeps it usable on slow phones (guideline §6.4).
 */
class Quiz extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Quiz_model', 'Curriculum_model'));
	}

	public function start()
	{
		$this->require_post();
		$chapter = $this->Curriculum_model->chapter((int) $this->input->post('chapter_id'));
		if ( ! $chapter || ! $chapter['is_active']) {
			show_404();
		}
		$topic_id = (int) $this->input->post('topic_id') ?: NULL;
		if ($topic_id) {
			$topic = $this->Curriculum_model->topic($topic_id);
			if ( ! $topic || (int) $topic['chapter_id'] !== (int) $chapter['id']) {
				show_404();
			}
		}
		$back = 'practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug'];

		$user_id = $this->user ? (int) $this->user['id'] : NULL;
		// Light device fingerprint, the v1.1 mitigation against farming the
		// guest flow by clearing cookies.
		$device = hash('sha256', $this->input->ip_address() . '|' . $this->input->user_agent());
		$mode = $this->input->post('mode') === 'hard' ? 'hard' : 'standard';
		$result = $this->Quiz_model->start($chapter['id'], $topic_id, $user_id, $this->guest_token(), $device, $mode);

		if ($result['ok']) {
			redirect('quiz/' . $result['attempt_id']);
		}
		switch ($result['error']) {
			case 'guest_limit':
				$this->flash('info', 'You have used your ' . GUEST_FREE_QUIZZES . ' free quizzes. Create a free account to keep practising and save your progress.');
				redirect('login');
			case 'insufficient_balance':
				$this->flash('warning', 'Each quiz costs Tk ' . number_format(QUIZ_FEE_TAKA, 0) . '. Add balance to your wallet to continue.');
				redirect('wallet');
			default:
				$this->flash('warning', 'This quiz is not available yet.');
				redirect($back);
		}
	}

	public function take($id)
	{
		$attempt = $this->load_attempt($id);
		if ($attempt['status'] !== 'in_progress') {
			redirect('quiz/' . $id . '/result');
		}
		if ($this->Quiz_model->seconds_left($attempt) === 0) {
			$this->Quiz_model->finish($id);
			$this->flash('info', 'Time is up. Your answers were submitted.');
			redirect('quiz/' . $id . '/result');
		}

		$items = $this->Quiz_model->items($id);
		$q = (int) $this->input->get('q');
		if ($q < 1 || $q > count($items)) {
			// Resume at the first unanswered question.
			$q = 1;
			foreach ($items as $it) {
				if ( ! $it['selected_option_id']) {
					$q = (int) $it['position'];
					break;
				}
			}
		}
		$chapter = $this->Curriculum_model->chapter($attempt['chapter_id']);

		$this->render('quiz/take', array(
			'attempt'      => $attempt,
			'items'        => $items,
			'current'      => $items[$q - 1],
			'q'            => $q,
			'chapter'      => $chapter,
			'topic'        => $attempt['topic_id'] ? $this->Curriculum_model->topic($attempt['topic_id']) : NULL,
			'seconds_left' => $this->Quiz_model->seconds_left($attempt),
			'paused'       => (bool) $attempt['paused_at'],
			'answered'     => count(array_filter(array_column($items, 'selected_option_id'))),
			'attempts'     => $this->Quiz_model->chapter_attempts($attempt),
			'coverage'     => $this->Quiz_model->chapter_coverage($this->user ? $this->user['id'] : NULL, $chapter['id']),
		), array('title' => 'Question ' . $q . ' of ' . count($items), 'nav' => 'practice',
		         'crumbs' => $this->crumbs($chapter, 'Practice Quiz')));
	}

	public function answer($id)
	{
		$this->require_post();
		$attempt = $this->load_attempt($id);
		if ($attempt['status'] !== 'in_progress') {
			redirect('quiz/' . $id . '/result');
		}

		$position = (int) $this->input->post('position');
		$this->Quiz_model->save_answer($attempt, $position,
			(int) $this->input->post('option_id'), (bool) $this->input->post('review'));

		$go = $this->input->post('go');
		$total = (int) $attempt['total_questions'];
		if ($go === 'submit') {
			$this->Quiz_model->finish($id);
			redirect('quiz/' . $id . '/result');
		}
		if ($go === 'prev') {
			$target = max(1, $position - 1);
		} elseif ($go === 'next') {
			$target = min($total, $position + 1);
		} else {
			$target = min($total, max(1, (int) $go));   // navigator number
		}
		redirect('quiz/' . $id . '?q=' . $target);
	}

	/** Pause / resume the timer (design 08). */
	public function pause($id)
	{
		$this->require_post();
		$attempt = $this->load_attempt($id);
		$this->Quiz_model->pause($attempt);
		redirect('quiz/' . $id . '?q=' . (int) $this->input->post('position'));
	}

	public function resume($id)
	{
		$this->require_post();
		$attempt = $this->load_attempt($id);
		$this->Quiz_model->resume($attempt);
		redirect('quiz/' . $id . '?q=' . (int) $this->input->post('position'));
	}

	public function submit($id)
	{
		$this->require_post();
		$this->load_attempt($id);
		$this->Quiz_model->finish($id);
		redirect('quiz/' . $id . '/result');
	}

	public function result($id)
	{
		$attempt = $this->load_attempt($id);
		if ($attempt['status'] !== 'completed') {
			redirect('quiz/' . $id);
		}
		$items = $this->Quiz_model->items($id);
		$chapter = $this->Curriculum_model->chapter($attempt['chapter_id']);

		$data = array(
			'attempt'   => $attempt,
			'items'     => $items,
			'chapter'   => $chapter,
			'topic'     => $attempt['topic_id'] ? $this->Curriculum_model->topic($attempt['topic_id']) : NULL,
			'breakdown' => $this->Quiz_model->breakdown($items),
			'wrong'     => count(array_filter($items, function ($i) { return ! $i['is_correct']; })),
			'next_chapter' => $this->next_chapter($chapter),
			'coverage'  => $this->Quiz_model->chapter_coverage($this->user ? $this->user['id'] : NULL, $chapter['id']),
			// "Try Harder Quiz" needs enough hard questions in the chapter.
			'can_harder' => (bool) $this->Quiz_model->plan($this->Curriculum_model->question_mix($chapter['id']), 'hard'),
		);
		if ($this->user) {
			$this->load->model('Points_model');
			$uid = $this->user['id'];
			$progress = $this->Curriculum_model->progress_by_chapter($uid);
			$data['rank']        = $this->Points_model->rank($uid);
			$data['rank_change'] = $this->Points_model->rank_change($uid);
			$data['streak']      = $this->Quiz_model->streak($uid);
			$data['progress']    = isset($progress[$chapter['id']]) ? $progress[$chapter['id']] : NULL;
		}
		$this->render('quiz/result', $data, array(
			'title' => 'Result', 'nav' => 'practice',
			'crumbs' => $this->crumbs($chapter, 'Result'),
		));
	}

	public function review($id)
	{
		$attempt = $this->load_attempt($id);
		if ($attempt['status'] !== 'completed') {
			redirect('quiz/' . $id);
		}
		$items = $this->Quiz_model->items($id);
		$filter = in_array($this->input->get('filter'), array('correct', 'incorrect'), TRUE) ? $this->input->get('filter') : 'all';
		$shown = array_values(array_filter($items, function ($i) use ($filter) {
			return $filter === 'all' || ($filter === 'correct') === (bool) $i['is_correct'];
		}));
		$q = (int) $this->input->get('q');
		$current = NULL;
		foreach ($shown as $it) {
			if ((int) $it['position'] === $q) {
				$current = $it;
			}
		}
		$current = $current ?: ($shown ? $shown[0] : NULL);
		$chapter = $this->Curriculum_model->chapter($attempt['chapter_id']);

		$data = array(
			'attempt' => $attempt, 'items' => $items, 'shown' => $shown, 'current' => $current,
			'filter'  => $filter, 'chapter' => $chapter,
			'correct' => (int) $attempt['score'], 'incorrect' => count($items) - (int) $attempt['score'],
			'coverage' => $this->Quiz_model->chapter_coverage($this->user ? $this->user['id'] : NULL, $chapter['id']),
		);
		if ($this->user) {
			$this->load->model('Points_model');
			$data['rank'] = $this->Points_model->rank($this->user['id']);
			$data['rank_change'] = $this->Points_model->rank_change($this->user['id']);
		}
		$this->render('quiz/review', $data, array(
			'title' => 'Answer Review', 'nav' => 'practice',
			'crumbs' => $this->crumbs($chapter, 'Quiz Result'),
		));
	}

	/* ------------------------------------------------------------------ */

	private function load_attempt($id)
	{
		$attempt = $this->Quiz_model->get($id);
		$uid = $this->user ? $this->user['id'] : NULL;
		// 404, not 403: do not confirm that someone else's attempt exists.
		if ( ! $attempt || ! $this->Quiz_model->owns($attempt, $uid, $this->session->userdata('guest_token'))) {
			show_404();
		}
		return $attempt;
	}

	private function require_post()
	{
		if ($this->input->method() !== 'post') {
			show_error('Method not allowed', 405);
		}
	}

	private function next_chapter(array $chapter)
	{
		$playable = $this->Curriculum_model->playable_chapter_ids();
		foreach ($this->Curriculum_model->chapters_with_status($chapter['subject_id']) as $c) {
			if ($c['sort_order'] > $chapter['sort_order'] && in_array((int) $c['id'], $playable, TRUE)) {
				return $c;
			}
		}
		return NULL;
	}

	private function crumbs(array $chapter, $last)
	{
		return array(
			array('Home', ''), array('Practice', 'practice'),
			array($chapter['class_name'], 'practice?class=' . $chapter['class_slug']),
			array($chapter['subject_name'], 'practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug']),
			array('Chapter ' . (int) $chapter['chapter_no'], 'practice/' . $chapter['class_slug'] . '/' . $chapter['subject_slug'] . '?chapter=' . $chapter['slug']),
			array($last, NULL),
		);
	}
}
