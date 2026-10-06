<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Read-only analytics for the dashboard and My Progress pages.
 */
class Progress_model extends CI_Model
{
	/** Overall accuracy: correct answers over questions answered, all quizzes. */
	public function accuracy($user_id)
	{
		$row = $this->db->query(
			"SELECT SUM(score) correct, SUM(total_questions) total, COUNT(*) quizzes
			 FROM quiz_attempts WHERE user_id = ? AND status = 'completed'",
			array($user_id)
		)->row_array();
		return array(
			'quizzes'  => (int) $row['quizzes'],
			'accuracy' => $row['total'] ? round($row['correct'] * 100 / $row['total'], 1) : NULL,
		);
	}

	/** Average score per subject (design 12 "Subject Performance"). */
	public function subjects($user_id)
	{
		return $this->db->query(
			"SELECT s.id, s.name, s.slug, s.icon, AVG(a.percentage) avg_p, COUNT(*) quizzes,
			        COUNT(DISTINCT a.chapter_id) chapters
			 FROM quiz_attempts a
			 JOIN chapters c ON c.id = a.chapter_id
			 JOIN subjects s ON s.id = c.subject_id
			 WHERE a.user_id = ? AND a.status = 'completed'
			 GROUP BY s.id ORDER BY s.sort_order",
			array($user_id)
		)->result_array();
	}

	/**
	 * Weakest chapters by average score. Only chapters attempted at least
	 * once, and only below 70%, so a student who is doing well everywhere
	 * gets an empty list rather than being told 85% is a weakness.
	 */
	public function weak_chapters($user_id, $limit = 3)
	{
		return $this->db->select('p.*, c.name, c.name_bn, s.name AS subject_name, s.slug AS subject_slug, cl.slug AS class_slug, c.slug AS chapter_slug')
			->from('user_chapter_progress p')
			->join('chapters c', 'c.id = p.chapter_id')
			->join('subjects s', 's.id = c.subject_id')
			->join('classes cl', 'cl.id = s.class_id')
			->where('p.user_id', $user_id)->where('p.avg_percentage <', 70)
			->order_by('p.avg_percentage')->limit($limit)->get()->result_array();
	}

	/** Most recently practised chapter, for "Continue Practicing". */
	public function continue_chapter($user_id)
	{
		return $this->db->select('p.*, c.name, c.name_bn, c.id AS chapter_id, c.chapter_no, c.slug AS chapter_slug, s.name AS subject_name, s.slug AS subject_slug, cl.name AS class_name, cl.slug AS class_slug')
			->from('user_chapter_progress p')
			->join('chapters c', 'c.id = p.chapter_id')
			->join('subjects s', 's.id = c.subject_id')
			->join('classes cl', 'cl.id = s.class_id')
			->where('p.user_id', $user_id)
			->order_by('p.last_attempt_at', 'DESC')->limit(1)->get()->row_array();
	}

	public function badges($user_id)
	{
		return $this->db->select('b.*, ub.awarded_at')->from('user_badges ub')
			->join('badges b', 'b.id = ub.badge_id')
			->where('ub.user_id', $user_id)->order_by('ub.awarded_at', 'DESC')->get()->result_array();
	}
}
