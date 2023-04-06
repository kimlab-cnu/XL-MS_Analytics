<?php defined('BASEPATH') OR exit('No direct script access allowed');
class crosslinker extends CI_Controller{
    // intergration with crosslinker page
    public function index()
    {
        $this->load->view('common/header');
        $this->load->view('crosslinker/crosslinker');
        $this->load->view('common/footer');
    }
}?>