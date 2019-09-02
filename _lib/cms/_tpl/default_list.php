<?php
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

// search filters
check_table('cms_filters', $this->cms_filters);

if ($_POST['delete_filter']) {
	$qs = http_build_query($_GET);
	
	sql_query("DELETE FROM cms_filters WHERE
		user = '".escape($auth->user['id'])."' AND
		section = '".escape($this->section)."' AND
		`filter` = '".escape($qs)."'
	");
}

if( $_POST['save_filter'] ){
	$qs = http_build_query($_GET);
	
	sql_query("INSERT INTO cms_filters SET
		user = '".escape($auth->user['id'])."',
		section = '".escape($this->section)."',
		name = '".escape($_POST['save_filter'])."',
		`filter` = '".escape($qs)."'
	");
}

$filters = sql_query("SELECT * FROM cms_filters WHERE 
	user = '".escape($auth->user['id'])."' AND
	section = '".escape($this->section)."'
");

$filter_exists = false;
$qs = http_build_query($_GET);
foreach($filters as $v) {
	if ($v['filter'] == $qs) {
		$filter_exists = true;
	}
}

//bulk export
if( $_POST['action']=='export' ){
	$this->export_items($this->section, $_POST['id']);
}

if( $_POST['action']=='delete' ){
	$this->delete_items($this->section, $_POST['id']);
}

if( $_POST['action']=='email' ){
	if( $_POST['select_all_pages'] ){
		$users = $this->get($this->section,$_GET);
	}else{
		$_POST['id'];

		$users = array();
		foreach( $_POST['id'] as $v ){
			$users[] = $this->get($this->section,$v);
		}
	}

	require(dirname(__FILE__).'/shiftmail.php');
}elseif( $_POST['custom_button'] ){
	if ($cms_buttons[$_POST['custom_button']]['page']=='list') {
		if( $_POST['select_all_pages'] or !$_POST['id'] ){
			$items = $this->get($this->section, $_GET);
		} else if($_POST['id']) {
			$_POST['id'];
	
			$items = array();
			foreach( $_POST['id'] as $v ){
				$items[] = $this->get($this->section, $v);
			}
		}
	}
	if($cms_buttons[$_POST['custom_button']]['handler']) {
		$cms_buttons[$_POST['custom_button']]['handler']($items);
	}
}elseif( $_POST['sms'] ){
	$users = $this->get($this->section, $_GET);
	require(dirname(__FILE__).'/sms.php');
}elseif( $_POST['shiftmail'] ){
	$users = $this->get($this->section, $_GET);
	require(dirname(__FILE__).'/shiftmail.php');
}else{
	if( $_POST['select_all_pages'] and $_POST['section'] and $_POST['action']=='delete' ){
		$this->delete_all_pages($this->section, $_GET);
	}

	if( $_POST['export'] or $_GET['export_all'] ){
		set_time_limit(300);
		ob_end_clean();

		$conditions = $_POST['export'] ? $_GET : NULL;
		
		// staff perms
		foreach( $auth->user['filters'][$this->section] as $k=>$v ){
			$conditions[$k]=$v;
		}
		
		$sql = $this->conditions_to_sql($this->section, $conditions);
		$table = underscored($this->section);
		$field_id = in_array('id',$vars['fields'][$this->section]) ? array_search('id',$vars['fields'][$this->section]) : 'id';

		$query = "SELECT *
		FROM `$table` T_$table
			".$sql['joins']."
		".$sql['where_str']."
		GROUP BY
			T_".$table.".".$field_id."
		".$sql['having_str'];
		
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

		$vars['content']=$this->get($this->section, $_GET);

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
	unset($conditions['option']);
	/*
	foreach($_GET as $k=>$v) {
		if (in_array($k, $vars['fields'][$this->section])) {
			$conditions[$k] = $v;
		}
	}
	*/

	if( in_array('parent',$vars['fields'][$this->section]) ){
		$parent_field = array_search('parent',$vars['fields'][$this->section]);
	}

	if( $parent_field and !count($conditions) ){
		$conditions[underscored($parent_field)] = 0;
	}

	// staff perms
	foreach( $auth->user['filters'][$this->section] as $k=>$v ){
		$conditions[$k]=$v;
	}
	//debug($conditions, 1);

	$vars['content'] = $this->get($this->section, $conditions, $limit, NULL, $asc, 'list');
	$p = $this->p;

	foreach( $vars['fields'][$this->section] as $field=>$type ){
		if( $type == 'position' ){
			continue;
		}

		$fields[]=$field;
	}
?>

<div class="main-content-inner">
    <div class="row">

<!-- tab start -->
<div class="col-lg-12 mt-5">


	<div class="row">
		<div class="col-sm-6">
			<form method="get">
				<input type="hidden" name="option" value="<?=$this->section;?>" />
		
				<input class="search-field form-control" type="text" name="s" id="s" value="<?=$_GET['s'];?>" tabindex="1" placeholder="Search">
				
				<br />
			</form>
		</div>
		
		<div class="col-sm-6">
			
			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#searchModal">Advanced search</button>
			
			<?php if (count($_GET)>1) { ?>
				<?php if ($filter_exists) { ?>
				<button type="button" class="btn btn-default delete_filter">Delete filter</button>
				<?php } else { ?>
				<button type="button" class="btn btn-default save_filter">Save filter</button>
				<?php } ?>
			<?php } ?>
			
			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#importModal">Import</button>
			<a class="btn btn-primary" href="?option=<?=$_GET['option'];?>&export_all=1">Export all</a>
		</div>
	</div>
	

    <!-- Modal -->
    <div class="modal fade" id="searchModal">
        <div class="modal-dialog">
            <div class="modal-content">
				<form method="get" id="search_form">
				<input type="hidden" name="option" value="<?=$this->section;?>" />
				
                <div class="modal-header">
                    <h5 class="modal-title">Advanced search</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                	

	<?php
	foreach( $vars['fields'][$this->section] as $name=>$type ){
		if( in_array($name,$vars["non_searchable"][$this->section]) ){
			continue;
		}

		$field_name = underscored($name);
		$label = ucfirst(spaced($name));
		
		switch ($type) {
			case 'file':
		?>
	    <div>
	    	<input type="checkbox" name="<?=$field_name;?>" value="1" <?php if( $_GET[$field_name] ){ ?>checked<?php } ?>>
	    	<label for="<?=underscored($name);?>" class="col-form-label"><?=$label;?></label>
	    </div>
		<?php
			break;
			case 'checkbox':
			case 'deleted':
			case 'read':
		?>
	    <div>
	    	<label for="<?=underscored($name);?>" class="col-form-label"><?=$label;?></label><br>
			<select name="<?=$field_name;?>">
				<option value=""></option>
				<?=html_options(array(1=>'Yes', 0=>'No'),$_GET[$field_name]);?>
			</select>
			<br>
			<br>
	    </div>
		<?php
			break;
			case 'select':
			case 'radio':
				$options = $vars['options'][$name];
				if( !is_array($vars['options'][$name]) ){
					reset($vars['fields'][$vars['options'][$name]]);
	
					$conditions = array();
					foreach( $auth->user['filters'][$vars['options'][$name]] as $k=>$v ){
						$conditions[$k]=$v;
					}
					
					$table = underscored($vars['options'][$name]);
					$db_field_name = $this->db_field_name($vars['options'][$name],$field);
					$cols = "`".underscored($db_field_name)."` AS `".underscored($field)."`"."\n";
					$rows = sql_query("SELECT id,$cols FROM $table ORDER BY `".underscored($db_field_name)."`");
					
					$options = array();
					foreach($rows as $v) {
						$options[$v['id']] = current($v);
					}
				}
		?>
	    <div>
	    	<?=$label;?>
	    </div>
		<select name="<?=$name;?>[]" multiple size="4">
			<option value=""></option>
			<?=html_options($options,$_GET[$field_name]);?>
		</select>
		<br>
		<br>
		<?php
			break;
			case 'checkboxes':
				$value=array();
				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					$vars['options'][$name]=get_options(underscored($vars['options'][$name]),underscored(key($vars['fields'][$vars['options'][$name]])));
				}
		?>
	    <div>
	    	<label for="<?=underscored($name);?>" class="col-form-label"><?=$label;?></label>
	    </div>
        <div style="max-height: 200px; width: 200px; overflow: scroll">
			<?php
			$is_assoc = is_assoc_array($vars['options'][$name]);
            foreach( $vars['options'][$name] as  $k=>$v ){
                $val = $is_assoc ? $k : $v;
            ?>
			<label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$val;?>" <?php if( in_array($k,$_GET[$field_name]) ){ ?>checked="checked"<?php } ?> /> <?=$v;?></label><br />
			<?php } ?>
        </div>
		<?php
			break;
			case 'date':
			case 'timestamp':
			case 'month':
			case 'datetime':
		?>
		<div>
		    <label class="col-form-label"><?=$label;?></label>
		    
		    <div>
				<div style="float:left">
					From&nbsp;
				</div>
				<div style="float:left">
					<input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" data-type="date" autocomplete="off">
				</div>
				<div style="float:left">
					&nbsp;To&nbsp;
				</div>
				<div style="float:left">
					<input type="text" name="end[<?=$field_name;?>]" value="<?=$_GET['end'][$field_name];?>" size="8" data-type="date" autocomplete="off">
				</div>
				<br style="clear: both;">
			</div>
		</div>
		<br>
		
		<?php
			break;
			case 'dob':
				for( $i=1; $i<=60; $i++ ){
					$opts['age'][]=$i;
				}
		?>

		<?=$label;?><br>
		<select name="<?=$field_name;?>">
			<option value="">Any</option>
			<?=html_options($opts['age'],$_GET[$field_name]);?>
			</select>
			To
			<select name="func[<?=$field_name;?>]">
			<option value="">Any</option>
			<?=html_options($opts['age'],$_GET['func'][$field_name]);?>
		</select>
		<br>
		
		<?php
			break;
			case 'postcode':
		?>
			<div>
				Distance from <?=$label;?><br>
				
				Within
				<select name="func[<?=$field_name;?>]">
				<option value=""></option>
					<?=html_options($opts['distance'],$_GET['func'][$field_name]);?>
				</select>
				of
				<input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="7">
			</div>
		<?php
			break;
			case 'int':
			case 'decimal':
		?>
			<?=$label;?><br>

			<div>
				<div style="float:left">
					<select name="func[<?=$field_name;?>]">
					<option value=""></option>
					<?=html_options(array('='=>'=','!='=>'!=','>'=>'>','<'=>'<'),$_GET['func'][$field_name]);?>
					</select>
				</div>
				<div style="float:left">
					<input type="text" id="<?=$name;?>" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" />
				</div>
				<br style="clear: both;">
			</div>
		<?php
			break;
			case 'text':
			case 'hidden':
			case 'email':
			case 'mobile':
			case 'id':
		?>
	    <label for="<?=underscored($name);?>" class="col-form-label"><?=$label;?></label>
		<input type="text" class="form-control" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>">
		<?php
			break;
		}
	}
	?>
	</table>


                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
				</form>
            </div>
        </div>
    </div>
		
    <!-- Modal -->
    <form method="post" id="importForm" enctype="multipart/form-data" onSubmit="checkForm(); return false;">
	    <div class="modal fade" id="importModal">
	        <div class="modal-dialog">
	            <div class="modal-content">
	                <div class="modal-header">
	                    <h5 class="modal-title">Import</h5>
	                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
	                </div>
	                <div class="modal-body">

						<input type="hidden" name="section" value="<?=$this->section;?>" />
						Upload a <strong>comma delimited csv</strong> file.<br>
						<br>
					
						<div>
							File: <span id="file_field"><input type="file" id="file" name="file"></span>
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
							<?php
							foreach( $fields as $field ){
								if( $field=='email' ){
									$style='font-weight:bold;';
								}else{
									$style='';
								}
							?>
							<tr>
								<td width="100" style="<?=$style;?>"><?=$field;?><?php if( $field=='email' ){ ?> *<?php } ?></td>
								<td width="100">should receive</td>
								<td>
									<select id="field_<?=$field;?>" name="fields[<?=$field;?>]" style="width:100px; font-weight:bold;">
										<option value="">Select Column</option>
									</select>
								</td>
							</tr>
							<?php } ?>
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

	                </div>
	                <div class="modal-footer">
	                    <button type="submit" class="btn btn-primary">Save</button>
	                </div>
	            </div>
	        </div>
	    </div>
</form>

	<table width="100%">
	<tr>
		<td>
	
			<?php if( $parent_field ){ ?>
			<h3>
				<a href="?option=<?=$this->section;?>">Root</a>
				<?php
				if($_GET[$parent_field]){
					$parent_id = $_GET[$parent_field];
	
					reset($vars['fields'][$this->section]);
					$label=key($vars['fields'][$this->section]);
	
					$parents=array();
					while( $parent[$parent_field]!=='0' ){
	
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
				<?php
					}
				}
				?>
			</h3>
			<?php } ?>
		</td>
	</tr>
	</table>

	<div id="progress" title="Importing"></div>

	<?php 
	$conditions = $_GET;
	unset($conditions['option']);
		
	require(dirname(__FILE__).'/list.php');
	?>
	
	<br>
	<br>
	<div>
		<fieldset style="padding:10px;">
			<legend>With results</legend>
			<form method="post" style="display:inline">
			<input type="hidden" name="export" value="1" />
				<button class="btn btn-default" type="submit">Export</button>
			</form>
			<?php if( $vars['settings'][$this->section]['shiftmail'] ){ ?>
			<form method="post" style="display:inline">
			<input type="hidden" name="shiftmail" value="1">
				<button class="btn btn-default" type="submit">Email</button>
			</form>
			<?php } ?>
			<?php
			foreach( $cms_buttons as $k=>$button ){
				if( $this->section==$button['section'] and $button['page']=='list-all' ){
	                require('includes/button.php');
	    		}
	    	}
	    	?>
		</fieldset>
	</div>

</div>

	</div>
</div>
</div>

<script>
jQuery(document).ready(function() {
	$('#file').on('change', changeFile);
	
	$('.save_filter').click(function() {
		var filter = prompt('Save filter', 'New filter');
		
		if (filter != null) {
			$('<form method="post"><input type="hidden" name="save_filter" value="'+filter+'"></form>').appendTo('body').submit();
		}
	});
	
	$('.delete_filter').click(function() {
		if (window.confirm("Delete this filter?")) { 
			$('<form method="post"><input type="hidden" name="delete_filter" value="1"></form>').appendTo('body').submit();
		}
	});
});
</script>
<script>
/*
$(document).keydown(function(e){
    if( !$(e.target).is('input, textarea, select, [contenteditable]') && !e.ctrlKey ){
        $('#s').focus();
    }
});
*/

//omit empty fields on search
$("#search_form").submit(function() {
    $(this).find(":input").filter(function() {
	    return !$(this).val();
	}).prop("disabled", true);
    return true; // ensure form still submits
});
</script>

<?php
}
?>
