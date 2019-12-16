<?php
if (1 != $auth->user['admin']) {
    die('access denied');
}

$max_input_vars = ini_get('max_input_vars');

global $db_config, $auth_config, $upload_config, $shop_config, $shop_enabled, $from_email, $tpl_config, $sms_config, $mailer_config, $live_site, $vars, $table_dropped, $count, $section, $table, $fields, $admin_config, $cms_config;

$cms_multiple_select_fields = [
    'section' => 'text',
    'field' => 'text',
    'item' => 'int',
    'value' => 'text',
];

//section templates
$section_templates['pages'] = [
    'heading' => 'text',
    'copy' => 'editor',
    'page name' => 'page-name',
    'page title' => 'text',
    'meta description' => 'textarea',
    'id' => 'id',
];

$section_templates['page'] = [
    'heading' => 'text',
    'copy' => 'editor',
    'meta description' => 'textarea',
];

$section_templates['blog'] = [
    'heading' => 'text',
    'copy' => 'editor',
    'tags' => 'textarea',
    'blog category' => 'checkboxes',
    'date' => 'timestamp',
    'page name' => 'page-name',
    'display' => 'checkbox',
    'id' => 'id',
];

$section_templates['blog categories'] = [
    'category' => 'text',
    'page name' => 'page-name',
    'id' => 'id',
];

$section_templates['news'] = [
    'heading' => 'text',
    'copy' => 'editor',
    'date' => 'timestamp',
    'meta description' => 'textarea',
    'id' => 'id',
];

$section_templates['comments'] = [
    'name' => 'text',
    'email' => 'email',
    'website' => 'url',
    'comment' => 'textarea',
    'blog' => 'select',
    'date' => 'timestamp',
    'ip' => 'text',
    'id' => 'id',
];

$section_templates['enquiries'] = [
    'name' => 'text',
    'email' => 'email',
    'tel' => 'tel',
    'enquiry' => 'textarea',
    'date' => 'timestamp',
    'id' => 'id',
];

$section_templates['cms logs'] = [
    'user' => 'select',
    'section' => 'text',
    'item' => 'int',
    'task' => 'text',
    'details' => 'text',
    'date' => 'timestamp',
    'id' => 'id',
];

$section_templates['blank'] = [
];

$themes = glob(dirname(__FILE__) . '/../css/themes/*.css');

$theme_opts = [];
foreach ($themes as $v) {
    $theme_opts[] = str_replace('.css', '', basename($v));
}

function array_to_csv($array)
{
    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes($v) . "'";
    }

    return implode(",\n", $array);
}

function str_to_csv($str)
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

