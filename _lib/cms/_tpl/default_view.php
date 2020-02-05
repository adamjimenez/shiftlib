<?php
// check permissions
if (1 != $auth->user['admin'] and !$auth->user['privileges'][$this->section]) {
    die('access denied');
}

$this->set_section($this->section, $_GET['id']);
$content = $this->content;

// get id if it exists
$id = $content['id'];

// mark as read
if ('0' === $content['read']) {
    $content['read'] = 1;
    $this->save($content);
}

$has_logs = table_exists('cms_logs');

$has_privileges = false;
if (
    1 == $auth->user['admin'] and
    underscored($this->section) == $auth->table and
    1 < $this->content['admin']
) {
    $has_privileges = true;
}

if ($_POST['custom_button']) {
    $cms_buttons[$_POST['custom_button']]['handler']($_GET['id']);

    $content = $this->get($this->section, $_GET['id']);
}

// get all items
if ($_POST['select_all_pages']) {
    $conditions = [];
    foreach ($vars['fields'][$_POST['section']] as $k => $v) {
        if (('select' == $v or 'combo' == $v or 'radio' == $v) and $vars['options'][$k] == $_GET['option']) {
            $conditions[$k] = escape($this->id);
            break;
        }
    }

    $items = $this->get($_POST['section'], $conditions);
    
    $_POST['items'] = [];
    foreach ($items as $v) {
        $_POST['items'][] = $v['id'];
    }
}

// delete this item
if ($_POST['delete'] and $this->id) {
    $this->delete_items($this->section, $this->id);
    redirect('?option=' . $this->section);
}

//label
$label = $this->get_label();

$title = ucfirst($this->section) . ' | ' . ($label ? $label : '&lt;blank&gt;');

// previous / next links
if (isset($content['position'])) {
    $conditions = $_GET;
    unset($conditions['id']);

    $qs = http_build_query($conditions);

    $conditions['position'] = $content['position'];
    $conditions['func']['position'] = '<';

    $prev = $this->get($this->section, $conditions, 1, null, false);

    $conditions['func']['position'] = '>';

    $next = $this->get($this->section, $conditions, 1);

    //var_dump($prev); exit;

    if ($prev) {
        $prev_link = '?id=' . $prev['id'] . '&' . $qs;
    }

    if ($next) {
        $next_link = '?id=' . $next['id'] . '&' . $qs;
    }
}


$qs_arr = $_GET;
unset($qs_arr['option']);
unset($qs_arr['view']);
unset($qs_arr['id']);
$qs = http_build_query($qs_arr);

$section = '';
foreach ($vars['fields'][$this->section] as $name => $type) {
    if ($_GET[underscored($name)] and 'id' != $name and 'select' == $type) {
        $section = $name;
        break;
    }
}

// back links
if ($section and in_array('id', $vars['fields'][$this->section])) {
    $back_link = '?option=' . $vars['options'][$section] . '&view=true&id=' . $this->content[$section];
    $back_label = ucfirst($vars['options'][$section]);
} else {
    $back_link = '?option=' . $this->section . '&' . http_build_query($_GET['s']);
    $back_label = ucfirst($this->section);
} ?>

<div class="main-content-inner">
        
    <div class="row">
        

