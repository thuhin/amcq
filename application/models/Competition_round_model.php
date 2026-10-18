<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The national competition's rounds (blueprint §10, schema section 7).
 *
 *   draft -> registration -> round1 -> grading -> final -> completed
 *
 * - Round 1: each registrant gets their own random set (COMPETITION_ROUND1_*
 *   per difficulty), one sitting, timed on the server, free to move between
 *   questions.
 * - Final: the top qualify_percent of Round 1, their own set (avoiding their
 *   Round 1 questions where the pool allows), one question at a time with no
 *   going back.
 * - Scoring: each correct answer earns its difficulty weight, frozen when the
 *   set is served, so a later weight change cannot rescore a finished round.
 * - Ties are broken by time taken, never by registration time (v2.0). Equal
 *   score and equal time share a rank.
 * - Prizes and winner points come from competition_prizes. Prize money is paid
 *   outside the app; an admin records the payment. Nothing here touches a wallet.
 */
class Competition_round_model extends CI_Model
{
	const STAGES = array('draft', 'registration', 'round1', 'grading', 'final', 'completed');

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('Activity_model', 'Points_model', 'Certificate_model'));
	}

	// ------------------------------------------------------------- the rules

	public function mix($round)
	{
		return $round === 'final'
			? array('easy' => COMPETITION_FINAL_EASY, 'medium' => COMPETITION_FINAL_MEDIUM, 'hard' => COMPETITION_FINAL_HARD)
			: array('easy' => COMPETITION_ROUND1_EASY, 'medium' => COMPETITION_ROUND1_MEDIUM, 'hard' => COMPETITION_ROUND1_HARD);
	}

	public function question_count(array $c, $round)
	{
		return (int) $c[$round === 'final' ? 'final_question_count' : 'round1_question_count'];
	}

	public function time_limit(array $c, $round)
	{
		return 60 * (int) $c[$round === 'final' ? 'final_time_limit_minutes' : 'round1_time_limit_minutes'];
	}

	/** Highest possible score for a round, from the live weights (10/10/10 at 1/1.5/2 = 45). */
	public function max_score($round)
	{
		$w = array_column($this->db->get('difficulties')->result_array(), 'weight', 'code');
		$max = 0.0;
		foreach ($this->mix($round) as $d => $n) {
			$max += $n * (float) $w[$d];
		}
		return $max;
	}

	/** Published questions available to this competition, per difficulty. */
	public function pool(array $c)
	{
		$sql = "SELECT q.difficulty, COUNT(*) AS n FROM questions q
		        JOIN chapters ch ON ch.id = q.chapter_id JOIN subjects s ON s.id = ch.subject_id
		        WHERE q.status = 'published'" . ($c['class_id'] ? ' AND s.class_id = ?' : '') . '
		        GROUP BY q.difficulty';
		$pool = array('easy' => 0, 'medium' => 0, 'hard' => 0);
		foreach ($this->db->query($sql, $c['class_id'] ? array($c['class_id']) : array())->result_array() as $r) {
			$pool[$r['difficulty']] = (int) $r['n'];
		}
		return $pool;
	}

	/** What stops a round from opening; empty means ready. */
	public function round_problems(array $c, $round)
	{
		$problems = array();
		$mix = $this->mix($round);
		if (array_sum($mix) !== $this->question_count($c, $round)) {
			$problems[] = 'The difficulty mix (' . implode('/', $mix) . ') does not add up to the round\'s ' . $this->question_count($c, $round) . ' questions.';
		}
		$pool = $this->pool($c);
		foreach ($mix as $d => $n) {
			if ($pool[$d] < $n) {
				$problems[] = 'Needs ' . ($n - $pool[$d]) . ' more published ' . $d . ' question' . ($n - $pool[$d] === 1 ? '' : 's') . ' (' . $pool[$d] . ' of ' . $n . ').';
			}
		}
		return $problems;
	}

	// ------------------------------------------------------- a student's round

	private function col($round, $what)
	{
		return ($round === 'final' ? 'final_' : 'round1_') . $what;
	}

	/**
	 * Where this registrant stands in a round:
	 * closed | not_registered | not_qualified | ready | in_progress | expired | submitted
	 */
	public function state(array $c, $reg, $round)
	{
		if ($c['status'] !== $round) {
			return 'closed';
		}
		if ( ! $reg || ! $reg['fee_paid']) {
			return 'not_registered';
		}
		if ($round === 'final' && ! in_array($reg['status'], array('qualified', 'finalist', 'winner'), TRUE)) {
			return 'not_qualified';
		}
		if ($reg[$this->col($round, 'submitted_at')]) {
			return 'submitted';
		}
		if ( ! $reg[$this->col($round, 'started_at')]) {
			return 'ready';
		}
		return $this->seconds_left($c, $reg, $round) > 0 ? 'in_progress' : 'expired';
	}

	public function seconds_left(array $c, array $reg, $round)
	{
		$started = $reg[$this->col($round, 'started_at')];
		if ( ! $started) {
			return $this->time_limit($c, $round);
		}
		return max(0, $this->time_limit($c, $round) - (time() - strtotime($started)));
	}

	/**
	 * Serve this registrant's set and start the clock. Starting twice resumes
	 * the same set: the registration row is locked so two tabs cannot both serve.
	 *
	 * @return string 'ok' | 'pool' (not enough questions) | 'not_allowed'
	 */
	public function start(array $c, array $reg, $round)
	{
		if ( ! in_array($this->state($c, $reg, $round), array('ready', 'in_progress'), TRUE)) {
			return 'not_allowed';
		}
		$this->db->trans_begin();
		$r = $this->db->query('SELECT * FROM competition_registrations WHERE id = ? FOR UPDATE', array($reg['id']))->row_array();
		if ($r[$this->col($round, 'started_at')]) {
			$this->db->trans_commit();
			return 'ok';
		}

		$position = 0;
		foreach ($this->mix($round) as $difficulty => $n) {
			$ids = $this->pick($c, $difficulty, $n, $r['id']);
			if (count($ids) < $n) {
				$this->db->trans_rollback();
				return 'pool';
			}
			$weight = $this->db->get_where('difficulties', array('code' => $difficulty))->row()->weight;
			foreach ($ids as $qid) {
				$this->db->insert('competition_answers', array(
					'registration_id' => $r['id'], 'round' => $round, 'position' => ++$position,
					'question_id' => $qid, 'weight' => $weight,
				));
			}
		}
		$this->db->update('competition_registrations', array($this->col($round, 'started_at') => date('Y-m-d H:i:s')), array('id' => $r['id']));

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return 'not_allowed';
		}
		$this->db->trans_commit();
		return 'ok';
	}

	/** Random published questions of one difficulty; for the final, unseen in Round 1 first. */
	private function pick(array $c, $difficulty, $n, $registration_id)
	{
		$sql = "SELECT q.id FROM questions q
		        JOIN chapters ch ON ch.id = q.chapter_id JOIN subjects s ON s.id = ch.subject_id
		        WHERE q.status = 'published' AND q.difficulty = ?";
		$params = array($difficulty);
		if ($c['class_id']) {
			$sql .= ' AND s.class_id = ?';
			$params[] = $c['class_id'];
		}
		$sql .= ' ORDER BY (SELECT COUNT(*) FROM competition_answers ca WHERE ca.registration_id = ? AND ca.question_id = q.id), RAND() LIMIT ' . (int) $n;
		$params[] = $registration_id;
		return array_map('intval', array_column($this->db->query($sql, $params)->result_array(), 'id'));
	}

	/** The set, in order, with each question's options. Correctness is never included. */
	public function items($registration_id, $round)
	{
		$items = $this->db->select('ca.position, ca.question_id, ca.selected_option_id, ca.answered_at, ca.weight, q.stem')
			->from('competition_answers ca')->join('questions q', 'q.id = ca.question_id')
			->where('ca.registration_id', (int) $registration_id)->where('ca.round', $round)
			->order_by('ca.position')->get()->result_array();
		if ( ! $items) {
			return array();
		}
		$options = array();
		foreach ($this->db->select('id, question_id, label, body')->where_in('question_id', array_column($items, 'question_id'))
			->order_by('label')->get('question_options')->result_array() as $o) {
			$options[$o['question_id']][] = $o;
		}
		foreach ($items as &$it) {
			$it['options'] = isset($options[$it['question_id']]) ? $options[$it['question_id']] : array();
		}
		return $items;
	}

	/** Final: the first position not yet answered or skipped (no going back). */
	public function current_final_position(array $items)
	{
		foreach ($items as $it) {
			if ( ! $it['answered_at']) {
				return (int) $it['position'];
			}
		}
		return NULL;
	}

	/**
	 * Record an answer. Round 1 may change an answer while time is left. The
	 * final accepts only the current question, once; an empty answer is a skip.
	 *
	 * @return bool
	 */
	public function save_answer(array $c, array $reg, $round, $position, $option_id)
	{
		if ($this->state($c, $reg, $round) !== 'in_progress') {
			return FALSE;
		}
		$row = $this->db->get_where('competition_answers', array('registration_id' => $reg['id'], 'round' => $round, 'position' => (int) $position))->row_array();
		if ( ! $row) {
			return FALSE;
		}
		$option_id = (int) $option_id;
		if ($option_id && ! $this->db->where(array('id' => $option_id, 'question_id' => $row['question_id']))->count_all_results('question_options')) {
			return FALSE;
		}

		if ($round === 'final') {
			if ($row['answered_at'] || (int) $position !== $this->current_final_position($this->items($reg['id'], 'final'))) {
				return FALSE;   // no going back, no skipping ahead
			}
			$this->db->update('competition_answers',
				array('selected_option_id' => $option_id ?: NULL, 'answered_at' => date('Y-m-d H:i:s')),
				array('id' => $row['id'], 'answered_at' => NULL));
			return $this->db->affected_rows() === 1;
		}

		if ( ! $option_id) {
			return TRUE;   // Round 1: moving on without choosing keeps any earlier answer
		}
		$this->db->update('competition_answers', array('selected_option_id' => $option_id, 'answered_at' => date('Y-m-d H:i:s')), array('id' => $row['id']));
		return TRUE;
	}

	/** Submit and grade. Safe to call twice. @return string 'ok' | 'done' | 'not_started' */
	public function submit(array $c, array $reg, $round)
	{
		$this->db->trans_begin();
		$r = $this->db->query('SELECT * FROM competition_registrations WHERE id = ? FOR UPDATE', array($reg['id']))->row_array();
		$result = $this->grade($c, $r, $round);
		$this->db->trans_commit();
		return $result;
	}

	/** Grade one registrant's round. Caller holds the transaction and the row lock. */
	private function grade(array $c, array $r, $round)
	{
		if ($r[$this->col($round, 'submitted_at')]) {
			return 'done';
		}
		if ( ! $r[$this->col($round, 'started_at')]) {
			return 'not_started';
		}
		$this->db->query(
			'UPDATE competition_answers ca LEFT JOIN question_options o ON o.id = ca.selected_option_id
			 SET ca.is_correct = COALESCE(o.is_correct, 0)
			 WHERE ca.registration_id = ? AND ca.round = ?',
			array($r['id'], $round)
		);
		$score = (float) $this->db->query(
			'SELECT COALESCE(SUM(weight * is_correct), 0) AS s FROM competition_answers WHERE registration_id = ? AND round = ?',
			array($r['id'], $round)
		)->row()->s;
		$taken = min($this->time_limit($c, $round), max(0, time() - strtotime($r[$this->col($round, 'started_at')])));

		$update = array(
			$this->col($round, 'score')            => number_format($score, 1, '.', ''),
			$this->col($round, 'submitted_at')     => date('Y-m-d H:i:s'),
			$this->col($round, 'duration_seconds') => $taken,
		);
		if ($round === 'round1') {
			$update['status'] = 'round1_done';
		} else {
			$update['status'] = 'finalist';
		}
		$this->db->update('competition_registrations', $update, array('id' => $r['id']));
		return 'ok';
	}

	// ------------------------------------------------------------ admin steps

	/**
	 * Move the competition one stage on. Only forward: registrations are paid,
	 * and a round that has been sat cannot be re-opened fairly.
	 *
	 * @return array [ok, messages]
	 */
	public function advance(array $c)
	{
		$i = array_search($c['status'], self::STAGES, TRUE);
		if ($i === FALSE || $i === count(self::STAGES) - 1) {
			return array(FALSE, array('The competition is already complete.'));
		}
		$to = self::STAGES[$i + 1];
		switch ($to) {
			case 'registration':
				if (trim(strip_tags((string) $c['rules_html'])) === '') {
					return array(FALSE, array('Publish the full rules first (proctoring, disputes, prize payment). The competition page promises them before registration opens.'));
				}
				return $this->set_stage($c, $to);
			case 'round1':
				if ($problems = $this->round_problems($c, 'round1')) {
					return array(FALSE, $problems);
				}
				return $this->set_stage($c, $to);
			case 'grading':
				return $this->close_round1($c);
			case 'final':
				if ($problems = $this->round_problems($c, 'final')) {
					return array(FALSE, $problems);
				}
				if ( ! $this->db->where(array('competition_id' => $c['id'], 'status' => 'qualified'))->count_all_results('competition_registrations')) {
					return array(FALSE, array('Nobody qualified from Round 1.'));
				}
				return $this->set_stage($c, $to);
			case 'completed':
				return $this->close_final($c);
		}
		return array(FALSE, array('Unknown stage.'));
	}

	private function set_stage(array $c, $to)
	{
		$this->db->update('competitions', array('status' => $to), array('id' => $c['id'], 'status' => $c['status']));
		return $this->db->affected_rows() === 1 ? array(TRUE, array()) : array(FALSE, array('Someone else changed the competition. Reload and try again.'));
	}

	/** Competition ranking (1, 1, 3): equal score and equal time share a rank. */
	private function rank_rows(array $rows, $score, $time)
	{
		$rank = 0;
		$prev = NULL;
		foreach ($rows as $i => &$r) {
			$key = $r[$score] . '|' . $r[$time];
			if ($key !== $prev) {
				$rank = $i + 1;
				$prev = $key;
			}
			$r['rank'] = $rank;
		}
		return $rows;
	}

	/** Round 1 closes: grade stragglers, rank, qualify the top qualify_percent (ties at the cut go through). */
	public function close_round1(array $c)
	{
		$this->db->trans_begin();
		$locked = $this->db->query('SELECT * FROM competitions WHERE id = ? FOR UPDATE', array($c['id']))->row_array();
		if ($locked['status'] !== 'round1') {
			$this->db->trans_rollback();
			return array(FALSE, array('Round 1 is not open.'));
		}

		// Anyone still mid-round is submitted as they stand, time capped at the limit.
		foreach ($this->db->query('SELECT * FROM competition_registrations WHERE competition_id = ? AND round1_started_at IS NOT NULL AND round1_submitted_at IS NULL FOR UPDATE', array($c['id']))->result_array() as $r) {
			$this->grade($c, $r, 'round1');
		}

		$rows = $this->rank_rows($this->db->query(
			'SELECT r.*, u.name FROM competition_registrations r JOIN users u ON u.id = r.user_id
			 WHERE r.competition_id = ? AND r.round1_submitted_at IS NOT NULL
			 ORDER BY r.round1_score DESC, r.round1_duration_seconds ASC, r.id ASC',
			array($c['id'])
		)->result_array(), 'round1_score', 'round1_duration_seconds');

		$n = count($rows);
		$cut_rank = 0;
		if ($n) {
			$want = max(1, (int) ceil($n * (float) $c['qualify_percent'] / 100));
			$cut_rank = $rows[min($want, $n) - 1]['rank'];
		}
		$max = $this->max_score('round1');
		foreach ($rows as $r) {
			$qualified = $r['rank'] <= $cut_rank;
			$this->db->update('competition_registrations',
				array('round1_rank' => $r['rank'], 'status' => $qualified ? 'qualified' : 'eliminated'), array('id' => $r['id']));
			$this->Certificate_model->issue($r['user_id'], $c['id'], 'participation', 'Round 1 participant');
			$scored = rtrim(rtrim($r['round1_score'], '0'), '.') . ' / ' . rtrim(rtrim(number_format($max, 1), '0'), '.');
			if ($qualified) {
				$this->db->query('INSERT IGNORE INTO user_badges (user_id, badge_id) SELECT ?, id FROM badges WHERE code = ?', array($r['user_id'], 'finalist'));
				$this->Activity_model->notify($r['user_id'], 'competition', 'You qualified for the final!',
					'Round 1: ' . $scored . ', rank ' . $r['rank'] . ' of ' . $n . '. The final will be announced on the competition page.', 'competition');
			} else {
				$this->Activity_model->notify($r['user_id'], 'result', 'Round 1 results are out',
					'You scored ' . $scored . ' and ranked ' . $r['rank'] . ' of ' . $n . '. Your participation certificate is ready.', 'certificates');
			}
		}
		// Registered but never sat Round 1.
		$this->db->query("UPDATE competition_registrations SET status = 'eliminated' WHERE competition_id = ? AND round1_submitted_at IS NULL", array($c['id']));
		$this->db->update('competitions', array('status' => 'grading'), array('id' => $c['id']));

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return array(FALSE, array('Round 1 could not be closed. Nothing was changed.'));
		}
		$this->db->trans_commit();
		return array(TRUE, array());
	}

	/** The final closes: grade stragglers, rank, winners from the prize table, points, certificates. */
	public function close_final(array $c)
	{
		$this->db->trans_begin();
		$locked = $this->db->query('SELECT * FROM competitions WHERE id = ? FOR UPDATE', array($c['id']))->row_array();
		if ($locked['status'] !== 'final') {
			$this->db->trans_rollback();
			return array(FALSE, array('The final is not open.'));
		}
		foreach ($this->db->query('SELECT * FROM competition_registrations WHERE competition_id = ? AND final_started_at IS NOT NULL AND final_submitted_at IS NULL FOR UPDATE', array($c['id']))->result_array() as $r) {
			$this->grade($c, $r, 'final');
		}

		$rows = $this->rank_rows($this->db->query(
			'SELECT r.* FROM competition_registrations r
			 WHERE r.competition_id = ? AND r.final_submitted_at IS NOT NULL
			 ORDER BY r.final_score DESC, r.final_duration_seconds ASC, r.id ASC',
			array($c['id'])
		)->result_array(), 'final_score', 'final_duration_seconds');
		$prizes = $this->db->order_by('rank_from')->get_where('competition_prizes', array('competition_id' => $c['id']))->result_array();

		foreach ($rows as $r) {
			$prize = NULL;
			foreach ($prizes as $p) {
				if ($r['rank'] >= $p['rank_from'] && $r['rank'] <= $p['rank_to']) {
					$prize = $p;
				}
			}
			$label = self::ordinal($r['rank']) . ' place';
			$update = array('final_rank' => $r['rank'], 'status' => $prize ? 'winner' : 'finalist', 'prize_amount' => $prize ? $prize['prize_amount'] : NULL);
			$this->db->update('competition_registrations', $update, array('id' => $r['id']));
			if ($prize) {
				$this->Points_model->award($r['user_id'], 'competition_winner', (int) $prize['points'], 'competition_registration', $r['id'], $c['name'] . ': ' . $label);
				$this->Certificate_model->issue($r['user_id'], $c['id'], 'winner', $label);
				$this->Activity_model->notify($r['user_id'], 'competition', 'Congratulations: ' . $label . '!',
					'You won ' . taka($prize['prize_amount']) . ' and ' . (int) $prize['points'] . ' Academic Points. We will contact you about the prize payment.', 'competition');
			} else {
				$this->Activity_model->notify($r['user_id'], 'result', 'Final results are out',
					'You finished ' . $label . ' in the final. Your finalist certificate is ready.', 'certificates');
			}
		}
		// Every qualifier is a finalist, whether or not they sat the final.
		foreach ($this->db->query("SELECT user_id FROM competition_registrations WHERE competition_id = ? AND status IN ('qualified','finalist','winner')", array($c['id']))->result_array() as $q) {
			$this->Certificate_model->issue($q['user_id'], $c['id'], 'finalist', 'Finalist');
		}
		$this->db->update('competitions', array('status' => 'completed'), array('id' => $c['id']));

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return array(FALSE, array('The final could not be closed. Nothing was changed.'));
		}
		$this->db->trans_commit();
		return array(TRUE, array());
	}

	/** 1st, 2nd, 3rd, 4th ... 11th, 12th, 13th ... 21st. */
	public static function ordinal($n)
	{
		$n = (int) $n;
		if (in_array($n % 100, array(11, 12, 13), TRUE)) {
			return $n . 'th';
		}
		$ends = array(1 => 'st', 2 => 'nd', 3 => 'rd');
		return $n . (isset($ends[$n % 10]) ? $ends[$n % 10] : 'th');
	}

	// ----------------------------------------------------------- read / admin

	/** Ranked results of a round, with names and schools. */
	public function results(array $c, $round, $limit = 200)
	{
		$score = $round === 'final' ? 'final_score' : 'round1_score';
		$rank = $round === 'final' ? 'final_rank' : 'round1_rank';
		$time = $round === 'final' ? 'final_duration_seconds' : 'round1_duration_seconds';
		return $this->db->select("r.id, r.user_id, r.status, r.$score AS score, r.$rank AS pos, r.$time AS seconds, r.prize_amount, r.prize_paid_at, r.prize_payout_ref, u.name, u.phone, s.name AS school")
			->from('competition_registrations r')->join('users u', 'u.id = r.user_id')->join('schools s', 's.id = u.school_id', 'left')
			->where('r.competition_id', $c['id'])->where("r.$rank IS NOT NULL", NULL, FALSE)
			->order_by("r.$rank")->order_by('r.id')->limit($limit)->get()->result_array();
	}

	public function counts(array $c)
	{
		$row = $this->db->query(
			"SELECT COUNT(*) AS registered, SUM(fee_paid = 1) AS paid,
			        SUM(round1_started_at IS NOT NULL) AS round1_started, SUM(round1_submitted_at IS NOT NULL) AS round1_done,
			        SUM(status IN ('qualified','finalist','winner')) AS qualified,
			        SUM(final_submitted_at IS NOT NULL) AS final_done, SUM(status = 'winner') AS winners
			 FROM competition_registrations WHERE competition_id = ?",
			array($c['id'])
		)->row_array();
		return array_map('intval', $row);
	}

	/**
	 * Record that a winner's prize was paid (outside the app), once.
	 *
	 * @return string 'ok' | 'not_found' | 'not_winner' | 'already' | 'bad_ref'
	 */
	public function mark_prize_paid(array $c, $registration_id, $admin_id, $reference)
	{
		$reference = trim((string) $reference);
		if (mb_strlen($reference) < 3 || mb_strlen($reference) > 100) {
			return 'bad_ref';
		}
		$this->db->trans_begin();
		$r = $this->db->query('SELECT * FROM competition_registrations WHERE id = ? AND competition_id = ? FOR UPDATE', array((int) $registration_id, $c['id']))->row_array();
		if ( ! $r) {
			$this->db->trans_rollback();
			return 'not_found';
		}
		if ($r['status'] !== 'winner' || (float) $r['prize_amount'] <= 0) {
			$this->db->trans_rollback();
			return 'not_winner';
		}
		if ($r['prize_paid_at']) {
			$this->db->trans_rollback();
			return 'already';
		}
		$this->db->update('competition_registrations',
			array('prize_paid_at' => date('Y-m-d H:i:s'), 'prize_payout_ref' => $reference, 'prize_paid_by' => (int) $admin_id),
			array('id' => $r['id']));
		$this->Activity_model->notify($r['user_id'], 'competition', 'Your prize has been paid', taka($r['prize_amount']) . ' was sent. Reference: ' . $reference . '.', 'competition');
		$this->db->trans_commit();
		return 'ok';
	}
}