function str_to_assoc($str)
{
    if (!$str) {
        return;
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $pos = strpos($v, '=');
        $array[$k] = "\t'" . addslashes(trim(substr($v, 0, $pos))) . "'=>'" . addslashes(trim(substr($v, $pos + 1))) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_bool($str)
{
    if ($str) {
        return 'true';
    }
    return 'false';
}

$field_opts = [
    'text',
    'textarea',
    'hidden',
    'int',
    'decimal',
    'editor',
    'checkbox',
    'checkboxes',
    'coords',
    'polygon',
    'date',
    'time',
    'datetime',
    'timestamp',
    'dob',
    'month',
    'file',
    'files',
    'phpupload',
    'phpuploads',
    'rating',
    'avg-rating',
    'select',
    'select-multiple',
    'select-distance',
    'radio',
    'combo',
    'password',
    'email',
    'postcode',
    'tel',
    'mobile',
    'url',
    'ip',
    'page-name',
    'approve',
    'language',
    'translated-from',
    'parent',
    'position',
    'read',
    'deleted',
    'id',
    'separator',
    'sql',
    'array',
    'color',
];

foreach ($vars['fields'] as $section => $fields) {
    $section_opts[] = $section;
}

//check config file
global $root_folder;
$config_file = $root_folder . '/_inc/config.php';

if (!file_exists($config_file)) {
    die('Error: config file does not exist: ' . $config_file);
}

function loop_fields($field_arr) // should be anonymous function
{
    global $vars, $table_dropped, $count, $section, $table, $fields;

    foreach ($field_arr as $k => $v) {
        $count['fields']++;

        if (is_array($v)) {
            loop_fields($v);
        } else {
            if ($table_dropped) {
                continue;
            }

            $new_name = $_POST['vars']['fields'][$count['sections']][$count['fields']]['name'];
            $new_type = $_POST['vars']['fields'][$count['sections']][$count['fields']]['value'];

            //optimise select fields
            if ('select' == $new_type) {
                foreach ($_POST['options'] as $option) {
                    if ($option['name'] == $new_name) {
                        if ('section' == $option['type']) {
                            $new_type = 'int';
                        }

                        break;
                    }
                }
            }

            $fields[] = $new_name;

            //drop fields
            if (!$_POST['vars']['fields'][$count['sections']][$count['fields']]) {
                if ('id' == $v or 'separator' == $v or 'sql' == $v or 'checkboxes' == $v) { //don't drop id!
                    continue;
                }

                $query = "ALTER TABLE `$table` DROP `" . underscored($k) . '`';
                sql_query($query);
                continue;
            }

            if (underscored($k) != underscored($new_name) or $v != $new_type) {
                $db_field = form_to_db($new_type);

                if ($db_field) {
                    $query = "ALTER TABLE `$table` CHANGE `" . underscored($k) . '` `' . underscored($new_name) . '` ' . $db_field . ' ';
                }

                if ($query and 'hidden' != $new_type) {
                    sql_query($query);
                }

                //convert select to multiple select
                if ('select' == $v and 'select-multiple' == $new_type) {
                    $rows = sql_query("SELECT * FROM `$table`");

                    foreach ($rows as $row) {
                        sql_query("INSERT INTO cms_multiple_select SET
							section='" . $section . "',
							field='" . $new_name . "',
							item='" . escape($row['id']) . "',
							value='" . escape($row[$new_name]) . "'
						");
                    }
                }
            }
        }
    }
}

if ($_POST['save']) {
    if (!$_POST['last']) {
        die('Error: form submission incomplete');
    }

    if (!is_writable($config_file)) {
        die('Error: config file is not writable: ' . $config_file);
    }

    check_table('cms_multiple_select', $cms_multiple_select_fields);
    check_table('cms_privileges', $this->cms_privileges_fields);

    $count['sections'] = 0;
    $count['fields'] = 0;
    $count['subsections'] = 0;
    $count['options'] = 0;

    foreach ($vars['fields'] as $section => $fields) {
        $count['sections']++;

        $table = underscored($section);

        $table_dropped = false;
        if (!$_POST['sections'][$count['sections']]) {
            $query = 'DROP TABLE IF EXISTS `' . $table . '`';
            sql_query($query);

            $table_dropped = true;
        }

        $fields = [];

        loop_fields($vars['fields'][$section]);

        if ($table_dropped) {
            continue;
        }

        $after = '';
        foreach ($_POST['vars']['fields'][$count['sections']] as $field_id => $field) {
            if ('separator' == $field['value'] or 'array' == $field['value'] or 'checkboxes' == $field['value']) {
                continue;
            }

            if ('select' == $field['value'] or 'select-multiple' == $field['value'] or 'radio' == $field['value']) {
                $field_options[] = $field['name'];
            }

            if (in_array($field['name'], $fields)) {
                $after = underscored($field['name']);
            } else {
                $db_field = form_to_db($field['value']);

                if (!$db_field) {
                    continue;
                }

                if ($after) {
                    $query = "ALTER TABLE `$table` ADD `" . underscored($field['name']) . '` ' . $db_field . " NOT NULL AFTER `$after`";
                } else {
                    $query = "ALTER TABLE `$table` ADD `" . underscored($field['name']) . '` ' . $db_field . ' NOT NULL AFTER `id`'; //FIRST
                }

                if ($query) {
                    sql_query($query);
                }
            }
        }

        //rename table
        if ($_POST['sections'][$count['sections']] != $section) {
            $table = underscored($section);
            $new_table = underscored($_POST['sections'][$count['sections']]);

            $query = 'RENAME TABLE `' . $table . '`  TO `' . $new_table . '`';
            sql_query($query);
        }
    }

    foreach ($_POST['sections'] as $section_id => $section) {
        $table = underscored($section);

        if ($section_id > $count['sections']) {
            $fields = [];

            foreach ($_POST['vars']['fields'][$section_id] as $field_id => $field) {
                $fields[$field['name']] = $field['value'];
            }

            if (count($fields)) {
                check_table($table, $fields);
            }
        }
    }

    $display = [];
    foreach ($_POST['vars']['settings'] as $k => $v) {
        if ($v['display']) {
            $display[] = $_POST['sections'][$k];
        }
    }

    //hash passwords
    if (!$auth_config['hash_password'] and $_POST['auth_config']['hash_password']) {
        $users = sql_query('SELECT * FROM users');

        foreach ($users as $user) {
            $password = $auth->create_hash($user['password']);
            sql_query("UPDATE users SET
    			password = '" . escape($password) . "'
    			WHERE
    				id = '" . $user['id'] . "'
    		");
        }
    }

    $config = '<?php
#GENERAL SETTINGS
$db_config["host"]="' . $db_config['host'] . '";
$db_config["user"]="' . $db_config['user'] . '";
$db_config["pass"]="' . $db_config['pass'] . '";
$db_config["name"]="' . $db_config['name'] . '";

$db_config["dev_host"]="' . $db_config['dev_host'] . '";
$db_config["dev_user"]="' . $db_config['dev_user'] . '";
$db_config["dev_pass"]="' . $db_config['dev_pass'] . '";
$db_config["dev_name"]="' . $db_config['dev_name'] . '";

$live_site=' . str_to_bool($_POST['live_site']) . ';
$admin_config["theme"]="' . $_POST['admin_config']['theme'] . '";

#TPL
$tpl_config["catchers"]=array(' . str_to_csv($_POST['tpl_config']['catchers']) . ');

$tpl_config["redirects"]=array(' . str_to_assoc($_POST['tpl_config']['redirects']) . ');

$tpl_config["secure"]=array(' . str_to_csv($_POST['tpl_config']['secure']) . ');

$tpl_config["ssl"]=' . str_to_bool($_POST['tpl_config']['ssl']) . ';


#USER LOGIN
$auth_config=array();

//table where your users are stored
$auth_config["table"]="' . $_POST['auth_config']['table'] . '";

//required fields when registering and updating
$auth_config["required"]=array(
	' . array_to_csv($_POST['auth_config']['required']) . '
);

//automated emails will be sent from this address
$from_email="' . $_POST['from_email'] . '";
$auth_config["from_email"]="' . $_POST['from_email'] . '";

//specify pages where users are redirected
$auth_config["login"]="' . $_POST['auth_config']['login'] . '";
$auth_config["register_success"]="' . $_POST['auth_config']['register_success'] . '";
$auth_config["forgot_success"]="' . $_POST['auth_config']['forgot_success'] . '";

//hash passwords
$auth_config["hash_password"]=' . str_to_bool($_POST['auth_config']['hash_password']) . ';

//activation_required
$auth_config["email_activation"]=' . str_to_bool($_POST['auth_config']['email_activation']) . ';
$auth_config["activation_required"]=' . str_to_bool($_POST['auth_config']['activation_required']) . ';

//auto login on register
$auth_config["register_login"]=' . str_to_bool($_POST['auth_config']['register_login']) . ';

//use a secret term to encrypt cookies
$auth_config["secret_phrase"]="' . $_POST['auth_config']['secret_phrase'] . '";

//for use with session and cookie vars
$auth_config["cookie_prefix"]="' . $_POST['auth_config']['cookie_prefix'] . '";

//how long a cookie lasts with remember me
$auth_config["cookie_duration"]="' . $_POST['auth_config']['cookie_duration'] . '";

//send registration notifications
$auth_config["registration_notification"]=' . str_to_bool($_POST['auth_config']['registration_notification']) . ';

$auth_config["facebook_appId"]="' . $_POST['auth_config']['facebook_appId'] . '";
$auth_config["facebook_secret"]="' . $_POST['auth_config']['facebook_secret'] . '";

$auth_config["login_wherestr"]="' . $_POST['auth_config']['login_wherestr'] . '";

#UPLOADS
$upload_config=array();

// configure the variables before use.
$upload_config["upload_dir"]="' . $_POST['upload_config']['upload_dir'] . '";
$upload_config["web_path"]="' . $_POST['upload_config']['web_path'] . '";
$upload_config["resize_images"]=' . str_to_bool($_POST['upload_config']['resize_images']) . ';
$upload_config["resize_dimensions"]=array(' . str_replace('x', ',', $_POST['upload_config']['resize_dimensions']) . ');

$upload_config["allowed_exts"]=array(' . str_to_csv($_POST['upload_config']['allowed_exts']) . ');

#ADMIN AREA
$languages=array(' . str_to_csv($_POST['languages']) . ');

// sections in menu
$vars["sections"]=array(
' . array_to_csv($display) . '
);

//fields in each section
';

    foreach ($_POST['sections'] as $section_id => $section) {
        $fields = '';
        $required = '';
        $labels = '';
        $subsections = '';
        $label = '';

        foreach ($_POST['vars']['fields'][$section_id] as $field_id => $field) {
            if ($_POST['vars']['fields'][$section_id][$field_id]['label']) {
                $label .= '"' . $field['name'] . '" => "' . $_POST['vars']['fields'][$section_id][$field_id]['label'] . '", ';
            }

            if ($field['parent']) {
                continue;
            }

            if ('array' == $field['value']) {
                $fields .= "\t" . '"' . $field['name'] . '"=>array(' . "\n";

                foreach ($_POST['vars']['fields'][$section_id] as $k => $v) {
                    if ($v['parent'] != $field_id) {
                        continue;
                    }

                    $fields .= "\t" . '"' . $v['name'] . '"=>"' . $v['value'] . '",' . "\n";

                    if ($_POST['vars']['required'][$k]) {
                        $required .= '"' . $v['name'] . '",';
                    }

                    if ($_POST['vars']['labels'][$k]) {
                        $labels .= '"' . $v['name'] . '",';
                    }
                }

                $fields .= ')' . ',' . "\n";
            } else {
                $fields .= "\t" . '"' . $field['name'] . '"=>"' . $field['value'] . '",' . "\n";
            }

            if ($_POST['vars']['required'][$field_id]) {
                $required .= '"' . $field['name'] . '",';
            }

            if ($_POST['vars']['labels'][$field_id]) {
                $labels .= '"' . $field['name'] . '",';
            }
        }

        foreach ($_POST['vars']['subsections'][$section_id] as $subsection) {
            $subsections .= '"' . $subsection . '",';
        }

        $config .= '
$vars["fields"]["' . $section . '"]=array(
' . chop($fields) . '
);

$vars["required"]["' . $section . '"]=array(' . $required . ');

$vars["labels"]["' . $section . '"]=array(' . $labels . ');

$vars["label"]["' . $section . '"]=array(' . $label . ');

$vars["subsections"]["' . $section . '"]=array(' . $subsections . ');

$vars["settings"]["' . $section . '"]["import"]=' . str_to_bool($_POST['vars']['settings'][$section_id]['import']) . ';
$vars["settings"]["' . $section . '"]["export"]=' . str_to_bool($_POST['vars']['settings'][$section_id]['export']) . ';
$vars["settings"]["' . $section . '"]["shiftmail"]=' . str_to_bool($_POST['vars']['settings'][$section_id]['shiftmail']) . ';
$vars["settings"]["' . $section . '"]["sms"]=' . str_to_bool($_POST['vars']['settings'][$section_id]['sms']) . ';
$vars["settings"]["' . $section . '"]["show_id"]=' . str_to_bool($_POST['vars']['settings'][$section_id]['show_id']) . ';
';
    }

    $config .= '

$vars["files"]["dir"]="' . $_POST['vars']['files']['dir'] . '"; //folder to store files

#cms
$cms_config["editor"]="' . $_POST['cms_config']['editor'] . '";

#SHOP
$shop_enabled=' . str_to_bool($_POST['shop_enabled']) . ';

$shop_config["paypal_email"]="' . $_POST['shop_config']['paypal_email'] . '";
$shop_config["include_vat"]=' . str_to_bool($_POST['shop_config']['include_vat']) . ';

#mailer
$mailer_config["provider"]="' . $_POST['mailer_config']['provider'] . '";
$mailer_config["username"]="' . $_POST['mailer_config']['username'] . '";
$mailer_config["password"]="' . $_POST['mailer_config']['password'] . '";
$mailer_config["originator"]="' . $_POST['mailer_config']['originator'] . '";

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
            $option['list'] = strip_tags($option['list']);

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

    $_SESSION['message'] = 'Configuration Saved';

    redirect('/admin?option=configure');
}

$count['sections'] = 0;
$count['fields'] = 0;
$count['subsections'] = 0;
$count['options'] = 0;
?>

<script type="text/javascript">
jQuery(function() {
    jQuery( "#tabs" ).tabs();
});

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

function initSortables(){
    jQuery( ".fields, .subsections" ).sortable({
        handle: '.handle',
        opacity: 0.5,
        items: ".draggable",
        axis: 'y'
    });

    jQuery( "#sections" ).sortable({
        handle: '.handle',
        opacity: 0.5,
        items: ".draggable",
        axis: 'y'
    });
}

function delTR(obj)
{
	while(obj.nodeName!='TR'){
		obj=obj.parentNode;
	}

    obj.parentNode.removeChild(obj);
}

function addTR(obj,contentHolder,sortable,countKey,section_id)
{
	count[countKey]++;

	var contentRow=document.getElementById(contentHolder).childNodes[1].childNodes[0];

	while(obj.nodeName!='TBODY'){
		obj=obj.parentNode;
	}

	var row = obj.insertRow(obj.rows.length-1);

	row.style.display='none';

	row.className=contentRow.className;

	var content=contentRow.innerHTML;
	content=str_replace('{$count}',count[countKey],content);
	if( section_id ){
		content=str_replace('{$section_id}',section_id,content);
	}
    row.innerHTML=content;

	jQuery(row).fadeIn();

	//initAutoGrows();

	if( countKey=='sections' ){
		var form=document.getElementById('form');

		var section_template = jQuery('#section_template').val();

		form['sections['+count[countKey]+']'].value=section_template;

		for (var field in section_templates[section_template]) {
			if (section_templates[section_template].hasOwnProperty(field)) {
				addTR(document.getElementById('fields_'+count[countKey]),'tr_field',true,'fields',count[countKey]);

				form['vars[fields]['+count[countKey]+']['+count['fields']+'][name]'].value=field;
				form['vars[fields]['+count[countKey]+']['+count['fields']+'][value]'].value=section_templates[section_template][field];
			}
		}

		jQuery(form['sections['+count[countKey]+']']).focus();
		jQuery(form['sections['+count[countKey]+']']).select();
	}

	initSortables();
}

function set_list_type(id,type)
{
	if( type=='list' ){
		jQuery('#options_list_'+id).show();
		jQuery('#options_section_'+id).hide();
	}else{
		jQuery('#options_list_'+id).hide();
		jQuery('#options_section_'+id).show();
	}
}

window.onload=initSortables;

var section_templates=<?=json_encode($section_templates);?>;
</script>

<!-- these hidden tables are used to populate new table rows -->
<table id="tr_section" style="display:none;">
<tr class="draggable" id="tr_{$count}" style="height:100%">
	<td style="height:100%"><div class="handle" style="height:100%;">&nbsp;</div></td>
	<td>
		<h3>
			<input type="text" name="sections[{$count}]" value="" />
			<a href="javascript:;" onclick="jQuery('#div_{$count}').slideToggle(); return false;">toggle</a> &nbsp;&nbsp;
			<a href="javascript:;" onclick="delTR(this)">delete</a></h3>
		</h3>

		<div id="div_{$count}" style="display:none;">
			<table cellspacing="0">
			<tbody id="fields_{$count}" class="fields">
			<tr>
				<th>&nbsp;</th>
				<th>Name</th>
				<th>Label</th>
				<th>Type</th>
				<th>Label</th>
				<th>Required</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<td colspan="6"><a href="javascript:;" onclick="addTR(this,'tr_field',true,'fields','{$count}');">Add field</a></td>
			</tr>
			</tbody>
			</table>
			<br />

			<h4>Settings</h4>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][display]" value="1" checked="checked"> display
			</label>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][import]" value="1"> import
			</label>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][export]" value="1"> export
			</label>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][shiftmail]" value="1"> shiftmail
			</label>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][sms]" value="1"> sms
			</label>
			<label>
				<input type="checkbox" name="vars[settings][{$count}][show_id]" value="1"> show id
			</label>
			<br>
			<br />

			<h4>Subsections</h4>
			<table cellspacing="0">
			<tbody id="subsections_{$count}" class="subsections">
			<tr>
				<td><a href="javascript:;" onclick="addTR(this,'tr_subsection',true,'subsections',{$count});">Add subsection</a></td>
			</tr>
			</table>
		</div>
	</td>
