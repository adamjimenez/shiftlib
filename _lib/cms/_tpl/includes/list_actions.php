<?
if( !$qs ){
	$qs = $_GET;
	unset($qs['option']);
    unset($qs['id']);
	$qs = http_build_query($qs);
}
?>

<table width="100%" cellspacing="0" cellpadding="3">
<tr class="table-heading">
	<td valign="top">
		<button type="button" onclick="location.href='?option=<?=$this->section;?>&edit=1&<?=$qs;?>'; return false;" style="font-weight:bold;">Create New</button>


		<button type="submit" onclick="return export_selected();">Export</button>

		<!--
		<? if( $vars['settings'][$this->section]['sms'] ){ ?>
		<button type="submit">Send SMS text</button>
		<? } ?>
		-->
		<? if( $vars['settings'][$this->section]['shiftmail'] ){ ?>
		<button type="submit" onclick="return email_selected();">Email</button>
		<? } ?>

		<button type="submit" onclick="return delete_selected();">Delete</button>
	</td>
	<td align="right" valign="top" style="text-align:right;">
		<p><?=$p->get_results(1);?></p>
	</td>
</tr>
</table>