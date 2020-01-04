<?php
if (1 != $auth->user['admin']) {
    die('access denied');
}

$max_input_vars = ini_get('max_input_vars');

global $db_config, $auth_config, $upload_config, $shop_config, $shop_enabled, $from_email, $tpl_config, $live_site, $vars, $table_dropped, $count, $section, $table, $fields, $admin_config, $cms_config;

// internal tables
$file_fields = [
    'date' => 'date',
    'name' => 'text',
    'size' => 'text',
    'type' => 'text',
];

$cms_privileges_fields = [
    'user' => 'text',
    'section' => 'text',
    'access' => 'int',
    'filter' => 'text',
];

$cms_filters = [
    'user' => 'text',
    'section' => 'text',
    'name' => 'text',
    'filter' => 'textarea',
];

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

$section_templates['blank'] = [];

function array_to_csv($array): ?string // returns null or string
{
    if (null === $array) {
        return '';
    }

    foreach ($array as $k => $v) {
        $array[$k] = "\t'" . addslashes($v) . "'";
    }

    return implode(",\n", $array);
}

function str_to_csv($str): string
{
    if (!$str) {
        return '';
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
        return '';
    }

    $array = explode("\n", $str);

    foreach ($array as $k => $v) {
        $pos = strpos($v, '=');
        $array[$k] = "\t'" . addslashes(trim(substr($v, 0, $pos))) . "'=>'" . addslashes(trim(substr($v, $pos + 1))) . "'" . '';
    }

    return implode(",\n", $array);
}

function str_to_bool($str): string
{
    if ($str) {
        return 'true';
    }
    return 'false';
}