</tr>
</table>

<table id="tr_field" style="display:none;">
<tr class="draggable">
	<td><div class="handle">&nbsp;</div></td>
	<td><input type="text" name="vars[fields][{$section_id}][{$count}][name]" value="" /></td>
	<td><input type="text" name="vars[fields][{$section_id}][{$count}][label]" value="" /></td>
	<td>
		<select name="vars[fields][{$section_id}][{$count}][value]" onblur="if( $(form['vars[fields][{$section_id}][{$count}][name]']).val()=='' ){ $(form['vars[fields][{$section_id}][{$count}][name]']).val($(this).val().replace('-',' ')) }">
			<?=html_options($field_opts);?>
		</select>
	</td>
	<td><input type="checkbox" name="vars[labels][{$count}]" value="1"></td>
	<td><input type="checkbox" name="vars[required][{$count}]" value="1"></td>
	<td><a href="javascript:;" onclick="delTR(this)">delete</a></td>
</tr>
</table>

<table id="tr_subsection" style="display:none;">
<tr class="draggable">
	<td><div class="handle">&nbsp;</div></td>
	<td>
		<select name="vars[subsections][{$section_id}][]">
			<?=html_options($section_opts, $v);?>
		</select>
	</td>
	<td><a href="javascript:;" onclick="delTR(this)">delete</a></td>
