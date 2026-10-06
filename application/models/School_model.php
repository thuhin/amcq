<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Top Schools This Week" (homepage designs 02-07) and the testimonials
 * that sit beside it.
 */
class School_model extends CI_Model
{
	/** The most recent week that has scores, or NULL if none yet. */
	public function latest_week()
	{
		$row = $this->db->select_max('week_start')->get('school_weekly_scores')->row_array();
		return $row['week_start'];
	}

	/**
	 * @param string $by 'average' (Average Score tab) or 'total' (Total Score tab)
	 */
	public function top_week($by = 'average', $limit = 5)
	{
		$week = $this->latest_week();
		if ( ! $week) {
			return array();
		}
		$col = $by === 'total' ? 'w.total_score' : 'w.average_score';
		return $this->db->select('s.id, s.name, w.average_score, w.total_score, w.quizzes')
			->from('school_weekly_scores w')->join('schools s', 's.id = w.school_id')
			->where('w.week_start', $week)->where('w.quizzes >', 0)
			->order_by($col, 'DESC')->order_by('s.name')
			->limit($limit)->get()->result_array();
	}

	/**
	 * The signed-in student's school for the "Your School" card: its position
	 * this week by average score, out of all ranked schools.
	 */
	public function school_card($school_id)
	{
		$week = $this->latest_week();
		if ( ! $week || ! $school_id) {
			return NULL;
		}
		$mine = $this->db->select('w.*, s.name')->from('school_weekly_scores w')->join('schools s', 's.id = w.school_id')
			->where('w.week_start', $week)->where('w.school_id', $school_id)->get()->row_array();
		if ( ! $mine) {
			return NULL;
		}
		$ranked = $this->db->where('week_start', $week)->where('quizzes >', 0)->count_all_results('school_weekly_scores');
		$ahead = $this->db->where('week_start', $week)->where('quizzes >', 0)
			->where('average_score >', $mine['average_score'])->count_all_results('school_weekly_scores');
		return array('name' => $mine['name'], 'position' => $ahead + 1, 'of' => $ranked,
			'average' => (float) $mine['average_score'], 'total' => (int) $mine['total_score']);
	}

	/**
	 * Recompute this week's scores from quiz attempts. Run weekly (or more
	 * often) from cron: `php index.php cli/schools refresh`.
	 *
	 * average_score divides by quizzes taken, not by students (blueprint v2.0),
	 * so a school is not penalised for having more active students.
	 */
	public function refresh_week()
	{
		$week = date('Y-m-d', strtotime('monday this week'));
		$this->db->query(
			"REPLACE INTO school_weekly_scores (school_id, week_start, quizzes, average_score, total_score)
			 SELECT u.school_id, ?, COUNT(*), ROUND(AVG(a.percentage), 3), SUM(a.score)
			 FROM quiz_attempts a JOIN users u ON u.id = a.user_id
			 WHERE a.status = 'completed' AND u.school_id IS NOT NULL AND a.completed_at >= ?
			 GROUP BY u.school_id",
			array($week, $week)
		);
		return $this->db->affected_rows();
	}

	public function testimonials($limit = 3)
	{
		return $this->db->where('is_published', 1)->order_by('sort_order')->order_by('id', 'DESC')
			->limit($limit)->get('testimonials')->result_array();
	}
}
