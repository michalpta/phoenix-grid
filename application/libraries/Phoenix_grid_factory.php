<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Phoenix_grid_factory {

    private $CI;
    private $grids = array();
    private $do_cleaning;

    public function __construct($params = array('do_cleaning' => TRUE)) {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
        $this->do_cleaning = $params['do_cleaning'];
        $this->get_definition();

        // cleaning
        if ($this->do_cleaning) {
            foreach ($this->grids as $gridname => $grid) {
                if ($grid->expired())
                    unset($this->grids[$gridname]);
            }
        }

        $this->set_definition();
    }

    private function set_definition() {
        $this->CI->session->set_userdata('phoenix_grid', serialize($this->grids));
    }

    private function get_definition() {
        if ($this->CI->session->userdata('phoenix_grid'))
            $this->grids = unserialize($this->CI->session->userdata('phoenix_grid'));
    }

    /**
     * 
     * @param string $id
     * @return Phoenix_grid
     */
    public function new_grid($id = NULL) {
        if (!$id) {
            $id = uniqid();
            $permanent = FALSE;
        } else {
            $permanent = TRUE;
        }
        if (isset($this->grids[$id])) {
            $this->CI->{'phoenix_grid_' . $id} = &$this->grids[$id];
        } else {
            $this->grids[$id] = new Phoenix_grid(array('id' => $id, 'permanent' => $permanent));
            $this->set_definition();
            $this->CI->{'phoenix_grid_' . $id}=$this->grids[$id];
        }
        return $this->CI->{'phoenix_grid_' . $id};
    }

    /**
     * 
     * @param string $id
     * @return Phoenix_grid
     */
    public function get_grid($id) {
        if (isset($this->grids[$id])) {
            $this->CI->{'phoenix_grid_' . $id} = &$this->grids[$id];
            return $this->CI->{'phoenix_grid_' . $id};
        } else {
            return NULL;
        }
    }

}

class Phoenix_grid {
    
    // query types constants
    const MAIN_QUERY = 1;
    const FILTER_OPTIONS_QUERY = 2;

    // grid params
    private $id;
    private $permanent = FALSE;
    private $expiration_stage = 0;
    private $expiration_target = 2;
    
    private $column_names = array();
    private $column_headers = array();
    
    private $column_callbacks = array();
    
    private $pagination = array('page_first' => 0, 'page_size' => 10);
    
    /**
     * SQL Params
     */
    
    // static sql params
    private $select;
    private $from;
    private $join = array();
    private $group_by = array();
    private $where;
    private $having;
    private $order_by = array();
    // dynamic sql params
    private $filters = array();
    private $searches = array();
    private $sorting = array();   
    
    private $all_results_count = 0;
    
    // timestamp param - not sure why i put it here :)
    private $timestamp;

    public function __construct($params = array('id' => NULL, 'permanent' => FALSE)) {
        $this->id = $params['id'];
        $this->permanent = $params['permanent'];
        $this->timestamp = date("Y-m-d H:i:s");
    }

    private function set_definition() {
        $CI = &get_instance();
        $CI->load->library('session');
        if ($CI->session->userdata('phoenix_grid'))
            $grids = unserialize($CI->session->userdata('phoenix_grid'));
        $grids[$this->id] = &$this;
        $CI->session->set_userdata('phoenix_grid', serialize($grids));
    }

    public function expired() {
        if (!$this->permanent) {
            if ($this->expiration_stage++ >= $this->expiration_target)
                return TRUE;
            else
                return FALSE;
        }
        else
            return FALSE;
    }
    