</tr>
</table>

<table id="tr_option" style="display:none;">
<tr>
	<th valign="top">
		<input type="text" name="options[{$count}][name]" value="<?=$opt;?>" /><br />
		<label><input type="radio" name="options[{$count}][type]" value="list" checked="checked" onclick="set_list_type('{$count}','list')" /> list</label><br />
		<label><input type="radio" name="options[{$count}][type]" value="section" onclick="set_list_type('{$count}','section')" /> section</label><br />
	</th>
	<td>
		<textarea id="options_list_{$count}" cols="30" type="text" name="options[{$count}][list]" class="autogrow"></textarea>
		<select id="options_section_{$count}" name="options[{$count}][section]" style="display:none;">
			<?=html_options($section_opts, $val);?>
		</select>
	</td>
	<td valign="top"><a href="javascript:;" onclick="delTR(this)">delete</a></td>
</tr>
</table>
<!-- end of hidden tables -->

<form method="post" id="form">
<input type="hidden" name="save" value="1" />

<div id="tabs">
    <ul class="nav">
        <li><a href="#sections">Sections</a></li>
        <li><a href="#general">General</a></li>
        <li><a href="#template">Template</a></li>
        <li><a href="#login">Login</a></li>
        <li><a href="#upload">Upload</a></li>
    </ul>

    <div id="sections">
        <div class="box">
        <table>
        <tbody id="sections">
        <?php
        foreach ($vars['fields'] as $section => $fields) {
            $count['sections']++; ?>
        	<tr class="draggable" id="tr_<?=$count['sections']; ?>">
        		<td style="height:100%"><div class="handle" style="height:100%;">&nbsp;</div></td>
        		<td>
        			<h3>
            			<input type="text" name="sections[<?=$count['sections']; ?>]" value="<?=$section; ?>" <?php if ('users' == $section) { ?> readonly <?php } ?> />
            			<a href="javascript:;" onclick="jQuery('#div_<?=$count['sections']; ?>').slideToggle(); return false;">toggle</a> &nbsp;&nbsp;
            			<?php if ('users' != $section) { ?>
            			    <a href="javascript:;" onclick="delTR(this)">delete</a>
            			<?php } ?>
        			</h3>

        			<div id="div_<?=$count['sections']; ?>" style="display:none;">
        				<table cellspacing="0">
        				<tbody id="fields_<?=$count['sections']; ?>" class="fields">
        				<tr>
        					<th>&nbsp;</th>
        					<th>Name</th>
        					<th>Label</th>
        					<th>Type</th>
        					<th>Label</th>
        					<th>Required</th>
        					<th>&nbsp;</th>
        				</tr>
        				<?php
                        foreach ($vars['fields'][$section] as $k => $v) {
                            $count['fields']++;

                            if (is_array($v)) {
                                $field_type = 'array';
                            } else {
                                $field_type = $v;
                            } ?>
        				<tr class="draggable">
        					<td><div class="handle">&nbsp;</div></td>
        					<td><input type="text" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][name]" value="<?=$k; ?>" /></td>
        					<td><input type="text" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][label]" value="<?=$vars['label'][$section][$k]; ?>" /></td>
        					<td>
        						<select name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][value]">
        							<?=html_options($field_opts, $field_type); ?>
        						</select>
        					</td>
        					<td><input type="checkbox" name="vars[labels][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k, $vars['labels'][$section])) { ?> checked<?php } ?>></td>
        					<td><input type="checkbox" name="vars[required][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k, $vars['required'][$section])) { ?> checked<?php } ?>></td>
        					<td><a href="javascript:;" onclick="delTR(this)">delete</a></td>
        				</tr>
        				<?php
                            if (is_array($v)) {
                                $parent = $count['fields'];
                                foreach ($v as $k2 => $v2) {
                                    $count['fields']++; ?>
        						<tr class="draggable">
        							<td><div class="handle" style="margin-left:10px;">&nbsp;</div></td>
        							<td>
        								<input type="hidden" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][parent]" value="<?=$parent; ?>" />

        								<input type="text" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][name]" value="<?=$k2; ?>" />
        							</td>
        							<td>
        								<select name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][value]">
        									<?=html_options($field_opts, $v2); ?>
        								</select>
        							</td>
        							<td><input type="checkbox" name="vars[labels][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k2, $vars['labels'][$section])) { ?> checked<?php } ?>></td>
        							<td><input type="checkbox" name="vars[required][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k2, $vars['required'][$section])) { ?> checked<?php } ?>></td>
        							<td><a href="javascript:;" onclick="delTR(this)">delete</a></td>
        						</tr>
        						<?php
                                }
                            }
                        } ?>
        				<tr>
        					<td colspan="6"><a href="javascript:;" onclick="addTR(this,'tr_field',true,'fields','<?=$count['sections']; ?>');">Add field</a></td>
        				</tr>
        				</tbody>
        				</table>
        				<br />

        				<h4>Settings</h4>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][display]" value="1" <?php if (in_array($section, $vars['sections'])) { ?> checked<?php } ?>> display
        				</label>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][import]" value="1" <?php if ($vars['settings'][$section]['import']) { ?> checked<?php } ?>> import
        				</label>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][export]" value="1" <?php if ($vars['settings'][$section]['export']) { ?> checked<?php } ?>> export
        				</label>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][shiftmail]" value="1" <?php if ($vars['settings'][$section]['shiftmail']) { ?> checked<?php } ?>> shiftmail
        				</label>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][sms]" value="1" <?php if ($vars['settings'][$section]['sms']) { ?> checked<?php } ?>> sms
        				</label>
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][show_id]" value="1" <?php if ($vars['settings'][$section]['show_id']) { ?> checked<?php } ?>> show id
        				</label>
        				<br>
        				<br />

        				<h4>Subsections</h4>
        				<table cellspacing="0">
        				<tbody id="subsections_<?=$count['sections']; ?>" class="subsections">
        				<?php
                        foreach ($vars['subsections'][$section] as $k => $v) {
                            $count['subsections']++; ?>
        				<tr class="draggable" id="tr_<?=$count['sections']; ?>_<?=$v; ?>">
        					<td><div class="handle">&nbsp;</div></td>
        					<td>
        						<select name="vars[subsections][<?=$count['sections']; ?>][]">
        							<?=html_options($section_opts, $v); ?>
        						</select>
        					</td>
        					<td><a href="javascript:;" onclick="delTR(this)">delete</a></td>
        				</tr>
        				<?php
                        } ?>
        				<tr>
        					<td colspan="2"><a href="javascript:;" onclick="addTR(this,'tr_subsection',true,'subsections','<?=$count['sections']; ?>');">Add subsection</a></td>
        				</tr>
        				</tbody>
        				</table>
        			</div>
        		</td>
        	</tr>
        <?php
        }
        ?>
        <tr>
        	<td colspan="2">
        		<select id="section_template">
        			<?=html_options(array_keys($section_templates));?>
        		</select>
        		<a href="javascript:;" onclick="addTR(this,'tr_section',true,'sections');">Add section</a></td>
        </tr>
        </tbody>
        </table>
        </div>
        <br>

        <h2>Drop-down options</h2>

        <div id="options_config">
        	<div style="padding:5px 10px;">
        		<table id="options" width="400" class="box">
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
        			<td valign="top">
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
        			<td valign="top"><a href="javascript:;" onclick="delTR(this)">delete</a></td>
        		</tr>
        		<?php
                }
                ?>
        		<tr>
        			<td colspan="3" ><a href="javascript:;" onclick="addTR(this,'tr_option',false,'options');">Add option</a></td>
        		</tr>
        		</tfoot>
        		</table>
        	</div>
        </div>
        <br>

        <h2>Website code</h2>
        <ul style="list-style:inside; margin-left:20px;">
        <?php
        foreach ($vars['fields'] as $section => $fields) {
            ?>
        	<li><a href="javascript:;" onclick="jQuery('#code_<?=underscored($section); ?>').slideToggle(); return false;"><?=$section; ?></a></li>

        	<div id="code_<?=underscored($section); ?>" style="display:none;">
        		<div style="padding:5px 10px; background:#fff;">
        		    <pre>

        <?php
        $source = '
        <?php
        $content = $cms->get(\'' . $section . '\'';

            if (in_array('id', $fields)) {
                $source .= ',$_GET[\'id\'],1';
            }

            $source .= ');
        ';

            if (in_array('id', $fields)) {
                $source .= '$items = $cms->get(\'' . $section . '\');' . "\n";
            }
            $source .= '
        ?>
        ';

            if (in_array('id', $fields)) {
                $source .= '<div>';
                $source .= '
        <?php foreach( $items as $v ){ ?>
        	<a href="?id=<?=$v[\'id\'];?>"><?=$v[\'heading\'];?></a><br>
        <?php } ?>
        </div>
        ';
            }

            $source .= '<div>
        ';
            foreach ($fields as $k => $v) {
                $source .= "\t" . $k . ': <?=$content[\'' . $k . '\'];?><br>' . "\n";
            }
            $source .= '
        </div>
        ';

            print htmlentities($source); ?>
                    </pre>
        		</div>
        	</div>
        <?php
        }
        ?>
        </ul>
    </div>

    <div id="general">
        <div id="general_config">
        	<div style="padding:5px 10px;">
        		<table>
        		<tr>
        			<th>From email</th>
        			<td><input type="text" name="from_email" value="<?=$from_email;?>"></td>
        		</tr>
        		<tr>
        			<th>languages</th>
        			<td><textarea type="text" name="languages"><?=implode("\n", $languages);?></textarea></td>
        		</tr>
        		<tr>
        			<th>configure dropdowns</th>
        			<td><input type="checkbox" name="vars[configure_dropdowns]" value="1" <?php if ($vars['configure_dropdowns']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>folder to store upload data</th>
        			<td><input type="text" name="vars[files][dir]" value="<?=$vars['files']['dir'];?>"></td>
        		</tr>
        		<tr>
        			<th>shopping cart</th>
        			<td><input type="checkbox" name="shop_enabled" value="1" <?php if ($shop_enabled) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>paypal email</th>
        			<td><input type="text" name="shop_config[paypal_email]" value="<?=$shop_config['paypal_email'];?>"></td>
        		</tr>
            	<tr>
        			<th>gc merchant id</th>
        			<td><input type="text" name="shop_config[gc_merchant_id]" value="<?=$shop_config['gc_merchant_id'];?>"></td>
        		</tr>
                <tr>
        			<th>gc merchant key</th>
        			<td><input type="text" name="shop_config[gc_merchant_key]" value="<?=$shop_config['gc_merchant_key'];?>"></td>
        		</tr>
        		<tr>
        			<th>vat</th>
        			<td><input type="checkbox" name="shop_config[include_vat]" value="1" <?php if ($shop_config['include_vat']) { ?> checked<?php } ?>></td>
        		</tr>
            	<tr>
        			<th>editor</th>
        			<td>
        				<select name="cms_config[editor]">
        					<option value=""></option>
        					<?=html_options(['xinha','tinymce'], $cms_config['editor']);?>
        				</select>
        			</td>
        		</tr>
        		<tr>
        			<th>sms provider</th>
        			<td>
        				<select name="sms_config[provider]">
        					<option value=""></option>
        					<?=html_options(['esendex','txtlocal'], $sms_config['provider']);?>
        				</select>
        			</td>
        		</tr>
        		<tr>
        			<th>sms username</th>
        			<td><input type="text" name="sms_config[username]" value="<?=$sms_config['username'];?>"></td>
        		</tr>
        		<tr>
        			<th>sms password</th>
        			<td><input type="text" name="sms_config[password]" value="<?=$sms_config['password'];?>"></td>
        		</tr>
        		<tr>
        			<th>sms account</th>
        			<td><input type="text" name="sms_config[account]" value="<?=$sms_config['account'];?>"></td>
        		</tr>
        		<tr>
        			<th>sms originator</th>
        			<td><input type="text" name="sms_config[originator]" value="<?=$sms_config['originator'];?>"></td>
        		</tr>
        		<tr>
        			<th>mailer provider</th>
        			<td>
        				<select name="mailer_config[provider]">
        					<option value=""></option>
        					<?=html_options(['shiftmail'], $mailer_config['provider']);?>
        				</select>
        			</td>
        		</tr>
        		<tr>
        			<th>mailer username</th>
        			<td><input type="text" name="mailer_config[username]" value="<?=$mailer_config['username'];?>"></td>
        		</tr>
        		<tr>
        			<th>mailer password</th>
        			<td><input type="password" name="mailer_config[password]" value="<?=$mailer_config['password'];?>"></td>
        		</tr>
        		<tr>
        			<th>mailer originator</th>
        			<td><input type="text" name="mailer_config[originator]" value="<?=$mailer_config['originator'];?>"></td>
        		</tr>
        		<tr>
        			<th>twitter consumer key</th>
        			<td><input type="text" name="vars[twitter][consumer_key]" value="<?=$vars['twitter']['consumer_key'];?>"></td>
        		</tr>
        		<tr>
        			<th>twitter consumer secret</th>
        			<td><input type="text" name="vars[twitter][consumer_secret]" value="<?=$vars['twitter']['consumer_secret'];?>"></td>
        		</tr>
        		<tr>
        			<th>twitter oauth token</th>
        			<td><input type="text" name="vars[twitter][oauth_token]" value="<?=$vars['twitter']['oauth_token'];?>"></td>
        		</tr>
        		<tr>
        			<th>twitter oauth secret</th>
        			<td><input type="text" name="vars[twitter][oauth_secret]" value="<?=$vars['twitter']['oauth_secret'];?>"></td>
        		</tr>
        		<tr>
        			<th>Theme</th>
        			<td>
        				<select name="admin_config[theme]">
        					<option value="">Default</option>
        					<?=html_options($theme_opts, $admin_config['theme']);?>
        				</select>
        			</td>
        		</tr>
        		</table>
        	</div>
        </div>
    </div>

    <div id="template">
        <div id="tpl_config">
        	<div style="padding:5px 10px;">
        		<table>
        		<tr>
        			<th valign="top">Catchers</th>
        			<td><textarea name="tpl_config[catchers]" cols="70" class="autogrow"><?=implode("\n", $tpl_config['catchers']);?></textarea></td>
        		</tr>
        		<tr>
        			<th valign="top">Redirects</th>
        			<td>
        				<?php
                        $redirects = '';
                        foreach ($tpl_config['redirects'] as $k => $v) {
                            $redirects .= $k . '=' . $v . "\n";
                        }
                        $redirects = trim($redirects);
                        ?>
        				<textarea name="tpl_config[redirects]" cols="70" class="autogrow"><?=$redirects;?></textarea>
        			</td>
        		</tr>
        		<tr>
        			<th valign="top">Secure</th>
        			<td>
        				<?php
                        $secure = '';
                        foreach ($tpl_config['secure'] as $v) {
                            $secure .= $v . "\n";
                        }
                        $secure = trim($secure);
                        ?>
        				<textarea name="tpl_config[secure]" cols="70" class="autogrow"><?=$secure;?></textarea>
        			</td>
        		</tr>
        		<tr>
        			<th valign="top">Site-wide SSL</th>
		        		<td><input type="checkbox" name="tpl_config[ssl]" value="1" <?php if ($tpl_config['ssl']) { ?> checked<?php } ?>></td>
        		</tr>
        		</table>
        	</div>
        </div>
    </div>

    <div id="login">
        <div id="auth_config">
        	<div style="padding:5px 10px;">
        		<table>
        		<tr>
        			<th>Table</th>
        			<td><input type="text" name="auth_config[table]" value="<?=$auth_config['table'];?>"></td>
        		</tr>
        		<tr>
        			<th>login</th>
        			<td><input type="text" name="auth_config[login]" value="<?=$auth_config['login'];?>"></td>
        		</tr>
        		<tr>
        			<th>register success</th>
        			<td><input type="text" name="auth_config[register_success]" value="<?=$auth_config['register_success'];?>"></td>
        		</tr>
        		<tr>
        			<th>forgot success</th>
        			<td><input type="text" name="auth_config[forgot_success]" value="<?=$auth_config['forgot_success'];?>"></td>
        		</tr>
        		<tr>
        			<th>hash passwords</th>
        			<td><input type="checkbox" name="auth_config[hash_password]" value="1" <?php if ($auth_config['hash_password']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>email activation</th>
        			<td><input type="checkbox" name="auth_config[email_activation]" value="1" <?php if ($auth_config['email_activation']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>activation required</th>
        			<td><input type="checkbox" name="auth_config[activation_required]" value="1" <?php if ($auth_config['activation_required']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>register login</th>
        			<td><input type="checkbox" name="auth_config[register_login]" value="1" <?php if ($auth_config['register_login']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>secret phrase</th>
        			<td><input type="text" name="auth_config[secret_phrase]" value="<?=$auth_config['secret_phrase'];?>"></td>
        		</tr>
        		<tr>
        			<th>cookie prefix</th>
        			<td><input type="text" name="auth_config[cookie_prefix]" value="<?=$auth_config['cookie_prefix'];?>"></td>
        		</tr>
        		<tr>
        			<th>cookie duration</th>
        			<td><input type="text" name="auth_config[cookie_duration]" value="<?=$auth_config['cookie_duration'];?>"></td>
        		</tr>
        		<tr>
        			<th>registration notification</th>
        			<td><input type="checkbox" name="auth_config[registration_notification]" value="1" <?php if ($auth_config['registration_notification']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>facebook appId</th>
        			<td><input type="text" name="auth_config[facebook_appId]" value="<?=$auth_config['facebook_appId'];?>"></td>
        		</tr>
        		<tr>
        			<th>facebook secret</th>
        			<td><input type="text" name="auth_config[facebook_secret]" value="<?=$auth_config['facebook_secret'];?>"></td>
        		</tr>
        		<tr>
        			<th>login wherestr</th>
        			<td><textarea name="auth_config[login_wherestr]"><?=$auth_config['login_wherestr'];?></textarea></td>
        		</tr>
        		</table>
        	</div>
        </div>
    </div>

    <div id="upload">
        <div id="upload_config">
        	<div style="padding:5px 10px;">
        		<div>
        			<table>
        			<tr>
        				<th>upload dir</th>
        				<td><input type="text" name="upload_config[upload_dir]" value="<?=$upload_config['upload_dir'];?>"></td>
        			</tr>
        			<tr>
        				<th>web path</th>
        				<td><input type="text" name="upload_config[web_path]" value="<?=$upload_config['web_path'];?>"></td>
        			</tr>
        			<tr>
        				<th>resize images</th>
        				<td><input type="checkbox" name="upload_config[resize_images]" value="1" <?php if ($upload_config['resize_images']) { ?> checked<?php } ?>></td>
        			</tr>
        			<tr>
        				<th>resize dimensions</th>
        				<td><input type="text" name="upload_config[resize_dimensions]" value="<?=implode('x', $upload_config['resize_dimensions']);?>"></td>
        			</tr>
        			<tr>
        				<th>allowed exts</th>
        				<td><textarea type="text" name="upload_config[allowed_exts]" class="autogrow"><?=implode("\n", $upload_config['allowed_exts']);?></textarea></td>
        			</tr>
        			</table>
        		</div>
        	</div>
        </div>
    </div>
</div>

<br>
    <p>
		<button type="submit" onclick="return confirm('WARNING: changing settings can result in loss of data or functionality. Are you sure you want to continue?');">Save config</button>
	</p>
</div>
<br>

<input type="hidden" name="last" value="1">
</form>

<script type="text/ecmascript">
var count=<?=json_encode($count);?>
</script>

<script src="/_lib/js/jquery.maxsubmit.js"></script>
<script type="text/javascript">
    /* Plugin: Max Submit Protect */
    jQuery(document).ready(function($) {
        $('form[method*=post]').maxSubmit({
            max_count: <?=$max_input_vars;?>,
            confirm_display: function(){
                alert('Save aborted: This form has too many fields for the server to accept.');
                return false;
            }
        });
    })
</script>