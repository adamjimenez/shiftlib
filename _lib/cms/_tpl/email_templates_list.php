<div class="box">
<?php
if (count($email_templates)) {
    ?>
<table width="100%" class="box">
<tr>
	<th>Subject</th>
</tr>
<?php
foreach ($vars['content'] as $k => $v) {
        ?>
<tr>
	<td><a href="?option=email_templates&edit=true&subject=<?=$v['subject']; ?>"><?=$v['subject']; ?></a></td>
</tr>
<?php
    } ?>
</table>
<?php
}
?>
</div>
