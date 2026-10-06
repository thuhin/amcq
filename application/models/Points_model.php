<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Academic Points: reputation, never money.
 *
 * Points never decrease and can never be spent or converted to Taka (blueprint
 * v1.1, guideline §5.3). Nothing in this class touches the wallet.
 */
class Points_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Activity_model');
	}

	/**
	 * Award points for one event, at most once.
	 *
	 * Idempotence comes from the database, not from a check-then-insert: the
	 * UNIQUE (user_id, source, ref_type, ref_id) key rejects a second award for
	 * the same event even when two requests race. A duplicate returns 0.
	 *
	 * Call inside the caller's transaction so the award commits or rolls back
	 * with the event that earned it.
	 *
	 * @return int points actually awarded
	 */
	public function award($user_id, $source, $points, $ref_type, $ref_id, $description)
	{
		$points = (int) $points;
		if ($points <= 0) {
			return 0;
		}

		$this->db->query(
			'INSERT IGNORE INTO point_transactions (user_id, source, points, ref_type, ref_id, description)
			 VALUES (?, ?, ?, ?, ?, ?)',
			array($user_id, $source, $points, $ref_type, $ref_id, $description)
		);
		if ($this->db->affected_rows() !== 1) {
			return 0;   // already awarded for this event
		}

		$this->db->query(
			'INSERT INTO user_lifetime_points (user_id, total_points) VALUES (?, ?)
			 ON DUPLICATE KEY UPDATE total_points = total_points + VALUES(total_points)',
			array($user_id, $points)
		);
		$this->Activity_model->log($user_id, 'points', '+' . $points . ' Academic Points', $description);
		$this->refresh_tier($user_id);
		return $points;
	}

	/**
	 * Move the user to the highest tier their total qualifies for. Tiers only
	 * go up, because points only go up.
	 */
	public function refresh_tier($user_id)
	{
		$row = $this->db->select('p.total_points, p.tier_id')->from('user_lifetime_points p')
			->where('p.user_id', $user_id)->get()->row_array();
		if ( ! $row) {
			return;
		}
		$tier = $this->tier_for($row['total_points']);
		if ((int) $tier['id'] > (int) $row['tier_id']) {
			$this->db->update('user_lifetime_points', array('tier_id' => $tier['id']), array('user_id' => $user_id));
			$this->db->query(
				'INSERT IGNORE INTO user_badges (user_id, badge_id) SELECT ?, id FROM badges WHERE code = ?',
				array($user_id, 'tier_' . $tier['code'])
			);
			$this->Activity_model->log($user_id, 'tier', 'Reached ' . $tier['title']);
			$this->Activity_model->notify($user_id, 'tier', 'You reached ' . $tier['title'] . '!', NULL, 'rank');
		}
	}

	public function tiers()
	{
		return $this->db->order_by('min_points')->get('tiers')->result_array();
	}

	public function tier_for($points)
	{
		return $this->db->where('min_points <=', (int) $points)
			->order_by('min_points', 'DESC')->limit(1)->get('tiers')->row_array();
	}

	public function next_tier($points)
	{
		return $this->db->where('min_points >', (int) $points)
			->order_by('min_points')->limit(1)->get('tiers')->row_array();
	}

	public function total($user_id)
	{
		$row = $this->db->get_where('user_lifetime_points', array('user_id' => $user_id))->row_array();
		return $row ? (int) $row['total_points'] : 0;
	}

	public function history($user_id, $limit = 50)
	{
		return $this->db->where('user_id', $user_id)->order_by('id', 'DESC')
			->limit($limit)->get('point_transactions')->result_array();
	}

	/**
	 * National rank: 1 + the number of students with strictly more points.
	 * Ties share a rank. Derived on read; never stored (see schema).
	 */
	public function rank($user_id)
	{
		$points = $this->total($user_id);
		$ahead = $this->db->where('total_points >', $points)->count_all_results('user_lifetime_points');
		return $ahead + 1;
	}

	/**
	 * Rank change against the last weekly snapshot. Positive = moved up.
	 * NULL when there is no snapshot to compare with.
	 */
	public function rank_change($user_id)
	{
		$snap = $this->db->where('user_id', $user_id)->order_by('week_start', 'DESC')
			->limit(1)->get('rank_snapshots')->row_array();
		return $snap ? (int) $snap['national_rank'] - $this->rank($user_id) : NULL;
	}

	public function leaderboard($limit = LEADERBOARD_PUBLIC_SIZE)
	{
		// display_name only: never phone or email on a public page (§4.6).
		return $this->db->select('u.id, u.display_name, u.name, p.total_points, t.title AS tier, s.name AS school')
			->from('user_lifetime_points p')
			->join('users u', 'u.id = p.user_id')
			->join('tiers t', 't.id = p.tier_id')
			->join('schools s', 's.id = u.school_id', 'left')
			->join('user_settings us', 'us.user_id = u.id', 'left')
			->where('u.status', 'active')
			->where('COALESCE(us.show_on_leaderboard, 1) =', 1)
			->where('p.total_points >', 0)
			->order_by('p.total_points', 'DESC')->order_by('u.id')
			->limit($limit)->get()->result_array();
	}

	/** Students just above and below this one, for "you and nearby" (§6.3). */
	public function nearby($user_id, $span = 2)
	{
		$points = $this->total($user_id);
		$above = $this->db->select('u.id, u.display_name, p.total_points')
			->from('user_lifetime_points p')->join('users u', 'u.id = p.user_id')
			->where('p.total_points >', $points)->order_by('p.total_points', 'ASC')
			->limit($span)->get()->result_array();
		$below = $this->db->select('u.id, u.display_name, p.total_points')
			->from('user_lifetime_points p')->join('users u', 'u.id = p.user_id')
			->where('p.total_points <=', $points)->where('u.id !=', $user_id)
			->order_by('p.total_points', 'DESC')->limit($span)->get()->result_array();
		$me = $this->db->select('u.id, u.display_name, p.total_points')
			->from('user_lifetime_points p')->join('users u', 'u.id = p.user_id')
			->where('u.id', $user_id)->get()->row_array();

		$rows = array_merge(array_reverse($above), $me ? array($me) : array(), $below);
		foreach ($rows as &$r) {
			$r['rank'] = $this->db->where('total_points >', $r['total_points'])->count_all_results('user_lifetime_points') + 1;
			$r['is_me'] = (int) $r['id'] === (int) $user_id;
		}
		return $rows;
	}
}