    /**
     * main method building all the grid queries
     * @param int $query_type
     * @param array $query_params
     */
    private function _build_query($query_type = self::MAIN_QUERY, $query_params = NULL) {

        $CI = &get_instance();
        
        // PREPARING QUERY

        // selecting
        $select = $this->select;
        if ($select)
            $CI->db->select($select, FALSE);
        $from = $this->from;
        
        // froming
        $CI->db->from($from);

        // joining
        $join = $this->join;
        if ($join) {
            foreach ($join as $key => $value) {
                $CI->db->join($key, $value, 'left');
            }
        }

        // permanent filtering
        $where = "";
        $where = $this->where;
        if ($where)
            $CI->db->where($where);

        // custom filtering
        $where = "";
        $filters = $this->filters;
        if ($filters) {
            foreach ($filters as $key => $filter) {
                $where.="(";
                if ($query_type === self::FILTER_OPTIONS_QUERY && $key == $query_params["column_id"]) {
                    $where.="1=1 OR ";
                }
                else {
                    foreach ($filter as $condition) {
                        if ($condition == "phoenix_grid_null")
                            $where.=$this->column_names[$key] . "='' OR ";
                        else
                            $where.=$this->column_names[$key] . "='$condition' OR ";
                    }
                }
                $where = substr($where, 0, -4);
                $where.=") AND ";
            }
            $where = substr($where, 0, -5);
        }

        // quick searching
        $searches = $this->searches;
        if ($searches) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            foreach ($searches as $key => $search) {
                if ($search != "")
                    $where.=$this->column_names[$key] . " like '%$search%' AND ";
                else
                    $where.="1=1 AND ";
            }
            $where = substr($where, 0, -5);
            $where.=")";
        }
        
