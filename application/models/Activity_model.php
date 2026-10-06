<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The dashboard activity feed and the (rarer) notifications.
 *
 * Two channels on purpose: every quiz is activity, but notifying on every
 * quiz is the over-notifying the guideline warns against (§5.7).
 */
class Activity_model extends CI_Model
{
	public function log($user_id, $type, $title, $detail = NULL)
	{
		$this->db->insert('user_activity', array(
			'user_id' => $user_id, 'type' => $type, 'title' => $title, 'detail' => $detail,
		));
	}

	public function notify($user_id, $type, $title, $body = NULL, $link = NULL)
	{
		$this->db->insert('notifications', array(
			'user_id' => $user_id, 'type' => $type, 'title' => $title, 'body' => $body, 'link' => $link,
		));
	}

	public function recent($user_id, $limit = 6)
	{
		return $this->db->where('user_id', $user_id)->order_by('id', 'DESC')
			->limit($limit)->get('user_activity')->result_array();
	}

	public function unread_count($user_id)
	{
		return (int) $this->db->where('user_id', $user_id)->where('read_at IS NULL', NULL, FALSE)
			->count_all_results('notifications');
	}
}
