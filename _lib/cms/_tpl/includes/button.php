<?php
$label = is_string($button['label']) ? $button['label'] : $button['label']();

if (is_callable($button['disabled'])) {
    $disabled = $button['disabled']($content) ? 'disabled' : '';
} else {
    $disabled = $button['disabled'] ? 'disabled' : '';
}

if (false !== $label) {
    if (false === $button['submit']) {
        $handler = $button['handler'];
    
        if ($button['confirm']) {
            $handler = 'if( confirm("' . escape($button['confirm']) . '") ){ ' . $handler . ' }';
        } ?>
	<a class="dropdown-item" href="#" onclick="<?=$button['handler']; ?>" <?=$disabled; ?>><?=$label?></a>
	<?php
    } else {
        ?>
	<form method="post" <?php if ($button['target']) { ?>target="<?=$button['target'];?>"<?php } ?> style="display:inline" <?php if ($button['confirm']) { ?>onsubmit="return confirm('<?=escape($button['confirm']);?>');"<?php } ?>>
	    <input type="hidden" name="section" value="<?=$this->section; ?>">
	    <input type="hidden" name="custom_button" value="<?=$k; ?>">
	    
	    <?php 
	    if ($button['page'] === 'view') {
	    ?>
	    <button type="submit" class="dropdown-item" href="#" <?=$disabled; ?>><?=$label?></button>
	    <?php } else { ?> 
	    <button type="button" class="dropdown-item" href="#" <?=$disabled; ?> onclick="var table = $('#dataTable-<?=underscored($button['section']);?>').DataTable(); button_handler('custom', false, table, '<?=$k; ?>');"><?=$label?></button>
	    <?php } ?>
	</form>
	<?php
    }
}
