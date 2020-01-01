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

//bulk export
if ('export' == $_POST['action']) {
    $this->export_items($_POST['section'], $_POST['id']);
}

//subsections delete
if ('delete' == $_POST['action']) {
    $this->delete_items($_POST['section'], $_POST['id']);
}

//save privileges
if (
    1 == $auth->user['admin'] and
    underscored($this->section) == $auth->table and
    (2 == $content['admin'] or 3 == $content['admin']) and
    $_POST['privileges']
) {
    sql_query("DELETE FROM cms_privileges WHERE user='" . $this->id . "'");

    foreach ($_POST['privileges'] as $k => $v) {
        sql_query("INSERT INTO cms_privileges SET
			user='" . escape($this->id) . "',
			section='" . escape($k) . "',
			access='" . escape($v) . "',
			filter='" . escape($_POST['filters'][$k]) . "'
		");
    }
}

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

if ($section and in_array('id', $vars['fields'][$this->section])) {
    $back_link = '?option=' . $vars['options'][$section] . '&view=true&id=' . $this->content[$section];
    $back_label = ucfirst($vars['options'][$section]);
} else {
    $back_link = '?option=' . $this->section . '&' . http_build_query($_GET['s']);
    $back_label = ucfirst($this->section);
} ?>

<!-- page title area start -->
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-10">
            <div class="breadcrumbs-area clearfix" style="margin: 20px 0;">
                <h4 class="page-title pull-left">Admin</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="/admin">Dashboard</a></li>
                    <li><a href="<?=$back_link; ?>"><?=$back_label; ?></a></li>
                    <li><?=$this->get_label(); ?></li>
                </ul>
            </div>
        </div>

        <div class="col-sm-2 clearfix">
            <ul class="breadcrumbs pull-right">
		        <?php if ($prev_link) { ?>
		        <li><a href="<?=$prev_link;?>">Prev</a></li>
		        <?php } ?>
		        <?php if ($next_link) { ?>
		        <li><a href="<?=$next_link;?>">Next</a></li>
		        <?php } ?>
        	</ul>
        </div>

    </div>
</div>
<!-- page title area end -->


<div class="main-content-inner">
    <div class="row">



<!-- tab start -->
<div class="col-lg-12 mt-5">
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pills-summary-tab" data-toggle="pill" href="#pills-summary" role="tab" aria-controls="pills-summary" aria-selected="true">
                    	Summary
                    </a>
                </li>
				<?php
                foreach ($vars['subsections'][$this->section] as $count => $subsection) {
                    ?>
                <li class="nav-item">
                    <a class="nav-link" id="pills-<?=$count; ?>-tab" data-toggle="pill" href="#pills-<?=$count; ?>" role="tab" aria-controls="pills-tab_<?=$count; ?>" aria-selected="true">
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

                	<div>
				        <button class="btn btn-default" type="button" onclick="location.href='?option=<?=$this->section; ?>&edit=true&id=<?=$id; ?>&<?=$qs; ?>'" style="font-weight:bold;">Edit</button>

						<?php
                        foreach ($cms_buttons as $k => $button) {
                            if ($this->section == $button['section'] and 'view' == $button['page']) {
                                require('includes/button.php');
                            }
                        } ?>

						&nbsp;

				        <form method="post" style="display:inline;">
					        <input type="hidden" name="delete" value="1">
					        <button class="btn btn-danger" type="submit" onclick="return confirm('are you sure you want to delete?');">Delete</button>
				        </form>
                	</div>




<?php
    $content = $this->get($this->section, $_GET['id']);
?>
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
        }
        ?>

		<tr>
			<th align="left" valign="top" width="20%"><?=$label; ?></th>
			<td>
        		<?=$value;?>
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

            $table = underscored($this->section);

            if (in_array('position', $vars['fields'][$this->section])) {
                $limit = null;
            } else {
                $label = $vars['fields'][$this->section][0];
                $type = array_search($label, $vars['fields'][$this->section]);
                $limit = 10;
            }

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

            $vars['content'] = $this->get($subsection, $conditions, $limit, null, null, $table);
            $p = $this->p;
        } ?>

<div class="tab-pane fade" id="pills-<?=$count; ?>" role="tabpanel" aria-labelledby="pills-<?=$count; ?>-tab">
	<?php
    if (count($vars['fields'][$subsection])) {
        require(dirname(__FILE__) . '/list.php');
    } elseif (file_exists('_tpl/admin/' . $subsection . '.php')) {
        require('_tpl/admin/' . $subsection . '.php');
    } ?>
</div>

<script>
	$('#tab_<?=$count; ?>').text('<?=ucfirst($subsection); ?> (<?=$p->total; ?>)');
</script>

<?php
    } ?>

<?php if ($has_privileges) { ?>
	<div class="tab-pane fade" id="pills-priveleges" role="tabpanel" aria-labelledby="pills-priveleges-tab">
	    <div class="box" style="clear:both;">
			<? require('includes/privileges.php'); ?>
		</div>
	</div>
<?php } ?>

<?php if ($has_logs) { ?>
	<div class="tab-pane fade" id="pills-logs" role="tabpanel" aria-labelledby="pills-logs-tab">
	    <div class="box" style="clear:both;">
			<? require('includes/logs.php'); ?>
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
function init()
{
	init_tabs();
}

function choose_filter(field)
{
	window.open('/admin?option=choose_filter&section='+field,'Insert','width=700,height=450,screenX=100,screenY=100,left=100,top=100,status,dependent,alwaysRaised,resizable,scrollbars')
}

window.onload=init;
</script>

<script>
$('#privileges_none').click(function(){
	$('select.privileges').val('0');
	return false;
});

$('#privileges_read').click(function(){
	$('select.privileges').val('1');
	return false;
});

$('#privileges_write').click(function(){
	$('select.privileges').val('2');
	return false;
});

// hash tabs
$(function(){
  var hash = window.location.hash;
  hash && $('ul.nav a[href="' + hash + '"]').tab('show');

  $('.nav-tabs a').click(function (e) {
    $(this).tab('show');
    var scrollmem = $('body').scrollTop() || $('html').scrollTop();
    window.location.hash = this.hash;
    $('html,body').scrollTop(scrollmem);
  });
});

// resize datatables
$('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
    $($.fn.dataTable.tables(true)).DataTable()
       .columns.adjust()
       .responsive.recalc();
});

</script>