        // havinging
        $having = $this->having;
        if ($having) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            $where.=$having;
            $where.=")";
        }

        // applying filters, searches and havings
        if ($where)
            $CI->db->having($where);
        
        // grouping
        $group_by = $this->group_by;
        if ($group_by)
            $CI->db->group_by($group_by);

        // sorting
        // applying sorting only for main query
        if ($query_type === self::MAIN_QUERY) {
            $sorting = $this->sorting;
            if ($sorting) {
                foreach ($sorting as $key => $sort) {
                    if ($sort == 0)
                        $CI->db->order_by($this->column_names[$key], 'asc');
                    else if ($sort == 1)
                        $CI->db->order_by($this->column_names[$key], 'desc');
                }
            }
        }
        
        // ordering by
        // ordering by filter column only for filter options query
        if ($query_type === self::FILTER_OPTIONS_QUERY) {
            $CI->db->order_by($this->column_names[$query_params["column_id"]], 'asc', FALSE);
        }
        // applying permanent order
        else {
            $order_bys = $this->order_by;
            if ($order_bys) {
                foreach ($order_bys as $order_column => $order_direction) {
                    $CI->db->order_by($order_column, $order_direction);
                }
            }
        }
        
        // QUERY READY FOR EXECUTING

    }

    public function render($from = NULL) {
        $CI = &get_instance();

        $this->_build_query();
        
        $CI->db->limit('1');
        
        $query = $CI->db->get();
        
        $fields_list = $query->list_fields();

        $i = 0;
        foreach ($fields_list as $field) {
            $this->column_names['phoenix_grid_' . $this->id . '_' . $i] = $field;
            $i++;
        }
        
        $this->set_definition();
        
        $data['phoenix_grid_id'] = $this->id;
        return $CI->load->view('phoenix_grid/phoenix_grid_view', $data, TRUE);
    }

    public function render_page() {
        // reseting expiration counter each time the grid is shown in application
        $this->expiration_stage = 0;

        $CI = &get_instance();

        $this->_build_query();
        
        $query = $CI->db->get();

        // getting result array
        $result=$query->result_array();
        
        // rows count calculation
        $this->all_results_count = $query->num_rows();
        
        // pagination
        // slicing the result array basing on the pagination params
        $result = $this->_page_result_array($result);

        // column headers
        // preparing column headers
        $column_headers = $this->_prepare_column_headers($result_array);
        
        // custom columns
        // adding custom columns to result array and adding corresponding headers to column_headers array
        $result = $this->_add_custom_columns($result, $column_headers);        

        // view data preparation
        $data = $this->_prepare_grid_page_view_data($result, $column_headers);

        // loading view
        if ($this->all_results_count > 0)
            return $CI->load->view('phoenix_grid/phoenix_grid_page_view', $data, TRUE);
        else
            return $CI->load->view('phoenix_grid/phoenix_grid_no_data_view', $data, TRUE);
    }

    public function option_box() {
        $CI = &get_instance();
        
        $column_id = $CI->input->post('phoenix_grid_' . $this->id . '_col_id');

        $this->_build_query(self::FILTER_OPTIONS_QUERY,array("column_id" => $column_id));

        $query = $CI->db->get();
        
        $result = $query->result_array();
        
        $data['result'] = $this->_process_filter_options_result_array($column_id, $result);

        return $CI->load->view('phoenix_grid/phoenix_grid_filter_box_view', $data, TRUE);
    }

    public function apply_filter() {
        $CI = &get_instance();
        $filters = &$this->filters;
        $newfilters = $CI->input->post('phoenix_grid_filters');
        if (is_array($newfilters))
            $filters = array_merge($filters, $newfilters);
        $searches = &$this->searches;
        $newsearches = $CI->input->post('phoenix_grid_searches');
        if (is_array($newsearches))
            $searches = array_merge($searches, $newsearches);
        $this->pagination['page_first'] = 0;
        $this->all_results_count = 0;
        $this->set_definition();
    }

    public function remove_filter() {
        $CI = &get_instance();
        $filters = &$this->filters;
        unset($filters[$CI->input->post('phoenix_grid_' . $this->id . '_col_id')]);
        $searches = &$this->searches;
        unset($searches[$CI->input->post('phoenix_grid_' . $this->id . '_col_id')]);
        $this->all_results_count = 0;
        $this->set_definition();
    }

    public function sort() {
        $CI = &get_instance();
        $sorting = &$this->sorting;
        $col_id = $CI->input->post('phoenix_grid_' . $this->id . '_col_id');
        if (isset($sorting[$col_id]))
            $sorting[$col_id] = ($sorting[$col_id] + 1) % 2;
        else
            $sorting[$col_id] = 0;
        $this->set_definition();
    }

    public function remove_sort() {
        $CI = &get_instance();
        $sorting = &$this->sorting;
        $col_id = $CI->input->post('phoenix_grid_' . $this->id . '_col_id');
        unset($sorting[$col_id]);
        $this->set_definition();
    }

    public function change_page() {
        $CI = &get_instance();
        $page_size = $this->pagination['page_size'];
        $page_number = $this->id_from_id_string($CI->input->post('page_number'));
        $this->pagination = array('page_first' => ($page_number - 1) * $page_size, 'page_size' => $page_size);
        $this->set_definition();
    }

    public function reset() {
        $this->filters = array();
        $this->searches = array();
        $this->sorting = array();
        $this->pagination['page_first'] = 0;
        $this->all_results_count = 0;
        $this->set_definition();
    }

    public function pagination($page_size) {
        $this->pagination['page_size'] = $page_size;
    }

    public function select($select_string) {
        $this->select = $select_string;
    }

    public function from($from_string) {
        $this->from = $from_string;
    }

    public function join($join_table, $join_string) {
        $this->join[$join_table] = $join_string;
    }

    public function where($where_string) {
        $this->where = $where_string;
    }

    public function having($having_string) {
        $this->having = $having_string;
    }

    public function group_by($group_by_string) {
        $this->group_by = $group_by_string;
    }

    public function order_by($order_column, $order_direction) {
        $this->order_by[$order_column] = $order_direction;
    }

    public function column_headers($column_headers) {
        $this->column_headers = array();
        foreach ($column_headers as $header) {
            $this->column_headers[] = $header;
        }
    }

    // callbacks
    public function column_callback($callback, $column_name = NULL, $offset = 0, $library = 'phoenix_grid_callbacks', $params = array()) {
        $this->column_callbacks[$offset] = array('callback' => $callback, 'library' => $library, 'params' => $params, 'column_name' => $column_name);
    }

    // excel export
    public function export_to_excel() {
        $CI = &get_instance();

        $this->_build_query();

        $query = $CI->db->get();

        $heading = $query->list_fields();
        $result = $query->result_array();

        $CI->load->library('table');
        $CI->load->helper('download');
        $CI->table->set_heading($heading);
        force_download('export.xls', chr(0xEF) . chr(0xBB) . chr(0xBF) . $CI->table->generate($result));
    }
    
    public function export_to_csv() {
        $CI = &get_instance();
        $CI->load->dbutil();

        $this->_build_query();

        $query = $CI->db->get();

        $CI->load->helper('download');
        force_download('export.csv', chr(0xEF) . chr(0xBB) . chr(0xBF) . $CI->dbutil->csv_from_result($query));
    }
    
    private function _page_result_array($result_array) {
        $result = array();
        $pagination = $this->pagination;
        if ($pagination) {
            $result = array_slice($result_array,min($pagination['page_first'],$this->all_results_count),min($pagination['page_size'],$this->all_results_count));
        } else {
            $result = array_slice($result_array,0,min(1000,$this->all_results_count)-1);
        }
        return $result;
    }
    
    private function _prepare_column_headers($result_array) {
        $column_headers = array();
        
        $fields_list = $this->column_names;
        if (count($this->column_headers) > 0) {
            
            // custom column headers
            
            $i = 0;
            foreach ($fields_list as $field) {
                if (isset($this->column_headers[$i])) {
                    $column_headers[$field] = array('field_name' => $this->column_headers[$i], 'field_id' => 'phoenix_grid_' . $this->id . '_' . $i);
                } else {
                    $column_headers['hidden_' . $i] = array('field_name' => 'phoenix_grid_hidden_column', 'field_id' => 'phoenix_grid_hidden_column');
                }
                $i++;
            }
        } else {
            
            // default column headers
            
            $i = 0;
            foreach ($fields_list as $field) {
                $column_headers[$field] = array('field_name' => $field, 'field_id' => 'phoenix_grid_' . $this->id . '_' . $i);
                $i++;
            }
        }
        
        return $column_headers;        
    }
    
    private function _add_custom_columns($result_array, &$column_headers) {
        $CI = &get_instance();
        foreach ($this->column_callbacks as $key => $callback) {
            $CI->load->library($callback['library']);
            $column_headers = array_slice($column_headers, 0, $key) + array($key => array('field_name' => $callback['column_name'], 'field_id' => NULL)) + array_slice($column_headers, $key, count($column_headers) - $key);
            foreach ($result_array as &$row) {
                $row = array_slice($row, 0, $key) + array($key => ($CI->$callback['library']->$callback['callback'](array_merge($callback['params'], $row)))) + array_slice($row, $key, count($row) - $key);
            }
        }
        return $result_array;
    }
    
    private function _process_filter_options_result_array($column_id, $result_array) {
        $column = $this->column_names[$column_id];
        $final_result = array();
        $last_value = 'phoenix_grid_null';
        foreach ($result_array as $row) {
            $current_value = $row[$column];
            if ($last_value != $current_value) {
                $checked = FALSE;
                if (isset($this->filters[$column_id])) {
                    foreach ($this->filters[$column_id] as $filter_value) {
                        if ($current_value == $filter_value)
                            $checked = TRUE;
                    }
                }
                $last_value = $current_value;
                $final_result[] = array('value' => $current_value, 'checked' => $checked);
            }
        }
        return $final_result;
    }
    
    private function _prepare_pagination_view_data() {
        $pagination_data = array();
        $pagination_data['id'] = $this->id;
        $pagination_data['all_results_count'] = $this->all_results_count;
        $pagination_data['page_count'] = ceil($this->all_results_count / $this->pagination['page_size']);
        $pagination_data['page_current'] = ceil($this->pagination['page_first'] / $this->pagination['page_size']) + 1;
        return $pagination_data;
    }
    
    private function _prepare_grid_page_view_data($result_array, $column_headers) {
        $CI = &get_instance();
        $data = array();
        $data['id'] = $this->id;
        $data['result'] = $result_array;
        $data['column_headers'] = $column_headers;
        $data['filters'] = $this->filters;
        $data['searches'] = $this->searches;
        $data['sorting'] = $this->sorting;
        $pagination_data = $this->_prepare_pagination_view_data();
        $data['pagination'] = $CI->load->view('phoenix_grid/phoenix_grid_pagination_view', $pagination_data, TRUE);
        return $data;
    }

    // helpers
    private function id_from_id_string($id_string) {
        $explode_result = explode('_', $id_string);
        return $explode_result[count($explode_result) - 1];
    }

}

?>
