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

	/** Reference shown to the sender, e.g. AMCQ-000042. */
	public static function reference($id)
	{
		return 'AMCQ-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
	}
}
