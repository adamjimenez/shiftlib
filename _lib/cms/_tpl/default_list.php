<?
//check permissions
if( $auth->user['admin']!=1 and !$auth->user['privileges'][$this->section] ){
    die('access denied');
}

$field_id = in_array('id',$vars['fields'][$this->section]) ? array_search('id',$vars['fields'][$this->section]) : 'id';

//sortable
$sortable = in_array('position',$vars['fields'][$this->section]);

//distance options
$opts['distance']=array(
	3=>'3 miles',
	10=>'10 miles',
	15=>'15 miles',
	20=>'20 miles',
	30=>'30 miles',
	40=>'40 miles',
	50=>'50 miles',
	75=>'75 miles',
	100=>'100 miles',
	150=>'150 miles',
	200=>'200 miles',
);

if( in_array('select-distance',$vars['fields'][$this->section]) ){
	$opts['distance'][array_search('select-distance',$vars['fields'][$this->section])]='specified '.array_search('select-distance',$vars['fields'][$this->section]);
}

//bulk delete
if( $_POST['action']=='export' ){
	foreach( $_POST['items'] as $item ){
		$vars['content'][] = $this->get($this->section,$item);
	}

	$i=0;
	foreach( $vars['content'] as $row ){
		if($i==0){
			$j=0;
			foreach($row as $k=>$v){
				$data.='"'.$k.'",';
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
	header('Content-Disposition: attachment; filename="'.$this->section.'.csv"');

	die($data);
}

if( $_POST['action']=='delete' ){
	$this->delete_items($this->section, $_POST['items']);
}

if( $_POST['action']=='email' ){
	if( $_POST['select_all_pages'] ){
		$users = $this->get($this->section,$_GET);
	}else{
		$_POST['items'];

		$users = array();
		foreach( $_POST['items'] as $v ){
			$users[] = $this->get($this->section,$v);
		}
	}

	require(dirname(__FILE__).'/shiftmail.php');
}elseif( isset($_POST['custom_button']) ){
	global $content;
	$content = $this->get($this->section,$_GET);
	$cms_buttons[$_POST['custom_button']]['handler']();
}elseif( $_POST['sms'] ){
	$users = $this->get($this->section,$_GET);
	require(dirname(__FILE__).'/sms.php');
}elseif( $_POST['shiftmail'] ){
	$users = $this->get($this->section,$_GET);
	require(dirname(__FILE__).'/shiftmail.php');
}else{
	if( $_POST['select_all_pages'] and $_POST['section'] and $_POST['action']=='delete' ){
		$this->delete_all_pages($this->section,$_GET);
	}

	if( $_POST['export'] or $_GET['export_all'] ){
		set_time_limit(300);
		ob_end_clean();

		$conditions = $_POST['export'] ? $_GET : NULL;
		$sql = $this->conditions_to_sql($this->section, $conditions);
		$table = underscored($this->section);
		$field_id = in_array('id',$vars['fields'][$this->section]) ? array_search('id',$vars['fields'][$this->section]) : 'id';

		$query = "SELECT *
		FROM `$table` T_$table
			".$sql['joins']."
		".$sql['where_str']."
		GROUP BY
			T_".$table.".".$field_id."
		".$sql['having_str']."
		";
		
		global $db_connection;
		$result = mysqli_query($db_connection, $query);
		
	    if( $result===false ){
	        throw new Exception(mysqli_error());
	    }

		header('Pragma: cache');
		header('Content-Type: text/comma-separated-values; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$this->section.'.csv"');

		$i=0;
		while ($row = mysqli_fetch_assoc($result)) {
			$data='';

			if($i==0){
				$j=0;
				foreach($row as $k=>$v){
					$data.='"'.$k.'",';
					$headings[$j]=$k;
				}
				$data = substr($data,0,-1);
				$data .= "\n";

				$j++;
			}
			$j = 0;
			foreach($row as $k=>$v){
			    if(is_array($v)){
			    	$data .= '"'.str_replace('"', '""', serialize($v)).'",';
			    }else{
			    	$data .= '"'.str_replace('"', '""', $v).'",';
			    }

				$j++;
			}
			$data = substr($data,0,-1);
			$data .= "\n";
			$i++;

			print($data);
		}

		exit();
	}

	if( $_GET['xml'] ){
		header('Content-Type: text/xml');

		$vars['content']=$this->get($this->section,$_GET);

		$xml = new SimpleXMLElement('<subscribers />');

		foreach( $vars['content'] as $cust ){
			$subscriber = $xml->addChild('subscriber');

			foreach( $cust as $k=>$v ){
				$subscriber->addChild($k,htmlspecialchars($v));
			}
		}

		echo $xml->asXML();
		exit;
	}

	$limit = ($sortable) ? NULL : 25;

	$first_field_type = $vars['fields'][$this->section][$vars['labels'][$this->section][0]];

	$asc = ($first_field_type=='date' or $first_field_type=='timestamp') ? false : true;

	$conditions = $_GET;

	if( in_array('parent',$vars['fields'][$this->section]) ){
		$parent_field = array_search('parent',$vars['fields'][$this->section]);
	}

	if( $parent_field and !$conditions[underscored($parent_field)] ){
		$conditions[underscored($parent_field)]=0;
	}

	foreach( $auth->user['filters'][$this->section] as $k=>$v ){
		$conditions[$k]=$v;
	}

	$vars['content'] = $this->get($this->section, $conditions, $limit, NULL, $asc, 'list');
	$p = $this->p;

	foreach( $vars['fields'][$this->section] as $field=>$type ){
		if( $type == 'position' or $type == 'id' ){
			continue;
		}

		$fields[]=$field;
	}
?>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#file').on('change',changeFile);
});
</script>

<form method="get" id="search_form">
<input type="hidden" name="option" value="<?=$this->section;?>" />

<div class="top-row">
	
	<div id="basic" class="search-container">
		<input class="search-field form-control" type="text" name="s" id="s" value="<?=$_GET['s'];?>" tabindex="1" placeholder="Search">
		<button type="submit" class="btn btn-default" tabindex="5">Search <?=ucfirst($this->section);?></button>
		
		<a href="#" class="btn btn-default" onclick="toggle_advanced(true); return false;">Advanced search</a><br />
		
	</div>
	
	
	<div class="imp-exp-container">
		<a class="btn btn-primary" href="javascript:;" onclick="toggle_import()">Import</a>
		<a class="btn btn-primary" href="?option=<?=$_GET['option'];?>&export_all=1">Export all</a>
	</div>
	
	
</div>

<table width="100%">
<tr>
	<td>
		

		<? if( $parent_field ){ ?>
		<h3>
			<a href="?option=<?=$this->section;?>">Root</a>
			<?
			if($_GET[$parent_field]){
				$parent_id = $_GET[$parent_field];

				reset($vars['fields'][$this->section]);
				$label=key($vars['fields'][$this->section]);

				$parents=array();
				while( $parent[$parent_field]!=='0' ){
					$parent = sql_query("SELECT * FROM `".underscored($this->section)."` WHERE $field_id='".escape($parent_id)."'", 1);

					if( !$parent){
						break;
					}

					$parent_id = $parent[$parent_field];

					$parents[$parent['id']]=$parent[$label];

				}

				$parents=array_reverse($parents,true);

				foreach( $parents as $k=>$v ){
			?>
				&raquo; <a href="?option=<?=$this->section;?>&parent=<?=$k;?>"><?=$v;?></a>
			<?
				}
			}
			?>
		</h3>
		<? } ?>
	</td>

</table>


<div id="advanced" style="display:none;">
	<div style="padding:10px 0; margin:0 auto; text-align:center;">
		<fieldset>
		<legend>Advanced search</legend>
		<table class="box" border="0" cellspacing="0" cellpadding="3">
		<?
		foreach( $vars['fields'][$this->section] as $name=>$type ){
			if( in_array($name,$vars["non_searchable"][$this->section]) ){
				continue;
			}

			$field_name=underscored($name);

			$label=ucfirst(spaced($name));
		?>
			<? if( $type == 'file' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<input type="checkbox" name="<?=$field_name;?>" value="1" <? if( $_GET[$field_name] ){ ?>checked<? } ?> />
				</td>
			</tr>
			<?
			}elseif( $type == 'select' or $type=='radio' ){
				$options = $vars['options'][$name];
				if( !is_array($vars['options'][$name]) ){
					$table = underscored($vars['options'][$name]);

					reset($vars['fields'][$vars['options'][$name]]);

					foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
						if( $v!='separator' ){
							$field=$k;
							break;
						}
					}

					$db_field_name = $this->db_field_name($vars['options'][$name],$field);

					$cols = "`".underscored($db_field_name)."` AS `".underscored($field)."`"."\n";

					$rows = sql_query("SELECT id,$cols FROM $table ORDER BY `".underscored($db_field_name)."`");

					$options = array();
					foreach($rows as $row){
						$options[$row['id']]=$row[$field];
					}
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$name;?>">
					<option value=""></option>
					<?=html_options($options,$_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<?
			}elseif( $type == 'select-multiple' ){
				$value=array();
				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					$rows=sql_query("SELECT T1.value FROM cms_multiple_select T1
						INNER JOIN `".escape($vars['options'][$name])."` T2 ON T1.value=T2.$field_id
						WHERE
							section='".$this->section."' AND
							field='".escape($name)."' AND
							item='".$id."'
					");

					$vars['options'][$name]=get_options($vars['options'][$name],key($vars['fields'][$vars['options'][$name]]));
				}else{
					$rows=sql_query("SELECT value FROM cms_multiple_select
						WHERE
							section='".$this->section."' AND
							field='".escape($name)."' AND
							item='".$id."'
					");
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$field_name;?>[]" multiple="multiple" size="10" style="width:100%">
					<?=html_options($vars['options'][$name], $_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<?
			}elseif( $type == 'checkboxes' ){
				$value=array();
				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					$vars['options'][$name]=get_options(underscored($vars['options'][$name]),underscored(key($vars['fields'][$vars['options'][$name]])));
				}else{
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
                    <div style="max-height: 200px; width: 200px; overflow: scroll">
    				<?
    				$is_assoc = is_assoc_array($vars['options'][$name]);
                    foreach( $vars['options'][$name] as  $k=>$v ){
                        $val = $is_assoc ? $k : $v;
                    ?>
					<label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$val;?>" <? if( in_array($k,$_GET[$field_name]) ){ ?>checked="checked"<? } ?> /> <?=$v;?></label><br />
					<? } ?>
                    </div>
				</td>
			</tr>
			<? }elseif( $type == 'checkbox' or $type == 'deleted' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$field_name;?>">
					<option value=""></option>
					<?=html_options(array(1=>'yes',0=>'no'),$_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<? }elseif( $type == 'date' or $type=='timestamp' or $type=='month' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<div style="float:left">
						<select name="func[<?=$field_name;?>]">
						<option value=""></option>
						<?=html_options(array('='=>'On','>'=>'After','<'=>'Before'),$_GET['func'][$field_name]);?>
						</select>
					</div>
					<div style="float:left">
						<input type="text" id="<?=$name;?>" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" class="date" />
					</div>
				</td>
			</tr>
			<?
			}elseif( $type == 'dob' ){
				for( $i=1; $i<=60; $i++ ){
					$opts['age'][]=$i;
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$field_name;?>">
					<option value="">Any</option>
					<?=html_options($opts['age'],$_GET[$field_name]);?>
					</select>
					To
					<select name="func[<?=$field_name;?>]">
					<option value="">Any</option>
					<?=html_options($opts['age'],$_GET['func'][$field_name]);?>
					</select>
				</td>
			</tr>
			<? }elseif( $type == 'postcode' ){ ?>
			<tr>
				<th align="left" valign="top">Distance from <?=$label;?></th>
				<td>
					Within
					<select name="func[<?=$field_name;?>]">
					<option value=""></option>
						<?=html_options($opts['distance'],$_GET['func'][$field_name]);?>
					</select>
					of
					<input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="7">
				</td>
			</tr>
			<? }elseif( $type == 'int' or $type=='decimal' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<div style="float:left">
						<select name="func[<?=$field_name;?>]">
						<option value=""></option>
						<?=html_options(array('='=>'=','>'=>'>','<'=>'<'),$_GET['func'][$field_name]);?>
						</select>
					</div>
					<div style="float:left">
						<input type="text" id="<?=$name;?>" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" />
					</div>
				</td>
			</tr>
			<? }elseif( $type == 'text' or $type == 'hidden' or $type == 'email' or $type == 'mobile' or ($type=='id' and $vars["settings"][$this->section]['show_id']) ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td><input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="50"></td>
			</tr>
			<? } ?>
		<? } ?>
		</table>
		<br />
		<p align="center"><button type="submit">Search</button> &nbsp; <button type="button" onclick="toggle_advanced(false)">Cancel</button></p>
		</fieldset>
		<br />
		<br />
	</div>
</div>
</form>

<div id="progress" title="Importing"></div>

<div id="csv" style="display:none; margin:0 auto; text-align:center;">
	<fieldset>
	<legend>Import</legend>
	<form method="post" id="importForm" enctype="multipart/form-data" onSubmit="checkForm(); return false;">
	<input type="hidden" name="section" value="<?=$this->section;?>" />
	Upload a <strong>comma delimited csv</strong> file.<br />
	<br />

	<div>
		File: <span id="file_field"><input type="file" id="file" name="file" /></span>
	</div>

	<div id="csv_loaded" style="display:none; width:auto;">
		<div id="csv_preview" style="margin:1em; border: 1px solid #000; padding:0.5em; overflow:auto; width:750px;"></div>

		<p>Match up the columns with the spreadsheet columns below.</p>

		<table width="310" class="box">
		<tr>
			<th>List column</th>
			<th>&nbsp;</th>
			<th>File column</th>
		</tr>
		<?
		foreach( $fields as $field ){
			if( $field=='email' ){
				$style='font-weight:bold;';
			}else{
				$style='';
			}
		?>
		<tr>
			<td width="100" style="<?=$style;?>"><?=$field;?><? if( $field=='email' ){ ?> *<? } ?></td>
			<td width="100">should receive</td>
			<td>
				<select id="field_<?=$field;?>" name="fields[<?=$field;?>]" style="width:100px; font-weight:bold;">
					<option value="">Select Column</option>
				</select>
			</td>
		</tr>
		<? } ?>
		</table>
		<br />
		<p>
			<label><input type="checkbox" name="update" value="1" /> update existing?</label>
		</p>
    	<p>
			<label><input type="checkbox" name="validate" value="1" /> validate?</label>
		</p>
		<br />
		<p>
			<button type="submit" id="submitButton">Import data</button>
			&nbsp; &nbsp;
		</p>
		<br />
		</div>


		<button type="button" onclick="toggle_import()">Cancel</button>

	</form>
	</fieldset>
	<br />
	<br />
</div>

<? require(dirname(__FILE__).'/list.php'); ?>

<br>
<br>

<div>
	<fieldset style="padding:10px;">
		<legend>With all results</legend>
		<form method="post" style="display:inline">
		<input type="hidden" name="export" value="1" />
			<button  class="btn btn-default" type="submit">Export</button>
		</form>
		<? /*
		<? if( $vars['settings'][$this->section]['shiftmail'] ){ ?>
		<form method="get" style="display:inline" action="http://mail.shiftcreate.com/list/import_subscribers">
		<input type="hidden" name="source_type" value="xml" />
		<input type="hidden" name="crm_xml" value="http://<?=$_SERVER['HTTP_HOST'];?><?=$_SERVER['REQUEST_URI'];?>&xml=1" />
		<input type="hidden" name="crm_username" value="<?=$_SESSION[$auth->cookie_prefix.'_email'];?>" />
		<input type="hidden" name="crm_password" value="<?=md5($auth->secret_phrase.$_SESSION[$auth->cookie_prefix.'_password']);?>" />
			<button type="submit">ShiftMail</button>
		</form>
		<? } ?>
		*/ ?>
		<? if( $vars['settings'][$this->section]['shiftmail'] ){ ?>
		<form method="post" style="display:inline">
		<input type="hidden" name="shiftmail" value="1">
			<button type="submit">Email</button>
		</form>
		<? } ?>
		<? if( $vars['settings'][$this->section]['sms'] ){ ?>
		<form method="post" style="display:inline">
		<input type="hidden" name="sms" value="1">
			<button type="submit">SMS</button>
		</form>
		<? } ?>
		<?
		foreach( $cms_buttons as $k=>$button ){
			if( $this->section==$button['section'] and $button['page']=='list' ){
                require('includes/button.php');
    		}
    	}
    	?>
	</fieldset>
</div>

</div>

<script type="text/javascript">
$(document).keydown(function(e){
    if( !$(e.target).is('input, textarea, select, [contenteditable]') && !e.ctrlKey ){
        $('#s').focus();
    }
});

//omit empty fields on search
$("#search_form").submit(function() {
    $(this).find(":input").filter(function() {
	    return !$(this).val();
	}).prop("disabled", true);
    return true; // ensure form still submits
});
</script>

<?
}
?>
