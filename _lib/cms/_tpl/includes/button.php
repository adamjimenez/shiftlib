<?php
if( $button['submit']===false ){
    $handler = $button['handler'];

    if( $button['confirm'] ){
        $handler = 'if( confirm("'.escape($button['confirm']).'") ){ '.$handler.' }';
    }
?>
<button type="button" onclick="<?=$button['handler'];?>"><?=$button['label'];?></button>
<?
}else{
?>
<form method="post" <? if($button['target']){ ?>target="<?=$button['target'];?>"<? } ?> style="display:inline" <? if( $button['confirm'] ){ ?>onsubmit="return confirm('<?=escape($button['confirm']);?>');"<? } ?>>
<input type="hidden" name="custom_button" value="<?=$k;?>">
	<button type="submit"><?=$button['label'];?></button>
</form>
<?
}
?>