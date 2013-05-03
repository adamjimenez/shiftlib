
<div class="box">
<?
if( count($email_templates) ){
?>
<table width="100%" class="box">
<tr>
	<th>Subject</th>
</tr>
<?
foreach( $vars['content'] as $k=>$v){
?>
<tr>
	<td><a href="?option=email_templates&edit=true&subject=<?=$v['subject'];?>"><?=$v['subject'];?></a></td>
</tr>
<?
}
?>
</table>
<?
}
?>
</div>