// todo, auto-generate this list from the components dir
$field_opts = [
    'text',
    'textarea',
    'hidden',
    'integer',
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
    'avg_rating',
    'select',
    'radio',
    'combo',
    'password',
    'email',
    'postcode',
    'tel',
    'mobile',
    'url',
    'ip',
    'page_name',
    'approve',
    'parent',
    'position',
    'read',
    'deleted',
    'id',
    'separator',
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
    global $vars, $table_dropped, $count, $section, $table, $fields, $cms;

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
                if (in_array($v, ['id', 'separator', 'checkboxes'])) { //don't drop id!
                    continue;
                }

                $query = "ALTER TABLE `$table` DROP `" . underscored($k) . '`';
                sql_query($query);
                continue;
            }

            if (underscored($k) != underscored($new_name) or $v != $new_type) {
                $db_field = $cms->form_to_db($new_type);

                if ($db_field) {
                    $query = "ALTER TABLE `$table` CHANGE `" . underscored($k) . '` `' . underscored($new_name) . '` ' . $db_field . ' ';
                }

                if ($query and 'hidden' != $new_type) {
                    sql_query($query);
                }

                //convert select to checkboxes
                if ('select' == $v and 'checkboxes' == $new_type) {
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

    $this->check_table('cms_filters', $cms_filters);
    $this->check_table('files', $file_fields);
    $this->check_table('cms_multiple_select', $cms_multiple_select_fields);
    $this->check_table('cms_privileges', $cms_privileges_fields);

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
            if (in_array($field['value'], ['separator', 'checkboxes'])) {
                continue;
            }

            if (in_array($field['value'], ['select', 'radio'])) {
                $field_options[] = $field['name'];
            }

            if (in_array($field['name'], $fields)) {
                $after = underscored($field['name']);
            } else {
                $db_field = $this->form_to_db($field['value']);

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
                $this->check_table($table, $fields);
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

#TPL
$tpl_config["catchers"]=array(' . str_to_csv($_POST['tpl_config']['catchers']) . ');

$tpl_config["redirects"]=array(' . str_to_assoc($_POST['tpl_config']['redirects']) . ');

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
$auth_config["from_email"]  = $from_email;

//specify pages where users are redirected
$auth_config["login"]="' . $_POST['auth_config']['login'] . '";
$auth_config["register_success"]="' . $_POST['auth_config']['register_success'] . '";
$auth_config["forgot_success"]="' . $_POST['auth_config']['forgot_success'] . '";

//hash passwords
$auth_config["hash_password"]=' . str_to_bool($_POST['auth_config']['hash_password']) . ';

//email activation
$auth_config["email_activation"]=' . str_to_bool($_POST['auth_config']['email_activation']) . ';

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
$upload_config["resize_images"]=' . str_to_bool($_POST['upload_config']['resize_images']) . ';
$upload_config["resize_dimensions"]=array(' . str_replace('x', ',', $_POST['upload_config']['resize_dimensions']) . ');

$upload_config["allowed_exts"]=array(' . str_to_csv($_POST['upload_config']['allowed_exts']) . ');

#ADMIN AREA
// sections in menu
$vars["sections"]=array(
' . array_to_csv($display) . '
);

//fields in each section
';

    foreach ($_POST['sections'] as $section_id => $section) {
        $fields = '';
        $required = '';
        $subsections = '';
        $label = '';

        foreach ($_POST['vars']['fields'][$section_id] as $field_id => $field) {
            if ($_POST['vars']['fields'][$section_id][$field_id]['label']) {
                $label .= '"' . $field['name'] . '" => "' . $_POST['vars']['fields'][$section_id][$field_id]['label'] . '", ';
            }

            $fields .= "\t" . '"' . $field['name'] . '"=>"' . $field['value'] . '",' . "\n";

            if ($_POST['vars']['required'][$field_id]) {
                $required .= '"' . $field['name'] . '",';
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

$vars["subsections"]["' . $section . '"]=array(' . $subsections . ');

';
    }

    $config .= '

$vars["files"]["dir"]="' . $_POST['vars']['files']['dir'] . '"; //folder to store files

#SHOP
$shop_enabled=' . str_to_bool($_POST['shop_enabled']) . ';

$shop_config["paypal_email"]="' . $_POST['shop_config']['paypal_email'] . '";
$shop_config["include_vat"]=' . str_to_bool($_POST['shop_config']['include_vat']) . ';

#OPTIONS
';

    foreach ($_POST['options'] as $option) {
        while (in_array($option['name'], $field_options)) {
            $index = array_search($option['name'], $field_options);
            unset($field_options[$index]);
        }

        if ('section' == $option['type']) {
            $config .= '
$vars["options"]["' . $option['name'] . '"]="' . $option['section'] . '";
';
        } else {
            $option['list'] = strip_tags($option['list']);

            if (strstr($option['list'], '=')) {
                $config .= '
$vars["options"]["' . $option['name'] . '"]=array(
' . str_to_assoc($option['list']) . '
);
';
            } else {
                $config .= '
$vars["options"]["' . $option['name'] . '"]=array(
' . str_to_csv($option['list']) . '
);
';
            }
        }
    }

    foreach ($field_options as $field_option) {
        if (!in_array($field_option, $_POST['options'])) {
            $config .= '
$vars["options"]["' . $field_option . '"]="";
';
        }
    }

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
        items: ".draggableSections",
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
<tr class="draggableSections" id="tr_{$count}" style="height:100%">
    <td valign="top">
		<a href="javascript:;" onclick="$('#div_{$count}').slideToggle(); $(this).find('i').toggleClass('fa-rotate-90'); return false;"><i class="fas fa-caret-right"></i></a>
    </td>
	<td style="height:100%" valign="top"><div class="handle" style="height:100%;">&nbsp;</div></td>
	<td>
		<h3>
			<input type="text" name="sections[{$count}]" value="" />
			<a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></h3>
		</h3>

		<div id="div_{$count}" style="display:none;">
			<label>
				<input type="checkbox" name="vars[settings][{$count}][display]" value="1" checked="checked">  Show in navigation
			</label>
			<br>
			
			<table cellspacing="0">
			<tbody id="fields_{$count}" class="fields">
			<tr>
				<th>&nbsp;</th>
				<th>Name</th>
				<th>Label</th>
				<th>Type</th>
				<th>Required</th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<td colspan="6"><a href="javascript:;" onclick="addTR(this,'tr_field',true,'fields','{$count}');">Add field</a></td>
			</tr>
			</tbody>
			</table>
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
	<td style="text-align: center;"><input type="checkbox" name="vars[required][{$count}]" value="1"></td>
	<td><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
	<td><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
	<td valign="top"><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
</tr>
</table>
<!-- end of hidden tables -->

<form method="post" id="form">
<input type="hidden" name="save" value="1" />

<div id="tabs">
    <ul class="nav">
        <li><a href="#sections">Sections</a></li>
        <li><a href="#dropdowns">Dropdowns</a></li>
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
        	<tr class="draggableSections" id="tr_<?=$count['sections']; ?>">
        	    <td valign="top">
            		<a href="javascript:;" onclick="$('#div_<?=$count['sections']; ?>').slideToggle(); $(this).find('i').toggleClass('fa-rotate-90'); return false;"><i class="fas fa-caret-right"></i></a> &nbsp;&nbsp;
        	    </td>
        		<td style="height:100%" valign="top"><div class="handle" style="height:100%;">&nbsp;</div></td>
        		<td>
        			<h3>
            			<input type="text" name="sections[<?=$count['sections']; ?>]" value="<?=$section; ?>" <?php if ('users' == $section) { ?> readonly <?php } ?> />
            			<?php if ('users' != $section) { ?>
            			    <a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a>
            			<?php } ?>
        			</h3>

        			<div id="div_<?=$count['sections']; ?>" style="display:none;">
        				<label>
        					<input type="checkbox" name="vars[settings][<?=$count['sections']; ?>][display]" value="1" <?php if (in_array($section, $vars['sections'])) { ?> checked<?php } ?>> Show in navigation
        				</label>
        				<br>
        				
        				<table cellspacing="0">
        				<tbody id="fields_<?=$count['sections']; ?>" class="fields">
        				<tr>
        					<th>&nbsp;</th>
        					<th>Name</th>
				            <th>Label</th>
        					<th>Type</th>
        					<th>Required</th>
        					<th>&nbsp;</th>
        				</tr>
        				<?php
                        foreach ($vars['fields'][$section] as $k => $v) {
                            $count['fields']++;

                            $field_type = $v; ?>
        				<tr class="draggable">
        					<td><div class="handle">&nbsp;</div></td>
        					<td><input type="text" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][name]" value="<?=$k; ?>" /></td>
        					<td><input type="text" name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][label]" value="<?=$vars['label'][$section][$k]; ?>" /></td>
        					<td>
        						<select name="vars[fields][<?=$count['sections']; ?>][<?=$count['fields']; ?>][value]">
        							<?=html_options($field_opts, $field_type); ?>
        						</select>
        					</td>
        					<td style="text-align: center;"><input type="checkbox" name="vars[required][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k, $vars['required'][$section])) { ?> checked<?php } ?>></td>
        					<td><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
        							<td><input type="checkbox" name="vars[required][<?=$count['fields']; ?>]" value="1" <?php if (in_array($k2, $vars['required'][$section])) { ?> checked<?php } ?>></td>
        							<td><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
        				<br>

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
        					<td><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
        	<td colspan="5">
        		<select id="section_template">
        			<?=html_options(array_keys($section_templates));?>
        		</select>
        		<a href="javascript:;" onclick="addTR(this,'tr_section',true,'sections');">Add section</a></td>
        </tr>
        </tbody>
        </table>
        </div>

    </div>
    
    
    <div id="dropdowns">
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
        			<td valign="top"><a href="javascript:;" onclick="delTR(this)"><i class="fas fa-trash"></i></a></td>
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
        			<th>Folder to store upload data</th>
        			<td><input type="text" name="vars[files][dir]" value="<?=$vars['files']['dir'];?>"></td>
        		</tr>
        		<tr>
        			<th>Shopping cart</th>
        			<td><input type="checkbox" name="shop_enabled" value="1" <?php if ($shop_enabled) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>Paypal email</th>
        			<td><input type="text" name="shop_config[paypal_email]" value="<?=$shop_config['paypal_email'];?>"></td>
        		</tr>
        		<tr>
        			<th>VAT</th>
        			<td><input type="checkbox" name="shop_config[include_vat]" value="1" <?php if ($shop_config['include_vat']) { ?> checked<?php } ?>></td>
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
        			<th>Login</th>
        			<td><input type="text" name="auth_config[login]" value="<?=$auth_config['login'];?>"></td>
        		</tr>
        		<tr>
        			<th>Register success</th>
        			<td><input type="text" name="auth_config[register_success]" value="<?=$auth_config['register_success'];?>"></td>
        		</tr>
        		<tr>
        			<th>Forgot success</th>
        			<td><input type="text" name="auth_config[forgot_success]" value="<?=$auth_config['forgot_success'];?>"></td>
        		</tr>
        		<tr>
        			<th>Hash passwords</th>
        			<td><input type="checkbox" name="auth_config[hash_password]" value="1" <?php if ($auth_config['hash_password']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>Email activation</th>
        			<td><input type="checkbox" name="auth_config[email_activation]" value="1" <?php if ($auth_config['email_activation']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>Secret phrase</th>
        			<td><input type="text" name="auth_config[secret_phrase]" value="<?=$auth_config['secret_phrase'];?>"></td>
        		</tr>
        		<tr>
        			<th>Cookie prefix</th>
        			<td><input type="text" name="auth_config[cookie_prefix]" value="<?=$auth_config['cookie_prefix'];?>"></td>
        		</tr>
        		<tr>
        			<th>Cookie duration</th>
        			<td><input type="text" name="auth_config[cookie_duration]" value="<?=$auth_config['cookie_duration'];?>"></td>
        		</tr>
        		<tr>
        			<th>Registration notification</th>
        			<td><input type="checkbox" name="auth_config[registration_notification]" value="1" <?php if ($auth_config['registration_notification']) { ?> checked<?php } ?>></td>
        		</tr>
        		<tr>
        			<th>Facebook appId</th>
        			<td><input type="text" name="auth_config[facebook_appId]" value="<?=$auth_config['facebook_appId'];?>"></td>
        		</tr>
        		<tr>
        			<th>Facebook secret</th>
        			<td><input type="text" name="auth_config[facebook_secret]" value="<?=$auth_config['facebook_secret'];?>"></td>
        		</tr>
        		<tr>
        			<th>Login wherestr</th>
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
        				<th>Upload dir</th>
        				<td><input type="text" name="upload_config[upload_dir]" value="<?=$upload_config['upload_dir'];?>"></td>
        			</tr>
        			<tr>
        				<th>Resize images</th>
        				<td><input type="checkbox" name="upload_config[resize_images]" value="1" <?php if ($upload_config['resize_images']) { ?> checked<?php } ?>></td>
        			</tr>
        			<tr>
        				<th>Resize dimensions</th>
        				<td><input type="text" name="upload_config[resize_dimensions]" value="<?=implode('x', $upload_config['resize_dimensions']);?>"></td>
        			</tr>
        			<tr>
        				<th>Allowed exts</th>
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

<script>
    (function($) {
    	/**
    	 * Set a trigger on a form to pop up a warning if the fields to be submitted
    	 * exceed a specified maximum.
    	 * Usage: $('form#selector').maxSubmit({options});
    	 */
    	$.fn.maxSubmit = function(options) {
    		// this.each() is the wrapper for each form.
    		return this.each(function() {
    
    			var settings = $.extend({
    				// The maximum number of parameters the form will be allowed to submit
    				// before the user is issued a confirm (OK/Cancel) dialogue.
    
    				max_count: 1000,
    
    				// The message given to the user to confirm they want to submit anyway.
    				// Can use {max_count} as a placeholder for the permitted maximum
    				// and {form_count} for the counted form items.
    
    				max_exceeded_message:
    					'This form has too many fields for the server to accept.\n'
    					+ ' Data may be lost if you submit. Are you sure you want to go ahead?',
    
    				// The function that will display the confirm message.
    				// Replace this with something fancy such as jquery.ui if you wish.
    
    				confirm_display: function(form_count) {
    					if (typeof(form_count) === 'undefined') form_count = '';
    					return confirm(
    						settings
    							.max_exceeded_message
    							.replace("{max_count}", settings.max_count)
    							.replace("{form_count}", form_count)
    					);
    				}
    			}, options);
    
    			// Form elements will be passed in, so we need to trigger on
    			// an attempt to submit that form.
    
    			// First check we do have a form.
    			if ($(this).is("form")) {
    				$(this).on('submit', function(e) {
    					// We have a form, so count up the form items that will be
    					// submitted to the server.
    
    					// For now, add one for the submit button.
    					var form_count = $(this).maxSubmitCount() + 1;
    
    					if (form_count > settings.max_count) {
    						// If the user cancels, then abort the form submit.
    						if (!settings.confirm_display(form_count)) return false;
    					}
    
    					// Allow the submit to go ahead.
    					return true;
    				});
    			}
    
    			// Support chaining.
    			return this;
    		});
    	};
    
    	/**
    	 * Count the number of fields that will be posted in a form.
    	 * If return_elements is true, then an array of elements will be returned
    	 * instead of the count. This is handy for testing.
    	 * TODO: elements without names will not be submitted.
    	 * Another approach may be to get all input fields at once using $("form :input")
    	 * then knock out the ones that we don't want. That would keep the same order as the
    	 * items would be submitted.
    	 */
    	$.fn.maxSubmitCount = function(return_elements) {
    		// Text fields and submit buttons will all post one parameter.
    
    		// Find the textareas.
    		// These will count as one post parameter each.
    		var fields = $('textarea:enabled[name]', this).toArray();
    
    		// Find the basic textual input fields (text, email, number, date and similar).
    		// These will count as one post parameter each.
    		// We deal with checkboxes, radio buttons sparately.
    		// Checkboxes will post only if checked, so exclude any that are not checked.
    		// There may be multiple form submit buttons, but only one will be posted with the
    		// form, assuming the form has been submitted by the user with a button.
    		// An image submit will post two fields - an x and y coordinate.
    		fields = fields.concat(
    			$('input:enabled[name]', this)
    				// Data items that are handled later.
    				.not("[type='checkbox']:not(:checked)")
    				.not("[type='radio']")
    				.not("[type='file']")
    				.not("[type='reset']")
    				// Submit form items.
    				.not("[type='submit']")
    				.not("[type='button']")
    				.not("[type='image']")
    				.toArray()
    		);
    
    		// Single-select lists will always post one value.
    		fields = fields.concat(
    			$('select:enabled[name]', this)
    				.not('[multiple]')
    				.toArray()
    		);
    
    		// Multi-select lists will post one parameter for each selected option.
    		// The parent select is $(this).parent() with its name being $(this).parent().attr('name')
    		$('select[multiple]:enabled[name] option:selected', this).each(function() {
    			// We collect all the options that have been selected.
    			fields = fields.concat(this);
    		});
    
    		// Each radio button group will post one parameter.
    		// We assume all checked radio buttons will be posted.
    		fields = fields.concat(
    			$('input:enabled:radio:checked', this)
    				.toArray()
    		);
    
    		// TODO: provide an option to return an array of objects containing the form field names,
    		// types and values, in a form that can be compared to what is actually posted.
    		if (typeof(return_elements) === 'undefined') return_elements = false;
    
    		if (return_elements === true) {
    			// Return the full list of elements for analysis.
    			return fields;
    		} else {
    			// Just return the number of elements matched.
    			return fields.length;
    		}
    	};
    }(jQuery));

    /* Plugin: Max Submit Protect */
    $(function($) {
        $('form[method*=post]').maxSubmit({
            max_count: <?=$max_input_vars;?>,
            confirm_display: function(){
                alert('Save aborted: This form has too many fields for the server to accept.');
                return false;
            }
        });
    })
</script>
