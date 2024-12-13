<?php defined('BASEPATH') OR exit('No direct script access allowed');
class humanprotein extends CI_Controller{
    // intergration with humanprotein page
    public function index()
    {
        $this->load->view('common/header');
        $this->load->view('humanprotein/humanprotein');
        $this->load->view('common/footer');
    }
}?>