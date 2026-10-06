<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daily practice quizzes: start, answer, grade, and the rules that hang off a
 * finished quiz (chapter progress, streak, mastery).
 */
class Quiz_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Curriculum_model', 'Wallet_model', 'Points_model', 'Activity_model'));
	}

	/* ------------------------------------------------------------------ */
	/*  Start                                                              */
	/* ------------------------------------------------------------------ */

	public function guest_attempt_count($guest_token)
	{
		return (int) $this->db->where('session_id', $guest_token)->where('user_id IS NULL', NULL, FALSE)
			->count_all_results('quiz_attempts');
	}

	/**
	 * Create an attempt and pick its questions.
	 *
	 * Signed-in students pay QUIZ_FEE_TAKA from their wallet, in the same
	 * transaction that creates the attempt: either both happen or neither.
	 * Guests pay nothing but are capped at GUEST_FREE_QUIZZES.
	 *
	 * @return array ['ok' => TRUE, 'attempt_id' => int]
	 *            or ['ok' => FALSE, 'error' => 'not_playable'|'guest_limit'|'insufficient_balance']
	 */
	public function start($chapter_id, $topic_id, $user_id, $guest_token, $device_hash = NULL)
	{
		$mix = $this->Curriculum_model->question_mix($chapter_id, $topic_id);
		if ( ! $this->Curriculum_model->mix_is_playable($mix)) {
			return array('ok' => FALSE, 'error' => 'not_playable');
		}
		if ( ! $user_id && $this->guest_attempt_count($guest_token) >= GUEST_FREE_QUIZZES) {
			return array('ok' => FALSE, 'error' => 'guest_limit');
		}

		$this->db->trans_start();

		$wallet_txn_id = NULL;
		if ($user_id) {
			$chapter = $this->Curriculum_model->chapter($chapter_id);
			$wallet_txn_id = $this->Wallet_model->debit(
				$user_id, QUIZ_FEE_TAKA, 'quiz_fee', 'Quiz: ' . $chapter['name']
			);
			if ($wallet_txn_id === FALSE) {
				$this->db->trans_complete();
				return array('ok' => FALSE, 'error' => 'insufficient_balance');
			}
		}

		$this->db->insert('quiz_attempts', array(
			'user_id'            => $user_id ?: NULL,
			'session_id'         => $user_id ? NULL : $guest_token,
			'device_hash'        => $device_hash,
			'chapter_id'         => $chapter_id,
			'topic_id'           => $topic_id ?: NULL,
			'total_questions'    => QUIZ_QUESTION_COUNT,
			'fee_charged'        => $user_id ? QUIZ_FEE_TAKA : 0,
			'wallet_txn_id'      => $wallet_txn_id ?: NULL,
			'time_limit_seconds' => QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION,
		));
		$attempt_id = (int) $this->db->insert_id();

		// Easy, then medium, then hard: a student warms up before the hard
		// questions instead of meeting one cold as question 1.
		$position = 1;
		foreach (array('easy' => QUIZ_MIX_EASY, 'medium' => QUIZ_MIX_MEDIUM, 'hard' => QUIZ_MIX_HARD) as $difficulty => $n) {
			foreach ($this->pick($chapter_id, $topic_id, $difficulty, $n, $user_id) as $question_id) {
				$this->db->insert('quiz_attempt_answers', array(
					'attempt_id' => $attempt_id, 'question_id' => $question_id, 'position' => $position++,
				));
			}
		}

		$this->db->trans_complete();
		return $this->db->trans_status()
			? array('ok' => TRUE, 'attempt_id' => $attempt_id)
			: array('ok' => FALSE, 'error' => 'failed');
	}

	/**
	 * Random published questions, preferring ones this student has seen least,
	 * so a repeat attempt is a different quiz rather than the same ten again.
	 */
	private function pick($chapter_id, $topic_id, $difficulty, $n, $user_id)
	{
		// Placeholders bind in the order they appear in the SQL text, so the
		// ORDER BY subquery's parameter must be appended last.
		$sql = "SELECT q.id FROM questions q
		        WHERE q.chapter_id = ? AND q.difficulty = ? AND q.status = 'published'";
		$params = array($chapter_id, $difficulty);
		if ($topic_id) {
			$sql .= ' AND q.topic_id = ?';
			$params[] = $topic_id;
		}
		if ($user_id) {
			$sql .= ' ORDER BY (SELECT COUNT(*) FROM quiz_attempt_answers qa JOIN quiz_attempts a ON a.id = qa.attempt_id
			                    WHERE qa.question_id = q.id AND a.user_id = ?), RAND()';
			$params[] = $user_id;
		} else {
			$sql .= ' ORDER BY RAND()';
		}
		$sql .= ' LIMIT ' . (int) $n;

		return array_map('intval', array_column($this->db->query($sql, $params)->result_array(), 'id'));
	}

	/* ------------------------------------------------------------------ */
	/*  Read                                                               */
	/* ------------------------------------------------------------------ */

	public function get($attempt_id)
	{
		return $this->db->get_where('quiz_attempts', array('id' => (int) $attempt_id))->row_array();
	}

	/**
	 * Only the student, or the guest browser, that started an attempt may
	 * see or answer it. Attempt ids are sequential and therefore guessable.
	 */
	public function owns(array $attempt, $user_id, $guest_token)
	{
		if ($attempt['user_id']) {
			return $user_id && (int) $attempt['user_id'] === (int) $user_id;
		}
		return $guest_token && hash_equals((string) $attempt['session_id'], (string) $guest_token);
	}

	public function seconds_left(array $attempt)
	{
		if ( ! $attempt['time_limit_seconds']) {
			return NULL;
		}
		$elapsed = time() - strtotime($attempt['started_at']);
		return max(0, (int) $attempt['time_limit_seconds'] - $elapsed);
	}

	/**
	 * Every question of an attempt with its options, in position order.
	 * is_correct on options is included; views must only reveal it once the
	 * attempt is completed.
	 */
	public function items($attempt_id)
	{
		$items = $this->db->select('qa.*, q.stem, q.difficulty, q.explanation, q.source_ref, q.topic_id, t.name AS topic_name, t.code AS topic_code')
			->from('quiz_attempt_answers qa')
			->join('questions q', 'q.id = qa.question_id')
			->join('topics t', 't.id = q.topic_id', 'left')
			->where('qa.attempt_id', $attempt_id)
			->order_by('qa.position')->get()->result_array();
		if ( ! $items) {
			return array();
		}

		$options = $this->db->where_in('question_id', array_column($items, 'question_id'))
			->order_by('label')->get('question_options')->result_array();
		$by_q = array();
		foreach ($options as $o) {
			$by_q[$o['question_id']][] = $o;
		}
		foreach ($items as &$it) {
			$it['options'] = $by_q[$it['question_id']];
			foreach ($it['options'] as $o) {
				if ($o['is_correct']) {
					$it['correct_label'] = $o['label'];
					$it['correct_option_id'] = (int) $o['id'];
				}
				if ((int) $o['id'] === (int) $it['selected_option_id']) {
					$it['selected_label'] = $o['label'];
				}
			}
		}
		return $items;
	}

	/* ------------------------------------------------------------------ */
	/*  Answer                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Save one answer. Ignored if the attempt is finished or out of time, or
	 * if the option does not belong to the question at that position (a
	 * tampered form must not be able to answer with another question's option).
	 */
	public function save_answer(array $attempt, $position, $option_id, $marked_for_review)
	{
		if ($attempt['status'] !== 'in_progress' || $this->seconds_left($attempt) === 0) {
			return FALSE;
		}
		$row = $this->db->get_where('quiz_attempt_answers', array(
			'attempt_id' => $attempt['id'], 'position' => (int) $position,
		))->row_array();
		if ( ! $row) {
			return FALSE;
		}

		$update = array('marked_for_review' => $marked_for_review ? 1 : 0);
		if ($option_id) {
			$valid = $this->db->where('id', (int) $option_id)->where('question_id', $row['question_id'])
				->count_all_results('question_options');
			if ( ! $valid) {
				return FALSE;
			}
			$update['selected_option_id'] = (int) $option_id;
			$update['answered_at'] = date('Y-m-d H:i:s');
		}
		$this->db->update('quiz_attempt_answers', $update, array('id' => $row['id']));
		return TRUE;
	}

	/* ------------------------------------------------------------------ */
	/*  Finish                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Grade and close an attempt, then apply progress, streak and mastery.
	 *
	 * Safe to call twice (double-click, timer and button racing): the attempt
	 * row is locked and a completed attempt is returned untouched, so points
	 * and streak credit can only be applied once.
	 */
	public function finish($attempt_id)
	{
		$this->db->trans_start();
		$attempt = $this->db->query('SELECT * FROM quiz_attempts WHERE id = ? FOR UPDATE', array($attempt_id))->row_array();
		if ( ! $attempt || $attempt['status'] !== 'in_progress') {
			$this->db->trans_complete();
			return $attempt;
		}

		// Grade from the option table, never from anything the browser sent.
		$this->db->query(
			'UPDATE quiz_attempt_answers qa
			 LEFT JOIN question_options o ON o.id = qa.selected_option_id
			 SET qa.is_correct = COALESCE(o.is_correct, 0)
			 WHERE qa.attempt_id = ?',
			array($attempt_id)
		);
		$score = (int) $this->db->where('attempt_id', $attempt_id)->where('is_correct', 1)
			->count_all_results('quiz_attempt_answers');
		$total = (int) $attempt['total_questions'];
		$percentage = $total ? round($score * 100 / $total, 2) : 0;

		$elapsed = time() - strtotime($attempt['started_at']);
		if ($attempt['time_limit_seconds']) {
			$elapsed = min($elapsed, (int) $attempt['time_limit_seconds']);
		}

		$user_id = $attempt['user_id'] ? (int) $attempt['user_id'] : NULL;
		$counts_for_streak = $user_id && $percentage >= STREAK_PASS_PERCENTAGE;

		$this->db->update('quiz_attempts', array(
			'status'            => 'completed',
			'score'             => $score,
			'percentage'        => $percentage,
			'counts_for_streak' => $counts_for_streak ? 1 : 0,
			'completed_at'      => date('Y-m-d H:i:s'),
			'duration_seconds'  => $elapsed,
		), array('id' => $attempt_id));

		$this->db->query(
			'UPDATE questions q JOIN quiz_attempt_answers qa ON qa.question_id = q.id
			 SET q.times_served = q.times_served + 1, q.times_correct = q.times_correct + qa.is_correct
			 WHERE qa.attempt_id = ?',
			array($attempt_id)
		);

		$earned = 0;
		if ($user_id) {
			$chapter = $this->Curriculum_model->chapter($attempt['chapter_id']);
			$this->update_progress($user_id, $attempt['chapter_id'], $percentage);
			$earned += $this->apply_mastery($user_id, $chapter, $percentage);
			if ($counts_for_streak) {
				$earned += $this->apply_streak($user_id, $attempt_id);
			}
			$this->db->update('quiz_attempts', array('points_earned' => $earned), array('id' => $attempt_id));
			$this->Activity_model->log($user_id, 'quiz',
				'Scored ' . pct($percentage) . ' in ' . ($chapter['name_bn'] ?: $chapter['name']),
				$chapter['subject_name'] . ' · ' . $score . '/' . $total . ' correct');
		}

		$this->db->trans_complete();
		return $this->get($attempt_id);
	}

	/**
	 * Running per-chapter stats for the dashboard. avg is recomputed from the
	 * attempts rather than averaged incrementally, so it can never drift.
	 */
	private function update_progress($user_id, $chapter_id, $percentage)
	{
		$stats = $this->db->query(
			"SELECT COUNT(*) n, AVG(percentage) avg_p, MAX(percentage) best_p
			 FROM quiz_attempts WHERE user_id = ? AND chapter_id = ? AND status = 'completed'",
			array($user_id, $chapter_id)
		)->row_array();

		$this->db->query(
			'INSERT INTO user_chapter_progress (user_id, chapter_id, attempts, best_percentage, last_percentage, avg_percentage, last_attempt_at)
			 VALUES (?, ?, ?, ?, ?, ?, NOW())
			 ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), best_percentage = VALUES(best_percentage),
			   last_percentage = VALUES(last_percentage), avg_percentage = VALUES(avg_percentage),
			   last_attempt_at = VALUES(last_attempt_at)',
			array($user_id, $chapter_id, $stats['n'], $stats['best_p'], $percentage, round($stats['avg_p'], 2))
		);
	}

	/**
	 * MASTERY_RUN consecutive quizzes at MASTERY_PERCENTAGE+ in one chapter
	 * masters it: MASTERY_POINTS, once per chapter. A lower score breaks the run.
	 */
	private function apply_mastery($user_id, array $chapter, $percentage)
	{
		$row = $this->db->get_where('user_chapter_progress',
			array('user_id' => $user_id, 'chapter_id' => $chapter['id']))->row_array();
		$run = $percentage >= MASTERY_PERCENTAGE ? (int) $row['consecutive_mastery'] + 1 : 0;
		$this->db->update('user_chapter_progress', array('consecutive_mastery' => min($run, 255)),
			array('user_id' => $user_id, 'chapter_id' => $chapter['id']));

		if ($run < MASTERY_RUN || (int) $row['mastery_points_awarded'] > 0) {
			return 0;
		}
		$awarded = $this->Points_model->award($user_id, 'chapter_mastery', MASTERY_POINTS,
			'chapter', $chapter['id'], 'Chapter mastery: ' . ($chapter['name_bn'] ?: $chapter['name']));
		if ($awarded) {
			$this->db->update('user_chapter_progress', array('mastery_points_awarded' => $awarded),
				array('user_id' => $user_id, 'chapter_id' => $chapter['id']));
			$this->db->query('INSERT IGNORE INTO user_badges (user_id, badge_id) SELECT ?, id FROM badges WHERE code = ?',
				array($user_id, 'chapter_master'));
			$this->Activity_model->notify($user_id, 'points', 'Chapter mastered! +' . $awarded . ' Academic Points');
		}
		return $awarded;
	}

	/**
	 * Streak (blueprint §12, as fixed in v2.0): STREAK_REQUIRED_QUIZZES
	 * quizzes, EACH at STREAK_PASS_PERCENTAGE+, within STREAK_WINDOW_HOURS of
	 * the first = 1 point, then an immediate reset to zero.
	 *
	 * Only called for a qualifying quiz. A quiz under 60% "doesn't count"
	 * (§12 worked example): it neither advances nor resets the streak. An
	 * expired window starts a fresh streak with this quiz as #1.
	 */
	private function apply_streak($user_id, $attempt_id)
	{
		$this->db->query('INSERT IGNORE INTO user_quiz_streaks (user_id) VALUES (?)', array($user_id));
		$s = $this->db->query('SELECT * FROM user_quiz_streaks WHERE user_id = ? FOR UPDATE', array($user_id))->row_array();

		$expired = $s['current_streak_started_at']
			&& strtotime($s['current_streak_started_at']) + STREAK_WINDOW_HOURS * 3600 < time();
		$count = ($expired || ! $s['current_streak_started_at']) ? 0 : (int) $s['current_streak_quizzes'];
		$started = $count === 0 ? date('Y-m-d H:i:s') : $s['current_streak_started_at'];
		$count++;

		$earned = 0;
		$completed = (int) $s['completed_streaks'];
		if ($count >= STREAK_REQUIRED_QUIZZES) {
			$earned = $this->Points_model->award($user_id, 'streak', 1, 'attempt', $attempt_id,
				'Streak completed: ' . STREAK_REQUIRED_QUIZZES . ' quizzes at ' . STREAK_PASS_PERCENTAGE . '%+');
			$completed++;
			$this->db->query('INSERT IGNORE INTO user_badges (user_id, badge_id) SELECT ?, id FROM badges WHERE code = ?',
				array($user_id, 'first_streak'));
			$this->Activity_model->log($user_id, 'streak', 'Streak completed!', '+1 Academic Point');
			$this->Activity_model->notify($user_id, 'streak', 'Streak completed! +1 Academic Point');
			$best = max((int) $s['best_streak_quizzes'], $count);
			$count = 0;          // immediate reset
			$started = NULL;
		} else {
			$best = max((int) $s['best_streak_quizzes'], $count);
		}

		$this->db->update('user_quiz_streaks', array(
			'current_streak_quizzes'    => $count,
			'current_streak_started_at' => $started,
			'completed_streaks'         => $completed,
			'best_streak_quizzes'       => $best,
		), array('user_id' => $user_id));
		return $earned;
	}

	/* ------------------------------------------------------------------ */
	/*  Results and history                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Breakdown for the result page: by topic when the quiz spans topics
	 * ("Performance by Topic", design 10), otherwise by difficulty
	 * ("Easy 5/5, Medium 2/3, Hard 1/2", guideline §4.4).
	 */
	public function breakdown(array $items)
	{
		$by_topic = count(array_unique(array_filter(array_column($items, 'topic_id')))) > 1;
		$groups = array();
		foreach ($items as $it) {
			$key = $by_topic && $it['topic_id'] ? $it['topic_code'] . ' ' . $it['topic_name'] : ucfirst($it['difficulty']);
			if ( ! isset($groups[$key])) {
				$groups[$key] = array('label' => $key, 'correct' => 0, 'total' => 0);
			}
			$groups[$key]['total']++;
			$groups[$key]['correct'] += (int) $it['is_correct'];
		}
		return array('by' => $by_topic ? 'topic' : 'difficulty', 'groups' => array_values($groups));
	}

	public function streak($user_id)
	{
		$s = $this->db->get_where('user_quiz_streaks', array('user_id' => $user_id))->row_array();
		if ( ! $s) {
			return array('count' => 0, 'hours_left' => NULL, 'completed' => 0);
		}
		$hours_left = NULL;
		$count = (int) $s['current_streak_quizzes'];
		if ($s['current_streak_started_at']) {
			$left = strtotime($s['current_streak_started_at']) + STREAK_WINDOW_HOURS * 3600 - time();
			if ($left <= 0) {
				$count = 0;   // window lapsed; the next qualifying quiz starts over
			} else {
				$hours_left = (int) ceil($left / 3600);
			}
		}
		return array('count' => $count, 'hours_left' => $hours_left, 'completed' => (int) $s['completed_streaks']);
	}

	public function history($user_id, $limit = 20)
	{
		return $this->db->select('a.*, c.name AS chapter_name, c.name_bn AS chapter_name_bn, s.name AS subject_name, t.name AS topic_name')
			->from('quiz_attempts a')
			->join('chapters c', 'c.id = a.chapter_id')
			->join('subjects s', 's.id = c.subject_id')
			->join('topics t', 't.id = a.topic_id', 'left')
			->where('a.user_id', $user_id)->where('a.status', 'completed')
			->order_by('a.completed_at', 'DESC')->limit($limit)->get()->result_array();
	}

	/**
	 * Move a guest's attempts onto the account they just created, so the
	 * result they earned is kept (guideline §4.9). Chapter progress is rebuilt
	 * from them; streak credit is not, since a streak is earned while signed in.
	 */
	public function claim_guest_attempts($guest_token, $user_id)
	{
		$this->db->where('session_id', $guest_token)->where('user_id IS NULL', NULL, FALSE)
			->update('quiz_attempts', array('user_id' => $user_id, 'session_id' => NULL));
		$claimed = $this->db->affected_rows();

		$chapters = $this->db->select('chapter_id, percentage')->where('user_id', $user_id)
			->where('status', 'completed')->order_by('completed_at')->get('quiz_attempts')->result_array();
		foreach ($chapters as $c) {
			$this->update_progress($user_id, $c['chapter_id'], $c['percentage']);
		}
		return $claimed;
	}
}
