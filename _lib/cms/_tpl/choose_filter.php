<?php
if ($_GET['section']) {
    $this->section = $_GET['section'];
} else {
    die('no section');
}

if ($_GET['choose_filter']) {
    $qs = $_GET;
    unset($qs['page']);
    unset($qs['option']);
    unset($qs['section']);
    unset($qs['choose_filter']);

    foreach ($qs as $k => $v) {
        if ('' === $v) {
            unset($qs[$k]);
        }
    } ?>

    <script>
        window.opener.document.getElementById('filters_<?=underscored($_GET['section']); ?>').value = '<?=http_build_query($qs); ?>';
        window.close();
    </script>

    <?php
}
?>

<form method="get">
    <input type="hidden" name="option" value="choose_filter">
    <input type="hidden" name="choose_filter" value="1">
    <input type="hidden" name="section" value="<?=$_GET['section']; ?>">
    <div id="advanced">
        <div style="padding:10px 0; margin:0 auto; text-align:center;">
            <fieldset>
                <legend>Create filter</legend>
                <table class="box" border="0" cellspacing="0" cellpadding="3">
                    <?php
                    $fields = $this->get_fields($this->section);

                    foreach ($fields as $name => $field) {
                        $type = $field['type'];

                        if (in_array($name, (array)$vars['non_searchable'][$this->section])) {
                            continue;
                        }

                        $field_name = underscored($name);

                        $label = ucfirst(spaced($name)); ?>
                        <?php if ('file' == $type) {
                            ?>
                            <tr>
                                <th align="left" valign="top"><?=$label; ?></th>
                                <td>
                                    <input type="checkbox" name="<?=$field_name; ?>" value="1" <?php if ($_GET[$field_name]) { ?>checked<?php } ?> />
                            </td>
                        </tr>
                        <?php
                    } elseif ('select' == $type or 'radio' == $type) {
                        $options = $vars['options'][$name];
                        if (false === is_array($vars['options'][$name])) {
                            $table = underscored($vars['options'][$name]);
                            
                            $component = $this->get_component('select');

                            $options = $component->get_options($name);
                        } ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td>
                                <select name="<?=$name; ?>">
                                    <option value=""></option>
                                    <?=html_options($options, $_GET[$field_name]); ?>
                                </select>
                            </td>
                        </tr>
                        <?php
                    } elseif ('select-multiple' == $type) {
                        ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td>
                                <select name="<?=$field_name; ?>[]" multiple="multiple" size="10" style="width:100%">
                                    <?=html_options($vars['options'][$name], $_GET[$field_name]); ?>
                                </select>
                            </td>
                        </tr>
                        <?php
                    } elseif ('checkbox' == $type) {
                        ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td>
                                <select name="<?=$field_name; ?>">
                                    <option value=""></option>
                                    <?=html_options([1 => 'yes', 0 => 'no'], $_GET[$field_name]); ?>
                                </select>
                            </td>
                        </tr>
                        <?php
                    } elseif ('date' == $type) {
                        ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td>
                                <div style="float:left">
                                    <select name="func[<?=$field_name; ?>]">
                                        <option value=""></option>
                                        <?=html_options(['=' => 'On', '>' => 'After', '<' => 'Before'], $_GET['func'][$field_name]); ?>
                                    </select>
                                </div>
                                <div style="float:left">
                                    <input type="text" id="<?=$name; ?>" name="<?=$field_name; ?>" value="<?=$_GET[$field_name]; ?>" size="8" class="date">
                                </div>
                            </td>
                        </tr>
                        <?php
                    } elseif ('postcode' == $type) {
                        ?>
                        <tr>
                            <th align="left" valign="top">Distance from <?=$label; ?></th>
                            <td>
                                Within
                                <select name="func[<?=$field_name; ?>]">
                                    <option value=""></option>
                                    <?=html_options($opts['distance'], $_GET['func'][$field_name]); ?>
                                </select>
                                of
                                <input type="text" name="<?=$field_name; ?>" value="<?=$_GET[$field_name]; ?>" size="7">
                            </td>
                        </tr>
                        <?php
                    } elseif ('int' == $type) {
                        ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td>
                                <div style="float:left">
                                    <select name="func[<?=$field_name; ?>]">
                                        <option value=""></option>
                                        <?=html_options(['=' => '=', '>' => '>', '<' => '<'], $_GET['func'][$field_name]); ?>
                                    </select>
                                </div>
                                <div style="float:left">
                                    <input type="text" id="<?=$name; ?>" name="<?=$field_name; ?>" value="<?=$_GET[$field_name]; ?>" size="8">
                                </div>
                            </td>
                        </tr>
                        <?php
                    } elseif ('text' == $type or 'email' == $type or 'mobile' == $type or ('id' == $type and $vars['settings'][$this->section]['show_id'])) {
                        ?>
                        <tr>
                            <th align="left" valign="top"><?=$label; ?></th>
                            <td><input type="text" name="<?=$field_name; ?>" value="<?=$_GET[$field_name]; ?>" size="50"></td>
                        </tr>
                        <?php
                    } ?>
                    <?php
                } ?>
            </table>
            <br>
            <p align="center">
                <button type="submit">Create Filter</button> &nbsp; <button type="button" onclick="window.close();">Cancel</button>
            </p>
        </fieldset>
        <br>
        <br>
    </div>
</div>

<?php
exit;