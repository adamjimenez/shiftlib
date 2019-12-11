<?php
$label = is_string($button['label']) ? $button['label'] : $button['label']();

if (is_callable($button['disabled'])) {
	$disabled = $button['disabled']($content) ? 'disabled' : '';
} else {
	$disabled = $button['disabled'] ? 'disabled' : '';
}

if ($label!==false) {
	if( $button['submit']===false ){
	    $handler = $button['handler'];
	
	    if( $button['confirm'] ){
	        $handler = 'if( confirm("'.escape($button['confirm']).'") ){ '.$handler.' }';
	    }
	?>
	<button class="btn btn-default" type="button" onclick="<?=$button['handler'];?>" <?=$disabled;?>><?=$label?></button>
	<?php
	}else{
	?>
	<form method="post" <?php if($button['target']){ ?>target="<?=$button['target'];?>"<?php } ?> style="display:inline" <?php if( $button['confirm'] ){ ?>onsubmit="return confirm('<?=escape($button['confirm']);?>');"<?php } ?>>
	<input type="hidden" name="custom_button" value="<?=$k;?>">
		<button class="btn btn-default" type="submit" <?=$disabled;?>><?=$label;?></button>
	</form>
	<?php
	}
}
?>