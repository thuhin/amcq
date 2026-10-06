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
}
