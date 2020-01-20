<?php
//check permissions
if (1 != $auth->user['admin'] and !$auth->user['privileges'][$this->section]) {
    die('access denied');
}

// search filters
if ($_POST['delete_filter']) {
    $qs = http_build_query($_GET);
    
    sql_query("DELETE FROM cms_filters WHERE
		user = '" . escape($auth->user['id']) . "' AND
		section = '" . escape($this->section) . "' AND
		`filter` = '" . escape($qs) . "'
	");
} elseif ($_POST['save_filter']) {
    $qs = http_build_query($_GET);
    
    sql_query("INSERT INTO cms_filters SET
		user = '" . escape($auth->user['id']) . "',
		section = '" . escape($this->section) . "',
		name = '" . escape($_POST['save_filter']) . "',
		`filter` = '" . escape($qs) . "'
	");
}

// search filters
$filters = sql_query("SELECT * FROM cms_filters WHERE 
	user = '" . escape($auth->user['id']) . "' AND
	section = '" . escape($this->section) . "'
");

$filter_exists = false;
$params = $_GET;

$qs = http_build_query($params);
foreach ($filters as $v) {
    if ($v['filter'] == $qs) {
        $filter_exists = true;
    }
}

// custom button handler
if ($_POST['custom_button']) {
    if ('list' == $cms_buttons[$_POST['custom_button']]['page'] and $cms_buttons[$_POST['custom_button']]['handler']) {
        $items = [];
        if ($_POST['select_all_pages'] or !$_POST['id']) {
            $items = $this->get($_GET['option'], $_GET);
        } elseif ($_POST['id']) {
            foreach ($_POST['id'] as $v) {
                $items[] = $this->get($_POST['section'], $v);
            }
        }

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
<div class="col-lg-12 p-0">

    <div class="d-flex my-1">
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
                        $field_name = underscored($name);
                        $component = $this->get_component($type); ?>
                        <div>
                            <?=$component->search_field($name, $_GET[$field_name]); ?>
                        </div>
                        <?php
                    } ?>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
				</form>
            </div>
        </div>
    </div>
		
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
	
    <div class="col-12 p-0">
        <div class="card">
            <div class="card-body">
                
                <div class="toolbar top-row mt-1 mb-3">
                    <span class="holder"></span>
                    
                    <div class="dropdown" style="display: inline-block;">
                        <button class="btn btn-secondary" type="button" id="dropdownMenuButton<?=underscored($button['section']);?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton<?=underscored($button['section']);?>">
                			<?php
                            foreach ($cms_buttons as $k => $button) {
                                if (($this->section == $button['section'] || in_array($this->section, $button['section'])) && 'list' == $button['page']) {
                                    require('includes/button.php');
                                }
                            } ?>
                        </div>
                    </div>
                    
                </div>
            
                <h1 class="header-title"><?=ucwords($this->section);?></h1>
                
            	<?php
                $conditions = $_GET;
                unset($conditions['option']);
                
                $qs = http_build_query(['s' => $conditions]);
                require(dirname(__FILE__) . '/list.php');
                ?>
    
            </div>
        </div>
    </div>
</div>

	</div>
</div>
</div>

<script>
    jQuery(document).ready(function() {
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
    
    jQuery(document).ready(function() {
        //omit empty fields on search
        $("#search_form").submit(function() {
            $(this).find(":input").filter(function() {
        	    return !$(this).val();
        	}).prop("disabled", true);
            return true; // ensure form still submits
        });
    });
</script>

<?php
}
?>