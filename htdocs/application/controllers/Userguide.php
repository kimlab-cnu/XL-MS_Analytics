<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Userguide extends CI_Controller {

	# intergration with userguide page	
	public function index()
	{
		$this->load->view('common/header');
        $this->load->view('userguide/userguide');
        $this->load->view('common/footer');
	}
}
?>