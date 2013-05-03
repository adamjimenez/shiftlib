<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php print(TITLE); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" onUnload="window.opener.location.reload();">
<form name="form" method="post" style='display:inline;'>
<table width="100%" height="100%">
<tr> 
	<td height="30" align="center" class="header"><?php print(TITLE); ?></td>
</tr>
<tr>
	<td style="border:solid" align="center">
		<textarea rows="15" cols="57" name="content"><?php print $vars['content']; ?></textarea>
	</td>
</tr>
<tr>
	<td style="border:solid">
		<button type="submit" name="edit_text" value="Save">Save</button>
		<button type="button" onClick="window.close()">Cancel</button></a>
	</td>
</tr>
</table>
</form>
</body>
</html>