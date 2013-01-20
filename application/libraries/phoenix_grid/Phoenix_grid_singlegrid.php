<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Phoenix_grid_singlegrid {
    
    private $id;

    private $permanent=FALSE;
    private $expiration_stage=0;
    private $expiration_target=2;
    
    private $filters=array();
    private $searches=array();
    private $sorting=array();
    private $pagination=array('page_first'=>0,'page_size'=>15);
	
    private $select;
    private $from;
    private $join=array();
    private $group_by=array();
    private $where;
    private $having;

    private $column_names=array();

    private $column_headers=array();
    
    private $column_callbacks=array();

    private $timestamp;

    public function __construct($params=array('id'=>NULL,'permanent'=>FALSE))
    {
        $this->id=$params['id'];
        $this->permanent=$params['permanent'];
        $this->timestamp=date("Y-m-d H:i:s");
    }

    private function set_definition() {
        $CI=&get_instance();
        $CI->load->library('session');
        if ($CI->session->userdata('phoenix_grid'))
            $grids=unserialize($CI->session->userdata('phoenix_grid'));
        $grids[$this->id]=&$this;
        $CI->session->set_userdata('phoenix_grid',serialize($grids));
    }

    public function expired() {
        if (!$this->permanent) {
            if ($this->expiration_stage++>=$this->expiration_target)
                return TRUE;
            else
                return FALSE;
        }
        else
            return FALSE;	
    }
	
    public function render($from=NULL) {
        $CI=&get_instance();
        if ($from)
            $this->from=$from;
        $this->set_definition();
        $data['phoenix_grid_id']=$this->id;
        return $CI->load->view('phoenix_grid/phoenix_grid_view',$data,true);
    }
	
    public function render_page() {
        //var_dump($this);
		
        // reseting expiration counter
        $this->expiration_stage=0;		

        $CI=&get_instance();

        $CI->db->start_cache();

        // selecting
        $select=$this->select;
        if ($select)
            $CI->db->select($select,FALSE);    
        $from=$this->from;
        $CI->db->from($from);
        
        // joining
        $join=$this->join;
        if ($join) {
            foreach($join as $key => $value) {
                $CI->db->join($key,$value,'left');
            }
        }
		
        // filters
        $where="";
        $filters=$this->filters;	
        if ($filters) {
            foreach($filters as $key => $filter) {
                $where.="(";
                foreach($filter as $condition) {
                    if ($condition=="phoenix_grid_null") $where.=$this->column_names[$key]."='' OR ";
                    else $where.=$this->column_names[$key]."='$condition' OR ";
                }
                $where=substr($where,0,-4);
                $where.=") AND ";
            }
            $where=substr($where,0,-5);
        }

		// searches
		$searches=$this->searches;
        if ($searches) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            foreach($searches as $key => $search) {
                if ($search!="")
                    $where.=$this->column_names[$key]." like '%$search%' AND ";
				else
					$where.="1=1 AND ";
            }
            $where=substr($where,0,-5);
            $where.=")";
        }
		
        // permanent filter
        $having=$this->having;
        if ($having) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            $where.=$having;
            $where.=")";
        }
        
        //var_dump($where);
		//return;
		
        // applying filter
        if ($where)
            $CI->db->having($where);
		
        // sorting
        $sorting=$this->sorting;
        if ($sorting) {
            $CI->db->order_by($sorting[0],$sorting[1]);
        }
		
        // grouping
        $group_by=$this->group_by;
        if ($group_by)
			$CI->db->group_by($group_by);

        $CI->db->stop_cache();

        $query=$CI->db->get();

        // paginating
        $all_results_count=$query->num_rows();
        $pagination=$this->pagination;
        if ($pagination) {
			$CI->db->limit($pagination['page_size'],$pagination['page_first']);
        }
        else {
			$CI->db->limit(1000);
        }
		
        $query=$CI->db->get();
	
        $CI->db->flush_cache();
		
        $result=$query->result_array();
		
        //column_names		
        $column_headers=array();
        // adding custom column
        foreach ($this->column_callbacks as $callback) {
			$column_headers['custom_column']=array('field_name'=>NULL,'field_id'=>NULL);
			foreach ($result as &$row) {
				$CI->load->library($callback['library']);
				$row=array('custom_column'=>($CI->$callback['library']->$callback['callback']($row)))+$row;
			}
        }
        // column headers
        $fields_list=$query->list_fields();
        if (count($this->column_headers)>0) {
            $i=0;
            foreach ($fields_list as $field) {
                if (isset($this->column_headers[$i])) {
                    $column_headers[$field]=array('field_name'=>$this->column_headers[$i],'field_id'=>'phoenix_grid_'.$this->id.'_'.$i);
                }
                $i++;
            }
        }
        else {
            $i=0;
            foreach ($fields_list as $field) {
                $column_headers[$field]=array('field_name'=>$field,'field_id'=>'phoenix_grid_'.$this->id.'_'.$i);
                $i++;
            }
        }
        $i=0;
        foreach ($fields_list as $field) {
            $this->column_names['phoenix_grid_'.$this->id.'_'.$i]=$field;
            $i++;
        }
        
        $this->set_definition();
		
		$data['id']=$this->id;
        $data['result']=$result;
        $data['column_headers']=$column_headers;
        $data['filters']=$filters;
        $data['searches']=$searches;
        
        $pagination_data['all_results_count']=$all_results_count;
        $pagination_data['page_count']=floor($all_results_count/$pagination['page_size'])+1;
        $pagination_data['page_current']=floor($pagination['page_first']/$pagination['page_size'])+1;
        
        $data['pagination']=$CI->load->view('phoenix_grid/phoenix_grid_pagination_view',$pagination_data,TRUE);
        
        if ($all_results_count)
            return $CI->load->view('phoenix_grid/phoenix_grid_page_view',$data,TRUE);
        else
            return $CI->load->view('phoenix_grid/phoenix_grid_no_data_view',$data,TRUE);
        
    }
	
	public function option_box() {
		$CI=&get_instance();
		$column_id=$CI->input->post('phoenix_grid_'.$this->id.'_col_id');
		$column=$this->column_names[$column_id];

		// selecting
		$select=$this->select;
		if ($select)
			$CI->db->select($select,FALSE);
		$CI->db->from($this->from);
		
		$join=$this->join;
		if ($join) {
			foreach($join as $key => $value) {
				$CI->db->join($key,$value,'left');
			}
		}
		
		// filters
        $where="";
        $filters=$this->filters;	
        if ($filters) {
            foreach($filters as $key => $filter) {
				$where.="(";
				if ($key!=$column_id) {
					foreach($filter as $condition) {
						if ($condition=="phoenix_grid_null") $where.=$this->column_names[$key]."='' OR ";
						else $where.=$this->column_names[$key]."='$condition' OR ";
					}
				}
				else {
					$where.="1=1 OR ";
				}
				$where=substr($where,0,-4);
				$where.=") AND ";
            }
            $where=substr($where,0,-5);
        }

		// searches
		$searches=$this->searches;
        if ($searches) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            foreach($searches as $key => $search) {
					if ($search!="" && $key!=$column_id)
						$where.=$this->column_names[$key]." like '%$search%' AND ";
					else
						$where.="1=1 AND ";
            }
            $where=substr($where,0,-5);
            $where.=")";
        }
		
        // permanent filter
        $having=$this->having;
        if ($having) {
            if ($where)
                $where.=" AND (";
            else
                $where.="(";
            $where.=$having;
            $where.=")";
        }
        
        //var_dump($where);
		//return;
		
        // applying filter
        if ($where)
            $CI->db->having($where);
		
		$CI->db->order_by($column,'asc',FALSE);
		
		// grouping
		$group_by=$this->group_by;
		if ($group_by)
			$CI->db->group_by($group_by);

		$CI->db->stop_cache();
		
		$query=$CI->db->get();

		$result=$query->result_array();
		$final_result=array();
		$last_value='phoenix_grid_null';
		foreach ($result as $row) {
			$current_value=$row[$column];
			if ($last_value!=$current_value) {
				$checked=FALSE;
				if (isset($this->filters[$column_id])) {
					foreach ($this->filters[$column_id] as $filter_value) {
						if ($current_value==$filter_value) 
							$checked=TRUE;
					}
				}
				$last_value=$current_value;
				$final_result[]=array('value'=>$current_value,'checked'=>$checked);
			}
		}
		$data['result']=$final_result;
		
        return $CI->load->view('phoenix_grid/phoenix_grid_option_box_view',$data,TRUE);
    }
	
	public function apply_filter() {
		$CI=&get_instance();
        $filters=&$this->filters;
        $newfilters=$CI->input->post('phoenix_grid_filters');
		if (is_array($newfilters)) $filters=array_merge($filters,$newfilters);
		$searches=&$this->searches;
        $newsearches=$CI->input->post('phoenix_grid_searches');
        if (is_array($newsearches)) $searches=array_merge($searches,$newsearches);
		$this->pagination['page_first']=0;
        $this->set_definition();
    }
    public function remove_filter() {
		$CI=&get_instance();
        $filters=&$this->filters;
        unset($filters[$CI->input->post('phoenix_grid_'.$this->id.'_col_id')]);
		$searches=&$this->searches;
		unset($searches[$CI->input->post('phoenix_grid_'.$this->id.'_col_id')]);
        $this->set_definition();
    }
	
	public function change_page() {
        $CI=&get_instance();
		$page_size=$this->pagination['page_size'];
        $page_number=$this->id_from_id_string($CI->input->post('page_number'));
        $this->pagination=array('page_first'=>($page_number-1)*$page_size,'page_size'=>$page_size);
        $this->set_definition();
    }
	
	public function reset() {
		$this->filters=array();
		$this->searches=array();
		$this->sorting=array();
		$this->pagination['page_first']=0;
        $this->set_definition();
    }
	
	public function select($select_string) {
        $this->select=$select_string;
    }
    public function from($from_string) {
        $this->from=$from_string;
    }
	public function join($join_table,$join_string) {
        $this->join[$join_table]=$join_string;
    }
	public function where($where_string) {
		$this->where=$where_string;
	}
	public function having($having_string) {
		$this->having=$having_string;
	}
	public function group_by($group_by_string) {
        $this->group_by=$group_by_string;
    }
	
	public function column_headers($column_headers) {
		$this->column_headers=array();
		foreach ($column_headers as $header) {
			$this->column_headers[]=$header;
		}
	}
	
	// callbacks
	public function column_callback($callback,$library='phoenix_grid_callbacks',$params=NULL) {
		$this->column_callbacks[0]=array('library'=>$library,'callback'=>$callback,'params'=>$params);
		$this->set_definition();
	}
	
	// helpers
    private function id_from_id_string($id_string) {
        $explode_result=explode('_',$id_string);
        return $explode_result[count($explode_result)-1];
    }
    
}

?>