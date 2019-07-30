<?php
function send_email_preview(){
    global $auth, $cms;

    $content = $cms->get('email templates', $_GET['id']);

    email_template($auth->user['email'], $content['id'], $auth->user);

    $_SESSION['message'] = 'Preview sent';
}

class cms{
	function cms()
	{
	    //enable inline editing
	    //$this->inline = true;

		$this->opts['rating']=array(
			1=>'Very Poor',
			2=>'Poor',
			3=>'Average',
			4=>'Good',
			5=>'Excellent',
		);

		$this->file_fields=array(
			'date'=>'date',
			'name'=>'text',
			'size'=>'text',
			'type'=>'text',
			'data'=>'blob'
		);

		/* these aren;t used yet - see configure.php */
		$this->cms_multiple_select_fields = array(
			'section'=>'text',
			'field'=>'text',
			'item'=>'int',
			'value'=>'text',
		);

		$this->cms_privileges_fields = array(
			'user'=>'text',
			'section'=>'text',
			'access'=>'int',
			'filter'=>'text',
		);

		$this->cms_filters = array(
			'user'=>'text',
			'section'=>'text',
			'name'=>'text',
			'filter'=>'textarea',
		);

		//built in extensions

		global $cms_buttons, $auth, $vars;

		$cms_buttons[] = array(
			'section'=>'email templates',
			'page'=>'view',
			'label'=>'Send Preview',
			'handler'=>'send_email_preview'
		);

		if( !$vars['fields']['email templates'] ){
			$vars['fields']['email templates'] = array(
				'subject'=>'text',
				'body'=>'textarea',
				'id'=>'id',
			);
		}
	}

	function db_field_name($section, $field) //convert a field name into a database field name - needed mostly for composite fields
	{
		global $vars;

		$table = underscored($section);
		$type = $vars['fields'][$section][$field];

		if( is_array($type) ){
			$concat = 'CONCAT(';
			foreach( $type as $k2=>$v2 ){
				$concat.=underscored($k2).",' ',";
			}
			$concat = substr($concat,0,-5);
			$concat .= ')';

			return $concat;
		}else{
			return $field;
		}
	}
	
