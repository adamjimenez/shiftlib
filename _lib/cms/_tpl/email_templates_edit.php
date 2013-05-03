<script type="text/javascript">
function init()
{
	initAutoGrows();
}

window.onload=init;
</script>

<div id="container">
<h1>Email Templates Editor</h1>
<div>
<form method="post">
<input type="hidden" name="save" value="1">
<table border="1" cellspacing="0" cellpadding="5" class="box">
<tr>
	<th align="left" valign="top">Subject:</th>
	<td><?=$vars['email']['subject'];?></td>
</tr>
<tr>
	<th align="left" valign="top">Body:</th>
	<td><textarea name="body" rows="10" cols="80" class="autogrow"><?=$vars['email']['body'];?></textarea></td>
</tr>
<tr>
	<td colspan="2" class="intro" align="right"><button type="submit">Save</button></td>
</tr>
</table>
</form>
</div>
</div>