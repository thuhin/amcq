<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "What Students Say" testimonials, as the admin panel manages them.
 *
 * These are real children's words, so: a testimonial is published only after a
 * guardian's consent is recorded, and consent covers the wording that was
 * agreed. Changing the name or the quote clears the consent and unpublishes.
 */
class Testimonial_model extends CI_Model
{
	public function all()
	{
		return $this->db->order_by('is_published', 'DESC')->order_by('sort_order')->order_by('id', 'DESC')
			->get('testimonials')->result_array();
	}

	public function find($id)
	{
		return $this->db->get_where('testimonials', array('id' => (int) $id))->row_array();
	}

	/** @return array [clean, errors] */
	public function validate(array $in)
	{
		$errors = array();
		$clean = array(
			'student_name' => trim((string) (isset($in['student_name']) ? $in['student_name'] : '')),
			'class_label'  => trim((string) (isset($in['class_label']) ? $in['class_label'] : '')),
			'school_name'  => trim((string) (isset($in['school_name']) ? $in['school_name'] : '')),
			'quote'        => trim(str_replace(array("\r\n", "\r"), "\n", (string) (isset($in['quote']) ? $in['quote'] : ''))),
			'rating'       => (int) (isset($in['rating']) ? $in['rating'] : 0),
			'sort_order'   => (int) (isset($in['sort_order']) ? $in['sort_order'] : 0),
			'consent'      => isset($in['consent']) && $in['consent'] === '1',
		);
		if (mb_strlen($clean['student_name']) < 2 || mb_strlen($clean['student_name']) > 100) {
			$errors['student_name'] = 'Enter the student\'s name (2 to 100 characters).';
		}
		if ($clean['class_label'] === '' || mb_strlen($clean['class_label']) > 30) {
			$errors['class_label'] = 'Enter the class, e.g. Class 5 (up to 30 characters).';
		}
		if (mb_strlen($clean['school_name']) > 200) {
			$errors['school_name'] = 'Keep the school name under 200 characters.';
		}
		if (mb_strlen($clean['quote']) < 10 || mb_strlen($clean['quote']) > 500) {
			$errors['quote'] = 'Write the quote as the student said it (10 to 500 characters).';
		}
		if ($clean['rating'] < 1 || $clean['rating'] > 5) {
			$errors['rating'] = 'Choose a rating from 1 to 5.';
		}
		if ($clean['sort_order'] < 0 || $clean['sort_order'] > 999) {
			$errors['sort_order'] = 'Use a position from 0 to 999.';
		}
		return array($clean, $errors);
	}

	/**
	 * Create or update.
	 *
	 * @return array [id|NULL, errors, consent_cleared]
	 */
	public function save($id, array $in)
	{
		list($c, $errors) = $this->validate($in);
		if ($errors) {
			return array(NULL, $errors, FALSE);
		}
		$row = array(
			'student_name' => $c['student_name'], 'class_label' => $c['class_label'],
			'school_name'  => $c['school_name'] !== '' ? $c['school_name'] : NULL,
			'quote'        => $c['quote'], 'rating' => $c['rating'], 'sort_order' => $c['sort_order'],
		);

		if ($id === NULL) {
			$row['consent_at'] = $c['consent'] ? date('Y-m-d H:i:s') : NULL;
			$this->db->insert('testimonials', $row);
			return array((int) $this->db->insert_id(), array(), FALSE);
		}

		$cur = $this->find($id);
		if ( ! $cur) {
			return array(NULL, array('_' => 'That testimonial no longer exists.'), FALSE);
		}
		$cleared = FALSE;
		if ($cur['consent_at'] && ($cur['student_name'] !== $c['student_name'] || $cur['quote'] !== $c['quote'])) {
			// The guardian agreed to other words. Fresh consent is needed.
			$row['consent_at'] = $c['consent'] ? date('Y-m-d H:i:s') : NULL;
			$row['is_published'] = 0;               // always re-published on purpose
			$cleared = ! $c['consent'];
		} elseif ( ! $cur['consent_at'] && $c['consent']) {
			$row['consent_at'] = date('Y-m-d H:i:s');
		}
		$this->db->update('testimonials', $row, array('id' => (int) $id));
		return array((int) $id, array(), $cleared);
	}

	/** @return string 'ok' | 'not_found' | 'no_consent' */
	public function publish($id)
	{
		$t = $this->find($id);
		if ( ! $t) {
			return 'not_found';
		}
		if ( ! $t['consent_at']) {
			return 'no_consent';
		}
		$this->db->update('testimonials', array('is_published' => 1), array('id' => (int) $id));
		return 'ok';
	}

	public function unpublish($id)
	{
		$this->db->update('testimonials', array('is_published' => 0), array('id' => (int) $id));
	}

	/** Only an unpublished testimonial can be deleted. @return bool */
	public function delete($id)
	{
		$this->db->delete('testimonials', array('id' => (int) $id, 'is_published' => 0));
		return $this->db->affected_rows() === 1;
	}
}
