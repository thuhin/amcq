<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The question bank, as the admin panel manages it.
 *
 * Rules (blueprint v2.0: verification and a source BEFORE publish; "Correct
 * Me" is the safety net, not the primary check):
 *  - only status = 'published' is ever served to a student;
 *  - a question reaches 'verified' and 'published' only when it is complete:
 *    four options A-D, exactly one correct, an explanation and a source;
 *  - changing what a question says or which answer is right pulls a verified
 *    or published question back to 'in_review', so a changed answer is never
 *    live without a second look.
 *
 * Options are updated in place by label, never deleted and re-inserted:
 * quiz_attempt_answers.selected_option_id points at them.
 */
class Question_model extends CI_Model
{
	const STATUSES = array(
		'draft'     => 'Draft',
		'in_review' => 'In review',
		'verified'  => 'Verified',
		'published' => 'Published',
		'retired'   => 'Retired',
	);

	const DIFFICULTIES = array('easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard');
	const ORIGINS      = array('manual' => 'Written by hand', 'ai' => 'AI-drafted', 'imported' => 'Imported');
	const LABELS       = array('A', 'B', 'C', 'D');

	/** From => allowed next statuses. 'published' from in_review means verify + publish. */
	const TRANSITIONS = array(
		'draft'     => array('in_review', 'retired'),
		'in_review' => array('verified', 'published', 'draft', 'retired'),
		'verified'  => array('published', 'in_review', 'retired'),
		'published' => array('in_review', 'retired'),
		'retired'   => array('draft'),
	);

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Curriculum_model');
	}

	// ---------------------------------------------------------------- reading

	private function apply_filters(array $f)
	{
		if ( ! empty($f['q'])) {
			if (ctype_digit($f['q'])) {
				$this->db->group_start()->where('q.id', (int) $f['q'])->or_like('q.stem', $f['q'])->group_end();
			} else {
				$this->db->like('q.stem', $f['q']);
			}
		}
		foreach (array('status', 'difficulty', 'origin') as $k) {
			if ( ! empty($f[$k])) {
				$this->db->where('q.' . $k, $f[$k]);
			}
		}
		if ( ! empty($f['chapter_id'])) {
			$this->db->where('q.chapter_id', (int) $f['chapter_id']);
		}
	}

	/** @return array [rows, total] */
	public function search(array $filters, $page, $per_page = ADMIN_PAGE_SIZE)
	{
		$this->apply_filters($filters);
		$total = (int) $this->db->from('questions q')->count_all_results();

		$this->apply_filters($filters);
		$rows = $this->db->select('q.id, q.stem, q.difficulty, q.status, q.origin, q.times_served, q.times_correct, q.updated_at,
				ch.name AS chapter_name, s.name AS subject_name, cl.name AS class_name')
			->from('questions q')
			->join('chapters ch', 'ch.id = q.chapter_id')
			->join('subjects s', 's.id = ch.subject_id')
			->join('classes cl', 'cl.id = s.class_id')
			->order_by('q.id', 'DESC')
			->limit($per_page, max(0, ($page - 1)) * $per_page)
			->get()->result_array();
		return array($rows, $total);
	}

	/** A question with its options keyed by label, or NULL. */
	public function find($id)
	{
		$q = $this->db->select('q.*, v.name AS verified_by_name, ch.name AS chapter_name, s.name AS subject_name, cl.name AS class_name')
			->from('questions q')
			->join('chapters ch', 'ch.id = q.chapter_id')
			->join('subjects s', 's.id = ch.subject_id')
			->join('classes cl', 'cl.id = s.class_id')
			->join('users v', 'v.id = q.verified_by', 'left')
			->where('q.id', (int) $id)->get()->row_array();
		if ( ! $q) {
			return NULL;
		}
		$q['options'] = array();
		foreach ($this->db->order_by('label')->get_where('question_options', array('question_id' => $q['id']))->result_array() as $o) {
			$q['options'][$o['label']] = $o;
		}
		return $q;
	}

	public function counts_by_status()
	{
		$counts = array_fill_keys(array_keys(self::STATUSES), 0);
		foreach ($this->db->select('status, COUNT(*) AS n')->group_by('status')->get('questions')->result_array() as $r) {
			$counts[$r['status']] = (int) $r['n'];
		}
		return $counts;
	}

	/** Chapters for the pickers, labelled "Class 5 › Mathematics › Fractions". */
	public function chapter_choices()
	{
		return $this->db->select('ch.id, ch.name, ch.name_bn, s.name AS subject_name, cl.name AS class_name')
			->from('chapters ch')->join('subjects s', 's.id = ch.subject_id')->join('classes cl', 'cl.id = s.class_id')
			->order_by('cl.sort_order')->order_by('s.sort_order')->order_by('ch.sort_order')
			->get()->result_array();
	}

	public function topic_choices()
	{
		return $this->db->select('id, chapter_id, code, name')->order_by('chapter_id')->order_by('sort_order')
			->get('topics')->result_array();
	}

	/**
	 * Published counts per active chapter against the 5/3/2 a quiz needs.
	 * "Playable" is Curriculum_model's own rule, so this can never disagree
	 * with what a student sees ("Coming Soon").
	 */
	public function coverage()
	{
		$rows = $this->db->query(
			"SELECT ch.id, ch.name, ch.name_bn, s.name AS subject_name, cl.name AS class_name,
			        COALESCE(SUM(q.status = 'published' AND q.difficulty = 'easy'), 0)   AS easy,
			        COALESCE(SUM(q.status = 'published' AND q.difficulty = 'medium'), 0) AS medium,
			        COALESCE(SUM(q.status = 'published' AND q.difficulty = 'hard'), 0)   AS hard,
			        COALESCE(SUM(q.status IN ('draft','in_review','verified')), 0)       AS in_pipeline
			 FROM chapters ch
			 JOIN subjects s ON s.id = ch.subject_id AND s.is_active = 1
			 JOIN classes cl ON cl.id = s.class_id AND cl.is_active = 1
			 LEFT JOIN questions q ON q.chapter_id = ch.id
			 WHERE ch.is_active = 1
			 GROUP BY ch.id, ch.name, ch.name_bn, s.name, cl.name, cl.sort_order, s.sort_order, ch.sort_order
			 ORDER BY cl.sort_order, s.sort_order, ch.sort_order"
		)->result_array();

		foreach ($rows as &$r) {
			$mix = array('easy' => (int) $r['easy'], 'medium' => (int) $r['medium'], 'hard' => (int) $r['hard']);
			$r['mix'] = $mix;
			$r['playable'] = $this->Curriculum_model->mix_is_playable($mix);
			$r['short'] = array(
				'easy'   => max(0, QUIZ_MIX_EASY   - $mix['easy']),
				'medium' => max(0, QUIZ_MIX_MEDIUM - $mix['medium']),
				'hard'   => max(0, QUIZ_MIX_HARD   - $mix['hard']),
			);
		}
		return $rows;
	}

	// ------------------------------------------------------------- validation

	/**
	 * Trim and use "\n" line breaks. Browsers post textareas with "\r\n"; without
	 * this an untouched re-save would look like an edit and pull a live question
	 * back to review.
	 */
	private static function norm($text)
	{
		return trim(str_replace(array("\r\n", "\r"), "\n", (string) $text));
	}

	/**
	 * Clean and check the editor's input.
	 *
	 * @return array [clean data, errors keyed by field]
	 */
	public function validate(array $in)
	{
		$errors = array();
		$clean = array(
			'chapter_id'  => (int) (isset($in['chapter_id']) ? $in['chapter_id'] : 0),
			'topic_id'    => (int) (isset($in['topic_id']) ? $in['topic_id'] : 0) ?: NULL,
			'difficulty'  => isset($in['difficulty']) ? (string) $in['difficulty'] : '',
			'stem'        => self::norm(isset($in['stem']) ? $in['stem'] : ''),
			'explanation' => self::norm(isset($in['explanation']) ? $in['explanation'] : ''),
			'source_ref'  => self::norm(isset($in['source_ref']) ? $in['source_ref'] : ''),
			'correct'     => isset($in['correct']) ? (string) $in['correct'] : '',
			'options'     => array(),
		);

		if ( ! $this->db->where('id', $clean['chapter_id'])->count_all_results('chapters')) {
			$errors['chapter_id'] = 'Choose a chapter.';
		} elseif ($clean['topic_id'] && ! $this->db->where(array('id' => $clean['topic_id'], 'chapter_id' => $clean['chapter_id']))->count_all_results('topics')) {
			$errors['topic_id'] = 'That topic does not belong to the chosen chapter.';
		}
		if ( ! isset(self::DIFFICULTIES[$clean['difficulty']])) {
			$errors['difficulty'] = 'Choose a difficulty.';
		}
		if (mb_strlen($clean['stem']) < 5 || mb_strlen($clean['stem']) > 2000) {
			$errors['stem'] = 'Write the question (5 to 2000 characters).';
		}
		if (mb_strlen($clean['explanation']) > 2000) {
			$errors['explanation'] = 'Keep the explanation under 2000 characters.';
		}
		if (mb_strlen($clean['source_ref']) > 255) {
			$errors['source_ref'] = 'Keep the source under 255 characters.';
		}

		$seen = array();
		foreach (self::LABELS as $label) {
			$body = self::norm(isset($in['option_' . strtolower($label)]) ? $in['option_' . strtolower($label)] : '');
			$clean['options'][$label] = $body;
			if ($body === '' || mb_strlen($body) > 500) {
				$errors['option_' . strtolower($label)] = 'Fill in option ' . $label . ' (up to 500 characters).';
			} elseif (isset($seen[mb_strtolower($body)])) {
				$errors['option_' . strtolower($label)] = 'Option ' . $label . ' repeats option ' . $seen[mb_strtolower($body)] . '. Every option must be different.';
			} else {
				$seen[mb_strtolower($body)] = $label;
			}
		}
		if ( ! in_array($clean['correct'], self::LABELS, TRUE)) {
			$errors['correct'] = 'Mark which option is correct.';
		}
		return array($clean, $errors);
	}

	/**
	 * What still stops this question from being verified or published.
	 *
	 * @param array $q a question from find()
	 * @return string[] problems; empty = ready
	 */
	public function completeness(array $q)
	{
		$problems = array();
		$labels = array_keys($q['options']);
		sort($labels);
		if ($labels !== self::LABELS) {
			$problems[] = 'It needs exactly four options, A to D.';
		} else {
			$correct = 0;
			$bodies = array();
			foreach ($q['options'] as $o) {
				$correct += (int) $o['is_correct'];
				$bodies[] = mb_strtolower(trim($o['body']));
			}
			if ($correct !== 1) {
				$problems[] = 'Exactly one option must be marked correct (it has ' . $correct . ').';
			}
			if (in_array('', $bodies, TRUE) || count(array_unique($bodies)) !== count($bodies)) {
				$problems[] = 'Every option must be filled in and different.';
			}
		}
		if (trim((string) $q['explanation']) === '') {
			$problems[] = 'Add an explanation; students see it when they review a mistake.';
		}
		if (trim((string) $q['source_ref']) === '') {
			$problems[] = 'Add a source reference, e.g. "NCTB Class 5 Math, Ch 6, p.41" (blueprint v2.0: a source before publishing).';
		}
		return $problems;
	}

	// ---------------------------------------------------------------- writing

	/**
	 * Create (id = NULL) or update a question.
	 *
	 * @return array [id|NULL, errors, status_reset]
	 */
	public function save($id, array $in)
	{
		list($c, $errors) = $this->validate($in);
		if ($errors) {
			return array(NULL, $errors, FALSE);
		}

		$this->db->trans_begin();
		$reset = FALSE;

		if ($id === NULL) {
			$this->db->insert('questions', array(
				'chapter_id'  => $c['chapter_id'],
				'topic_id'    => $c['topic_id'],
				'difficulty'  => $c['difficulty'],
				'stem'        => $c['stem'],
				'explanation' => $c['explanation'] !== '' ? $c['explanation'] : NULL,
				'source_ref'  => $c['source_ref'] !== '' ? $c['source_ref'] : NULL,
				'origin'      => 'manual',
				'status'      => 'draft',
			));
			$id = (int) $this->db->insert_id();
			foreach (self::LABELS as $label) {
				$this->db->insert('question_options', array(
					'question_id' => $id, 'label' => $label, 'body' => $c['options'][$label],
					'is_correct'  => $label === $c['correct'] ? 1 : 0,
				));
			}
		} else {
			// Lock the row: two admins saving at once must not interleave.
			$cur = $this->db->query('SELECT * FROM questions WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
			if ( ! $cur) {
				$this->db->trans_rollback();
				return array(NULL, array('_' => 'That question no longer exists.'), FALSE);
			}
			$have = array();
			foreach ($this->db->get_where('question_options', array('question_id' => $cur['id']))->result_array() as $o) {
				$have[$o['label']] = $o;
			}
			if (array_diff(array_keys($have), self::LABELS)) {
				$this->db->trans_rollback();
				return array(NULL, array('_' => 'This question has options outside A to D and cannot be edited here.'), FALSE);
			}

			$changed = self::norm($cur['stem']) !== $c['stem']
				|| $cur['difficulty'] !== $c['difficulty']
				|| (int) $cur['chapter_id'] !== $c['chapter_id'];
			foreach (self::LABELS as $label) {
				$was = isset($have[$label]) ? $have[$label] : NULL;
				if ( ! $was || self::norm($was['body']) !== $c['options'][$label] || (int) $was['is_correct'] !== ($label === $c['correct'] ? 1 : 0)) {
					$changed = TRUE;
				}
			}

			$update = array(
				'chapter_id'  => $c['chapter_id'],
				'topic_id'    => $c['topic_id'],
				'difficulty'  => $c['difficulty'],
				'stem'        => $c['stem'],
				'explanation' => $c['explanation'] !== '' ? $c['explanation'] : NULL,
				'source_ref'  => $c['source_ref'] !== '' ? $c['source_ref'] : NULL,
			);
			if ($changed && in_array($cur['status'], array('verified', 'published'), TRUE)) {
				$update['status'] = 'in_review';
				$update['verified_by'] = NULL;
				$update['verified_at'] = NULL;
				$reset = TRUE;
			}
			$this->db->update('questions', $update, array('id' => $cur['id']));

			foreach (self::LABELS as $label) {
				$row = array('body' => $c['options'][$label], 'is_correct' => $label === $c['correct'] ? 1 : 0);
				if (isset($have[$label])) {
					$this->db->update('question_options', $row, array('question_id' => $cur['id'], 'label' => $label));
				} else {
					$this->db->insert('question_options', $row + array('question_id' => $cur['id'], 'label' => $label));
				}
			}
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return array(NULL, array('_' => 'The question could not be saved. Please try again.'), FALSE);
		}
		$this->db->trans_commit();
		return array((int) $id, array(), $reset);
	}

	/**
	 * Move a question along the workflow.
	 *
	 * 'published' from 'in_review' is "verify and publish" in one step.
	 *
	 * @return array [ok, messages]  messages = what blocks it, or the reason it is not allowed
	 */
	public function transition($id, $to, $admin_id)
	{
		if ( ! isset(self::STATUSES[$to])) {
			return array(FALSE, array('Unknown status.'));
		}
		$this->db->trans_begin();
		$cur = $this->db->query('SELECT status FROM questions WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
		if ( ! $cur) {
			$this->db->trans_rollback();
			return array(FALSE, array('That question no longer exists.'));
		}
		if ( ! in_array($to, self::TRANSITIONS[$cur['status']], TRUE)) {
			$this->db->trans_rollback();
			return array(FALSE, array('A ' . strtolower(self::STATUSES[$cur['status']]) . ' question cannot move to ' . strtolower(self::STATUSES[$to]) . '.'));
		}

		$update = array('status' => $to);
		if (in_array($to, array('verified', 'published'), TRUE)) {
			$q = $this->find($id);
			if ($problems = $this->completeness($q)) {
				$this->db->trans_rollback();
				return array(FALSE, $problems);
			}
			if ($cur['status'] !== 'verified') {   // verified -> published keeps who verified it
				$update['verified_by'] = (int) $admin_id;
				$update['verified_at'] = date('Y-m-d H:i:s');
			}
		} elseif (in_array($to, array('draft', 'in_review'), TRUE)) {
			$update['verified_by'] = NULL;
			$update['verified_at'] = NULL;
		}
		$this->db->update('questions', $update, array('id' => (int) $id));

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return array(FALSE, array('The change could not be saved. Please try again.'));
		}
		$this->db->trans_commit();
		return array(TRUE, array());
	}
}
