<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallet (guideline §5.4). Looks and reads differently from Academic Points
 * on purpose; points never appear on this page.
 */
class Wallet extends MY_Controller
{
	const PRESETS = array(20, 50, 100);

	public function index()
	{
		$this->require_login();
		$this->load->model('Wallet_model');
		$uid = $this->user['id'];
		$this->render('wallet/index', array(
			'balance'      => $this->Wallet_model->balance($uid),
			'transactions' => $this->Wallet_model->transactions($uid),
			'presets'      => self::PRESETS,
			'simulated'    => $this->simulated(),
		), array('title' => 'Wallet', 'nav' => 'wallet', 'app' => TRUE));
	}

	/**
	 * Add balance.
	 *
	 * bKash and Nagad are not integrated yet. On a development machine the
	 * top-up is simulated (gateway 'manual') so the quiz flow can be tested
	 * end to end; anywhere else this refuses rather than pretending to
	 * take money.
	 */
	public function topup()
	{
		$this->require_login();
		if ($this->input->method() !== 'post') {
			redirect('wallet');
		}
		if ( ! $this->simulated()) {
			$this->flash('info', 'Online payment with bKash and Nagad is coming soon.');
			redirect('wallet');
		}
		// A typed custom amount wins over the preset radio.
		$amount = (int) ($this->input->post('custom') ?: $this->input->post('amount'));
		if ($amount < 10 || $amount > 1000) {
			$this->flash('warning', 'Enter an amount between ৳10 and ৳1,000.');
			redirect('wallet');
		}
		$this->load->model(array('Wallet_model', 'Activity_model'));
		$this->db->trans_start();
		$this->Wallet_model->credit($this->user['id'], $amount, 'topup', 'manual',
			'DEV-' . bin2hex(random_bytes(8)), 'Top-up (development, no real payment)');
		$this->Activity_model->log($this->user['id'], 'wallet', 'Added ' . taka($amount) . ' to wallet');
		$this->db->trans_complete();

		$this->flash('success', taka($amount) . ' added to your wallet.');
		redirect('wallet');
	}

	private function simulated()
	{
		return ENVIRONMENT === 'development' && ! ENABLE_LIVE_PAYMENTS;
	}
}
