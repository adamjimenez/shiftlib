<?php
set_time_limit(0);
ini_set('memory_limit','256M');

require('../../base.php');

ini_set('auto_detect_line_endings', '1');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

function send_msg($id , $msg) {
    echo "id: $id" . PHP_EOL;
    echo "data: {\n";
    echo "data: \"msg\": \"$msg\", \n";
    echo "data: \"id\": $id\n";
	echo "data: }\n";
	echo PHP_EOL;
	ob_flush();
	flush();
}

$startedAt = time();

$_POST = $_GET;

$table = str_replace(' ','_',$_POST['section']);

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

	$csv_path = 'uploads/'.$_SERVER['HTTP_HOST'].'.csv';

	$handle = fopen($csv_path, "r");
    if( $handle=== false){
        die('error opening '.basename($csv_path) );
    }
	while (($data = fgetcsv($handle, 0, ",")) !== FALSE ) {
		if( $i!=0 ){
    	    $row = array();
    		foreach( $fields as $k=>$v ){
				$row[$k] = $data[$v];
			}

    	    $i++;

            $query='';
        	$where='';
        	$data=array();

            //check dropdowns
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

        						$data = array(
        							key($vars['fields'][$vars['options'][$k]])=>trim($v)
        						);

        						if( count($cms->validate($data)) ){
        							continue;
        						}else{
        							$v=$cms->save($data);
        						}

        						/*
        						sql_query("INSERT INTO `".escape($vars['options'][$k])."`
        							SET
        								`".key($vars['fields'][$vars['options'][$k]])."`='".escape(trim($v))."'
        						");

        						$v=sql_insert_id();
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
            	    $data[$field_name] = explode("\n", $v);

                    //trim data
                    foreach( $data[$field_name] as $key=>$val ){
                        $data[$field_name][$key] = trim($val);
                    }

        			continue;
        		}

        		$v = trim($v);

        		if( $v ){
        			$data[$field_name] = $v;
        			$query.="`$field_name`='".escape($v)."',\n";
        			$where.="`$field_name`='".escape($v)."' AND\n";
        		}
        	}

        	if( !$where ){
        		continue;
        	}

        	$query=substr($query,0,-2);
        	$where=substr($where,0,-4);

    		$qry="SELECT id FROM `$table` WHERE
    			$where
    			LIMIT 1
    		";

            $row = sql_query($qry, 1);

        	//CMS SAVE
        	$cms->set_section($_POST['section'], $row['id']);

        	if( $_POST["validate"] and count($cms->validate($data)) ){
        		$result=0;
        	}else{
        		if( $row['id'] ){
        			$result=2;
        		}else{
        			$result=1;
        		}

                if( !$row['id'] or $_POST["update"] ){
            	    $id = $cms->save($data);
                }
        	}

            send_msg($startedAt, $result);

		}

		$i++;
	}

	//delete file
	unlink($csv_path);
}else{
    print json_encode($errors);
}

?>