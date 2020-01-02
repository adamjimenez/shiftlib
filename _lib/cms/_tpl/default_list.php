<?php
function export_all() {
    global $auth, $db_connection, $cms;
    
    set_time_limit(300);
    ob_end_clean();

    $conditions = $_POST['export'] ? $_GET : null;
    
    // staff perms
    foreach ($auth->user['filters'][$cms->section] as $k => $v) {
        $conditions[$k] = $v;
    }
    
    $sql = $cms->conditions_to_sql($cms->section, $conditions);
    $table = underscored($cms->section);
    $field_id = in_array('id', $vars['fields'][$cms->section]) ? array_search('id', $vars['fields'][$cms->section]) : 'id';

    $query = "SELECT *
	FROM `$table` T_$table
		" . $sql['joins'] . '
	' . $sql['where_str'] . '
	GROUP BY
		T_' . $table . '.' . $field_id . '
	' . $sql['having_str'];
    
    $result = mysqli_query($db_connection, $query);
    
    if (false === $result) {
        throw new Exception(mysqli_error(), E_ERROR);
    }

    header('Content-Type: text/comma-separated-values; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $cms->section . '.csv"');

    $i = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $data = '';

        if (0 == $i) {
            $j = 0;
            foreach ($row as $k => $v) {
                $data .= '"' . $k . '",';
                $headings[$j] = $k;
            }
            $data = substr($data, 0, -1);
            $data .= "\n";

            $j++;
        }
        $j = 0;
        foreach ($row as $k => $v) {
            if (is_array($v)) {
                $data .= '"' . str_replace('"', '""', serialize($v)) . '",';
            } else {
                $data .= '"' . str_replace('"', '""', $v) . '",';
            }

            $j++;
        }
        $data = substr($data, 0, -1);
        $data .= "\n";
        $i++;

        print($data);
    }

    exit();
}

//check permissions
if (1 != $auth->user['admin'] and !$auth->user['privileges'][$this->section]) {
    die('access denied');
}

$field_id = in_array('id', $vars['fields'][$this->section]) ? array_search('id', $vars['fields'][$this->section]) : 'id';

//sortable
$sortable = in_array('position', $vars['fields'][$this->section]);

// check table exists
$this->check_table('cms_filters', $this->cms_filters);

