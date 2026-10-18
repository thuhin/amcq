<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Correct Me": students report wrong questions, earn a point per approval,
 * and climb the career ladder.
 */
class Correction_model extends CI_Model
{
	public function submit($user_id, $question_id, array $data)
	{
		// One open report per student per question: re-submitting while the
		// first is pending would only duplicate the reviewer's work.
		$open = $this->db->where('user_id', $user_id)->where('question_id', $question_id)
			->where('status', 'pending')->count_all_results('correction_requests');
		if ($open) {
			return 'duplicate';
		}
		$option = NULL;
		if ( ! empty($data['claimed_option_id'])) {
			$option = (int) $this->db->where('id', (int) $data['claimed_option_id'])
				->where('question_id', $question_id)->count_all_results('question_options')
				? (int) $data['claimed_option_id'] : NULL;
		}
		$this->db->insert('correction_requests', array(
			'user_id'                   => $user_id,
			'question_id'               => $question_id,
			'claimed_correct_option_id' => $option,
			'what_is_wrong'             => $data['what_is_wrong'],
			'user_explanation'          => $data['explanation'],
			'source_ref'                => $data['source_ref'] ?: NULL,
		));
		return 'ok';
	}

	public function mine($user_id)
	{
		return $this->db->select('r.*, q.stem')->from('correction_requests r')
			->join('questions q', 'q.id = r.question_id')
			->where('r.user_id', $user_id)->order_by('r.id', 'DESC')->get()->result_array();
	}

	public function approved_count($user_id)
	{
		return (int) $this->db->where('user_id', $user_id)->where('status', 'approved')
			->count_all_results('correction_requests');
	}

	public function milestones()
	{
		return $this->db->order_by('approved_required')->get('career_milestones')->result_array();
	}

	// ------------------------------------------------------------ admin side

	public function counts()
	{
		$counts = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
		foreach ($this->db->select('status, COUNT(*) AS n')->group_by('status')->get('correction_requests')->result_array() as $r) {
			$counts[$r['status']] = (int) $r['n'];
		}
		return $counts;
	}

	/**
	 * Reports of one status. Pending ones oldest first, so nothing waits behind
	 * newer reports; decided ones newest first.
	 *
	 * @return array [rows, total]
	 */
	public function queue($status, $page, $per_page = ADMIN_PAGE_SIZE)
	{
		$total = (int) $this->db->where('status', $status)->count_all_results('correction_requests');
		$this->db->select('r.id, r.status, r.what_is_wrong, r.created_at, r.reviewed_at, u.name AS student, q.id AS question_id, q.stem, q.status AS question_status')
			->from('correction_requests r')
			->join('users u', 'u.id = r.user_id')
			->join('questions q', 'q.id = r.question_id')
			->where('r.status', $status);
		if ($status === 'pending') {
			$this->db->order_by('r.id', 'ASC');
		} else {
			$this->db->order_by('r.reviewed_at', 'DESC')->order_by('r.id', 'DESC');
		}
		$rows = $this->db->limit($per_page, max(0, $page - 1) * $per_page)->get()->result_array();
		return array($rows, $total);
	}

	/** One report with the student, the question and its options, or NULL. */
	public function find_full($id)
	{
		$r = $this->db->select('r.*, u.name AS student, u.display_name, rv.name AS reviewer')
			->from('correction_requests r')
			->join('users u', 'u.id = r.user_id')
			->join('users rv', 'rv.id = r.reviewed_by', 'left')
			->where('r.id', (int) $id)->get()->row_array();
		if ( ! $r) {
			return NULL;
		}
		$this->load->model('Question_model');
		$r['question'] = $this->Question_model->find($r['question_id']);
		$r['claimed_label'] = NULL;
		foreach ($r['question']['options'] as $label => $o) {
			if ((int) $o['id'] === (int) $r['claimed_correct_option_id']) {
				$r['claimed_label'] = $label;
			}
		}
		return $r;
	}

	/**
	 * Approve: the student earns CORRECTION_APPROVED_POINTS and is told.
	 * Optionally pull the question out of circulation until it is fixed.
	 *
	 * One transaction on a locked row: only a pending report can be decided, so
	 * a double click or two admins cannot decide it twice. The point is also
	 * guarded by point_transactions' unique key (source, ref_type, ref_id).
	 *
	 * @return string 'ok' | 'not_found' | 'decided' | 'failed'
	 */
	public function approve($id, $admin_id, $note, $pull_question)
	{
		$this->load->model(array('Points_model', 'Activity_model'));
		$this->db->trans_begin();
		$r = $this->db->query('SELECT * FROM correction_requests WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
		if ( ! $r) {
			$this->db->trans_rollback();
			return 'not_found';
		}
		if ($r['status'] !== 'pending') {
			$this->db->trans_rollback();
			return 'decided';
		}

		$this->db->update('correction_requests', array(
			'status' => 'approved', 'reviewed_by' => (int) $admin_id,
			'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => $note !== '' ? $note : NULL,
		), array('id' => $r['id']));

		$points = $this->Points_model->award($r['user_id'], 'correction', CORRECTION_APPROVED_POINTS, 'correction_request', $r['id'], 'Correction approved');
		$earned = CORRECTION_APPROVED_POINTS . ' Academic Point' . (CORRECTION_APPROVED_POINTS === 1 ? '' : 's');
		$this->Activity_model->log($r['user_id'], 'correction', 'Correction approved', '+' . $earned);
		$this->Activity_model->notify($r['user_id'], 'correction', 'Your correction was approved',
			'Thank you for helping keep the questions right. You earned ' . $earned . '.', 'correct-me');

		if ($pull_question) {
			$this->db->query(
				"UPDATE questions SET status = 'in_review', verified_by = NULL, verified_at = NULL
				 WHERE id = ? AND status IN ('verified', 'published')",
				array($r['question_id'])
			);
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return 'failed';
		}
		$this->db->trans_commit();
		return 'ok';
	}

	/** Reject, with a reason the student will read. @return string as approve() */
	public function reject($id, $admin_id, $note)
	{
		$this->load->model('Activity_model');
		$this->db->trans_begin();
		$r = $this->db->query('SELECT * FROM correction_requests WHERE id = ? FOR UPDATE', array((int) $id))->row_array();
		if ( ! $r) {
			$this->db->trans_rollback();
			return 'not_found';
		}
		if ($r['status'] !== 'pending') {
			$this->db->trans_rollback();
			return 'decided';
		}
		$this->db->update('correction_requests', array(
			'status' => 'rejected', 'reviewed_by' => (int) $admin_id,
			'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => $note,
		), array('id' => $r['id']));
		$this->Activity_model->log($r['user_id'], 'correction', 'Correction reviewed', 'Not approved');
		$this->Activity_model->notify($r['user_id'], 'correction', 'Your correction was reviewed',
			'Thank you for reporting it. This time it was not approved: ' . $note, 'correct-me');

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return 'failed';
		}
		$this->db->trans_commit();
		return 'ok';
	}
}
