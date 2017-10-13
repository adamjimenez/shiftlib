<?php
$label = is_string($button['label']) ? $button['label'] : $button['label']();
$disabled = $button['disabled'] ? 'disabled' : '';

if( $button['submit']===false ){
    $handler = $button['handler'];

    if( $button['confirm'] ){
        $handler = 'if( confirm("'.escape($button['confirm']).'") ){ '.$handler.' }';
    }
?>
<button class="btn btn-default" type="button" onclick="<?=$button['handler'];?>" <?=$disabled;?>><?=$label?></button>
<?
}else{
?>
<form method="post" <? if($button['target']){ ?>target="<?=$button['target'];?>"<? } ?> style="display:inline" <? if( $button['confirm'] ){ ?>onsubmit="return confirm('<?=escape($button['confirm']);?>');"<? } ?>>
<input type="hidden" name="custom_button" value="<?=$k;?>">
	<button class="btn btn-default" type="submit" <?=$disabled;?>><?=$label;?></button>
</form>
<?
}
?>