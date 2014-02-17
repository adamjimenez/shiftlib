<?php
//how it should work
/*
require_once("paging.class.php");

$query="SELECT * FROM table";
$p = new paging( $query );

$paging=$p->get_paging();
$content=sql_query($p->query);
*/

class paging {
    function paging( $query, $int_num_result=NULL, $order=NULL, $asc=true, $prefix, $num_pages=10 )
	{
		$this->num_pages = $num_pages;

		$this->paging_separator='&nbsp;'; // e.g. 1 2 3 4 5
		$this->paging_format='%1$s &nbsp;&nbsp;&nbsp;&nbsp; %2$s &nbsp;&nbsp;&nbsp;&nbsp; %3$s'; // e.g. previous 1 2 3 4 5 next
		$this->paging_hide_prev_next=true; // hide prev / next links if they don't exist
		$this->paging_previous_text='Previous';
		$this->paging_next_text='Next';

		$this->prefix = $prefix ? $prefix.'_' : '';

		$this->hash_secret='djkla9uwekj.sd';

		//querystring
		$qs=$_GET;
		unset($qs[$this->prefix.'page']);

		$str_ext_argv=http_build_query($qs);

		$this->int_num_result = (is_numeric($int_num_result)) ? $int_num_result : NULL;
		$this->page = (int) $_GET[$this->prefix.'page'];

		if( is_numeric( $_GET[$this->prefix.'limit'] ) ){
			$this->int_num_result = $_GET[$this->prefix.'limit'];
		}elseif( $_GET[$this->prefix.'limit']=='All' ){
			$this->int_num_result = NULL;
		}

		if( $this->page<0 ){
			$this->page=0;
		}

		$this->str_ext_argv = $str_ext_argv;
		$this->query = $query;

		if(
			$_GET[$this->prefix.'order'] and
			$_GET[$this->prefix.'hash'] and
			$_GET[$this->prefix.'hash']==md5($_GET[$this->prefix.'order'].$this->hash_secret)
		){
			$order=$_GET[$this->prefix.'order'];
		}

		if( isset($_GET[$this->prefix.'asc']) ){
			$asc=$_GET[$this->prefix.'asc'];
		}

		if( $order and is_string($query) ){
			$this->query .=  " ORDER BY ".escape($order);

			if( !$asc and substr($order,-5)!==' DESC' and substr($order,-4)!==' ASC' ){
				$this->query .=  " DESC";
			}

			$this->order=$order;
			$this->asc=$asc;
		}

		if( $this->int_num_result and is_string($query) ){
			$this->query .= " LIMIT ".$this->page.", ".$this->int_num_result;
		}

		if(  is_string($query) ){
			$this->query=trim($this->query);

			$pos=stripos($this->query,'select');

			$this->query = substr($this->query,$pos+6);
			$this->query = "SELECT SQL_CALC_FOUND_ROWS ".$this->query;

			$this->rows = sql_query($this->query);

			$count = mysql_fetch_array(mysql_query("SELECT FOUND_ROWS()"));
			$this->total=$count[0];
		}else{
			$this->total=count($query);
		}
	}

	function getNumberOfPage()
	{
		return $this->int_num_result ? ($this->total / $this->int_num_result) : 1;
	}

	function getCurrentPage()
	{
		$int_cur_page = ( $this->page * $this->getNumberOfPage() ) / $this->total;
		return number_format( $int_cur_page, 0 );
	}

	function getPagingArray()
	{
		$array_paging['lower'] = ( $this->page + 1 );

		if( $this->page + $this->int_num_result >= $this->total ){
			$array_paging['upper'] = $this->total;
		}elseif( $this->int_num_result ){
			$array_paging['upper'] = ( $this->page + $this->int_num_result );
		}else{
			$array_paging['upper'] = $this->total;
		}

		$array_paging['total'] = $this->total;

		$qs = $this->str_ext_argv ? '&'.$this->str_ext_argv : '';

		if ( $this->page != 0 ){
			$array_paging['previous_link'] = '<a class="prev" href="?'.$this->prefix.'page='.( $this->page - $this->int_num_result ).$qs.'">';
			$array_paging['start_link'] = '<a class="prev" href="?'.$this->prefix.'page=0'.$qs.'">';
		}else{
			$array_paging['previous_link'] = '';
			$array_paging['start_link'] = '';
		}

		if( $this->int_num_result and (($this->total - $this->page) > $this->int_num_result) ){
			$int_new_position = $this->page + $this->int_num_result;
			$int_end=floor( ($this->total/10) );
			$int_end*=10;

			$array_paging['next_link'] = '<a class="next" href="?'.$this->prefix.'page='.$int_new_position.$qs.'">';
			$array_paging['end_link'] = '<a class="next" href="?'.$this->prefix.'page='.$int_end.$qs.'">';
		}else{
			$array_paging['next_link'] = '';
			$array_paging['end_link'] = '';
		}
		return $array_paging;
	}

