<?php
// check permissions
if (1 != $auth->user['admin'] and !$auth->user['privileges'][$this->section]) {
    die('access denied');
}

$section = $this->section;
$this->set_section($this->section, $_GET['id'], ['read']);
$content = $this->content;

//debug($content);

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
    (
    	$auth->user['admin'] == 1 or
    	$auth->user['privileges']['cms privileges'] > 1
    ) and
    underscored($this->section) == $auth->table and
    $this->content['admin'] !== 1
) {
    $has_privileges = true;
}

if ($_POST['custom_button']) {
    if ($this->section == $_POST['section']) {
        $items = $_GET['id'];
    } else {
        $items = [];
        if ($_POST['select_all_pages'] || !$_POST['id']) {
            $items = $this->get($_POST['section'], $_POST);
        } elseif ($_POST['id']) {
            foreach ($_POST['id'] as $v) {
                $items[] = $this->get($_POST['section'], $v);
            }
        }
    }

    try {
        if (is_callable($this->buttons[$_POST['custom_button']]['handler'])) {
            $this->buttons[$_POST['custom_button']]['handler']($items, $content);
        }
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
    }

    $this->section = $section;
    $content = $this->get($this->section, $_GET['id']);
}

// get all items
if ($_POST['select_all_pages']) {
    $conditions = [];

    $fields = $this->get_fields($_POST['section']);

    foreach ($fields as $name => $type) {
        $type = $field['type'];
        if (in_array($type, ['select', 'combo', 'radio']) and $vars['options'][$name] == $_GET['option']) {
            $conditions[$name] = escape($this->id);
            break;
        }
    }

    $items = $this->get($_POST['section'], $conditions);

    $_POST['items'] = [];
    foreach ($items as $v) {
        $_POST['items'][] = $v['id'];
    }
}

$fields = $this->get_fields($this->section);

$section = '';
foreach ($fields as $name => $field) {
    $type = $field['type'];

    if ($_GET[underscored($name)] and 'id' != $name and in_array($type, ['select', 'combo'])) {
        $section = $name;
        break;
    }
}

// back links
if ($section && is_string($vars['options'][$section])) {
    $back_link = '?option=' . spaced($vars['options'][$section]) . '&view=true&id=' . $this->content[$section] . '#pills-' . underscored($section);
    $back_label = ucfirst($vars['options'][$this->section]);
} else {
    $back_link = '?option=' . $this->section;
    $back_label = ucfirst($this->section);
}

