<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Phoenix_grid {

    private $CI;
    
    private $grids=array();
    
    private $do_cleaning;
    
    public function __construct($params=array('do_cleaning'=>TRUE)) {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
        $this->CI->load->library('phoenix_grid/phoenix_grid_singlegrid');
        $this->do_cleaning=$params['do_cleaning'];
        $this->get_definition();

        // cleaning
        if ($this->do_cleaning) {
            foreach ($this->grids as $gridname=>$grid) {
                if ($grid->expired()) unset($this->grids[$gridname]);
            }
        }

        $this->set_definition();
    }
    
    private function set_definition() {
        $this->CI->session->set_userdata('phoenix_grid',serialize($this->grids));
    }
    private function get_definition() {
        if ($this->CI->session->userdata('phoenix_grid'))
            $this->grids=unserialize($this->CI->session->userdata('phoenix_grid'));
    }

    public function &new_grid($id=NULL) {
        if (!$id) {
            $id=uniqid();
            $permanent=FALSE;
        }
        else {
            $permanent=TRUE;
        }
        if (isset($this->grids[$id])) {
            $this->CI->{'phoenix_grid_'.$id}=&$this->grids[$id];
        }
        else {
            $this->CI->load->library('phoenix_grid/phoenix_grid_singlegrid',array('id'=>$id,'permanent'=>$permanent),'phoenix_grid_'.$id);
            $this->grids[$id]=&$this->CI->{'phoenix_grid_'.$id};
            $this->set_definition();
        }
        return $this->CI->{'phoenix_grid_'.$id};
    }
    public function &get_grid($id) {
        if (isset($this->grids[$id])) {
            $this->CI->{'phoenix_grid_'.$id}=&$this->grids[$id];
            return $this->CI->{'phoenix_grid_'.$id};
        }
        else {
            $null_value=NULL;
            return $null_value;
        }
    }
    
}

?>
