<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Cron: php index.php cli/schools refresh */
class Schools extends CI_Controller
{
	public function refresh()
	{
		if ( ! is_cli()) {
			show_404();
		}
		$this->load->model('School_model');
		echo 'Schools updated: ' . $this->School_model->refresh_week() . PHP_EOL;
	}
}
