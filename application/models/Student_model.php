<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Students, as the admin panel sees them. Read only, apart from suspending and
 * reactivating an account: no admin screen changes a wallet or points.
 */
class Student_model extends CI_Model
{
	private function apply_filters(array $f)
	{
		$this->db->where('u.user_type', 'student');
		if ( ! empty($f['q'])) {
			$q = $f['q'];
			$this->db->group_start()->like('u.name', $q)->or_like('u.display_name', $q)->or_like('u.phone', $q);
			if (ctype_digit($q)) {
				$this->db->or_where('u.id', (int) $q);
			}
			$this->db->group_end();
		}
		if ( ! empty($f['status'])) {
			$this->db->where('u.status', $f['status']);
		}
	}

	/** @return array [rows, total] */
	public function search(array $f, $page, $per_page = ADMIN_PAGE_SIZE)
	{
		$this->apply_filters($f);
		$total = (int) $this->db->from('users u')->count_all_results();
		$this->apply_filters($f);
		$rows = $this->db->select('u.id, u.name, u.display_name, u.phone, u.status, u.created_at, u.last_login_at,
				c.name AS class_name, s.name AS school, w.balance, p.total_points')
			->from('users u')
			->join('classes c', 'c.id = u.class_id', 'left')
			->join('schools s', 's.id = u.school_id', 'left')
			->join('wallets w', 'w.user_id = u.id', 'left')
			->join('user_lifetime_points p', 'p.user_id = u.id', 'left')
			->order_by('u.id', 'DESC')->limit($per_page, max(0, $page - 1) * $per_page)->get()->result_array();
		return array($rows, $total);
	}

	public function find($id)
	{
		return $this->db->select('u.*, c.name AS class_name, s.name AS school, w.balance, p.total_points, t.title AS tier')
			->from('users u')
			->join('classes c', 'c.id = u.class_id', 'left')
			->join('schools s', 's.id = u.school_id', 'left')
			->join('wallets w', 'w.user_id = u.id', 'left')
			->join('user_lifetime_points p', 'p.user_id = u.id', 'left')
			->join('tiers t', 't.id = COALESCE(p.tier_id, 0)', 'left')
			->where('u.id', (int) $id)->where('u.user_type', 'student')->get()->row_array();
	}

	/** Ledger sums, to show that the cached totals match their ledgers. */
	public function ledger_sums($id)
	{
		return array(
			'wallet' => (float) $this->db->query('SELECT COALESCE(SUM(amount), 0) AS s FROM wallet_transactions WHERE user_id = ?', array((int) $id))->row()->s,
			'points' => (int) $this->db->query('SELECT COALESCE(SUM(points), 0) AS s FROM point_transactions WHERE user_id = ?', array((int) $id))->row()->s,
		);
	}

	public function attempts($id, $limit = 20)
	{
		return $this->db->select('a.id, a.status, a.score, a.total_questions, a.percentage, a.fee_charged, a.started_at, ch.name AS chapter')
			->from('quiz_attempts a')->join('chapters ch', 'ch.id = a.chapter_id', 'left')
			->where('a.user_id', (int) $id)->order_by('a.id', 'DESC')->limit($limit)->get()->result_array();
	}

	public function registrations($id)
	{
		return $this->db->select('r.*, k.name AS competition, k.year')->from('competition_registrations r')
			->join('competitions k', 'k.id = r.competition_id')->where('r.user_id', (int) $id)->get()->result_array();
	}

	/** Suspend or reactivate. A suspended student is signed out on their next request. */
	public function set_status($id, $status)
	{
		if ( ! in_array($status, array('active', 'suspended'), TRUE)) {
			return FALSE;
		}
		$this->db->update('users', array('status' => $status), array('id' => (int) $id, 'user_type' => 'student'));
		return $this->db->affected_rows() === 1;
	}
}
