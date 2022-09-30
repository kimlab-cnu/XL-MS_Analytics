<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

	# intergration with main page
	public function index()
	{
		$this->load->view('common/header');
        $this->load->view('main/main');
        $this->load->view('common/footer');
	}
}
?>