	function export_items($section, $items)
	{
		global $vars;
		
		foreach( $items as $item ){
			$vars['content'][] = $this->get($section,$item);
		}
	
		$i=0;
		foreach( $vars['content'] as $row ){
			if($i==0){
				$j=0;
				foreach($row as $k=>$v){
					$data.='"'.spaced($k).'",';
					$headings[$j]=$k;
				}
				$data = substr($data,0,-1);
				$data .= "\n";
	
				$j++;
			}
			$j=0;
			foreach($row as $k=>$v){
				//$v=str_replace("\n","\r\n",$v);
	
				$data.='"'.str_replace('"','',$v).'",';
	
				$j++;
			}
			$data=substr($data,0,-1);
			$data.="\n";
			$i++;
		}
	
		header('Pragma: cache');
		header('Content-Type: text/comma-separated-values; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$section.'.csv"');
	
		die($data);
	}

	function delete_items($section, $items) // used in admin system
	{
		global $auth, $vars;

		if( isset($items) and $section ){
			if( !is_array($items) ){
				$items = array($items);
			}

			if( $auth->user['admin'] == 1 or $auth->user['privileges'][$section] == 2 ){
				$response = $this->delete($section, $items);

                if($response === false){
				    $_SESSION['message'] = 'Permission denied.';
                }else{
    				if( count($items)>1 ){
    					$_SESSION['message'] = 'The items have been deleted';
    				}else{
    					$_SESSION['message'] = 'The item has been deleted';
    				}
                }
			}else{
				$_SESSION['message'] = 'Permission denied.';
			}
		}
	}

	function delete_all_pages($section, $conditions) // used in admin system
	{
		global $auth;

		if( $auth->user['admin']==1 or $auth->user['privileges'][$section]==2 ){
			$rows = $this->get($section,$conditions);

			$items = array();
			foreach( $rows as $v ){
				$items[] = $v['id'];
			}

			$this->delete($section,$items);

			if( count($rows)>1 ){
				$_SESSION['message'] = 'The items have been deleted';
			}else{
				$_SESSION['message'] = 'The item has been deleted';
			}
		}else{
			$_SESSION['message'] = 'Permission denied, you have read-only access.';
		}
	}

	function delete($section, $ids) // no security checks
	{
		global $vars;

		if( !is_array($ids) ){
			$ids = array($ids);
		}

		$field_id = in_array('id', $vars['fields'][$section]) ? array_search('id',$vars['fields'][$section]) : 'id';
		
		$soft_delete = in_array('deleted', spaced($vars['fields'][$section]));

		$response = $this->trigger_event('beforeDelete', array($ids));

		if ($response===false) {
		    return false;
		}
		
		foreach($ids as $id) {
			// cheeck perms
			$conditions[$field_id] = $id;
			$content = $this->get($section, $conditions);
			if (!$content) {
				continue;
			}
			
			if ($soft_delete) {
				sql_query("UPDATE `".escape(underscored($section))."` SET
					deleted = 1
					WHERE ".$field_id."='$id'
						LIMIT 1
				");
	
				if( in_array('language',$vars['fields'][$section]) ){
					sql_query("UPDATE `".escape(underscored($section))."` SET
						deleted = 1
						WHERE
							translated_from='$id'
					");
				}
			} else {
				sql_query("DELETE FROM `".escape(underscored($section))."`
					WHERE ".$field_id."='$id'
						LIMIT 1
				");
	
				if( in_array('language',$vars['fields'][$section]) ){
					sql_query("DELETE FROM `".escape(underscored($section))."`
						WHERE
							translated_from='$id'
					");
				}
	
				//multiple select items
				sql_query("DELETE FROM cms_multiple_select
				    WHERE
				        section = '".escape(underscored($section))."' AND
				        item = '$id'
				");
			}
		}

		$this->trigger_event('delete', array($ids));
	}

	function conditions_to_sql($section, $conditions=array(), $num_results=null, $cols=null){
	    global $vars, $auth;

	    //debug($conditions);

		if( $this->language ){
			$language = $this->language;
		} else {
			$language = 'en';
		}

		if( is_numeric($conditions) ){
			if( $language == 'en' ){
				$id = $conditions;
				//$conditions['id'] = $id;
			}else{
				$id = NULL;
				$conditions = array('translated_from'=>$conditions, 'language'=>$language);
			}
			$num_results = 1;
		} elseif ( is_string($conditions) ){
			if( in_array('page-name', $vars['fields'][$section]) ){
				$page_name = $conditions;

				$field_page_name = array_search('page-name', $vars['fields'][$section]);

				$conditions = array($field_page_name=>$page_name);

				$num_results = 1;
			}
		} else {
			if ( in_array('language',$vars['fields'][$section]) and $language ){
				$conditions['language'] = $language;
			} elseif ( !in_array('id', $vars['fields'][$section]) ){
				$id = 1;
			}
		}

		$table = underscored($section);
		$field_id = in_array('id',$vars['fields'][$section]) ? array_search('id',$vars['fields'][$section]) : 'id';

		if( is_numeric($id) ){
			//$where[] = "T_$table.".$field_id."='".escape($id)."'";
			//$conditions['id'] = $id;
			$conditions = array('id' => $id);
	    	//debug($conditions);
		}
		
		// staff perms
		foreach( $auth->user['filters'][$this->section] as $k=>$v ){
			$conditions[$k] = $v;
		}
	    //debug($conditions);
		
		if( in_array('deleted', $vars['fields'][$section]) and !isset($conditions['deleted']) and !$id and !$conditions['id'] ){
			$where[] = "T_$table.deleted = 0";
		}

		if( in_array('language', $vars['fields'][$section]) and $language=='en' ){
			$where[] = "`translated_from`='0'";
		}

		if( is_array($conditions) ){
			foreach( $vars['fields'][$section] as $name=>$type ){
				$field_name = underscored($name);

				if(
					( isset($conditions[$name]) ) or
					( isset($conditions[$field_name]) )
				){
					$value = $conditions[$name] ? $conditions[$name] : $conditions[$field_name];

					switch( $type ){
						case 'select':
						case 'combo':
						case 'radio':
							if( $conditions['func'][$field_name]=='!=' ){
								$operator = '!=';
							}else{
								$operator = '=';
							}
							
							if (is_array($value)) {
								$or = '(';
								foreach( $conditions[$field_name] as $k=>$v ){
									$or .= "T_$table.".$field_name." ".$operator." '".escape($v)."' OR ";
								}
								$or = substr($or, 0, -4);
								$or.=')';
	
								$where[] = $or;
							} else {
								$where[] = "T_$table.".$field_name." ".$operator." '".escape($value)."'";
							}
						break;
						case 'select-multiple':
						case 'checkboxes':
							if( count($conditions[$field_name])==1 and !reset($conditions[$field_name]) ) {
								$conditions[$field_name] = array($conditions[$field_name]);
							}

							$joins .= " LEFT JOIN cms_multiple_select T_".$field_name." ON T_".$field_name.".item=T_".$table.".".$field_id;

							$or = '(';

							foreach( $conditions[$field_name] as $k=>$v ){
							    $v = is_array($v) ? $v['value'] : $v;
								$or .= "T_".$field_name.".value = '".escape($v)."' AND T_".$field_name.".field = '".escape($name)."' OR ";
							}
							$or = substr($or,0,-4);
							$or .= ')';

							$where[] = $or;
							$where[] = "T_".$field_name.".section='".$section."'";
						break;
						case 'date':
						case 'datetime':
						case 'timestamp':
						case 'month':
						    if(!$conditions['func'][$field_name]){
						        $conditions['func'][$field_name] = '=';
						    }

							if( $conditions['func'][$field_name] == 'month' ){
								$where[] = "date_format(".$field_name.", '%m%Y') = '".escape($value)."'";
							} else if($conditions[$field_name] and $conditions['end'][$field_name]) {
								$start = $conditions[$field_name];
								$end = $conditions['end'][$field_name];
								
								$where[] = "(T_".$table.".$field_name >= '".$start."' AND T_".$table.".$field_name <= '".$end."')";
							}elseif( $conditions['func'][$field_name] ){
								$where[] = "DATE_FORMAT(T_$table.".$field_name.", '%Y-%m-%d') ".escape($conditions['func'][$field_name])." '".escape(dateformat('Y-m-d', $value))."'";
							}
						break;
						case 'time':
						break;
						case 'dob':
							if( is_numeric($value) or is_numeric($conditions['func'][$field_name]) ){
								$where[] = "`".$field_name."`!='0000-00-00'";
							}
							if( is_numeric($value) ){
								$where[] = "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(".$field_name.", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(".$field_name.", '00-%m-%d'))<= ".escape($conditions['func'][$field_name])." ";
							}
							if( is_numeric($conditions['func'][$field_name]) ){
								$where[] = "DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(".$field_name.", '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(".$field_name.", '00-%m-%d'))>= ".escape($value)." ";
							}
						break;
						case 'coords':
							if( format_postcode($value) and is_numeric($conditions['func'][$field_name]) ){
								$grids = calc_grids(format_postcode($value), true);
								
								// fixme: ST_Distance_Sphere not supported in mariadb yet!
								
								if ($grids) {
									$cols .= ",
									ST_Distance(POINT(".$grids[0].", ".$grids[1]."), coords) AS distance";
									
									
	
									$where[] = "ST_Distance(POINT(".$grids[0].", ".$grids[1]."), coords) <= ".escape($conditions['func'][$field_name])."";
	
									$vars['labels'][$section][] = 'distance';
								}
							}
						break;
						case 'postcode':
							if( calc_grids($value) and is_numeric($conditions['func'][$field_name]) ){
								$grids = calc_grids($value);

								if ($grids) {
									$cols .= ",
									(
										SELECT
											ROUND(SQRT(POW(Grid_N-".$grids[0].",2)+POW(Grid_E-".$grids[1].",2)) * 0.000621371192)
											AS distance
										FROM postcodes
										WHERE
											Pcode=
											REPLACE(SUBSTRING(SUBSTRING_INDEX(T_$table.$field_name, ' ', 1), LENGTH(SUBSTRING_INDEX(T_$table.$field_name, ' ', 0)) + 1), ',', '')
	
									) AS distance";
	
	
									$having[] = "distance <= ".escape($conditions['func'][$field_name])."";
	
									$vars['labels'][$section][] = 'distance';
								}
							}
						break;
						case 'int':
						case 'decimal':
    					case 'position':
    						$pos = strrpos($value, '-');

							if( $conditions['func'][$field_name] ){
								$where[] = "T_$table.".$field_name." ".escape($conditions['func'][$field_name])." '".escape($value)."'";
							}elseif( $pos>0 ){
							    $min = substr($value, 0, $pos);
							    $max = substr($value, $pos+1);

								$where[] = "(
								    T_$table.".$field_name." >= '".escape($min)."' AND
								    T_$table.".$field_name." <= '".escape($max)."'
								)";
							} else {
								$where[] = "T_$table.".$field_name." = '".escape($value)."'";
							}
						break;
						case 'file':
							$where[] = "T_$table.".$field_name." > 0";
						break;
						default:
							if( $conditions['func'][$field_name]=='!=' ){
								$operator = '!=';
							}else{
								$operator = 'LIKE';
							}

							$value = str_replace('*','%',$value);
							$where[] = "T_$table.".$field_name." ".$operator." '".escape($value)."'";
						break;
					}
				}
			}

			if( $conditions['s'] or $conditions['w'] ){
				if ($conditions['w']) {
					$conditions['s'] = $conditions['w'];
				}
				
				$words = explode(' ', $conditions['s']);

				foreach( $words as $word ){
					$or = array();

					foreach( $vars['fields'][$section] as $name=>$type ){
						if(
                            (
                                $type == 'text' or
                                $type == 'textarea' or
                                $type == 'editor' or
                                $type == 'email' or
                                $type == 'mobile' or
                                $type == 'select' or
                                $type == 'id'
                            ) &&
                            !in_array($name, $vars["non_searchable"][$section])
                        ){
							$value = str_replace('*','%',$word);

							if($type == 'select'){
								if( is_string($vars['options'][$name]) ){
									$option = '';
									foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
										if( $v != 'separator' ){
											$option = $k;
											break;
										}
									}

									$or[] = "T_".underscored($name).".".underscored($option)." LIKE '%".escape($value)."%'";
								}
							} else {
								if ($conditions['w']) {
									$or[] = "T_$table.".underscored($name)." REGEXP '[[:<:]]".escape($value)."[[:>:]]'";
								} else {
									$or[] = "T_$table.".underscored($name)." LIKE '%".escape($value)."%'";
								}
							}
						}
					}

					if( count($or) ){
						$or_str = '';
						foreach($or as $w){
							$or_str .= $w." OR ";
						}
						$or_str = substr($or_str,0,-3);

						$where[] = '('.$or_str.')';
					}
				}
			}
		}

		//additional custom conditions
		foreach($conditions as $k=>$v){
			if(is_int($k)){
				$where[] = $v;
			}
		}

		if( count($where) ){
			foreach($where as $w){
				$where_str .= "\t".$w." AND"."\n";
			}
			$where_str = "WHERE \n".substr($where_str,0,-5);
		}

		if( count($having) ){
			foreach($having as $w){
				$having_str .= "\t".$w." AND"."\n";
			}
			$having_str = "HAVING \n".substr($having_str,0,-5);
		}

		if( in_array('select', $vars['fields'][$section]) or in_array('combo',$vars['fields'][$section]) or in_array('radio',$vars['fields'][$section]) ){
			$selects = array_keys($vars['fields'][$section], "select");
			$radios = array_keys($vars['fields'][$section], "radio");
			$combos = array_keys($vars['fields'][$section], "combo");

			$keys = array_merge($selects, $radios, $combos);

			foreach( $keys as $key ){
				if( !is_array($vars['options'][$key]) ){
					$option_table = underscored($vars['options'][$key]);

					$join_id = array_search('id', $vars['fields'][$vars['options'][$key]]);

					$joins .= "
						LEFT JOIN $option_table T_".underscored($key)." ON T_".underscored($key).".$join_id=T_$table.".underscored($key)."
					";

					//$option=key($vars['fields'][$vars['options'][$key]]);

					foreach( $vars['fields'][$vars['options'][$key]] as $k=>$v ){
						if( $v != 'separator' ){
							$option = $k;
							break;
						}
					}

					$type = $vars['fields'][$vars['options'][$key]][$option];

					if( is_array($type) ){
						$db_field_name=$this->db_field_name($vars['options'][$key],$option);

						$cols .= ",".$db_field_name." AS `".underscored($key)."_label`"."\n";
					}else{
						$cols .= ",T_".underscored($key).".".underscored($option)." AS '".underscored($key)."_label'";
					}
				}
			}
		}
		
		//debug($where_str);

		return array(
		    'where_str' => $where_str,
		    'having_str' => $having_str,
		    'joins' => $joins,
		    'num_results' => $num_results,
		    'cols' => $cols,
		);
	}

	function get($sections, $conditions=NULL, $num_results=NULL, $order=NULL, $asc=true, $prefix=NULL, $return_query=false)
	{
		global $vars, $auth;

		if( is_array($sections) ){
			die('array of sections not yet supported');
		}else{
			$section = $sections;
			$table = underscored($sections);

            //set a default prefix to prevent pagination clashing
            if( !$prefix ){
                $prefix = $table;
            }

			//sortable
			$sortable = in_array('position', $vars['fields'][$section]);

			//parent
			if(
				in_array('parent',$vars['fields'][$section]) or
				$vars['settings'][$section]['has_children']==true
			){
				$parent_field = array_search('parent',$vars['fields'][$section]);
			}

			if( !count($vars['labels'][$section]) ){
				reset($vars['fields'][$section]);
				$vars['labels'][$section][] = key($vars['fields'][$section]);
			}

			$cols = '';
			foreach( $vars['fields'][$section] as $k=>$v ){
				if( $v!='select-multiple' and $v!='checkboxes' and $v!='separator' ){
					//$cols.="\t"."T_$table.".underscored($k)." AS `".$k."`,"."\n";

                    if($v=='coords'){
                        $cols.="\tAsText(T_$table.".underscored($k).") AS ".underscored($k).','."\n";
					}elseif($v=='sql'){
						$cols .= "\t".$k.","."\n";
					}elseif( is_array($v) ){
						$db_field_name = $this->db_field_name($this->section,$k);

						$cols .= "\t".$db_field_name." AS `".underscored($k)."`,"."\n";
					}else{
						$cols .= "\t"."T_$table.`".underscored($k)."` AS `".underscored($k)."`,"."\n";
					}
				}
			}

			$field_id = in_array('id',$vars['fields'][$section]) ? array_search('id',$vars['fields'][$section]) : 'id';
			$cols .= "\tT_$table.$field_id";

			if( !$order ){
				if( $sortable ){
					$order = 'T_'.$table.'.position';
				}else if( ($field_date = array_search('date', $vars['fields'][$section]))!==false and in_array($field_date, $vars['labels'][$section]) ){
					$order = 'T_'.$table.'.'.underscored($field_date);
					$asc = false;
				}else if( ($field_date = array_search('timestamp', $vars['fields'][$section]))!==false and in_array($field_date, $vars['labels'][$section]) ){
					$order = 'T_'.$table.'.'.underscored($field_date);
					$asc = false;
				}else{
					$label = $vars['labels'][$section][0];

					$type = $vars['fields'][$this->section][$label];

					if( ($type=='select' or $type=='combo' or $type=='radio') and !is_array($vars['opts'][$label]) ){
						foreach( $vars['fields'][$vars['options'][$label]] as $k=>$v ){
							if( $v!='separator' ){
								if( is_array($v) ){
									$order = underscored($label);
								}else{
									$order = 'T_'.underscored($label).'.'.underscored($k);
								}

								break;
							}
						}
					}elseif( is_array($type) ){
						$order = underscored($vars['labels'][$this->section][0]);
					}elseif($vars['labels'][$section][0]){
						$order = "T_$table.".underscored($vars['labels'][$section][0]);
					}else{
						$order = "T_$table.id";
					}
				}
			}

			/*
			if( $parent_field ){
				$parent = is_numeric($conditions['parent']) ? $conditions['parent'] : 0;
				//$where[]="`".$parent_field."`='".$parent."'";
			}*/

            $sql = $this->conditions_to_sql($sections, $conditions, $num_results, $cols);

            $where_str = $sql['where_str'];
            $group_by_str = '';
            $having_str = $sql['having_str'];
            $joins = $sql['joins'];
            $cols = $sql['cols'];
            
            if($num_results===true) {
            	$cols = 'COUNT(*) AS `count`';
            } else {
            	$group_by_str = "GROUP BY T_$table.$field_id";
            }

			$query = "SELECT
			$cols
			FROM `$table` T_$table
				$joins
			$where_str
			$group_by_str
			$having_str
			";
			
			if ($_GET['debug'] and $auth->user['admin']==1) {
				debug($query);
			}

			if( $return_query===true ){
				return $query;
			}

			$limit = $sql['num_results'] ?: NULL;

            //require_once('_lib/paging.class.php');
			$this->p = new paging($query, $limit, $order, $asc, $prefix);

			$content = $this->p->rows;
			//debug($content);
			
			if( $num_results===true ){
				return $content[0]['count'];
			}

			//spaced versions of field names for compatibility
		    foreach( $content as $k=>$v ){
		        foreach( $v as $k2=>$v2 ){
		            $content[$k][spaced($k2)] = $v2;
		        }
		    }

            //nested arrays for checkbox info //disabled for now
			foreach( $vars['fields'][$section] as $field=>$type ){
				$label = '';

				if( $type=='checkboxes' ){
					foreach($content as $k=>$v){
						if( !is_array($vars['options'][$field]) and $vars['options'][$field] ){
							$join_id = array_search('id',$vars['fields'][$vars['options'][$field]]);

                            reset($vars['fields'][$vars['options'][$field]]);
                            $key = key($vars['fields'][$vars['options'][$field]]);

							$rows = sql_query("SELECT `".underscored($key)."`,T1.value FROM cms_multiple_select T1
								INNER JOIN `".escape(underscored($vars['options'][$field]))."` T2 ON T1.value = T2.$join_id
								WHERE
									T1.section='".escape($section)."' AND
									T1.field='".escape($field)."' AND
									T1.item='".escape($v['id'])."'
								GROUP BY T1.value
								ORDER BY T2.".underscored(key($vars['fields'][$vars['options'][$field]]))."
							");

							$items = array();
							foreach($rows as $row){
								$items[] = $row[$key];
							}
						}else{
							$rows = sql_query("SELECT value FROM cms_multiple_select
								WHERE
									section='".escape($section)."' AND
									field='".escape($field)."' AND
									item='".$v['id']."'
								ORDER BY id
							");

							$items = array();
							foreach($rows as $row){
								$items[] = $row['value'];
							}
						}

						$items = array_unique($items);

						$label = '';
						foreach($items as $item){
							$label .= $item.', ';
						}

						$content[$k][underscored($field)] = $rows;
						$content[$k][underscored($field).'_label'] = substr($label, 0, -2);
					}
				}
			}

			//inline editing
			if( $auth->user['admin'] and $this->inline ){
			    foreach( $content as $k=>$row ){
			        foreach( $vars['fields'][$section] as $name=>$type ){
			            $field_name=underscored($name);

			            if( in_array($name, array('heading')) ){
			                $content[$k][$field_name] = '<span data-section="'.$section.'" data-id="'.$row['id'].'" data-field="'.$name.'" class="cms_'.$type.'">'.$row[$field_name].'</span>';
			            }elseif( in_array($name, array('copy')) ){
			                $content[$k][$field_name] = '<div data-section="'.$section.'" data-id="'.$row['id'].'" data-field="'.$name.'" class="cms_'.$type.'">'.$row[$field_name].'</div>';
			            }
			        }
			    }
			}

			if( !in_array('id', $vars['fields'][$section]) or $id or $sql['num_results']==1 ){
				return $content[0];
			}else{
				return $content;
			}
		}
	}

	function set_section($section, $id=null, $editable_fields=null)
	{
		global $vars, $languages, $auth;

		if( !$vars['fields'][$section] ){
			throw new Exception("Section does not exist: ".$section, E_USER_ERROR);
		}

		$this->section = $section;
		$this->table = underscored($section);

		if( is_array($editable_fields) ){
	        foreach( $editable_fields as $k=>$v ){
	            $this->editable_fields[$k] = spaced($v);
	        }
		} else {
	        foreach( $vars['fields'][$this->section] as $k=>$v ){
	            $this->editable_fields[] = $k;
	        }
		}
		
		// don't allow staff to edit admin perms
		if ($auth->user['admin']!=1 and in_array('admin', $this->editable_fields)) {
			unset($this->editable_fields[array_search('admin', $this->editable_fields)]);
		}

		$this->field_id = array_search('id', $vars['fields'][$this->section]);

		if(!$this->field_id){
			$this->field_id = 'id';
			$id = 1;
		}

		if( $id ){
			$this->id = $id;

			$fields = '';
			foreach( $vars['fields'][$section] as $k=>$v ){
				if( $v=='separator' or $v=='checkboxes' ){
					continue;
				}

				if( $v=='coords' ){
					$fields.="AsText(".underscored($k).") AS ".underscored($k).','."\n";
					continue;
				}

				$fields.='`'.underscored($k).'`,'."\n";
			}
			$fields = substr($fields,0,-2);

			$row = sql_query("SELECT $fields FROM `".$this->table."`
				WHERE
					".$this->field_id."='".escape($this->id)."'
			", 1);

			$this->content = $this->get($section, array('id'=>$this->id), 1);

			if(!$this->content){
				$this->id = null;
			}

			if( in_array('language',$vars['fields'][$this->section]) ){
				foreach( $languages as $language ){
					if( $language!=='en' ){
						$this->content[$language]=sql_query("SELECT * FROM `".$this->table."`
							WHERE
								`translated_from`='".escape($this->id)."' AND
								`language`='".escape($language)."'
						",true);
					}
				}
			}
		}elseif( !in_array('id',$vars['fields'][$this->section]) ){
			$row = sql_query("SELECT * FROM `".$this->table."` ORDER BY ".$this->field_id." LIMIT 1", 1);

			if( $row ){
				$this->content = $row;
				$this->id = $this->content[$this->field_id];
			}

			if( in_array('language',$vars['fields'][$this->section]) ){
				foreach( $languages as $language ){
					if( $language!=='en' ){
						$this->content[$language]=sql_query("SELECT * FROM `".$this->table."`
							WHERE
								`translated_from`='".escape($this->id)."' AND
								`language`='".escape($language)."'
						",true);
					}
				}
			}
		}else{
			$this->content=$_GET;
			$this->id=NULL;
		}
	}

	function set_language( $language )
	{
		$this->language = $language;
	}

	function get_label()
	{
		global $vars;

		/*
		if( count($vars['labels'][$this->section]) ){
			$field=reset($vars['labels'][$this->section]);
		}else{
			$field=underscored(key($vars['fields'][$this->section]));
		}*/
		$field = underscored(key($vars['fields'][$this->section]));

		$field_type = $vars['fields'][$this->section][$field];

		$value = $this->content[$field];

		switch($field_type){
			case 'select':
			case 'combo':
				if( !is_array($vars['options'][$field]) ){
					if( $value==0 ){
						$value = '';
					}else{
						$join_id = array_search('id',$vars['fields'][$vars['options'][$field]]);

						$row = sql_query("SELECT `".underscored(key($vars['fields'][$vars['options'][$field]]))."` FROM `".escape(underscored($vars['options'][$field]))."` WHERE $join_id='".escape($value)."'");
						$value = '<a href="?option='.escape($vars['options'][$field]).'&view=true&id='.$value.'">'.reset($row[0]).'</a>';
					}
				}else{
					if( is_assoc_array($vars['options'][$field]) ){
						$value = $vars['options'][$field][$value];
					}
				}


			break;
		}

		return truncate(strip_tags($value));
	}

	function get_children($section, $parent_field, $parent=0, $depth=0)
	{
		global $vars;

		reset($vars['fields'][$section]);
		$label = key($vars['fields'][$section]);

		$rows = sql_query("SELECT id,`$label` FROM `".underscored($section)."`
			WHERE
				`".underscored($parent_field)."` = '$parent'
			ORDER BY `$label`
		");

		$indent = '';
		for( $i=0; $i<$depth; $i++ ){
			$indent .= '-';
		}

		$parents = array();
		foreach($rows as $row){
			if( $row['id'] === $this->id and $section === $this->section ){
				continue;
			}

			$parents[$row['id']] = $indent.' '.$row[$label];

			$children = $this->get_children($section, $parent_field, $row['id'], $depth+1);

			if( count($children) ){
				$parents = $parents + $children;
			}
		}

		return $parents;
	}

	function get_field($name, $attribs='', $placeholder = '', $separator=null, $where = false)
	{
		global $vars, $id, $strs, $cms_config;

		if( $vars['fields'][$this->section][spaced($name)] ){
            $name = spaced($name);
		}

		if( $vars['fields'][$this->section][$name] ){
			$type = $vars['fields'][$this->section][$name];
		}else{
			foreach( $vars['fields'][$this->section] as $k=>$v ){
				if( $vars['fields'][$this->section][$k][$name] ){
					$type = $vars['fields'][$this->section][$k][$name];
					break;
				}
			}
		}

		$field_name = underscored($name);

		$value = $this->content[$field_name];

		if( $this->language!='en' and $this->language!='' and in_array('language',$vars['fields'][$this->section]) ){
			$value = $this->content[$this->language][$field_name];

			$field_name = $this->language.'_'.$field_name;
		}

		$readonly = false;

		if( is_array($this->editable_fields) and !in_array($name,$this->editable_fields) ){
			$readonly = true;
		}

		switch($type){
			case 'text':
			case 'float':
			case 'decimal':
			case 'page-name':
			case 'tel':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'hidden':
		?>
			<input type="hidden" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'int':
		?>
			<input type="number" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'coords':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value,6,-1));?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="50" <?=$attribs;?> placeholder="coordinates">
		<?php
			break;
			case 'email':
		?>
			<input type="email" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'url':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'postcode':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> size="10" <?=$attribs;?>>
		<?php
			break;
			case 'mobile':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="14" <?=$attribs;?>>
		<?php
			break;
			case 'password':
			    global $auth;

			    if($auth->hash_password){
			        $value = '';
			    }
		?>
			<input type="password" name="<?=$field_name;?>" value="<?=$value;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?>>
		<?php
			break;
			case 'textarea':
		?>
			<textarea name="<?=$field_name;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<?php } ?> <?=$attribs ? $attribs : 'class="autogrow"';?>><?=$value;?></textarea>
		<?php
			break;
			case 'editor':
				//$value = mb_detect_encoding($value, "UTF-8") == "UTF-8" ? $value : utf8_encode($value);
		?>
			<textarea id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs ? $attribs : 'rows="25" style="width:100%; height: 400px;"';?> class="<?=$cms_config["editor"] ? $cms_config["editor"] : 'tinymce';?>"><?=htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');?></textarea>
		<?php
			break;
			case 'file':
				$file=sql_query("SELECT * FROM files WHERE id='".escape($value)."'",1);
		?>
			<?php if( $value ){ ?>
				<input type="hidden" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=$value;?>">
				<?php /*<a href="/_lib/cms/file.php?f=<?=$value;?>"><?=$file['name'];?></a> <?=file_size($file['size']);?> */?>
				<?=$file['name'];?>
				<a href="javascript:;" onClick="clearFile('<?=$field_name;?>')">clear</a>
			<?php }else{ ?>
				<input type="file" id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?> />
			<?php } ?>
		<?php
			break;
			case 'phpupload':
		?>
            <input type="text" name="<?=$field_name;?>" class="upload" value="<?=$value;?>">
		<?php
			break;
			case 'select':
			case 'combo':
			case 'select-distance':
			case 'radio':
				if($type=='radio') {
					$vars['options'][$name] = $this->get_options($name, $where);
					
					$assoc = is_assoc_array($vars['options'][$name]);
					foreach( $vars['options'][$name] as $k=>$v ) { 
						$val = $assoc ? $k : $v; 
					?>
					<label <?=$attribs;?>><input type="radio" name="<?=$field_name;?>" value="<?=$val;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( isset($value) and $val==$value ){ ?>checked="checked"<?php } ?> <?=$attribs;?>> <?=$v;?> &nbsp;</label><?=$separator;?>
					<?php 
					}
				} elseif($type=='combo') {
			?>
				<input type="text" name="<?=$field_name;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?> value="<?=$value;?>" autocomplete="off" class="combo">
			<?php
				} else {
					if (!is_array($vars['options'][$name]) and in_array('parent', $vars['fields'][$vars['options'][$name]])) {
					?>
					<div class="chained" data-name="<?=$field_name;?>" data-section="<?=$vars['options'][$name];?>" data-value="<?=$value;?>"></div>
					<?php
					} else {
						if (!is_array($vars['options'][$name])) {
							global $auth;
							
							$conditions = array();
							
							foreach( $auth->user['filters'][$this->section] as $k=>$v ){
								$conditions[$k]=$v;
							}
							
							$order = key($vars['fields'][$vars['options'][$name]]);
							$rows = $this->get($vars['options'][$name], $conditions, null, $order);
							
							$vars['options'][$name] = array();
							foreach($rows as $v) {
								$vars['options'][$name][$v['id']] = current($v);
							}
						}
					?>
					<select name="<?=$field_name;?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?>>
					<option value=""><?=$placeholder ?: 'Choose';?></option>
						<?=html_options($vars['options'][$name], $value);?>
					</select>
					<?php
					}
				}
			break;
			case 'select-multiple':
			case 'checkboxes':
				$value = array();

				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					if( $this->id ){
						$join_id = array_search('id', $vars['fields'][$vars['options'][$name]]);

						$rows = sql_query("SELECT T1.value FROM cms_multiple_select T1
							INNER JOIN `".escape(underscored($vars['options'][$name]))."` T2 ON T1.value=T2.$join_id
							WHERE
								section='".escape($this->section)."' AND
								field='".escape($name)."' AND
								item='".$this->id."'
						");

						foreach( $rows as $row ){
							$value[] = $row['value'];
						}
					}

					if( in_array('language',$vars['fields'][$vars['options'][$name]]) ){
						$language = $this->language ? $this->language : 'en';
						$table = underscored($vars['options'][$name]);

						foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
							if( $v!='separator' ){
								$field=$k;
								break;
							}
						}

						$raw_option = $vars['fields'][$vars['options'][$name]][$field];

						$cols = '';
						if( is_array($raw_option) ){
							$db_field_name = $this->db_field_name($vars['options'][$name],$field);

							$cols.="".underscored($db_field_name)." AS `".underscored($field)."`"."\n";
						}else{
							$cols.='`'.underscored($field).'`';
						}

						$rows = sql_query("SELECT id,$cols FROM
							$table
							WHERE
								language='".$language."'
							ORDER BY `".underscored($field)."`
						");

						$options = array();
						foreach($rows as $row){
							if( $row['translated_from'] ){
								$id = $row['translated_from'];
							}else{
								$id = $row['id'];
							}

							$options[$id] = $row[underscored($field)];
						}

						$vars['options'][$name] = $options;
					}else{
					    //make sure we get the first field
					    reset($vars['fields'][$vars['options'][$name]]);

						$vars['options'][$name] = $this->get_options($name, $where);
					}

				}else{
					if( $this->id ){
						$rows = sql_query("SELECT value FROM cms_multiple_select
							WHERE
								section='".escape($this->section)."' AND
								field='".escape($name)."' AND
								item='".$this->id."'
						");

						foreach( $rows as $row ){
							$value[] = $row['value'];
						}
					}
				}

				if( $type == 'select-multiple' ){
		?>
			<select name="<?=$field_name;?>[]" multiple="multiple" <?php if( $readonly ){ ?>disabled<?php } ?> size="10" style="width:100%">
				<?=html_options($vars['options'][$name], $value);?>
			</select>
		<?php
			}else{
		?>
				<?php /*
                Select <a href="javascript:;" onclick="selectAll('<?=$field_name;?>');">All</a>, <a href="javascript:;" onclick="selectNone('<?=$field_name;?>');">None</a><br />
                */

                $is_assoc = is_assoc_array($vars['options'][$name]);

				print '<ul class="checkboxes">';
				
                foreach( $vars['options'][$name] as  $k=>$v ){
                    $val = $is_assoc ? $k : $v;
                ?>
    			    <li><label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$val;?>" <?php if( $readonly ){ ?>readonly<?php } ?> <?php if( in_array($val, $value) ){ ?>checked="checked"<?php } ?> /> <?=$v;?></label></li>
				<?php
                }
                
                print '</ul>';
            }

			break;
			case 'parent':
				$parent_field = array_search('parent',$vars['fields'][$this->section]);

				reset($vars['fields'][$this->section]);

				$label=key($vars['fields'][$this->section]);

				$rows = sql_query("SELECT id,`$label` FROM `".$this->table."` ORDER BY `$label`");

				$parents = array();
				foreach($rows as $row){
					if( $row['id']==$this->id ){
						continue;
					}
					$parents[$row['id']]=$row[$label];
				}
		?>
				<select name="<?=$field_name;?>" <?php if( $readonly ){ ?>readonly<?php } ?>>
				<option value=""></option>
				<?=html_options($parents, $value);?>
				</select>
		<?php
			break;
			case 'checkbox':
		?>
			<input type="checkbox" name="<?=$field_name;?>" value="1" <?php if( $readonly ){ ?>disabled<?php } ?> <?php if( $value ){ ?>checked<?php } ?>  <?=$attribs;?> />
		<?php
			break;
			case 'files':

				if( $value ){
					$value = explode("\n",$value);
				}
		?>

			<ul class="files">
				<?php
				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$file=sql_query("SELECT * FROM files WHERE id='".escape($val)."'");

				?>
				<li>
				<?php if( $file ){ ?>
					<input type="hidden" name="<?=$field_name;?>[]" value="<?=$val;?>" <?php if( $readonly ){ ?>readonly<?php } ?>>
					<a href="/_lib/cms/file.php?f=<?=$val;?>">
						<img src="/_lib/cms/file.php?f=<?=$val;?>" style="max-width: 100px; max-height: 100px;"><br>
						<?=$file[0]['name'];?>
					</a>
					<a href="javascript:;" class="link" onClick="delItem(this)">Delete</a>
				<?php } ?>
				</li>
				<?php
					}
				}
				?>

				<li>
					<input type="file" name="<?=$field_name;?>[]" multiple="multiple" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs;?> />
				</li>
			</ul>
		<?php
			break;
			case 'phpuploads':
		?>
            <textarea name="<?=$field_name;?>" class="upload"><?=$value;?></textarea>
		<?php
			break;
			case 'color':
		?>
			<input type="color"  name="<?=$field_name;?>" value="<?=$value;?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="6" <?=$attribs;?> />
		<?php
			break;
			case 'date':
		?>
			<input type="text" data-type="date" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && $value!='0000-00-00') ? $value : '';?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> autocomplete="off" />
		<?php
			break;
			case 'month':
		?>
			<input type="text" class="month" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && $value!='0000-00-00') ? $value : '';?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> />
		<?php
			break;
			case 'dob':
		?>
			<input type="text" data-type="dob" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && $value!='0000-00-00') ? $value : '';?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> />
		<?php
			break;
			case 'time':
		?>
			<input type="time" step="1" data-type="time" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value!='00:00:00') ? substr($value,0,-3) : '';?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs ?: '';?> />
		<?php
			break;
			case 'datetime':

			if( $value ){
				$date = explode(' ', $value);
			}
		?>
			<input type="date" name="<?=$field_name;?>" value="<?=($date[0] and $date[0]!='0000-00-00') ? $date[0] : '';?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="10" <?=$attribs ? $attribs : '';?> />
			<input type="time" step="60" name="time[<?=$field_name;?>]" value="<?=($date[1] and $date[1]!='00:00:00') ? substr($date[1], 0, 5) : '00:00:00';?>" <?php if( $readonly ){ ?>disabled<?php } ?> <?=$attribs ? $attribs : '';?> />
		<?php
			break;
			case 'rating':
			case 'avg-rating':
		?>
			<select name="<?=$field_name;?>" class="rating" data-section="<?=$this->section;?>" data-item="<?=$this->content['id'];?>" <?php if($type=='avg-rating'){?>data-avg='data-avg'<?php } ?> <?=$attribs;?>>
			    <option value="">Choose</option>
				<?=html_options($this->opts['rating'], $value, true);?>
			</select>
		<?php
			break;
		}
	}

	function get_value($name, $return=true)
	{
		global $vars;

		$type = $vars['fields'][$this->section][$name];

		$field_name = underscored($name);

		$value = $this->content[$field_name];

		$id = $this->id;

		if( $type=='select-multiple' or $type=='checkboxes' ){
			$array = array();

			if( !is_array($vars['options'][$name]) and $vars['options'][$name] ){
				$join_id = array_search('id', $vars['fields'][$vars['options'][$name]]);

                //make sure we get the label from the first array item
                reset($vars['fields'][$vars['options'][$name]]);

				$rows = sql_query("SELECT `".underscored(key($vars['fields'][$vars['options'][$name]]))."`,T1.value FROM cms_multiple_select T1
					INNER JOIN `".escape(underscored($vars['options'][$name]))."` T2 ON T1.value = T2.$join_id
					WHERE
						T1.field='".escape($name)."' AND
						T1.item='".$id."' AND
						T1.section='".$this->section."'
					GROUP BY T1.value
					ORDER BY T2.".underscored(key($vars['fields'][$vars['options'][$name]]))."
				");

				foreach( $rows as $row ){
					$array[] = '<a href="?option='.escape($vars['options'][$name]).'&view=true&id='.$row['value'].'">'.current($row).'</a>';
				}
			}else{
				$rows = sql_query("SELECT value FROM cms_multiple_select
					WHERE
						field='".escape($name)."' AND
						item='".$id."'
					ORDER BY id
				");

				foreach( $rows as $row ){
					if( is_assoc_array($vars['options'][$name]) ){
						$array[] = $vars['options'][$name][$row['value']];
					}else{
						$array[] = current($row);
					}
				}
			}

			$value=implode('<br>'."\n", $array);
		}

		if( $type == 'url' ){
			$value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
		}elseif( $type == 'email' ){
			$value = '<a href="mailto:'.$value.'" target="_blank">'.$value.'</a>';
		}elseif( $type == 'file' ){
			if( $value ){
				$file = sql_query("SELECT * FROM files WHERE id='".escape($value)."'");

				$image_types = array('jpg','jpeg','gif','png');
				if( in_array(file_ext($file[0]['name']),$image_types) ){
					$value = '<img src="http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file_preview.php?f='.$file[0]['id'].'&w=320&h=240" id="'.$name.'_thumb" /><br />';
				}
				$value .= '<a href="http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file.php?f='.$file[0]['id'].'">'.$file[0]['name'].'</a> <span style="font-size:9px;">'.file_size($file[0]['size']).'</span>';

				$doc_types = array('pdf','doc','docx','xls','tiff');
				if( in_array(file_ext($file[0]['name']),$doc_types) ){
					$value .= '<a href="http://docs.google.com/viewer?url='.rawurlencode('http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file.php?f='.$file[0]['id'].'&auth_user='.$_SESSION[$auth->cookie_prefix.'_email'].'&auth_pw='.md5($auth->secret_phrase.$_SESSION[$auth->cookie_prefix.'_password'])).'" target="_blank">(view)</a>';
				}
			}
		}elseif( $type == 'phpupload' ){ ?>
			<img src="/_lib/modules/phpupload/?func=preview&file=<?=$value;?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
			<label id="<?=$name;?>_label"><?=$value;?></label>
		<?php
		}elseif( $type == 'select' or $type == 'combo' or $type == 'radio' or $type == 'select-distance' ){

			if( !is_array($vars['options'][$name]) ){
				if( $value == '0' ){
					$value = '';
				}else{
					$value = '<a href="?option='.escape($vars['options'][$name]).'&view=true&id='.$value.'">'.$this->content[underscored($name).'_label'].'</a>';
				}
			}else{
				if( is_assoc_array($vars['options'][$name]) ){
					$value = $vars['options'][$name][$value];
				}
			}
		?>
		<?php
		}elseif( $type == 'parent' ){
			reset($vars['fields'][$this->section]);

			$field = key($vars['fields'][$this->section]);

			$row = sql_query("SELECT id,`$field` FROM `".$this->table."` WHERE id='".escape($value)."' ORDER BY `$field`");

			$value = '<a href="?option='.escape($this->section).'&view=true&id='.$value.'">'.($row[0][$field]).'</a>';
		?>
		<?php }elseif( $type == 'checkbox' ){
			$value=$value ? 'Yes' : 'No';
		}elseif( $type == 'files' ){

		if( $value ){
			$value = explode("\n",$value);
		}
		?>
				<ul id="<?=$name;?>_files" class="files">
				<?php
				$count=0;

				if( is_array($value) ){
					$array = $value;
					foreach( $array as $key=>$val ){
						$count++;

						if( $val ){
							$file = sql_query("SELECT * FROM files WHERE id='".escape($val)."'");
						}
						
						if( in_array(file_ext($file[0]['name']),$image_types) ){
							$value = '<img src="http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file_preview.php?f='.$file[0]['id'].'&w=320&h=240" id="'.$name.'_thumb" /><br />';
						}
						$value .= '<a href="http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file.php?f='.$file[0]['id'].'">'.$file[0]['name'].'</a> <span style="font-size:9px;">'.file_size($file[0]['size']).'</span><br><br>';
				
					}
				}
				?>
				</ul>
		<?php }elseif( $type == 'phpuploads' ){

			if( $value ){
				$value = explode("\n", $value);
			}
		?>
				<ul id="<?=$name;?>_files">
				<?php
				$count=0;

				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$count++;
				?>
				<li id="item_<?=$name;?>_<?=$count;?>">
					<img src="/_lib/modules/phpupload/?func=preview&file=<?=$val;?>" id="file_<?=$name;?>_<?=$count;?>_thumb" /><br />
					<label id="file_<?=$name;?>_<?=$count;?>_label"><?=$val;?></label>
				</li>
				<?php
					}
				}
				?>
				</ul>
		<?php
		}elseif( $type == 'date' ){
			if( $value!='0000-00-00' and $value!='' ){
				$value = dateformat('d/m/Y', $value);
			}
		}elseif( $type=='dob' ){
			if( $value!='0000-00-00' and $value!='' ){
				$age = age($value);
				$value = dateformat('d/m/Y', $value);
			}

			$value = $value.' (<?=$age;?>)';
		}elseif( $type=='month' ){
			if( $value!='0000-00-00' and $value!='' ){
				$value=dateformat('F Y',$value);
			}
		}elseif( $type == 'datetime' ){
			if( $value!='0000-00-00 00:00:00' ){
				$date = explode(' ', $value);
				$value = dateformat('d/m/Y',$date[0]).' '.$date[1];
			}
		}elseif( $type == 'timestamp' ){
			if( $value!='0000-00-00 00:00:00' ){
				$date = explode(' ', $value);
				$value = dateformat('d/m/Y',$date[0]).' '.$date[1];
			}
		}elseif( $type == 'number' ){
		?>
			<?=number_format($value, 2);?>
		<?php 
		}elseif( $type == 'rating' ){
		?>
			<select name="<?=$field_name;?>" class="rating" disabled="disabled">
			    <option value="">Choose</option>
				<?=html_options($this->opts['rating'], $value, true);?>
			</select>
		<?php
		}elseif( $type == 'coords' ){
		?>
			<input type="text" class="map" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value,6,-1));?>" <?php if( $readonly ){ ?>disabled<?php } ?> size="50" <?=$attribs;?>>
		<?php
		}

		if( $return ){
			return $value;
		}else{
			print $value;
		}
	}

	function get_options($name, $where=false)
	{
		global $vars, $strs;

		if( !isset($vars['options'][$name]) ){
			return false;
		}

		if( !is_array($vars['options'][$name]) ){
			$table = underscored($vars['options'][$name]);

			foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
				if( $v!='separator' ){
					$field=$k;
					break;
				}
			}

			$raw_option = $vars['fields'][$vars['options'][$name]][$field];

			$cols = '';
			if( is_array($raw_option) ){
				$db_field_name = $this->db_field_name($vars['options'][$name], $field);
				$cols .= underscored($db_field_name)." AS `".underscored($field)."`"."\n";
			}else{
				$cols .= '`'.underscored($field).'`';
			}

			//sortable
			$sortable = in_array('position', $vars['fields'][$vars['options'][$name]]);

			if( $sortable ){
				$order = 'position';
			}else{
				$order = $field;
			}

			if( in_array('language', $vars['fields'][$vars['options'][$name]]) ){
				$where_str = '';
				if($where){
					$where_str = 'AND '.$where;
				}

				$language = $this->language ? $this->language : 'en';

				$rows = sql_query("SELECT id, $cols FROM
					$table
					WHERE
						language='".$language."'
						$where_str
					ORDER BY `".underscored($order)."`
				");

				$options = array();
				foreach($rows as $row){
					if( $row['translated_from'] ){
						$id = $row['translated_from'];
					}else{
						$id = $row['id'];
					}

					$options[$id] = $row[underscored($field)];
				}
			}else{
				$parent_field = array_search('parent',$vars['fields'][$vars['options'][$name]]);

				if( $parent_field !== false ){
					$options = $this->get_children($vars['options'][$name], $parent_field);
				}else{
					$where_str = '';
					if($where){
						$where_str = 'WHERE '.$where;
					}

					$rows = sql_query("SELECT id, $cols FROM
						$table
						$where_str
						ORDER BY `".underscored($order)."`
					");

					$options = array();
					foreach($rows as $row){
						$options[$row['id']] = $row[underscored($field)];
					}
				}
			}

			$vars['options'][$name]=$options;
		}elseif( is_object($strs) ){
			if( is_assoc_array($vars['options'][$name]) ){
				foreach( $vars['options'][$name] as $k=>$v ){
					if($strs->$v){
						$vars['options'][$name][$k]=$strs->$v;
					}
				}
			}else{
				$new_options = array();
				foreach( $vars['options'][$name] as $k=>$v ){
					if($strs->$v){
						$new_options[$v] = $strs->$v;
					}else{
						$new_options[$v] = $v;
					}
				}

				$vars['options'][$name] = $new_options;
			}
		}

		return $vars['options'][$name];
	}

	function admin()
	{
		global $auth, $vars, $blog;

		if( !$auth ){
			die('database settings not configured');
		}
		
		$option = $_GET['option'] ?: 'index';

		if( $_GET['option']!='login' ){
			$auth->check_admin();
		}

		if(
            $auth->user['admin']==2 and
            $_GET['option'] and $option!='preferences' and
            $_GET['option']!='logout' and
            $_GET['option']!='login' and
            !array_key_exists($option, $auth->user['privileges'])
        ){
			die('access denied');
		}

		//don't allow inline editing'
		$this->inline = false;

		//load blog handlers
		/*
		if( $vars['fields']['blog'] ){
		    $blog = new blog;
		}
		*/

		$this->language='en';

		if( file_exists('_tpl/admin/'.underscored($option).'.php') ){
			$this->template(underscored($option).'.php');
		}elseif( method_exists($this, $option) ){
			$this->$option();
		}elseif( $option!='index' ){
			check_table('cms_filters', $this->cms_filters);
			$this->default_section($option);
		}else{
			$this->main();
		}
	}

	function notify($subject=null, $to=null){
		global $vars, $from_email;

		if(!$subject or is_numeric($subject)){
			$subject = 'New submission to: '.$this->section;
		}

        $msg = 'New submission to '.$this->section.":\n\n";

        $this->set_section($this->section, $this->id);

        foreach( $vars['fields'][$this->section] as $name=>$type ){
            $msg .= ucfirst(spaced($name)).': '.$this->get_value($name)."\n";
        }
        $msg .= "\n".'http://'.$_SERVER['HTTP_HOST'].'/admin?option='.rawurlencode($this->section).'&edit=true&id='.$this->id;

        $msg = nl2br($msg);

        //$headers = 'From: auto@'.$_SERVER["HTTP_HOST"]."\n";

		$mail = new Rmail();

        if($_POST["email"]){
            $headers .= 'Reply-to: '.$_POST["email"]."\n";
            $mail->setHeader('Reply-to', $_POST["email"]);
        }

		$mail->setHTMLCharset('UTF-8');
		$mail->setHeadCharset('UTF-8');
		$mail->setHtml($msg);
		$mail->setFrom('auto@'.$_SERVER["HTTP_HOST"]);
		$mail->setSubject($subject);

		if(!is_string($to)){
		    $to = $from_email;
		}

		$result = $mail->send(array($to), 'mail');
	}

	function submit($notify=null, $other_errors = array()){
		global $vars;

        $errors = $this->validate();

		if(is_array($other_errors)){
		    $errors = array_values(array_unique(array_merge($errors, $other_errors)));
		}

        //handle validation
        if( count($errors) ){
            //validateion failed
            die(json_encode($errors));
        }elseif( $_POST['validate'] ){
            //validation passed
            die('1');
        }else{
            $this->id = $this->save();

    		if( $notify ){
    			$this->notify(null, $notify);
    		}

    		return $this->id;
        }
	}

	function validate($data=null)
	{
		global $vars, $languages, $strs;

		$in_use = is_object($strs) ? $strs->inUse : 'in use';

		if( !is_array($data) ){
			$data = $_POST;
		}

		$errors = $this->trigger_event('beforeValidate', array('data'=>$data));

		if( !is_array($errors) ){
			$errors = array();
		}

		$table_keys = sql_query("SHOW keys FROM `".$this->table."`");

		$keys = array();
		foreach( $table_keys as $v ){
			if( $v['Non_unique'] == 0 ){
				$keys[$v['Key_name']][] = $v['Column_name'];
			}
		}

		if( count($languages) and in_array('language',$vars['fields'][$this->section]) ){
			$languages = array_merge(array('en'), $languages);
		}else{
			$languages = array('en');
		}

		foreach( $languages as $language ){
			foreach( $vars['fields'][$this->section] as $k=>$v ){
				if( $this->editable_fields and !in_array($k, $this->editable_fields) ){
					continue;
				}

				$field_name=underscored($k);

				if( $language=='en' ){
					$name = $field_name;
				}else{
					$name = $language.'_'.$field_name;
				}

				//check items are valid
				switch($v){
					case 'url':
						if( $data[$name]=='http://' ){
							$data[$name] = '';
						}

						if( $data[$name] and !is_url($data[$name]) ){
							$errors[] = $name;
							continue;
						}
					break;
					case 'email':
						if( $data[$name] and !is_email($data[$name]) ){
							$errors[] = $name;
							continue;
						}
					break;
					case 'tel':
						if( $data[$name] and !is_tel($data[$name]) ){
							$errors[] = $name;
							continue;
						}
					break;
					case 'int':
					case 'decimal':
						if( $data[$name] and !is_numeric($data[$name]) ){
							$errors[] = $name;
							continue;
						}
					break;
					case 'postcode':
						if(
							$data[$name] and
							!format_postcode($data[$name]) and
							(!$data['country'] or $data['country']=='UK')
						){
							$errors[] = $name;
							continue;
						}elseif( format_postcode($data[$name]) ){
							$data[$name] = format_postcode($data[$name]);
						}
					break;
					case 'mobile':
						if( $data[$name] and !format_mobile($data[$name]) ){
							$errors[] = $name;
							continue;
						}elseif( format_mobile($data[$name]) ){
							$data[$name] = format_mobile($data[$name]);
						}
					break;
				}

				if(
					in_array($k, $vars['required'][$this->section]) and ($data[$name]==='' or !isset($data[$name]))
				){
					if( $_FILES[$name] ){
						continue;
					}

					if($v=='password' and $this->id){
					    continue;
					}

					$errors[] = $name;
					continue;
				}

				//check keys
				foreach( $keys as $key=>$fields ){
					if( in_array($name,$fields) ){
						$where = array();
						foreach( $fields as $field ){
							$where[] = "`".escape($field)."`='".escape($data[$field])."'";
						}
						$where[] = "`".$this->field_id."`!='".escape($this->id)."'";

						$where_str = '';
						foreach($where as $w){
							$where_str .= "\t".$w." AND"."\n";
						}
						$where_str = "WHERE \n".substr($where_str,0,-5);

						$field = sql_query("SELECT * FROM `".$this->table."`
							$where_str
							LIMIT 1
						", 1);

						if( $field ){
							$errors[] = $key.' '.$in_use;

							foreach( $fields as $field ){
								$errors[] = $field.' '.$in_use;
							}
							break;
						}
					}
				}
			}
		}

		//antispam
		/*
		if( $data['nospam']!='1' ){
		    $errors[] = 'nospam';
		}
		*/

		if( count($errors) ){
			$errors = array_values(array_unique($errors));
		}

		return $errors;
	}

	function build_query($field_arr,$data)
	{
		global $vars, $languages, $auth;

		$column_data = sql_query("SHOW COLUMNS FROM `".$this->table."`");

		$null_fields = array();
		foreach( $column_data as $v ){
			if( $v['Null']=='YES' ){
				$null_fields[] = $v['Field'];
			}
		}

		foreach( $field_arr as $k=>$v ){
			if( $v=='id' or $v=='related' or $v=='timestamp' or $v=='separator' or $v=='translated-from' or $v=='polygon' ){
				continue;
			}

			if( $this->editable_fields and !in_array($k, $this->editable_fields) ){
				continue;
			}

			if( is_array($v) ){
				$this->build_query($v,$data);
			}else{
				$k = underscored($k);
				
				if( $v == 'language' ){
					$this->query .= "`$k`='".escape($this->language)."',\n";

					if( $this->language != 'en' ){
						$this->query .= "`translated_from`='".escape($this->id)."',\n";
					}
					continue;
				}

				if( $this->language=='en' ){
					$name = $k;
				}else{
					$name = $this->language.'_'.$k;
				}

				if( $v == 'position' and !$this->id and !isset($data[$name]) ){
					//find last position
					$max_pos = sql_query("SELECT MAX(position) AS `max_pos` FROM `".$this->table."`", 1);
					$max_pos = $max_pos['max_pos']+1;

					$this->query .= "`$k`='".escape($max_pos)."',\n";
					continue;
				}elseif( $v == 'position' and !isset($data[$name]) ){
					continue;
				}

				if( $v=='password' ){
				    //leave passwords blank to keep
//print var_dump($data);exit;
//print var_dump($name);exit;
//print var_dump($auth->hash_password);exit;
				    if($data[$name]==''){
					    continue;
				    }
            		if( $auth->hash_password ){
            			$data[$name] = $auth->create_hash($data[$name]);
            		}
				}

				//protected vars
				if( !$auth->user['admin'] and $k=='admin' ){
					continue;
				}

				if(
					(
						in_array($k, $vars['protected'][$this->section]) or
						in_array(spaced($k), $vars['protected'][$this->section])
					)
					and
					!$auth->user['admin']
				){
					continue;
				}

				if( $v=='url' and $data[$name]=='http://' ){
					$data[$name] = '';
				}elseif( $v=='datetime' ){
					if ($data['time']) {
						$data[$name] .= ' '.$data['time'][$name].':00';
					}
				}elseif( $v=='date' or $v=='dob' ){
				}elseif( $v=='month' ){
					$data[$name] .= '-01';
				}elseif( $v=='file' ){
					if( $_FILES[$name]['error']=='UPLOAD_ERR_OK' ){
						check_table('files', $this->file_fields);

						$size = filesize($_FILES[$name]['tmp_name']);

						$result = sql_query("INSERT INTO files SET
							date=NOW(),
							name='".escape($_FILES[$name]['name'])."',
							size='".escape($size)."',
							type='".escape($_FILES[$name]['type'])."'
						");

						$data[$name] = sql_insert_id();

						//check folder exists
						if(!file_exists($vars['files']['dir'])){
							mkdir($vars['files']['dir']);
						}

						if( $vars['files']['dir'] ){
							rename($_FILES[$name]['tmp_name'], $vars['files']['dir'].$data[$name]);
						}else{
							sql_query("UPDATE files SET
								data='".escape(file_get_contents($_FILES[$name]['tmp_name']))."'
								WHERE
									id='".escape($data[$name])."'
							");
						}

						//thumb
						if( $_POST[$name.'_thumb'] ){
                            // Grab the MIME type and the data with a regex for convenience
                            if ( preg_match('/data:([^;]*);base64,(.*)/', $_POST[$name.'_thumb'], $matches) ) {
                                // Decode the data
                                $thumb = $matches[2];
                                $thumb = str_replace(' ','+',$thumb);
                                $thumb = base64_decode($thumb);

                                $file_name = $vars['files']['dir'].$data[$name].'_thumb';
                                file_put_contents($file_name, $thumb);
                            }else{
                                die('no mime type');
                            }
						}
					}elseif( !$data[$name] and $row[$name] ){
						sql_query("DELETE FROM files
							WHERE
							id='".escape($row[$name])."'
						");

						if( $vars['files']['dir'] ){
							unlink($vars['files']['dir'].$row[$name]);
						}

						$data[$name] = 0;
					}
				}elseif( $v=='files' ){
					$files = $data[$name];

					if( is_array($_FILES[$name]) ){
						foreach( $_FILES[$name]['error'] as $key => $error ){
							if( $error=='UPLOAD_ERR_OK' ){
								check_table('files', $this->file_fields);

								$content = file_get_contents($_FILES[$name]['tmp_name'][$key]);

								sql_query("INSERT INTO files SET
									date=NOW(),
									name='".escape($_FILES[$name]['name'][$key])."',
									size='".escape( strlen($content) )."',
									type='".escape($_FILES[$name]['type'][$key])."'
								");

								$files[] = sql_insert_id();

								//check folder exists
								if(!file_exists($vars['files']['dir'])){
									mkdir($vars['files']['dir']);
								}

								if( $vars['files']['dir'] ){
									file_put_contents($vars['files']['dir'].sql_insert_id(),$content) or
										trigger_error("Can't save ".$vars['files']['dir'].$data[$name], E_ERROR);
								}else{
									sql_query("UPDATE files SET
										data='".escape($content)."'
										WHERE
										id='".escape($data[$name])."'
									");
								}
							}
						}
					}

					if( $this->id ){
						//clean up
						$row = sql_query("SELECT `$name` FROM `".$this->table."`", 1);

						$old_files = explode("\n", $row[$name]);

						foreach( $old_files as $old_file ){
							if( in_array($old_file,$files) ){
								sql_query("DELETE FROM files
									WHERE
									id='".escape($old_file['id'])."'
								");

								if( $vars['files']['dir'] ){
									unlink($vars['files']['dir'].$old_file['id']);
								}
							}
						}
					}

					if (is_array($files)) {
						$data[$name] = implode("\n",$files);
					}
					$data[$name] = trim($data[$name]);
				}elseif( $v=='select-multiple' or $v=='checkboxes' ){
					continue;
				}elseif( $v=='postcode' ){
					$data[$name]=format_postcode($data[$name]);
				}elseif( $v=='mobile' ){
					if( $data[$name] ){
						$data[$name]=format_mobile($data[$name]);
					}
				}elseif( $v=='ip' ){
				    if( !$this->id ){
						$data[$name] = $_SERVER["REMOTE_ADDR"];
				    } else if (!$data[$name]) {
				    	continue;
				    }
				}elseif( $v=='page-name' ){
					$data[$name] = str_to_pagename($data[$name], false);
				}elseif( $v=='coords' ){
					$this->query.="`$k`=GeomFromText('POINT(".escape($data[$name]).")'),\n";
					continue;
				}elseif( $v=='text' ){
				    $data[$name] = strip_tags($data[$name]);
				}elseif( $v=='textarea' and !$auth->user['admin'] ){
				    $data[$name] = strip_tags($data[$name]);
				}elseif( $v=='editor' ){
					$doc = new DOMDocument();
					$doc->loadHTML("<div>".$data[$name]."</div>");
					
					$container = $doc->getElementsByTagName('div')->item(0);
					$container = $container->parentNode->removeChild($container);
					while ($doc->firstChild) {
					    $doc->removeChild($doc->firstChild);
					}
					
					while ($container->firstChild ) {
					    $doc->appendChild($container->firstChild);
					}
					
					$script = $doc->getElementsByTagName('script');
					
					foreach($script as $item) {
						$item->parentNode->removeChild($item); 
					}
					
					$data[$name] = $doc->saveHTML();
				}

				if( is_array($data[$name]) ){
					$data[$name] = implode("\n", strip_tags($data[$name]));
				}

				if( (!isset($data[$name]) or $data[$name]==='') and in_array($k, $null_fields) ){
					$this->query .= "`$k`=NULL,\n";
				}else{
					$this->query .= "`$k`='".escape($data[$name])."',\n";
				}
			}
		}
	}

	function save($data=null)
	{
		global $vars, $languages, $auth;

		if( !isset($data) ){
			$data = $_POST;
		}

		$result = $this->trigger_event('beforeSave', array('data'=>$data));
		
		if (is_array($result)) {
			$data = $result;
		}
		
		// force save data to match privileges
		foreach( $auth->user['filters'][$this->section] as $k=>$v ){
			$data[$k] = $v;
		}

		if( count($languages) and !in_array('en', $languages) ){
			$languages = array_merge(array('en'), $languages);
		}elseif( !count($languages) ){
			$languages = array('en');
		}

		//remember language
		$current_language = $this->language;

		foreach( $languages as $language ){
			$this->language = $language;

			//build query
			$this->query = '';

			$this->build_query($vars['fields'][$this->section], $data);

			$this->query = substr($this->query, 0, -2);

			//debug($this->query,true);

			if( $this->id and $language==='en' ){
				$language_exists = true;

				$where_str = $this->field_id."='".escape($this->id)."'";
			}elseif( $this->id and $language!=='en' ){
				$row = sql_query("SELECT * FROM `".$this->table."`
					WHERE
						`translated_from`='".escape($this->id)."' AND
						language='".escape($language)."'
				", 1);

				if( $row ){
					$language_exists = true;
					$where_str = "`translated_from`='".escape($this->id)."' AND language='".escape($language)."'";
				}else{
					$language_exists = false;
				}
			}
			
			//remember old state
			if( table_exists('cms_logs') ) {
				if( $this->id and $language==='en' ){
					$row = sql_query("SELECT * FROM `".$this->table."`
						WHERE
							`id`='".escape($this->id)."'
					", 1);
				}elseif( $this->id and $language!=='en' ){
					$row = sql_query("SELECT * FROM `".$this->table."`
						WHERE
							`translated_from`='".escape($this->id)."' AND
							language='".escape($language)."'
					", 1);
				}
			}

			if( $this->id and ($language_exists) ){
				sql_query("UPDATE `".$this->table."` SET
					".$this->query."
					WHERE $where_str
				");
			}else{
				sql_query("INSERT IGNORE INTO `".$this->table."` SET
					".$this->query."
				");

				if( $language=='en' ){
					$this->id = sql_insert_id();
				}
			}
			
			//log it
			if( table_exists('cms_logs') ) {
				$details = '';
				
				$task = 'add';
				if ($this->id) {
					$task = 'edit';
				
					if( $this->id and $language==='en' ){
						$updated_row = sql_query("SELECT * FROM `".$this->table."`
							WHERE
								`id`='".escape($this->id)."'
						", 1);
					}elseif( $this->id and $language!=='en' ){
						$updated_row = sql_query("SELECT * FROM `".$this->table."`
							WHERE
								`translated_from`='".escape($this->id)."' AND
								language='".escape($language)."'
						", 1);
					}
					
					foreach($updated_row as $k=>$v) {
						if ($row[$k] != $v) {
							$details.= $k.'='.$v."\n";
						}
					}
				}
				
				sql_query("INSERT INTO cms_logs SET
					user = '".$auth->user['id']."',
					section = '".escape($this->section)."',
					item = '".escape($this->id)."',
					task = '".$task."',
					details = '".escape($details)."'
				");
			}

			foreach( $languages as $language ){
				foreach( $vars['fields'][$this->section] as $k=>$v ){
    				if( $this->editable_fields and !in_array($k, $this->editable_fields) ){
    					continue;
    				}

					if( $v=='select-multiple' or $v=='checkboxes' ){
						if( $language=='en' ){
							$name = $k;
						}else{
							$name = $language.'_'.$k;
						}

						$name = underscored($name);

						sql_query("DELETE FROM cms_multiple_select
							WHERE
								section='".escape($this->section)."' AND
								field='".escape($k)."' AND
								item='".escape($this->id)."'
						");

						foreach( $data[$name] as $v ){
							sql_query("INSERT INTO cms_multiple_select SET
								section='".escape($this->section)."',
								field='".escape($k)."',
								item='".escape($this->id)."',
								value='".escape($v)."'
							");
						}

						continue;
					}
				}
			}
		}

		//restore language
		$this->language = $current_language;

		$this->trigger_event('save', array($this->id, $data));

		$this->saved = true;

		return $this->id;
	}

	function trigger_event($event, $args)
	{
		global $cms_handlers;

		if( is_array($cms_handlers) ){
			foreach( $cms_handlers as $handler ){
				if (!is_array($handler['section'])) {
					$handler['section'] = array($handler['section']);
				}
				
				if(
					(
						!$this->section or 
						in_array($this->section, $handler['section'])
					) and 
					$handler['event']===$event
				){
					return call_user_func_array($handler['handler'], (array)$args);
				}
			}
		}
	}

	function choose_filter()
	{
		$this->template('choose_filter.php',true);
	}

	function configure()
	{
		$this->template('configure.php',true);
	}

	function dropdowns()
	{
		$this->template('dropdowns.php',true);
	}

	function shop_orders()
	{
		$this->template('shop_orders.php',true);
	}

	function shop_order()
	{
		$this->template('shop_order.php',true);
	}

	function template($include,$local=false)
	{
		global $vars, $auth, $shop_enabled, $email_templates, $languages, $live_site, $sms_config, $mailer_config, $cms_buttons, $message, $admin_config;

		ob_start();
		if( $local ){
			require(dirname(__FILE__).'/_tpl/'.$include);
		}else{
			require('_tpl/admin/'.$include);
		}
		$include_content = ob_get_contents();
		ob_end_clean();

		if( !$title and preg_match('/<h1>([\s\S]*?)<\/h1>/i', $include_content, $matches) ){
			$title=strip_tags($matches[1]);
		}
		
		$this->filters = sql_query("SELECT * FROM cms_filters WHERE user = '".escape($auth->user['id'])."'");

		require(dirname(__FILE__).'/_tpl/template.php');
		exit;
	}

	function login()
	{
		$this->template('login.php',true);
	}

	function main()
	{
		$this->template('index.php',true);
	}

	function default_section($option)
	{
		global $vars, $sid;

		$vars["fields"]["sms templates"]=array(
			'subject'=>'text',
			'message'=>'textarea',
			'id'=>'id',
		);

		$vars["labels"]["sms templates"]=array('subject','message');

		$this->section=$option;

		$this->table=underscored($option);

		if( $vars['fields'][$this->section] ){
			check_table($this->table, $vars['fields'][$this->section]);

			//check files table
			if(
			    in_array('file', $vars['fields'][$this->section]) or
			    in_array('files', $vars['fields'][$this->section])
			){
			    check_table('files', $this->file_fields);
			}
		}else{
			$index = true;
		}

		if ($index) {
			$this->template('default_index.php', true);
		} elseif($_GET['edit']){
			$this->template('default_edit.php', true);
		}elseif( $_GET['view'] or !array_search('id',$vars['fields'][$this->section]) ){
			$this->template('default_view.php', true);
		}else{
			$this->template('default_list.php', true);
		}
	}

	function email_templates()
	{
		global $vars, $email_templates;

		$fields=array(
			'subject'=>'text',
			'body'=>'textarea',
			'id'=>'id',
		);
		check_table('email_templates', $fields);

		if( !isset($_GET['edit']) ){
			$vars['content'] = sql_query("SELECT * FROM email_templates ORDER BY subject");

			if( !count($vars['content']) ){
				foreach( $email_templates as $k=>$v ){
					sql_query("INSERT INTO email_templates SET
						body='".escape($v)."',
						subject='".escape($k)."'
					");
				}
				$vars['content'] = sql_query("SELECT * FROM email_templates ORDER BY subject");
			}

			$this->template('email_templates_list.php',true);
		}else{
			if( $_POST['save'] ){
				$rows = sql_query("SELECT * FROM email_templates
					WHERE
						subject='".escape($_GET['subject'])."'
				");

				if( $rows ){
					sql_query("UPDATE email_templates SET
						body='".escape($_POST['body'])."'
					WHERE
						subject='".escape($_GET['subject'])."'
					");
				}else{
					sql_query("INSERT INTO email_templates SET
						body='".escape($_POST['body'])."',
						subject='".escape($_GET['subject'])."'
					");
				}

				redirect("?option=email_templates");
			}

			$row = sql_query("SELECT * FROM email_templates
				WHERE
					subject='".escape($_GET['subject'])."'
			", 1);

			if( $row ){
				$vars['email'] = $row;
			}else{
				$vars['email']['subject'] = $_GET['subject'];
				$vars['email']['body'] = $email_templates[$_GET['subject']];
			}

			$vars['email']['body'] = str_replace("\t",'',$vars['email']['body']);

			$this->template('email_templates_edit.php',true);
		}
	}

	function logout()
	{
		global $auth;

		$auth->logout();
		redirect("/");
	}
}
?>