<?
require('../../base.php');

if( !$_GET['section'] ){
	die('no table');
}

$vars['section']=escape($_GET['section']);

$table=str_replace(' ','_',$vars['section']);

if( !is_array($vars['labels'][$vars['section']]) ){ 
	$vars['labels'][$vars['section']][]=key($vars['fields'][$vars['section']]);
}

$where=array('1=1');
$qs=array();

foreach( $vars['fields'][$vars['section']] as $k=>$v ){
	if( $v=='select' and $vars['options'][$k]==$_GET['option'] ){
		$where[]="`$k`='".escape($_GET['id'])."'";
		
		$qs[$k]=$_GET['id'];
	}
}

$qs=http_build_query($qs);

$where_str='';
foreach($where as $w){
	$where_str.=$w." AND ";
}
$where_str=substr($where_str,0,-4);

$query="SELECT * FROM `$table` WHERE $where_str";

$asc=($order=='date') ? false : true;

$p = new paging( $query, 25, $order, $asc );
		

$list=array();

$list['totalCount']=$p->int_nbr_row;

$list['items']=sql_query($p->query);

print json_encode($list);
?>