<!-- tab start -->
<div class="col-lg-12 mt-1 p-0">
    <div class="card">
        <div class="card-body">
            
            <div class="top-row mb-3">
                <div class="pull-left toolbar">
                    
                    <a href="<?=$back_link; ?>" class="btn btn-secondary" title="Back to <?=$back_label; ?>"><i class="fas fa-arrow-left"></i></a>
                    
                    <span class="holder"></span>
                    
                    <span data-section="<?=$this->section;?>">
            	        <button class="btn btn-secondary" type="button" onclick="location.href='?option=<?=$this->section; ?>&edit=true&id=<?=$id; ?>&<?=$qs; ?>'" style="font-weight:bold;"><i class="fas fa-pencil-alt"></i></button>
                        
            	        <form method="post" style="display:inline;">
            		        <input type="hidden" name="delete" value="1">
            		        <button class="btn btn-danger" type="submit" onclick="return confirm('are you sure you want to delete?');"><i class="fas fa-trash"></i></button>
            	        </form>
        	        </span>
                        
                    <div class="dropdown" style="display: inline-block;">
                        <button class="btn btn-secondary" type="button" id="dropdownMenuButton<?=underscored($button['section']);?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                           <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton<?=underscored($button['section']);?>">
                			<?php
                            foreach ($cms_buttons as $k => $button) {
                                if (($this->section == $button['section'] || in_array($this->section, $button['section'])) && 'view' == $button['page']) {
                                    require('includes/button.php');
                                }
                            } ?>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <ul class="nav nav-tabs" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pills-summary-tab" data-toggle="pill" href="#pills-summary" role="tab" aria-controls="pills-summary" aria-selected="true" data-section="<?=$this->section;?>">
                    	<?=ucwords($this->section);?>
                    </a>
                </li>
				<?php
                foreach ($vars['subsections'][$this->section] as $count => $subsection) {
                    ?>
                <li class="nav-item">
                    <a class="nav-link" id="pills-<?=$count; ?>-tab" data-toggle="pill" href="#pills-<?=$count; ?>" role="tab" aria-controls="pills-tab_<?=$count; ?>" aria-selected="true" data-section="<?=$subsection; ?>">
						<?=ucfirst($subsection); ?>
					</a>
				</li>
				<?php
                } ?>
				<?php if ($has_privileges) { ?>
                <li class="nav-item">
                    <a class="nav-link" id="pills-priveleges-tab" data-toggle="pill" href="#pills-priveleges" role="tab" aria-controls="pills-priveleges" aria-selected="true">
						Privileges
					</a>
				</li>
				<?php } ?>
				<?php if ($has_logs) { ?>
                <li class="nav-item">
                    <a class="nav-link" id="pills-logs-tab" data-toggle="pill" href="#pills-logs" role="tab" aria-controls="pills-logs" aria-selected="true">
						Logs
					</a>
				</li>
				<?php } ?>
            </ul>
            <div class="tab-content mt-3" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-summary" role="tabpanel" aria-labelledby="pills-summary-tab">

	<div class="box">
    	<table border="0" cellspacing="0" cellpadding="5" width="100%">
    
    	<?php
        foreach ($vars['fields'][$this->section] as $name => $type) {
            $label = $vars['label'][$this->section][$name];
    
            if (!$label) {
                $label = ucfirst(str_replace('_', ' ', $name));
            }
            
            $value = $this->get_value($name);
            
            if (!$value) {
                continue;
            } ?>
    
    		<tr>
    			<th align="left" valign="top" width="20%"><?=$label; ?></th>
    			<td>
            		<?=$value; ?>
    			</td>
    		</tr>
    	<?php
        } ?>
    	</table>
	</div>
<?php
?>

</div>

<?php
    foreach ($vars['subsections'][$this->section] as $count => $subsection) {
        if (count($vars['fields'][$subsection])) {
            $this->section = $subsection;

            $conditions = [];
            $qs = [];
            foreach ($vars['fields'][$this->section] as $k => $v) {
                if (('select' == $v or 'combo' == $v or 'radio' == $v) and $vars['options'][$k] == $_GET['option']) {
                    $conditions[$k] = escape($this->id);
                    $qs[$k] = $this->id;
                    break;
                }
            }

            $qs = http_build_query($qs);

            $p = $this->p;
        } ?>

<div class="tab-pane fade" id="pills-<?=$count; ?>" role="tabpanel" aria-labelledby="pills-<?=$count; ?>-tab">
	<?php
    if (count($vars['fields'][$subsection])) {
        require(__DIR__ . '/list.php');
    } elseif (file_exists('_tpl/admin/' . $subsection . '.php')) {
        require('_tpl/admin/' . $subsection . '.php');
    } ?>
</div>

<?php
    }
    
    if ($has_privileges) { ?>
	<div class="tab-pane fade" id="pills-priveleges" role="tabpanel" aria-labelledby="pills-priveleges-tab">
	    <div class="box" style="clear:both;">
			<?php require('includes/privileges.php'); ?>
		</div>
	</div>
<?php }

    if ($has_logs) { ?>
	<div class="tab-pane fade" id="pills-logs" role="tabpanel" aria-labelledby="pills-logs-tab">
	    <div class="box" style="clear:both;">
			<?php require('includes/logs.php'); ?>
		</div>
	</div>
<?php } ?>
            </div>
        </div>
    </div>
</div>
<!-- tab end -->

	</div>
</div>

<script>
    
    // hash tabs
    $(function() {
        var hash = window.location.hash;
        hash && $('ul.nav a[href="' + hash + '"]').tab('show');
        
        $('.nav-tabs a').click(function (e) {
            $(this).tab('show');
            var scrollmem = $('body').scrollTop() || $('html').scrollTop();
            window.location.hash = this.hash;
            $('html,body').scrollTop(scrollmem);
        });
    });
    
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        // resize datatables
        $($.fn.dataTable.tables(true)).DataTable()
           .columns.adjust()
           .responsive.recalc();
        
        // refresh toolbar
        set_toolbar($(this).data('section') );
    });
    
    function set_toolbar(section) {
        $('.toolbar [data-section]').hide();
        $('.toolbar [data-section="' + section+ '"]').show()
    }

    $(function() {
        $('.dt-buttons').hide();
    });
    
</script>
