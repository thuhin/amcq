<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Practice selector (guideline §4.2) and the chapter page (design 11).
 */
class Practice extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Curriculum_model');
	}

	public function index()
	{
		$class = $this->Curriculum_model->class_by_slug($this->input->get('class') ?: 'class-5');
		if ( ! $class) {
			show_404();
		}
		$this->render('practice/index', array(
			'classes'  => $this->Curriculum_model->classes($class['medium_id']),
			'class'    => $class,
			'subjects' => $class['is_active'] ? $this->Curriculum_model->subjects($class['id']) : array(),
		), array(
			'title' => 'Start Practice', 'nav' => 'practice',
			'crumbs' => array(array('Home', ''), array('Practice', NULL)),
		));
	}

	public function subject($class_slug, $subject_slug)
	{
		$class = $this->Curriculum_model->class_by_slug($class_slug);
		$subject = $class ? $this->Curriculum_model->subject_by_slug($class['id'], $subject_slug) : NULL;
		if ( ! $subject || ! $class['is_active']) {
			show_404();
		}

		$user_id = $this->user ? $this->user['id'] : NULL;
		$chapters = $this->Curriculum_model->chapters_with_status($subject['id'], $user_id);

		// Selected chapter: ?chapter=slug, else the first one a student can
		// actually practise, so the page never opens on a locked chapter.
		$selected = NULL;
		$want = $this->input->get('chapter');
		foreach ($chapters as $c) {
			if ($want ? $c['slug'] === $want : $c['playable']) {
				$selected = $c;
				break;
			}
		}
		$selected = $selected ?: $chapters[0];

		$topics = $this->Curriculum_model->topics_with_status($selected['id'], $user_id);
		$chapter_mix = $this->Curriculum_model->question_mix($selected['id']);

		$done = count(array_filter($chapters, function ($c) { return $c['state'] === 'done'; }));
		$this->render('practice/subject', array(
			'class'        => $class,
			'subject'      => $subject,
			'chapters'     => $chapters,
			'chapter'      => $selected,
			'topics'       => $topics,
			'chapter_mix'  => $chapter_mix,
			'chapter_playable' => $selected['playable'],
			'subject_done' => $done,
			'topics_done'  => count(array_filter($topics, function ($t) { return $t['best'] !== NULL; })),
		), array(
			'title' => $class['name'] . ' ' . $subject['name'], 'nav' => 'practice',
			'crumbs' => array(array('Home', ''), array('Practice', 'practice'), array($class['name'], 'practice?class=' . $class['slug']), array($subject['name'], NULL)),
		));
	}
}
