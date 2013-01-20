<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Phoenix_grid_controller extends CI_Controller {

	private $grid;
    
    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        if (!$this->session->userdata('phoenix_grid')) exit('phoenix_grid not initialized');
		$this->load->library('phoenix_grid',array('do_cleaning'=>FALSE));
		$this->load->library('phoenix_grid/phoenix_grid_singlegrid');
		$id=substr($this->input->post('phoenix_grid_id'),13);
		$this->grid=&$this->phoenix_grid->get_grid($id);
    }
    
	public function js() {
        $this->output->set_header('Content-Type: text/javascript; charset=UTF-8');
		$this->load->view('phoenix_grid/phoenix_grid_js_view');
    }
	
    public function index() {
		if ($this->grid===NULL)
			echo $this->load->view('phoenix_grid/phoenix_grid_reload_request_view',TRUE);
		else
			echo $this->grid->render_page();
    }
    
	public function option_box() {
        echo $this->grid->option_box();
    }
    public function apply_filter() {
        $this->grid->apply_filter();
        $this->index();
    }
    public function remove_filter() {
        $this->grid->remove_filter();
        $this->index();
    }
	public function sort() {
        $this->grid->sort();
        $this->index();
    }
	public function change_page() {
        $this->grid->change_page();
        $this->index();
    }
    public function reset() {
        $this->grid->reset();
        $this->index();
    }
        
}