// search filters
if ($_POST['delete_filter']) {
    $qs = http_build_query($_GET);
    
    sql_query("DELETE FROM cms_filters WHERE
		user = '" . escape($auth->user['id']) . "' AND
		section = '" . escape($this->section) . "' AND
		`filter` = '" . escape($qs) . "'
	");
}

if ($_POST['save_filter']) {
    $qs = http_build_query($_GET);
    
    sql_query("INSERT INTO cms_filters SET
		user = '" . escape($auth->user['id']) . "',
		section = '" . escape($this->section) . "',
		name = '" . escape($_POST['save_filter']) . "',
		`filter` = '" . escape($qs) . "'
	");
}

$filters = sql_query("SELECT * FROM cms_filters WHERE 
	user = '" . escape($auth->user['id']) . "' AND
	section = '" . escape($this->section) . "'
");

$filter_exists = false;
$params = $_GET;
unset($params['option']);

$qs = http_build_query($params);
foreach ($filters as $v) {
    if ($v['filter'] == $qs) {
        $filter_exists = true;
    }
}

//bulk export
if ($_POST['select_all_pages']) {
    switch($_POST['action']) {
        case 'export':
            export_all();
        break;
        case 'delete':
           $this->delete_all_pages($this->section, $_GET);
        break;
    }
} else {
    switch($_POST['action']) {
        case 'export':
            $this->export_items($this->section, $_POST['id']);
        break;
        case 'delete':
            $this->delete_items($this->section, $_POST['id']);
        break;
    }
}

if ($_POST['custom_button']) {
    if ('list' == $cms_buttons[$_POST['custom_button']]['page']) {
        if ($_POST['select_all_pages'] or !$_POST['id']) {
            $items = $this->get($this->section, $_GET);
        } elseif ($_POST['id']) {
            $_POST['id'];
    
            $items = [];
            foreach ($_POST['id'] as $v) {
                $items[] = $this->get($this->section, $v);
            }
        }
    }
    
    if ($cms_buttons[$_POST['custom_button']]['handler']) {
        $cms_buttons[$_POST['custom_button']]['handler']($items);
    }
} else {

    foreach ($vars['fields'][$this->section] as $field => $type) {
        if ('position' == $type) {
            continue;
        }

        $fields[] = $field;
    } 
?>

<div class="main-content-inner">
    <div class="row">

<!-- tab start -->
<div class="col-lg-12 mt-5">

	<div class="row m-3">
		<div class="col-sm-12">
		    <div class="d-flex">
		        
    			<form method="get" class="flex-grow-1" style="flex: 1;">
    				<input type="hidden" name="option" value="<?=$this->section; ?>" />
    		
    				<input class="search-field form-control" type="text" name="s" id="s" value="<?=$_GET['s']; ?>" tabindex="1" placeholder="Search">
    			</form>
    			
    			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#searchModal">Advanced search</button>
    			
    			<?php if (count($_GET) > 1) { ?>
    				<?php if ($filter_exists) { ?>
    				<button type="button" class="btn btn-default delete_filter" title="Delete filter"><i class="fas fa-trash"></i></button>
    				<?php } else { ?>
    				<button type="button" class="btn btn-default save_filter" title="Save filter"><i class="fas fa-save"></i></button>
    				<?php } ?>
    			<?php } ?>
    			
			</div>
		</div>
	</div>
	

    <!-- Modal -->
    <div class="modal fade" id="searchModal">
        <div class="modal-dialog">
            <div class="modal-content">
				<form method="get" id="search_form">
				<input type="hidden" name="option" value="<?=$this->section; ?>" />
				
				<!-- fake fields are a workaround for chrome autofill -->
				<div style="overflow: none; height: 0px;background: transparent;" data-description="dummyPanel for Chrome auto-fill issue">
					<input type="text" style="height:0;background: transparent; color: transparent;border: none;" data-description="dummyUsername"></input>
					<input type="password" style="height:0;background: transparent; color: transparent;border: none;" data-description="dummyPassword"></input>
				</div>
				
                <div class="modal-header">
                    <h5 class="modal-title">Advanced search</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                	

	<?php
    $this->set_section($this->section);
    $this->content = $_GET;
    
    foreach ($vars['fields'][$this->section] as $name => $type) {
        if (in_array($name, $vars['non_searchable'][$this->section])) {
            continue;
        }

        $field_name = underscored($name);
        $label = ucfirst(spaced($name));
        $component = $this->get_component($type);
        ?>
        <div>
            <?=$component->search_field($name, $_GET[$field_name]);?>
        </div>
        <?php
    } ?>
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

						<input type="hidden" name="section" value="<?=$this->section; ?>" />
						Upload a <strong>comma delimited csv</strong> file.<br>
						<br>
					
						<div>
							File: <span id="file_field"><input type="file" id="file" name="file"></span>
						</div>
					
						<div id="csv_loaded" style="display:none; width:auto;">
							<div id="csv_preview" style="margin:1em; border: 1px solid #000;"></div>
					
							<p>Match up the columns with the spreadsheet columns below.</p>
					
							<table width="310" class="box">
							<tr>
								<th>List column</th>
								<th>&nbsp;</th>
								<th>File column</th>
							</tr>
							<?php
                            foreach ($fields as $field) {
                                if ('email' == $field) {
                                    $style = 'font-weight:bold;';
                                } else {
                                    $style = '';
                                } ?>
							<tr>
								<td width="100" style="<?=$style; ?>"><?=$field; ?><?php if ('email' == $field) { ?> *<?php } ?></td>
								<td width="100">should receive</td>
								<td>
									<select id="field_<?=$field; ?>" name="fields[<?=$field; ?>]" style="width:100px; font-weight:bold;">
										<option value="">Select Column</option>
									</select>
								</td>
							</tr>
							<?php
                            } ?>
							</table>
							<br />
							<p>
								<label><input type="checkbox" name="update" value="1" /> update existing?</label>
							</p>
					    	<p>
								<label><input type="checkbox" name="validate" value="1" /> validate?</label>
							</p>
						</div>

	                </div>
	                <div class="modal-footer">
	                    <button type="submit" class="btn btn-primary">Import</button>
	                </div>
	            </div>
	        </div>
	    </div>
</form>

    <?php
    /*
	<table width="100%">
	<tr>
		<td>
	
			<?php if ($parent_field) { ?>
			<h3>
				<a href="?option=<?=$this->section;?>">Root</a>
				<?php
                if ($_GET[$parent_field]) {
                    $parent_id = $_GET[$parent_field];
    
                    reset($vars['fields'][$this->section]);
                    $label = key($vars['fields'][$this->section]);
    
                    $parents = [];
                    while ('0' !== $parent[$parent_field]) {
                        if (!$parent) {
                            break;
                        }
    
                        $parent_id = $parent[$parent_field];
    
                        $parents[$parent['id']] = $parent[$label];
                    }
    
                    $parents = array_reverse($parents, true);
    
                    foreach ($parents as $k => $v) {
                        ?>
					&raquo; <a href="?option=<?=$this->section; ?>&parent=<?=$k; ?>"><?=$v; ?></a>
				<?php
                    }
                }
                ?>
			</h3>
			<?php } ?>
		</td>
	</tr>
	</table>
	*/
	?>

	<?php
    $conditions = $_GET;
    unset($conditions['option']);
    
    $qs = http_build_query(['s' => $params]);
    require(dirname(__FILE__) . '/list.php'); ?>
	
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
