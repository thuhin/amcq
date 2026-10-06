<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class -> subject -> chapter -> topic, plus each student's progress through it.
 */
class Curriculum_model extends CI_Model
{
	public function mediums()
	{
		return $this->db->order_by('sort_order')->get('mediums')->result_array();
	}

	public function classes($medium_id = 1)
	{
		return $this->db->where('medium_id', $medium_id)
			->order_by('sort_order')->get('classes')->result_array();
	}

	public function class_by_slug($slug)
	{
		return $this->db->get_where('classes', array('slug' => $slug))->row_array();
	}

	/**
	 * Subjects of a class with their chapter counts and how many chapters are
	 * actually playable, i.e. hold enough published questions for a quiz.
	 */
	public function subjects($class_id)
	{
		$subjects = $this->db->select('s.*, COUNT(c.id) AS chapter_count')
			->from('subjects s')
			->join('chapters c', 'c.subject_id = s.id', 'left')
			->where('s.class_id', $class_id)->where('s.is_active', 1)
			->group_by('s.id')->order_by('s.sort_order')
			->get()->result_array();

		$playable = $this->playable_chapter_ids();
		foreach ($subjects as &$s) {
			$s['playable_chapters'] = (int) $this->db->where('subject_id', $s['id'])
				->where_in('id', $playable ?: array(0))->count_all_results('chapters');
		}
		return $subjects;
	}

	public function subject_by_slug($class_id, $slug)
	{
		return $this->db->get_where('subjects', array('class_id' => $class_id, 'slug' => $slug))->row_array();
	}

	public function chapter($id)
	{
		return $this->db->select('c.*, s.name AS subject_name, s.slug AS subject_slug, s.class_id, cl.name AS class_name, cl.slug AS class_slug')
			->from('chapters c')
			->join('subjects s', 's.id = c.subject_id')
			->join('classes cl', 'cl.id = s.class_id')
			->where('c.id', $id)->get()->row_array();
	}

	public function topic($id)
	{
		return $this->db->get_where('topics', array('id' => $id))->row_array();
	}

	/**
	 * Published question counts by difficulty, for a chapter or one topic.
	 */
	public function question_mix($chapter_id, $topic_id = NULL)
	{
		$this->db->select('difficulty, COUNT(*) AS n')->from('questions')
			->where('chapter_id', $chapter_id)->where('status', 'published');
		if ($topic_id) {
			$this->db->where('topic_id', $topic_id);
		}
		$mix = array('easy' => 0, 'medium' => 0, 'hard' => 0);
		foreach ($this->db->group_by('difficulty')->get()->result_array() as $r) {
			$mix[$r['difficulty']] = (int) $r['n'];
		}
		return $mix;
	}

	/**
	 * A quiz needs a full 5/3/2 set. Anything less shows "Coming Soon" rather
	 * than sending a student to a short or lopsided quiz.
	 */
	public function mix_is_playable(array $mix)
	{
		return $mix['easy'] >= QUIZ_MIX_EASY
			&& $mix['medium'] >= QUIZ_MIX_MEDIUM
			&& $mix['hard'] >= QUIZ_MIX_HARD;
	}

	public function playable_chapter_ids()
	{
		$rows = $this->db->query(
			"SELECT chapter_id FROM questions WHERE status = 'published'
			 GROUP BY chapter_id
			 HAVING SUM(difficulty = 'easy') >= ? AND SUM(difficulty = 'medium') >= ? AND SUM(difficulty = 'hard') >= ?",
			array(QUIZ_MIX_EASY, QUIZ_MIX_MEDIUM, QUIZ_MIX_HARD)
		)->result_array();
		return array_map('intval', array_column($rows, 'chapter_id'));
	}

	/**
	 * Chapters of a subject, each tagged playable / done / in progress for the
	 * sidebar on the chapter page (design 11).
	 */
	public function chapters_with_status($subject_id, $user_id = NULL)
	{
		$chapters = $this->db->where('subject_id', $subject_id)
			->order_by('sort_order')->get('chapters')->result_array();
		$playable = $this->playable_chapter_ids();
		$progress = $user_id ? $this->progress_by_chapter($user_id) : array();

		foreach ($chapters as &$c) {
			$p = isset($progress[$c['id']]) ? $progress[$c['id']] : NULL;
			$c['playable'] = $c['is_active'] && in_array((int) $c['id'], $playable, TRUE);
			$c['progress'] = $p;
			if ( ! $c['playable']) {
				$c['state'] = 'locked';
			} elseif ($p && $p['best_percentage'] >= STREAK_PASS_PERCENTAGE) {
				$c['state'] = 'done';
			} elseif ($p) {
				$c['state'] = 'started';
			} else {
				$c['state'] = 'new';
			}
		}
		return $chapters;
	}

	/**
	 * Topics of a chapter with MCQ counts and whether this student has
	 * practised each one.
	 */
	public function topics_with_status($chapter_id, $user_id = NULL)
	{
		$topics = $this->db->where('chapter_id', $chapter_id)->where('is_active', 1)
			->order_by('sort_order')->get('topics')->result_array();

		$done = array();
		if ($user_id && $topics) {
			$rows = $this->db->select('topic_id, MAX(percentage) AS best')
				->where('user_id', $user_id)->where('status', 'completed')
				->where_in('topic_id', array_column($topics, 'id'))
				->group_by('topic_id')->get('quiz_attempts')->result_array();
			foreach ($rows as $r) {
				$done[$r['topic_id']] = (float) $r['best'];
			}
		}

		foreach ($topics as &$t) {
			$t['mix']      = $this->question_mix($chapter_id, $t['id']);
			$t['total']    = array_sum($t['mix']);
			$t['playable'] = $this->mix_is_playable($t['mix']);
			$t['best']     = isset($done[$t['id']]) ? $done[$t['id']] : NULL;
		}
		return $topics;
	}

	public function progress_by_chapter($user_id)
	{
		$rows = $this->db->get_where('user_chapter_progress', array('user_id' => $user_id))->result_array();
		return array_column($rows, NULL, 'chapter_id');
	}

	/** Site-wide numbers for the homepage stats strip. */
	public function stats()
	{
		return array(
			'questions' => (int) $this->db->where('status', 'published')->count_all_results('questions'),
			'subjects'  => (int) $this->db->where('is_active', 1)->count_all_results('subjects'),
			'students'  => (int) $this->db->where('user_type', 'student')->count_all_results('users'),
		);
	}
}
