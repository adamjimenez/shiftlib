<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Edit image</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" onUnload="window.opener.location.href='?field=image&file=<?=$vars['file']; ?>'">
<script>
function calcHeight(width)
{
	if(document.getElementById('aspect_ratio').checked==true){
		document.getElementById('height').value=width/<?=$vars['ratio']; ?>;
	}
}

function calcWidth(height)
{
	if(document.getElementById('aspect_ratio').checked==true){
		document.getElementById('width').value=height*<?=$vars['ratio']; ?>;
	}
}
</script>
<table width="100%" height="100%">
<tr> 
	<td height="30" colspan="2" align="center" class="header">Edit image</td>
</tr>
<tr>
	<td style="border:solid" align="center">
		<iframe height="150" marginwidth="0" width="150" src="?func=preview&file=<?=$vars['file']; ?>" frameborder="0" marginheight="0"></iframe>
		<br><?=$vars['file']; ?>
	</td>
	<td valign="top" style="border:solid">
		<table height="100%">
			<form name="dimensions" method="post" enctype="multipart/form-data">
			<tr height="20">
				<td>Dimensions:</td>
				<td><input id="width" type="text" name="width" value="<?php print $vars['dimensions'][0]; ?>" style="width:35px" onKeyUp="calcHeight(this.value)"> x <input id="height" type="text" name="height" value="<?php print $vars['dimensions'][1]; ?>" style="width:35px"  onKeyUp="calcWidth(this.value)"></td>
				<td rowspan="2"><button type="submit" name="resize" value="Go">Go</button></td>
			</tr>
			</form>
			<tr height="20">
				<td>&nbsp;</td>
				<td>Maintain aspect ratio <input id="aspect_ratio" type="checkbox" name="aspect ratio" checked></td>
			</tr>
			<tr height="20">
				<td colspan="3"><hr></td>
			</tr>
			<tr height="20">
				<td align="center" colspan="3">
					<button type="button" onClick="location.href='?func=edit&file=<?php print $vars['file']; ?>&rotate=left'">Rotate Left</button>
					<button type="button" onClick="location.href='?func=edit&file=<?php print $vars['file']; ?>&rotate=right'">Rotate Right</button>
				</td>
			</tr>
			<tr height="20">
				<td colspan="3"><hr></td>
			</tr>
			<tr>
				<td align="center" colspan="3" valign="bottom"><button type="button" onClick="window.close()">Close</button></td>
			</tr>
		</table>
	</td>
</tr>

</table>
</body>
</html>