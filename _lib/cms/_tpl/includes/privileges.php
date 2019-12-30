<?php
//privileges
if (
    1 == $auth->user['admin'] and
    underscored($_GET['option']) == $auth->table and
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