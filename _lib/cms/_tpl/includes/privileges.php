<?php
$privilege_options = [1 => 'Read', 2 => 'Write', 3 => 'Full'];

//save privileges
if (
	isset($_POST['privileges']) &&
    isset($has_privileges) &&
    $has_privileges
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

//privileges
if (
    isset($has_privileges) &&
    $has_privileges
 ) {
    $rows = sql_query("SELECT * FROM cms_privileges WHERE user='" . $this->id . "'");
    foreach ($rows as $row) {
        $privileges[$row['section']] = $row;
    }
    
?>
<form method="post">
<table class="box" width="100%">
<tr>
	<th>Section</th>
	<th>
		Access<br>
		<a href="#" id="privileges_none">None</a>,
		<a href="#" id="privileges_read">Read</a>,
		<a href="#" id="privileges_write">Write</a>,
		<a href="#" id="privileges_full">Full Control</a>
	</th>
	<th>Filter</th>
</tr>
<?php
	global $db_config;
	
	$tables = [];
	if ($vars['fields']) {
		$tables = $vars['fields'];
	} else {
		$rows = sql_query("SHOW TABLES FROM `" . escape($db_config['name']) . "`");
		
		$tables = $vars['sections'];
		
		foreach($rows as $row) {
			$tables[] = spaced(current($row));
		}
		
		$tables = array_unique($tables);
		sort($tables);
	}
	
    foreach ($tables as $table) {
    	$section = spaced($table);
        ?>
<tr>
	<td><?=$section; ?></td>
	<td>
		<select name="privileges[<?=$section; ?>]" class="privileges">
			<option value=""></option>
			<?=html_options($privilege_options, $privileges[$section]['access']); ?>
		</select>
	</td>
	<td>
		<input type="text" id="filters_<?=underscored($section); ?>" name="filters[<?=$section; ?>]" value="<?=$privileges[$section]['filter']; ?>" />
		<button type="button" onclick="choose_filter('<?=$section; ?>');">Choose Filter</button>
	</td>
</tr>
<?php
    } ?>
<tr>
	<td>Email Templates</td>
	<td>
		<select name="privileges[email_templates]" class="privileges">
			<option value=""></option>
			<?=html_options($privilege_options, $privileges['uploads']['access']); ?>
		</select>
	</td>
</tr>
<tr>
	<td>Uploads</td>
	<td>
		<select name="privileges[uploads]" class="privileges">
			<option value=""></option>
			<?=html_options($privilege_options, $privileges['uploads']['access']); ?>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2" align="right">
		<button type="submit">Save</button>
	</td>
</tr>
</table>
</form>
<br />
<?php
}
?>

<script>
    function choose_filter(field) {
    	window.open('/admin?option=choose_filter&section='+field,'Insert','width=700,height=450,screenX=100,screenY=100,left=100,top=100,status,dependent,alwaysRaised,resizable,scrollbars')
    }
    
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
    
    $('#privileges_full').click(function(){
    	$('select.privileges').val('3');
    	return false;
    });
</script>