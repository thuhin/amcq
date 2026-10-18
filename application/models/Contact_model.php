<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Contact Us messages. Stored for staff to read; there is no outgoing mail.
 */
class Contact_model extends CI_Model
{
	const TOPICS = array(
		'general'     => 'General question',
		'payment'     => 'Payment & wallet',
		'competition' => 'National competition',
		'question'    => 'A question looks wrong',
		'school'      => 'School / teacher partnership',
		'technical'   => 'Technical problem',
	);

	/**
	 * Cap messages per IP address per hour. An open form with no limit is an
	 * invitation to fill the table with spam.
	 */
	public function over_limit($ip)
	{
		return $this->db->where('ip_address', $ip)
			->where('created_at >', date('Y-m-d H:i:s', time() - 3600))
			->count_all_results('contact_messages') >= CONTACT_LIMIT_PER_HOUR;
	}

	/** @return int message id */
	public function save(array $m)
	{
		$this->db->insert('contact_messages', array(
			'user_id'    => $m['user_id'] ?: NULL,
			'name'       => $m['name'],
			'phone'      => $m['phone'] ?: NULL,
			'email'      => $m['email'] ?: NULL,
			'topic'      => isset(self::TOPICS[$m['topic']]) ? $m['topic'] : 'general',
			'message'    => $m['message'],
			'ip_address' => $m['ip'],
			'user_agent' => mb_substr((string) $m['user_agent'], 0, 255),
		));
		return (int) $this->db->insert_id();
	}

	// ------------------------------------------------------------ admin side

	const STATUSES = array('new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'closed' => 'Closed');

	public function counts()
	{
		$counts = array_fill_keys(array_keys(self::STATUSES), 0);
		foreach ($this->db->select('status, COUNT(*) AS n')->group_by('status')->get('contact_messages')->result_array() as $r) {
			$counts[$r['status']] = (int) $r['n'];
		}
		return $counts;
	}

	/** Newest first. @return array [rows, total] */
	public function inbox(array $f, $page, $per_page = ADMIN_PAGE_SIZE)
	{
		$apply = function () use ($f) {
			if ( ! empty($f['status'])) {
				$this->db->where('status', $f['status']);
			}
			if ( ! empty($f['topic'])) {
				$this->db->where('topic', $f['topic']);
			}
		};
		$apply();
		$total = (int) $this->db->count_all_results('contact_messages');
		$apply();
		$rows = $this->db->select('id, name, phone, email, topic, message, status, created_at')
			->order_by('id', 'DESC')->limit($per_page, max(0, $page - 1) * $per_page)
			->get('contact_messages')->result_array();
		return array($rows, $total);
	}

	/** A message with the linked student's name, or NULL. */
	public function find($id)
	{
		return $this->db->select('m.*, u.name AS student_name')->from('contact_messages m')
			->join('users u', 'u.id = m.user_id', 'left')->where('m.id', (int) $id)->get()->row_array();
	}

	public function set_status($id, $status)
	{
		if ( ! isset(self::STATUSES[$status])) {
			return FALSE;
		}
		$this->db->update('contact_messages', array('status' => $status), array('id' => (int) $id));
		return $this->db->affected_rows() >= 0;
	}

	/** Opening a new message marks it read (idempotent; nothing else changes). */
	public function mark_read($id)
	{
		$this->db->update('contact_messages', array('status' => 'read'), array('id' => (int) $id, 'status' => 'new'));
	}

	/** Reference shown to the sender, e.g. AMCQ-000042. */
	public static function reference($id)
	{
		return 'AMCQ-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
	}
}
