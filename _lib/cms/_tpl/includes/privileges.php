<?php
//save privileges
if (
    1 == $auth->user['admin'] &&
    underscored($_GET['option']) == $auth->table &&
    (2 == $content['admin'] || 3 == $content['admin']) &&
    is_array($_POST['privileges'])
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
    1 == $auth->user['admin'] &&
    underscored($_GET['option']) == $auth->table &&
    1 < $content['admin']
 ) {
    $rows = sql_query("SELECT * FROM cms_privileges WHERE user='" . $this->id . "'");
    foreach ($rows as $row) {
        $privileges[$row['section']] = $row;
    } ?>
<form method="post">
<table class="box" width="100%">
<tr>
	<th>Section</th>
	<th>
		Access<br>
		<a href="#" id="privileges_none">None</a>,
		<a href="#" id="privileges_read">Read</a>,
		<a href="#" id="privileges_write">Write</a>
	</th>
	<th>Filter</th>
</tr>
<?php
    foreach ($vars['fields'] as $section => $fields) {
        ?>
<tr>
	<td><?=$section; ?></td>
	<td>
		<select name="privileges[<?=$section; ?>]" class="privileges">
			<option value=""></option>
			<?=html_options([1 => 'Read',2 => 'Write'], $privileges[$section]['access']); ?>
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
			<?=html_options([1 => 'Read',2 => 'Write'], $privileges['uploads']['access']); ?>
		</select>
	</td>
</tr>
<tr>
	<td>Uploads</td>
	<td>
		<select name="privileges[uploads]" class="privileges">
			<option value=""></option>
			<?=html_options([1 => 'Read',2 => 'Write'], $privileges['uploads']['access']); ?>
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
    function choose_filter(field)
    {
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
</script>