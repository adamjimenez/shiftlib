<?php
set_time_limit(0);
ini_set('memory_limit','256M');

/*
ob_end_clean();
header("Connection: close");
ignore_user_abort(); // optional
ob_start();*/

require('../../base.php');

ini_set('auto_detect_line_endings', '1');

$table=str_replace(' ','_',$_POST['section']);

$allowed_exts=array('csv');

if( !$_POST['csv'] ){
	$errors[]='csv';
}

if( !$_POST['section'] ){
	$errors[]='section';
}

if( !$errors ){
	$fields=$_POST['fields'];

	$i=0;
	$total=0;

	$handle = fopen(dirname(__FILE__).'/../tmp/'.$_SERVER['HTTP_HOST'].'.csv', "r");
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE ) {
		if( $i!=0 ){
			foreach( $fields as $k=>$v ){
				$rows[$i][$k]=$data[$v];
			}
		}
			
		$i++;
	}
}

$o=array();

if( $errors){
	$o['errors']=implode('\\n',$errors);
	$o['success']=false;
}else{
	$imported=0;
	$updated=0;
	$invalid=0;
	
	foreach( $rows as $row ){
		$query='';
		$where='';
		$data=array();
		
		foreach( $row as $k=>$v ){
			$field_name=underscored($k);
		
			if( $k=='id' or $k=='related' or $k=='position' ){
				continue;
			}
			
			if( $vars['fields'][$_POST['section']][$k]=='select' ){
				if( !is_array($vars['options'][$k]) ){
					if( !$v ){
						$v='';
					}elseif( !is_numeric($v) ){
						reset($vars['fields'][$vars['options'][$k]]);
					
						$option_id=sql_query("SELECT id FROM `".escape(underscored($vars['options'][$k]))."` WHERE `".underscored(key($vars['fields'][$vars['options'][$k]]))."`='".escape(trim($v))."'",true);
						
						if( count($option_id) ){
							$v=$option_id['id'];
						}else{
							
							//CMS SAVE
							$cms->set_section($vars['options'][$k]);
							
							$data=array(
								key($vars['fields'][$vars['options'][$k]])=>trim($v)
							);
							
							if( count($cms->validate($data)) ){
								continue;
							}else{
								$v=$cms->save($data);
							}
							
							/*
							mysql_query("INSERT INTO `".escape($vars['options'][$k])."`
								SET
									`".key($vars['fields'][$vars['options'][$k]])."`='".escape(trim($v))."'
							") or trigger_error("SQL", E_USER_ERROR);
							
							$v=mysql_insert_id();
							*/
						}
					}else{
						$v=$v;
					}
				}else{
					if( is_assoc_array($vars['options'][$k]) ){
						$v=key($vars['options'],$v);
					}else{
						$v=$v;
					}
				}
			}
			
			if( $vars['fields'][$_POST['section']][$k]=='mobile' ){
				$v=format_mobile($v);
			}
			
			if( $vars['fields'][$_POST['section']][$k]=='postcode' ){
				$v=format_postcode($v);
			}
			
			if( $vars['fields'][$_POST['section']][$k]=='select-multiple' or $vars['fields'][$_POST['section']][$k]=='checkboxes' ){
				continue;
			}
			
			$v=trim($v);
			
			if( $v ){
				$data[$field_name]=$v;
				$query.="`$field_name`='".escape($v)."',\n";
				$where.="`$field_name`='".escape($v)."' AND\n";
			}
		}
		
		if( !$where ){
			continue;
		}
		
		
		$query=substr($query,0,-2);
		$where=substr($where,0,-4);
		
		if( $_POST['filter_dupes'] ){
			$qry="SELECT * FROM `$table` WHERE
				$where
				LIMIT 1
			";
			
			$select=mysql_query($qry) or trigger_error("SQL ".$qry, E_USER_ERROR);
			
			if( mysql_num_rows($select) ){
				$result=mysql_fetch_assoc($select);
				$row['id']=$result['id'];
			}
		}
		
		//CMS SAVE
		$cms->set_section($_POST['section'],$row['id']);
		
		//debug(($data));
		//debug($cms->validate($data));
		
		if( count($cms->validate($data)) ){
			$invalid++;
			continue;
		}else{
			if( $row['id'] ){
				$updated++;
			}else{
				$imported++;
			}
			$id=$cms->save($data);
		}
		
		/*
		if( $row['id'] ){
			$result=mysql_query("SELECT * FROM `$table` 
				WHERE 
					id='".escape(trim($row['id']))."'
			") or trigger_error("SQL", E_USER_ERROR);
			
			if( mysql_num_rows($result) ){			
				mysql_query("UPDATE `$table` SET
					$query
					WHERE
						id='".escape(trim($row['id']))."'
					LIMIT 1
				") or trigger_error("SQL", E_USER_ERROR);
			}
			
			$id=$row['id'];
		}else{
			mysql_query("INSERT INTO `$table` SET
				$query
			");
	
			$id=mysql_insert_id();
		}
		*/
		
		foreach( $row as $k=>$v ){
		
			if( $vars['fields'][$_POST['section']][$k]=='select-multiple' or $vars['fields'][$_POST['section']][$k]=='checkboxes' ){
				if( !is_array($vars['options'][$k]) ){
					if( !is_numeric($v) ){
						$values=explode("\n", $v);
						
						foreach( $values as $value ){						
							reset($vars['fields'][$vars['options'][$k]]);
						
							$row=sql_query("SELECT id FROM `".escape($vars['options'][$k])."` 
								WHERE 
									`".underscored(key($vars['fields'][$vars['options'][$k]]))."`='".escape(trim($value))."'"
							);
							
							if( count($row) ){
								$v=$row[0]['id'];
							}else{
								mysql_query("INSERT INTO `".escape($vars['options'][$k])."`
									SET
										`".key($vars['fields'][$vars['options'][$k]])."`='".escape(trim($value))."'
								") or trigger_error("SQL", E_USER_ERROR);
								
								$v=mysql_insert_id();
							}
							
							if( $id ){
								mysql_query("INSERT INTO cms_multiple_select SET
									`section`='".escape($_POST['section'])."',
									`field`='".escape($k)."',
									`item`='".escape($id)."',
									`value`='".escape($v)."'
								") or trigger_error("SQL", E_USER_ERROR);
							}
						}
					}else{
						$v=$v;
					}
				}else{
				}
			}

		
		}
	}	
	
	$o['success']=true;
	
	$o['info']='Imported: '.$imported."\n".'Updated: '.$updated."\n".'Invalid: '.$invalid;
}

print json_encode($o);
?>