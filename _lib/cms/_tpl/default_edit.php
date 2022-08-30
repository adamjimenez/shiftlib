<?php
//check permissions
if (1 != $auth->user['admin'] and !$auth->user['privileges'][$this->section]) {
    die('access denied');
}

$this->set_section($this->section, $_GET['id']);
$this->trigger_event('beforeEdit', [$this->id]);

$fields = $this->get_fields($this->section);

//return url
$section = '';
foreach ($fields as $name => $field) {
    $type = $field['type'];

    if ($_GET[underscored($name)] and 'id' != $name) {
        $section = $name;
        break;
    }
}

if ($this->id and $section) {
    $cancel_url = '?option=' . $this->section . '&view=true&id=' . $this->id . '&' . $section . '=' . $this->content[$section];
} elseif ($section) {
    $cancel_url = '?option=' . $vars['options'][$section] . '&view=true&id=' . $this->content[underscored($section)];
} else {
    $cancel_url = '?option=' . $this->section;
}

$qs_arr = $_GET;
unset($qs_arr['option']);
unset($qs_arr['view']);
unset($qs_arr['edit']);
unset($qs_arr['id']);
$qs = http_build_query($qs_arr);
$cancel_url .= '&' . $qs;

if (isset($_POST['save'])) {
    $errors = $this->validate();

    if (count($errors)) {
        print json_encode($errors);
        exit;
    } elseif ($_POST['validate']) {
        print 1;
        exit;
    }
    
    if (1 == $auth->user['admin'] or $auth->user['privileges'][$this->section] >= 2) {
        $id = $this->save();

        if ($_POST['add_another']) {
            $qs = http_build_query($_GET);

            redirect('?' . $qs . '&add_another=1');
        } else {
            if ($section) {
                $return_url = '?option=' . $this->section . '&view=true&id=' . $id . '&' . $section . '=' . $this->content[$section];
            } elseif ($this->id) {
                $return_url = '?option=' . $this->section . '&view=true&id=' . $id;
            } else {
                $return_url = '?option=' . $this->section;
            }

            $_SESSION['message'] = 'The item has been saved';
            redirect($return_url . '&' . $qs);
        }
        exit;
    }
    die('Permission denied, you have read-only access. <a href="?option=' . $this->section . '&view=true&id=' . $this->id . '">continue</a>');
}

//label
$label = $this->get_label();

$title = ucfirst($this->section) . ' | ' . ($label ? $label : '&lt;blank&gt; | Edit');

//increment value
if ($_GET['id']) {
    $id = $_GET['id'];
} else {
    $row = sql_query("SHOW TABLE STATUS LIKE '" . underscored($this->section) . "'", 1);
    $id = $row['Auto_increment'];
}
?>

<div class="main-content-inner">
    <div class="row">
        <!-- tab start -->
        <div class="col-lg-12 mt-1 p-0">
            <div class="card">
                <div class="card-body">

                    <form id="form" method="post" enctype="multipart/form-data" class="validate">
                        <input type="hidden" name="save" value="1">

                        <div class="toolbar top-row mt-1 mb-3 sticky">
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?=$cancel_url; ?>';"><i class="fas fa-arrow-left"></i></button>
                            <button id="save" type="button" class="btn btn-primary">Save</button>
                        </div>

                        <h1 class="header-title"><?=ucwords($this->section); ?></h1>

                        <div class="box">

                            <?php
                            foreach ($fields as $name => $field) {
                                $type = $field['type'];

                                if (in_array($type, ['id', 'ip', 'position', 'timestamp', 'deleted'])) {
                                    continue;
                                }

                                if ('hidden' == $type) {
                                    print $this->get_field($name);
                                    continue;
                                }

                                $label = $field['label'];

                                if (!$label) {
                                    $label = ucfirst(spaced($name));
                                } ?>

                                <div class="form-group">
                                    <?php
                                    switch ($type) {
                                        case 'checkbox':
                                            ?>
                                            <div>
                                                <?=$this->get_field($name, 'id="' . underscored($name) . '" class="' . $class . '"'); ?>
                                                <label for="<?=underscored($name); ?>" class="col-form-label"><?=$label; ?></label>
                                            </div>
                                            <?php
                                            break;
                                        case 'radio':
                                            ?>
                                            <div>
                                                <?=$label; ?>
                                            </div>
                                            <br>
                                            <?=$this->get_field($name, 'class="' . $class . '"'); ?>
                                            <?php
                                            break;
                                        case 'password':
                                            ?>
                                            <label for="<?=underscored($name); ?>" class="col-form-label"><?=$label; ?></label>
                                            <?=$this->get_field($name, 'class="form-control" autocomplete="new-password"'); ?>
                                            <?php
                                            break;
                                        default:
                                            ?>
                                            <label for="<?=underscored($name); ?>" class="col-form-label"><?=$label; ?></label>
                                            <?=$this->get_field($name, 'class="form-control"'); ?>
                                            <?php
                                            break;
                                    } ?>
                                </div>

                                <?php
                            } ?>

                        </div>

                        <?php if (!$_GET['id']) {
                            ?>
                            <p>
                                <label><input type="checkbox" name="add_another" value="1" <?php if ($_GET['add_another']) { ?>checked="checked"<?php } ?>> add another?</label>
                            </p>
                            <br>
                            <?php
                        } ?>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .mce-notification {
        display: none !important;
    }
</style>

<script>
    <?php
    if ($this->components) {
        ?>
        var components = <?=json_encode($this->components); ?>;
        <?php
    }
    ?>
</script>

<script>
    var formChanged = false;

    $(function() {
        $('#form').change(function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', (event) => {
            if (formChanged) {
                event.returnValue = `Continue without saving?`;
            }
        });

        $('#save').click(function() {
            formChanged = false;
            $(this).closest('form').submit();
        });
    });
</script>

<script>
    // autofocus
    $(function() {
        $('form input:visible, form textarea:visible, form select:visible').first().focus();
    });
</script>