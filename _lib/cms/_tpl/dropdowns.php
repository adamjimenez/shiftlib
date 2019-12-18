<?php
global $db_config, $auth_config, $shop_config, $shop_enabled, $from_email, $tpl_config, $sms_config, $live_site;

function array_to_csv($array): string
{
    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes($v) . "'";
    }

    return implode(",\n", $array);
}

function str_to_csv($str): string
{
    if (!$str) {
        return;
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes(trim($v)) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_assoc($str): string
{
    if (!$str) {
        return;
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $pair = explode('=', $v);
        $array[$k] = "\t'" . addslashes(trim($pair[0])) . "'=>'" . addslashes(trim($pair[1])) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_bool($str): string
{
    return $str ? 'true' : 'false';
}

foreach ($vars['fields'] as $section => $fields) {
    $section_opts[] = $section;
}

//check config file
$config_file = '_inc/config.php';

if (!file_exists($config_file)) {
    die('Error: config file does not exist: ' . $config_file);
}

if ($_POST['save']) {
    if (!is_writable($config_file)) {
        die('Error: config file is not writable: ' . $config_file);
    }

    $config = file_get_contents($config_file);
    $pos = strpos($config, '#OPTIONS');
    $config = substr($config, 0, $pos);

    $config .= '
#OPTIONS
';

    foreach ($_POST['options'] as $option) {
        while (in_array($option['name'], $field_options)) {
            $index = array_search($option['name'], $field_options);
            unset($field_options[$index]);
        }

        if ('section' == $option['type']) {
            $config .= '
$opts["' . $option['name'] . '"]="' . $option['section'] . '";
';
        } else {
            if (strstr($option['list'], '=')) {
                $config .= '
$opts["' . $option['name'] . '"]=array(
' . str_to_assoc($option['list']) . '
);
';
            } else {
                $config .= '
$opts["' . $option['name'] . '"]=array(
' . str_to_csv($option['list']) . '
);
';
            }
        }
    }

    foreach ($field_options as $field_option) {
        if (!in_array($field_option, $_POST['options'])) {
            $config .= '
$opts["' . $field_option . '"]="";
';
        }
    }

    $config .= '

$vars["options"]=$opts;
?>';

    //die($config);
    file_put_contents($config_file, $config);

    unset($_POST);
    redirect('/admin?option=dropdowns');
}
?>

<script type="text/javascript">
function str_replace(search, replace, subject) {
	var f = search, r = replace, s = subject;
    var ra = r instanceof Array, sa = s instanceof Array, f = [].concat(f), r = [].concat(r), i = (s = [].concat(s)).length;

    while (j = 0, i--) {
        if (s[i]) {
            while (s[i] = (s[i]+'').split(f[j]).join(ra ? r[j] || "" : r[0]), ++j in f){};
        }
    };

    return sa ? s : s[0];
}

function init()
{
	initAutoGrows();

	Sortable.create('sections', { handle:'handle', only : 'draggable', tag:'TR' });
	//alert(count['fields']);
	for(i=1;i<=count['sections'];i++){
		//alert('fields_'+i,);
		Sortable.create('fields_'+i, { handle:'handle', only : 'draggable', tag:'TR' });
		//alert('subsections_'+i,);
		Sortable.create('subsections_'+i, { handle:'handle', only : 'draggable', tag:'TR' });
	}

}

function set_list_type(id,type)
{
	if( type=='list' ){
		$('options_list_'+id).show();
		$('options_section_'+id).hide();
	}else{
		$('options_list_'+id).hide();
		$('options_section_'+id).show();
	}
}

window.onload=init;
</script>

<form method="post">
	<input type="hidden" name="save" value="1" />
	
	<h2>Options</h2>
	<div class="box">
	<table id="options" width="400">
		<tbody>
		<?php
        foreach ($vars['options'] as $opt => $val) {
            $count['options']++; ?>
		<tr>
			<th valign="top">
				<input type="text" name="options[<?=$count['options']; ?>][name]" value="<?=$opt; ?>" /><br />
				<label><input type="radio" name="options[<?=$count['options']; ?>][type]" value="list" <?php if (is_array($val)) { ?>checked="checked"<?php } ?> onclick="set_list_type('<?=$count['options']; ?>','list')" /> list</label><br />
				<label><input type="radio" name="options[<?=$count['options']; ?>][type]" value="section" <?php if (!is_array($val)) { ?>checked="checked"<?php } ?> onclick="set_list_type('<?=$count['options']; ?>','section')" /> section</label><br />
			</th>
			<td>
				<textarea id="options_list_<?=$count['options']; ?>" cols="30" type="text" name="options[<?=$count['options']; ?>][list]" class="autogrow" <?php if (!is_array($val)) { ?>style="display:none;"<?php } ?>><?php
                    if (is_assoc_array($val)) {
                        $options = '';
        
                        foreach ($val as $k => $v) {
                            $options .= $k . '=' . $v . "\n";
                        }
        
                        print trim($options);
                    } else {
                        print implode("\n", $val);
                    } ?></textarea>
				<select id="options_section_<?=$count['options']; ?>" name="options[<?=$count['options']; ?>][section]" <?php if (is_array($val)) { ?>style="display:none;"<?php } ?>>
					<?=html_options($section_opts, $val); ?>
				</select>
			</td>
		</tr>
		<?php
        }
        ?>
		</tbody>
	</table>
	</div>
	
	<br />
	
	<p><button type="submit">Save</button></p>
</form>