<?php
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
		<button class="btn btn-default" type="button" onclick="location.href='?option=<?=$this->section;?>&edit=1&<?=$qs;?>'; return false;" style="font-weight:bold;">Create New</button>


		<?php /*
		<button class="btn btn-default" type="submit" onclick="return export_selected();">Export</button>
		*/ ?>

		<?php
		foreach( $cms_buttons as $k=>$button ){
			if( $this->section==$button['section'] and $button['page']=='list' ){
                require('submit.php');
    		}
    	}
    	?>

		<!--
		<?php if( $vars['settings'][$this->section]['sms'] ){ ?>
		<button type="submit">Send SMS text</button>
		<?php } ?>
		-->
		<?php if( $vars['settings'][$this->section]['shiftmail'] ){ ?>
		<button class="btn btn-default" type="submit" onclick="return email_selected();">Email</button>
		<?php } ?>

		<button class="btn btn-danger" type="submit" onclick="return delete_selected();">Delete</button>
	</td>
	<td align="right" valign="top" style="text-align:right;">
		<p><?=$p->get_results(1);?></p>
	</td>
</tr>
</table>
