<?php
function send_password_information()
{
	global $auth;

	$user = sql_query("SELECT * FROM ".$auth->table." WHERE id='".escape($_GET['id'])."'", 1);

	if( !$user ){
		$_SESSION['message'] = 'Email address is not in use.';
		return false;
	}

	$auth->forgot_success = false;
	$auth->forgot_password($user['email']);

	$_SESSION['message'] = 'Password details sent';
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

		//built in extensions

		global $cms_buttons, $auth, $vars;

		$cms_buttons[] = array(
			'section'=>'users',
			'page'=>'view',
			'label'=>'Send Password Information',
			'handler'=>'send_password_information'
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

	function delete_items($section, $items) // used in admin system
	{
		global $auth, $vars;

		if( isset($items) and $section ){
			if( !is_array($items) ){
				$items = array($items);
			}

			if( $auth->user['admin'] == 1 or $auth->user['privileges'][$section] == 2 ){
				$response = $this->delete($section,$items);

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

		$field_id = in_array('id',$vars['fields'][$section]) ? array_search('id',$vars['fields'][$section]) : 'id';

		$response = $this->trigger_event('beforeDelete', array($ids));

		if ($response===false) {
		    return false;
		}

		foreach( $ids as $id ){
			sql_query("DELETE FROM `".escape(underscored($section))."`
				WHERE ".$field_id."='$id'
			LIMIT 1
			");

			if( in_array('language', $vars['fields'][$section]) ){
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

		$this->trigger_event('delete', array($ids));
	}

	function conditions_to_sql($section, $conditions=NULL, $num_results){
	    global $vars;

	    //debug($conditions);

		if( $this->language ){
			$language = $this->language;
		}else{
			$language = 'en';
		}

		if( is_numeric($conditions) ){
			if( $language == 'en' ){
				$id = $conditions;
			}else{
				$id = NULL;
				$conditions = array('translated_from'=>$conditions, 'language'=>$language);
			}
			$num_results = 1;
		}elseif( is_string($conditions) ){
			if( in_array('page-name', $vars['fields'][$section]) ){
				$page_name = $conditions;

				$field_page_name = array_search('page-name', $vars['fields'][$section]);

				$conditions = array($field_page_name=>$page_name);

				$num_results = 1;
			}
		}else{
			if( in_array('language',$vars['fields'][$section]) and $language ){
				$conditions['language'] = $language;
			}elseif( !in_array('id', $vars['fields'][$section]) ){
				$id = 1;
			}
		}

		$table = underscored($section);
		$field_id = in_array('id',$vars['fields'][$section]) ? array_search('id',$vars['fields'][$section]) : 'id';

		if( is_numeric($id) ){
			$where[] = "T_$table.".$field_id."='".escape($id)."'";
		}

		if( in_array('language',$vars['fields'][$section]) and $language=='en' ){
			$where[] = "`translated_from`='0'";
		}

		if( is_array($conditions) ){
			foreach( $vars['fields'][$section] as $name=>$type ){
				$field_name = underscored($name);

				if(
					( isset($conditions[$name]) and $conditions[$name]!=='' ) or
					( isset($conditions[$field_name]) and $conditions[$field_name]!=='' )
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

							$where[] = "T_$table.".$field_name." ".$operator." '".escape($value)."'";
						break;
						case 'select-multiple':
						case 'checkboxes':
							if( count($conditions[$field_name])==1 and !reset($conditions[$field_name]) ){
								continue;
							}

							$joins .= " LEFT JOIN cms_multiple_select T_".$field_name." ON T_".$field_name.".item=T_".$table.".".$field_id;

							$or = '(';

							foreach( $conditions[$field_name] as $k=>$v ){
							    $v = is_array($v) ? $v['value'] : $v;
								$or .= "T_".$field_name.".value = '".escape($v)."' OR ";
							}
							$or = substr($or,0,-4);
							$or .= ')';

							$where[] = $or;
							$where[] = "T_".$field_name.".section='".$section."'";
						break;
						case 'date':
						case 'datetime':
						case 'timestamp':
							if( $conditions['func'][$field_name] == 'month' ){
								$where[] = "date_format(".$field_name.", '%m%Y') = '".escape($value)."'";
							}elseif( $conditions['func'][$field_name] ){
								$parts = explode('/',$value);
								$value = $parts[2].'-'.$parts[1].'-'.$parts[0];

								$where[] = "T_$table.".$field_name." ".escape($conditions['func'][$field_name])." '".escape(dateformat('Y-m-d',$value))."'";
							}
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
						case 'postcode':
							if( format_postcode($value) and is_numeric($conditions['func'][$field_name]) ){
								$grids = calc_grids(format_postcode($value));

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
							}
						break;
						default:
							$value = str_replace('*','%',$value);
							$where[] = "T_$table.".$field_name." LIKE '".escape($value)."'";
						break;
					}
				}
			}

			if( $conditions['s'] ){
				$words = explode(' ',$conditions['s']);

				foreach( $words as $word ){
					$or = array();

					foreach( $vars['fields'][$section] as $name=>$type ){
						if(
                            (
                                $type == 'text' or
                                $type == 'textarea' or
                                $type == 'email' or
                                $type == 'mobile' or
                                $type == 'select'
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
							}else{
								$or[] = "T_$table.".underscored($name)." LIKE '%".escape($value)."%'";
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

		return array(
		    'where_str' => $where_str,
		    'having_str' => $having_str,
		    'joins' => $joins,
		    'num_results' => $num_results,
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

			$cols='';
			foreach( $vars['fields'][$section] as $k=>$v ){
				if( $v!='select-multiple' and $v!='checkboxes' and $v!='separator' ){
					//$cols.="\t"."T_$table.".underscored($k)." AS `".$k."`,"."\n";

                    if($v=='coords'){
                        $cols.="\tAsText(T_$table.".underscored($k).") AS ".underscored($k).','."\n";
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
				}else if( in_array('date', $vars['fields'][$section]) ){
					$field_date = array_search('date', $vars['fields'][$section]);
					$order = 'T_'.$table.'.'.underscored($field_date);
					$asc = false;
				}else if( in_array('timestamp', $vars['fields'][$section]) ){
					$field_date = array_search('timestamp', $vars['fields'][$section]);
					$order = 'T_'.$table.'.'.underscored($field_date);
					$asc = false;
				}else if( in_array('date', $vars['fields'][$section]) ){
					$order = 'T_'.$table.'.date';
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

            $sql = $this->conditions_to_sql($sections, $conditions, $num_results);

            $where_str = $sql['where_str'];
            $having_str = $sql['having_str'];
            $joins = $sql['joins'];

			if( in_array('select',$vars['fields'][$section]) or in_array('combo',$vars['fields'][$section]) or in_array('radio',$vars['fields'][$section]) ){
				$selects = array_keys($vars['fields'][$section], "select");
				$radios = array_keys($vars['fields'][$section], "radio");
				$combos = array_keys($vars['fields'][$section], "combo");

				$keys = array_merge($selects, $radios, $combos);

				foreach( $keys as $key ){
					if( !is_array($vars['options'][$key]) ){
						$option_table = underscored($vars['options'][$key]);

						$join_id = array_search('id',$vars['fields'][$vars['options'][$key]]);

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

			$query = "SELECT
			$cols
			FROM `$table` T_$table
				$joins
			$where_str
			GROUP BY
				T_$table.$field_id
			$having_str
			";
			//debug($query);

			if( $return_query===true ){
				return $query;
			}

			$limit = $sql['num_results'] ?: NULL;

            //require_once('_lib/paging.class.php');
			$this->p = new paging( $query, $limit, $order, $asc, $prefix );

			$content = $this->p->rows;

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

	function set_section($section, $id, $editable_fields)
	{
		global $vars, $languages;

		if( !$vars['fields'][$section] ){
			throw new Exception("Section does not exist: ".$section, E_USER_ERROR);
		}

		$this->section = $section;
		$this->table = underscored($section);

        foreach( $editable_fields as $k=>$v ){
            $editable_fields[$k] = spaced($v);
        }

		if( is_array($editable_fields) ){
			$this->editable_fields = $editable_fields;
		}else{
			$this->editable_fields = null;
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

		$this->trigger_event('beforeEdit', array($id));
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

						$row = sql_query("SELECT `".key($vars['fields'][$vars['options'][$field]])."` FROM `".escape(underscored($vars['options'][$field]))."` WHERE $join_id='".escape($value)."'");
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
				$parent_field='$parent'
			ORDER BY `$label`
		");

		$indent = '';
		for( $i=0; $i<$depth; $i++ ){
			$indent .= '  ';
		}

		$parents = array();
		foreach($rows as $row){
			if( $row['id'] == $this->id ){
				continue;
			}

			$parents[$row['id']] = $indent.$row[$label];

			$children = $this->get_children($section,$parent_field,$row['id'],$depth+1);

			if( count($children) ){
				$parents = $parents + $children;
			}
		}

		return $parents;
	}

	function get_field($name, $attribs='', $separator='', $where = false)
	{
		global $vars, $id, $strs, $cms_config;

		$placeholder = '';

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
			<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<? } ?> <?=$attribs;?>>
		<?php
			break;
			case 'int':
		?>
			<input type="number" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<? } ?> <?=$attribs;?>>
		<?php
			break;
			case 'coords':
		?>
			<input type="text" size="40" value="London, UK" />
			<input type="button" value="Search" class="mapSearch" /><br />

			<input type="text" class="map" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value,6,-1));?>" <? if( $readonly ){ ?>disabled<? } ?> size="50" <?=$attribs;?> placeholder="coordinates">
		<?php
			break;
			case 'email':
		?>
			<input type="email" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<? } ?> <?=$attribs;?>>
		<?php
			break;
			case 'url':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=htmlspecialchars($value);?>" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs;?>>
		<?php
			break;
			case 'postcode':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<? } ?> size="10" <?=$attribs;?>>
		<?php
			break;
			case 'mobile':
		?>
			<input type="text" name="<?=$field_name;?>" value="<?=$value;?>" <? if( $readonly ){ ?>disabled<? } ?> size="14" <?=$attribs;?>>
		<?php
			break;
			case 'password':
		?>
			<input type="password" name="<?=$field_name;?>" value="<?=$value;?>" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs;?>>
		<?php
			break;
			case 'textarea':
		?>
			<textarea name="<?=$field_name;?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( $placeholder ){ ?>placeholder="<?=$placeholder;?>"<? } ?> <?=$attribs ? $attribs : 'class="autogrow"';?>><?=$value;?></textarea>
		<?php
			break;
			case 'editor':
				//$value = mb_detect_encoding($value, "UTF-8") == "UTF-8" ? $value : utf8_encode($value);
		?>
			<textarea id="<?=$field_name;?>" name="<?=$field_name;?>" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs ? $attribs : 'rows="25" style="width:100%; height: 400px;"';?> class="<?=$cms_config["editor"] ? $cms_config["editor"] : 'tinymce';?>"><?=htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');?></textarea>
		<?php
			break;
			case 'file':
				$file=sql_query("SELECT * FROM files WHERE id='".escape($value)."'",1);
		?>
			<? if( $value ){ ?>
				<input type="hidden" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=$value;?>">
				<? /*<a href="/_lib/cms/file.php?f=<?=$value;?>"><?=$file['name'];?></a> <?=file_size($file['size']);?> */?>
				<?=$file['name'];?>
				<a href="javascript:;" onClick="clearFile('<?=$field_name;?>')">clear</a>
			<? }else{ ?>
				<input type="file" id="<?=$field_name;?>" name="<?=$field_name;?>" <? if( $readonly ){ ?>disabled<? } ?> />
			<? } ?>
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
				if( $type!=='combo' ){
					$vars['options'][$name] = $this->get_options($name, $where);
				}

				if( $type=='radio' ){
		?>
				<? if( is_assoc_array($vars['options'][$name]) ){ ?>
					<? foreach( $vars['options'][$name] as $k=>$v ){ ?>
					<label><input type="radio" name="<?=$field_name;?>" value="<?=$k;?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( isset($value) and $k==$value ){ ?>checked="checked"<? } ?>  <?=$attribs;?>> <?=$v;?> &nbsp;</label><?=$separator;?>
					<? } ?>
				<? }else{ ?>
					<? foreach( $vars['options'][$name] as $k=>$v ){ ?>
					<label><input type="radio" name="<?=$field_name;?>" value="<?=$v;?>" <? if( $readonly ){ ?>disabled<? } ?> <? if( isset($value) and $v==$value ){ ?>checked="checked"<? } ?> <?=$attribs;?>> <?=$v;?> &nbsp;</label> <?=$separator;?>
					<? } ?>
				<? } ?>
			<?
				}elseif( $type=='combo' ){
			?>
				<input type="text" name="<?=$field_name;?>" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs;?> value="<?=$value;?>" autocomplete="off" class="combo">
			<?
				}else{
			?>
			<select name="<?=$field_name;?>" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs;?>>
			<option value=""><?=$placeholder ? $placeholder : 'Choose';?></option>
				<?=html_options($vars['options'][$name], $value);?>
			</select>
			<?
				}
			?>
		<?php
			break;
			case 'select-multiple':
			case 'checkboxes':
				$value = array();

				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					if( $this->id ){
						$join_id = array_search('id', $vars['fields'][$vars['options'][$name]]);

						$rows=sql_query("SELECT T1.value FROM cms_multiple_select T1
							INNER JOIN `".escape(underscored($vars['options'][$name]))."` T2 ON T1.value=T2.$join_id
							WHERE
								section='".escape($this->section)."' AND
								field='".escape($name)."' AND
								item='".$this->id."'
						");

						foreach( $rows as $row ){
							$value[]=$row['value'];
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
							$db_field_name=$this->db_field_name($vars['options'][$name],$field);

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
								$id = $row['id']	;
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
			<select name="<?=$field_name;?>[]" multiple="multiple" <? if( $readonly ){ ?>disabled<? } ?> size="10" style="width:100%">
				<?=html_options($vars['options'][$name], $value);?>
			</select>
		<?php
			}else{
		?>
				<? /*
                Select <a href="javascript:;" onclick="selectAll('<?=$field_name;?>');">All</a>, <a href="javascript:;" onclick="selectNone('<?=$field_name;?>');">None</a><br />
                */

                $is_assoc = is_assoc_array($vars['options'][$name]);

                foreach( $vars['options'][$name] as  $k=>$v ){
                    $val = $is_assoc ? $k : $v;
                ?>
    			    <label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$val;?>" <? if( $readonly ){ ?>readonly<? } ?> <? if( in_array($val, $value) ){ ?>checked="checked"<? } ?> /> <?=$v;?></label><?=$separator ? $separator : '<br>';?>
				<?
                }
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
				<select name="<?=$field_name;?>" <? if( $readonly ){ ?>readonly<? } ?>>
				<option value=""></option>
				<?=html_options($parents, $value);?>
				</select>
		<?php
			break;
			case 'checkbox':
		?>
			<input type="checkbox" name="<?=$field_name;?>" value="1" <? if( $readonly ){ ?>disabled<? } ?> <? if( $value ){ ?>checked<? } ?>  <?=$attribs;?> />
		<?php
			break;
			case 'files':

				if( $value ){
					$value = explode("\n",$value);
				}
		?>

			<ul class="files">
				<?
				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$file=sql_query("SELECT * FROM files WHERE id='".escape($val)."'");

				?>
				<li>
				<? if( $file ){ ?>
					<input type="hidden" name="<?=$field_name;?>[]" value="<?=$val;?>" <? if( $readonly ){ ?>readonly<? } ?>>
					<a href="/_lib/cms/file.php?f=<?=$val;?>"><?=$file[0]['name'];?></a>
					<a href="javascript:;" class="link" onClick="delItem(this)">Delete</a>
				<? } ?>
				</li>
				<?
					}
				}
				?>

				<li>
					<input type="file" name="<?=$field_name;?>[]" multiple="multiple" <? if( $readonly ){ ?>disabled<? } ?> <?=$attribs;?> />
				</li>
			</ul>
		<?php
			break;
			case 'phpuploads':
		?>
            <textarea name="<?=$field_name;?>" class="upload"><?=$value;?></textarea>
		<?php
			break;
			case 'date':
		?>
			<input type="text" class="date" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && $value!='0000-00-00') ? dateformat('d/m/Y',$value) : '';?>" <? if( $readonly ){ ?>disabled<? } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> />
		<?php
			break;
			case 'dob':
		?>
			<input type="text" class="dob" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value && $value!='0000-00-00') ? dateformat('d/m/Y',$value) : '';?>" <? if( $readonly ){ ?>disabled<? } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> />
		<?php
			break;
			case 'time':
		?>
			<input type="text" class="time" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=($value!='00:00:00') ? substr($value,0,-3) : '';?>" <? if( $readonly ){ ?>disabled<? } ?> size="10" <?=$attribs ? $attribs : 'style="width:40px;"';?> />
		<?php
			break;
			case 'datetime':

			if( $value ){
				$date=explode(' ',$value);
			}
		?>
			<input type="text" class="date" name="<?=$field_name;?>" value="<?=($date[0] and $date[0]!='0000-00-00') ? dateformat('d/m/Y',$date[0]) : '';?>" <? if( $readonly ){ ?>disabled<? } ?> size="10" <?=$attribs ? $attribs : 'style="width:75px;"';?> />
			<input type="text" name="time[<?=$field_name;?>]" value="<?=($date[1] and $date[1]!='00:00:00') ? $date[1] : '00:00:00';?>" <? if( $readonly ){ ?>disabled<? } ?> size="10" <?=$attribs ? $attribs : 'style="width:60px;"';?> />
		<?php
			break;
			case 'number':
		?>
			<input type="text" name="<?=$field_name;?>" onKeyPress="return numbersonly(event)" value="<?=$value;?>" <? if( $readonly ){ ?>disabled<? } ?> size="50" <?=$attribs;?>>
		<?php
			break;
			case 'rating':
			case 'avg-rating':
		?>
			<select name="<?=$field_name;?>" class="rating" data-section="<?=$this->section;?>" data-item="<?=$this->content['id'];?>" <? if($type=='avg-rating'){?>data-avg='data-avg'<?}?> <?=$attribs;?>>
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
		?>
				<?
				$image_types = array('jpg','jpeg','gif','png');
				if( in_array(file_ext($file[0]['name']),$image_types) ){
				?>
				<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
				<? } ?>
				<a href="/_lib/cms/file.php?f=<?=$file[0]['id'];?>"><?=$file[0]['name'];?></a> <span style="font-size:9px;"><?=file_size($file[0]['size']);?></span>

				<?
				$doc_types = array('pdf','doc','docx','xls','tiff');
				if( in_array(file_ext($file[0]['name']),$doc_types) ){
				?>
				<a href="http://docs.google.com/viewer?url=<?=rawurlencode('http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file.php?f='.$file[0]['id'].'&auth_user='.$_SESSION[$auth->cookie_prefix.'_email'].'&auth_pw='.md5($auth->secret_phrase.$_SESSION[$auth->cookie_prefix.'_password']));?>" target="_blank">(view)</a>
				<? } ?>
		<?
			}
		}elseif( $type == 'phpupload' ){ ?>
			<img src="/_lib/modules/phpupload/?func=preview&file=<?=$value;?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
			<label id="<?=$name;?>_label"><?=$value;?></label>
		<?
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
		<?
		}elseif( $type == 'parent' ){
			reset($vars['fields'][$this->section]);

			$field = key($vars['fields'][$this->section]);

			$row = sql_query("SELECT id,`$field` FROM `".$this->table."` WHERE id='".escape($value)."' ORDER BY `$label`");

			$value = '<a href="?option='.escape($this->section).'&view=true&id='.$value.'">'.($row[0][$field]).'</a>';
		?>
		<? }elseif( $type == 'checkbox' ){
			$value=$value ? 'Yes' : 'No';
		}elseif( $type == 'files' ){

		if( $value ){
			$value = explode("\n",$value);
		}
		?>
				<ul id="<?=$name;?>_files" class="files">
				<?
				$count=0;

				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$count++;

						if( $value ){
							$file=sql_query("SELECT * FROM files WHERE id='".escape($val)."'");
						}
				?>
				<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
				<a href="/_lib/cms/file.php?f=<?=$file[0]['id'];?>"><?=$file[0]['name'];?></a><br />
				<br />
				<?
					}
				}
				?>
				</ul>
		<? }elseif( $type == 'phpuploads' ){

			if( $value ){
				$value = explode("\n", $value);
			}
		?>
				<ul id="<?=$name;?>_files">
				<?
				$count=0;

				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$count++;
				?>
				<li id="item_<?=$name;?>_<?=$count;?>">
					<img src="/_lib/modules/phpupload/?func=preview&file=<?=$val;?>" id="file_<?=$name;?>_<?=$count;?>_thumb" /><br />
					<label id="file_<?=$name;?>_<?=$count;?>_label"><?=$val;?></label>
				</li>
				<?
					}
				}
				?>
				</ul>
		<?
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
		}elseif( $type == 'number' ){ ?>
			<?=number_format($value, 2);?>

		<? }elseif( $type == 'rating' ){
		?>
			<select name="<?=$field_name;?>" class="rating" disabled="disabled">
			    <option value="">Choose</option>
				<?=html_options($this->opts['rating'], $value, true);?>
			</select>
		<?
		}elseif( $type == 'coords' ){
		?>
			<input type="hidden" class="map" name="<?=$field_name;?>" value="<?=htmlspecialchars(substr($value,6,-1));?>" <? if( $readonly ){ ?>disabled<? } ?> size="50" <?=$attribs;?>>
		<?
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
			//$join_id=array_search('id',$vars['fields'][$vars['options'][$name]]);

			//$vars['options'][$name]=get_options(underscored($vars['options'][$name]),underscored(key($vars['fields'][$vars['options'][$name]])),NULL,$join_id);


			$table=underscored($vars['options'][$name]);

			foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
				if( $v!='separator' ){
					$field=$k;
					break;
				}
			}

			$raw_option = $vars['fields'][$vars['options'][$name]][$field];

			$cols = '';
			if( is_array($raw_option) ){
				$db_field_name=$this->db_field_name($vars['options'][$name], $field);

				$cols .= "".underscored($db_field_name)." AS `".underscored($field)."`"."\n";
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
						$id = $row['id']	;
					}

					$options[$id] = $row[underscored($field)];
				}
			}else{
				$parent_field = array_search('parent',$vars['fields'][$vars['options'][$name]]);

				if( $parent_field !== false ){
					$options = $this->get_children($vars['options'][$name],$parent_field);
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

		if( $_GET['option'] != 'login' ){
			$auth->check_admin();
		}

		if(
            $auth->user['admin']==2 and
            $_GET['option'] and $_GET['option']!='preferences' and
            $_GET['option']!='logout' and
            $_GET['option']!='login' and
            !array_key_exists($_GET['option'],$auth->user['privileges'])
        ){
			die('access denied');
		}

		//don't allow inline editing'
		$this->inline = false;

		//load blog handlers
		if( $vars['fields']['blog'] ){
		    $blog = new blog;
		}

		$this->language='en';

		if( file_exists('_tpl/admin/'.underscored($_GET['option']).'.php') ){
			$this->template(underscored($_GET['option']).'.php');
		}elseif( method_exists($this,$_GET['option']) ){
			$this->$_GET['option']();
		}elseif( isset($_GET['option']) ){
			$this->default_section($_GET['option']);
		}else{
			$this->main();
		}
	}

	function notify($subject){
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

		$mail->setHtml($msg);
		$mail->setFrom('auto@'.$_SERVER["HTTP_HOST"]);
		$mail->setSubject($subject);
		$result = $mail->send(array($from_email),'mail');
	}

	function submit($notify, $errors = array()){
		global $vars;
        $errors = array_merge($errors, $this->validate());

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
    			$this->notify();
    		}

    		return $this->id;
        }
	}

	function validate($data)
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
		if( $data['nospam']!='1' ){
		    $errors[] = 'nospam';
		}

		if( count($errors) ){
			$errors = array_values(array_unique($errors));
		}

		return $errors;
	}

	function build_query($field_arr,$data)
	{
		global $vars, $languages,$auth;

		$column_data = sql_query("SHOW COLUMNS FROM `".$this->table."`");

		$null_fields = array();
		foreach( $column_data as $v ){
			if( $v['Null']=='YES' ){
				$null_fields[] = $v['Field'];
			}
		}

		foreach( $field_arr as $k=>$v ){
			if( $v=='id' or $v=='related' or $v=='timestamp' or $v=='separator' or $v=='translated-from' or $v=='hidden' or $v=='polygon' ){
				continue;
			}

			if( $this->editable_fields and !in_array($k, $this->editable_fields) ){
				continue;
			}

			if( is_array($v) ){
				$this->build_query($v,$data);
			}else{
				$k = underscored($k);

				if( $v == 'position' and !$this->id ){
					//find last position
					$max_pos = sql_query("SELECT MAX(position) AS `max_pos` FROM `".$this->table."`", 1);
					$max_pos = $max_pos['max_pos']+1;

					$this->query .= "`$k`='".escape($max_pos)."',\n";
					continue;
				}elseif( $v == 'position' ){
					continue;
				}

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

				//leave passwords blank to keep
				if( $v=='password' and $data[$name]=='' ){
					continue;
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
					$parts = explode('/',$data[$name]);
					$date = $parts[2].'-'.$parts[1].'-'.$parts[0];

					$data[$name] = $date.' '.$data['time'][$name];
				}elseif( $v=='date' or $v=='dob' ){
					if( $data[$name] ){
						$parts = explode('/',$data[$name]);
						$data[$name] = $parts[2].'-'.$parts[1].'-'.$parts[0];
					}
				}elseif( $v=='file' ){
					if( $_FILES[$name]['error']=='UPLOAD_ERR_OK' ){
						check_table('files', $this->file_fields);

						$content = file_get_contents($_FILES[$name]['tmp_name']);

						$result = sql_query("INSERT INTO files SET
							date=NOW(),
							name='".escape($_FILES[$name]['name'])."',
							size='".escape( strlen($content) )."',
							type='".escape($_FILES[$name]['type'])."'
						");

						$data[$name] = sql_insert_id();

						//check folder exists
						if(!file_exists($vars['files']['dir'])){
							mkdir($vars['files']['dir']);
						}

						if( $vars['files']['dir'] ){
							file_put_contents($vars['files']['dir'].$data[$name],$content) or
								trigger_error("Can't save ".$vars['files']['dir'].$data[$name], E_ERROR);
						}else{
							sql_query("UPDATE files SET
								data='".escape($content)."'
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

						$old_files = explode("\n",$row[$name]);

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

					$data[$name] = implode("\n",$files);
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
				    if( $this->id ){
				        continue;
				    }

					$data[$name] = $_SERVER["REMOTE_ADDR"];
				}elseif( $v=='page-name' ){
					$data[$name] = str_to_pagename($data[$name]);
				}elseif( $v=='coords' ){
					$this->query.="`$k`=GeomFromText('POINT(".$data[$name].")'),\n";
					continue;
				}

				if( is_array($data[$name]) ){
					$data[$name] = implode("\n",$data[$name]);
				}

				if( (!isset($data[$name]) or $data[$name]==='') and in_array($k,$null_fields) ){
					$this->query .= "`$k`=NULL,\n";
				}else{
					$this->query .= "`$k`='".escape($data[$name])."',\n";
				}
			}
		}
	}

	function save($data)
	{
		global $vars, $languages, $auth;

		if( !isset($data) ){
			$data = $_POST;
		}

		$this->trigger_event('beforeSave', array('data'=>$data));

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

			if( $this->id and ($language_exists) ){
				sql_query("UPDATE `".$this->table."` SET
					".$this->query."
					WHERE $where_str
				");

				//log it
				if( table_exists('cms_logs') ){
					sql_query("INSERT INTO cms_logs SET
						user='".$auth->user['id']."',
						section='".escape($this->section)."',
						item='".escape($this->id)."',
						task='edit',
						details=''
					");
				}
			}else{
				sql_query("INSERT IGNORE INTO `".$this->table."` SET
					".$this->query."
				");

				if( $language=='en' ){
					$this->id = sql_insert_id();
				}

				//log it
				if( table_exists('cms_logs') ){
					sql_query("INSERT INTO cms_logs SET
						user='".$auth->user['id']."',
						section='".escape($this->section)."',
						item='".escape($this->id)."',
						task='add',
						details=''
					");
				}
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

		$this->trigger_event('save', array($this->id));

		$this->saved = true;

		return $this->id;
	}

	function trigger_event($event, $args)
	{
		global $cms_handlers;

		if( is_array($cms_handlers) ){
			foreach( $cms_handlers as $handler ){
				if(
				    (!$this->section or $handler['section']==$this->section)
				    and $handler['event'] == $event ){
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

		$this->section = $option;

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
			die('no fields');
		}


		if($_GET['edit']){
			$this->template('default_edit.php', true);
		}elseif( $_GET['view'] or !array_search('id', $vars['fields'][$this->section]) ){
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

	function preferences()
	{
		global $vars, $auth_config, $auth;

		if( isset($_POST['save']) ){
			if( $auth->user['admin']==1 ){
				sql_query("UPDATE  ".$auth_config['table']." SET password='".addslashes($_POST['password'])."' WHERE id='".escape($auth->user['id'])."'");
				$_SESSION['password']=$_POST['password'];

				$_SESSION['message']='Password saved';
			}else{
				$_SESSION['message']='Permission denied';
			}
		}

		$this->template('preferences.php',true);
	}

	function logout()
	{
		global $auth;

		$auth->logout();
		redirect("/");
	}
}
?>