$qs_arr = $_GET;
unset($qs_arr['option']);
unset($qs_arr['view']);
unset($qs_arr['edit']);
unset($qs_arr['id']);
$qs = http_build_query($qs_arr);
if ($qs) {
    $back_link .= '&' . $qs;
}
// delete this item
if (is_numeric($_POST['delete']) and $this->id) {
    if ((int)$_POST['delete'] === 0) {
        $this->set_section($this->section, $this->id, ['deleted']);
        $this->save(['deleted' => 0]);
        reload();
    } else {
        $this->delete_items($_POST['section'], $this->id);
        redirect($back_link);
    }
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

?>

<div class="main-content-inner">

    <div class="row">


        <!-- tab start -->
        <div class="col-lg-12 mt-1 p-0">
            <div class="card">
                <div class="card-body">

                    <div class="top-row mb-3">
                        <div class="pull-left toolbar sticky">

                            <a href="<?=$back_link; ?>" class="btn btn-secondary" title="Back to <?=$back_label; ?>"><i class="fas fa-arrow-left"></i></a>

                            <span class="holder"></span>

                            <div class="dropdown" style="display: inline-block;" data-section="<?=$this->section; ?>">
                                <button class="btn btn-secondary" type="button" id="dropdownMenuButton<?=underscored($button['section']); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton<?=underscored($button['section']); ?>">
                                    <?php
                                    foreach ($this->buttons as $k => $button) {
                                        if (($this->section == $button['section'] || is_array($button['section']) && in_array($this->section, $button['section'])) && 'view' == $button['page']) {
                                            require('includes/button.php');
                                        }
                                    } ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <ul class="nav nav-tabs" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pills-summary-tab" data-toggle="pill" href="#pills-summary" role="tab" aria-controls="pills-summary" aria-selected="true" data-section="<?=$this->section; ?>">
                                <?=ucwords($this->section); ?>
                            </a>
                        </li>
                        <?php
                        foreach ($vars['subsections'][$this->section] as $count => $subsection) {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-<?=underscored($subsection); ?>-tab" data-toggle="pill" href="#pills-<?=underscored($subsection); ?>" role="tab" aria-controls="pills-tab_<?=$subsection; ?>" aria-selected="true" data-section="<?=spaced($subsection); ?>">
                                    <?=ucfirst(spaced($subsection)); ?>
                                </a>
                            </li>
                            <?php
                        } ?>
                        <?php if ($has_privileges) {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-priveleges-tab" data-toggle="pill" href="#pills-priveleges" role="tab" aria-controls="pills-priveleges" aria-selected="true">
                                    Privileges
                                </a>
                            </li>
                            <?php
                        } ?>
                        <?php if ($has_logs) {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-logs-tab" data-toggle="pill" href="#pills-logs" role="tab" aria-controls="pills-logs" aria-selected="true">
                                    Logs
                                </a>
                            </li>
                            <?php
                        } ?>
                    </ul>
                    <div class="tab-content mt-3" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-summary" role="tabpanel" aria-labelledby="pills-summary-tab">
                            <div class="box">
                                <?php
                                require(__DIR__ . '/view.php');
                                ?>
                            </div>
                        </div>

                        <?php
                        foreach ($vars['subsections'][$this->section] as $count => $subsection) {
                            $this->section = spaced($subsection);

                            $fields = $this->get_fields($this->section);

                            $conditions = [];
                            $qs = [];

                            $subsection_fields = $this->get_fields($this->section);
                            foreach ($subsection_fields as $k => $field) {
                                $v = $field['type'];

                                if (('select' == $v or 'combo' == $v or 'radio' == $v) and $vars['options'][$k] == underscored($_GET['option'])) {
                                    $conditions[$k] = escape($this->id);
                                    $qs[$k] = $this->id;
                                    break;
                                }
                            }

                            $qs = http_build_query($qs);

                            $p = $this->p;
                            ?>

                            <div class="tab-pane fade" id="pills-<?=underscored($subsection); ?>" role="tabpanel" aria-labelledby="pills-<?=$count; ?>-tab">
                                <?php
                                if (file_exists('_tpl/admin/' . $subsection . '.php')) {
                                    require('_tpl/admin/' . $subsection . '.php');
                                } else if ($fields['id']) {
                                    require(__DIR__ . '/list.php');
                                } else {
                                    require(__DIR__ . '/view.php');
                                }
                                ?>
                            </div>
                            
                            <div class="dropdown" style="display: inline-block;" data-section="<?=$this->section; ?>">
                                <button class="btn btn-secondary" type="button" id="dropdownMenuButton<?=underscored($button['section']); ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton<?=underscored($button['section']); ?>">
                                    <?php
                                    foreach ($this->buttons as $k => $button) {
                                        if (($this->section == $button['section'] || is_array($button['section']) && in_array($this->section, $button['section'])) && 'list' == $button['page']) {
                                            require('includes/button.php');
                                        }
                                    } ?>
                                </div>
                            </div>

                            <?php
                        }

                        if ($has_privileges) {
                            ?>
                            <div class="tab-pane fade" id="pills-priveleges" role="tabpanel" aria-labelledby="pills-priveleges-tab">
                                <div class="box" style="clear:both;">
                                    <?php require('includes/privileges.php'); ?>
                                </div>
                            </div>
                            <?php
                        }

                        if ($has_logs) {
                            ?>
                            <div class="tab-pane fade" id="pills-logs" role="tabpanel" aria-labelledby="pills-logs-tab">
                                <div class="box" style="clear:both;">
                                    <?php require('includes/logs.php'); ?>
                                </div>
                            </div>
                            <?php
                        } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- tab end -->

    </div>
</div>

<script>
    var section = <?=json_encode($_GET['option']); ?>;
    var id = <?=json_encode($id); ?>;
    var offset = 0;
    var length = 20;

    // hash tabs
    $(function() {
        $('.dt-buttons').hide();

        $('.nav-tabs a').click(function (e) {
            $(this).tab('show');
            var scrollmem = $('body').scrollTop() || $('html').scrollTop();
            window.location.hash = this.hash;
            $('html,body').scrollTop(scrollmem);
        });

        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            // resize datatables
            $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust()
            .responsive.recalc();

            // refresh toolbar
            set_toolbar($(this).data('section'));

            // load logs
            if ($(this).attr('id') === 'pills-logs-tab') {
                load_logs();
            }
        });

        // switch tab on load
        var url = document.location.toString();
        if (url.match('#')) {
            $('.nav-tabs a[href="#' + url.split('#')[1] + '"]').tab('show');
        }

        function set_toolbar(section) {
            $('.toolbar [data-section]').hide();
            $('.toolbar [data-section="' + section+ '"]').show()
        }

        function load_logs() {
            if (offset < 0) {
                return false;
            }

            $.ajax({
                url: "_lib/api/?cmd=logs",
                type: 'GET',
                data: {
                    section: section,
                    id: id,
                    offset: offset,
                    length: length,
                },
                cache: false,
                success: function(result) {
                    result.data.forEach(function(item) {
                        var task = item.task;
                        
                        if (item.user > 0) {
                            task += ' by <a href="?option=users&view=true&id= ' + item.user + '"> ' + (item.name ? item.name : item.user) + '</a>';
                        }
                        
                        var html = '\
                        	<div>\
                        		<strong>' + task + ' on ' + item.date + '</strong><br>\
                        		<pre>' + item.details + '</pre>\
                        	</div>\
                        ';

                        $('#logs').append(html);
                    })

                    if (result.data.length < length) {
                        offset = -1;
                    } else {
                        offset += length;
                    }
                },
                error: function() {
                    alert("Error loading logs");
                }
            });
        }

        $(window).scroll(function() {
            if (Math.ceil($(window).scrollTop()) >= $(document).height() - $(window).height()) {
                load_logs();
            }
        });

    });

</script>