	function getPagingRowArray()
	{
		if($this->getNumberOfPage()>0){
			$start = $this->getCurrentPage() - floor($this->num_pages/2);

			if($start<0){
				$start=0;
			}

    		$end = $start + $this->num_pages;

			if($end>$this->getNumberOfPage()){
				$end=$this->getNumberOfPage();
			}
		}else{
			$start=0;
			$end=0;
		}

		$array_all_page=array();
		$j=0;
		for( $i=$start; $i<$end; $i++ ){
			// if current page, do not make a link
			if( $i == $this->getCurrentPage() ){
				$array_all_page[$j] = "<b>". ($i+1) ."</b>";
			}else{
				$int_new_position = ( $i * $this->int_num_result );
				$array_all_page[$j] = "<a href=\"?".$this->prefix."page=$int_new_position&$this->str_ext_argv\">". ($i+1) ."</a>";
			}
			$j++;
		}
		return $array_all_page;
	}

	function get_paging()
	{
		if( ($this->total < $this->int_num_result) or !$this->int_num_result ){
			return false;
		}

		// Load up the 2 array in order to display result
		$array_paging = $this->getPagingArray();
		$array_row_paging = $this->getPagingRowArray();

		if( !$array_paging['previous_link'] and !$this->paging_hide_prev_next ){
			$previous= '<strong>'.$this->paging_previous_text.'</strong>';
		}elseif( $array_paging['previous_link'] ){
			$previous= $array_paging['previous_link'] .'<strong>'.$this->paging_previous_text.'</strong></a>';
		}else{
			$previous='';
		}

		$pages='';
		if( sizeof($array_row_paging)>1 ){
			for( $i=0; $i<sizeof($array_row_paging); $i++ ){
				$pages.= $array_row_paging[$i];

				if( ($i+1)<sizeof($array_row_paging) ){
					$pages.=$this->paging_separator;
				}
			}
		}

		if( !$array_paging['next_link'] and !$this->paging_hide_prev_next ){
			$next= '<strong>'.$this->paging_next_text.'</strong>';
		}elseif( $array_paging['next_link'] ){
			$next= $array_paging['next_link'] .'<strong>'.$this->paging_next_text.'</strong></a>';
		}else{
			$next='';
		}

		return sprintf($this->paging_format, $previous, $pages, $next);
	}

	function get_results($links=false)
	{
		$array_paging = $this->getPagingArray();

		if( !$array_paging['total'] ){
			return false;
		}

		// Display the result as you like...
		$paging='';
		$paging.='<b>'. number_format($array_paging['lower']).'</b>';
		$paging.=' - <b>'. number_format($array_paging['upper']).'</b>';
		$paging.=' of <b>'. number_format($array_paging['total']).'</b>';

		if( $links ){
			// Load up the 2 array in order to display result
			$array_paging = $this->getPagingArray();
			//$array_row_paging = $this->getPagingRowArray();

			if( $array_paging['previous_link'] ){
				$paging='&nbsp;&nbsp;&nbsp;&nbsp;'.$array_paging['previous_link'] .'<strong style="font-size:16px;">'.$this->paging_previous_text.'</strong></a>&nbsp;&nbsp;&nbsp;&nbsp;'.$paging;
			}

			if( $array_paging['next_link'] ){
				$paging.= '&nbsp;&nbsp;&nbsp;&nbsp;'.$array_paging['next_link'] .'<strong style="font-size:16px;">'.$this->paging_next_text.'</strong></a>';
			}
		}

		return $paging;
	}

	function do_query()
	{
		$select = mysql_query($this->query);

        if( !$select ){
            throw new Exception(mysql_error());
        }

		return $select;
	}

	function get_rows()
	{
		if( is_array($this->query) ){
			return array_slice($this->query,$this->page,$this->int_num_result);
		}
	}

	function col($col,$label=NULL)
	{
		global $images_dir;

		$images_dir=(isset($images_dir)) ? $images_dir : '/_lib/images/';

		$qs=$_GET;

		unset($qs['order']);
		unset($qs['asc']);
		unset($qs['hash']);

		$query=http_build_query($qs);

		if( !$label ){
			$label=$col;
		}

		$hash=md5($col.$this->hash_secret);

		if( $this->order==$col ){
			if( !$this->asc ){
				$html='<a href="?'.$query.'&'.$this->prefix.'order='.$col.'&'.$this->prefix.'asc=1&'.$this->prefix.'hash='.$hash.'">'.($label).'</a>';
				$html.=' <img src="'.$images_dir.'sort_up.gif" vspace="2">';
			}else{
				$html='<a href="?'.$query.'&'.$this->prefix.'order='.$col.'&'.$this->prefix.'asc=0&'.$this->prefix.'hash='.$hash.'">'.($label).'</a>';
				$html.=' <img src="'.$images_dir.'sort_down.gif" vspace="2">';
			}
		}else{
			$html='<a href="?'.$query.'&'.$this->prefix.'order='.$col.'&'.$this->prefix.'asc=1&'.$this->prefix.'hash='.$hash.'">'.($label).'</a>';
		}

		return $html;
	}

	function items_per_page($options=array(25,50,100,200,'All'))
	{
		$qs = $_GET;

		unset($qs['hash']);

		$query=http_build_query($qs);

		if( !$label ){
			$label=$col;
		}

		$hash=md5($qs['order'].$this->hash_secret);

		$html='Items per page ';

		foreach( $options as $v ){
			if( $v==$this->int_num_result ){
				$html.=$v.' ';
			}else{
				$html.='<a href="?'.$query.'&'.$this->prefix.'limit='.$v.'&'.$this->prefix.'hash='.$hash.'">'.$v.'</a> ';
			}
		}

		return $html;
	}
};
?>