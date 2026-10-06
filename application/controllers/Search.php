<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Header search (all designs): subjects, chapters and topics, Bangla or English. */
class Search extends MY_Controller
{
	public function index()
	{
		$q = trim((string) $this->input->get('q'));
		$results = array();
		if (mb_strlen($q) >= 2) {
			$like = '%' . $this->db->escape_like_str($q) . '%';
			$results = $this->db->query(
				"SELECT 'chapter' kind, c.name, c.name_bn, c.slug chapter_slug, s.name subject, s.slug subject_slug, cl.slug class_slug, NULL code
				   FROM chapters c JOIN subjects s ON s.id = c.subject_id JOIN classes cl ON cl.id = s.class_id
				  WHERE cl.is_active = 1 AND (c.name LIKE ? ESCAPE '!' OR c.name_bn LIKE ? ESCAPE '!')
				 UNION ALL
				 SELECT 'topic', t.name, NULL, c.slug, s.name, s.slug, cl.slug, t.code
				   FROM topics t JOIN chapters c ON c.id = t.chapter_id JOIN subjects s ON s.id = c.subject_id JOIN classes cl ON cl.id = s.class_id
				  WHERE cl.is_active = 1 AND t.name LIKE ? ESCAPE '!'
				 UNION ALL
				 SELECT 'subject', s.name, s.name_bn, NULL, s.name, s.slug, cl.slug, NULL
				   FROM subjects s JOIN classes cl ON cl.id = s.class_id
				  WHERE cl.is_active = 1 AND (s.name LIKE ? ESCAPE '!' OR s.name_bn LIKE ? ESCAPE '!')
				 LIMIT 30",
				array($like, $like, $like, $like, $like)
			)->result_array();
		}
		$this->render('search/index', array('q' => $q, 'results' => $results), array('title' => 'Search'));
	}
}
