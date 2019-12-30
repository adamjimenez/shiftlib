<?php
$label = is_string($button['label']) ? $button['label'] : $button['label']();
$disabled = $button['disabled'] ? 'disabled' : '';

if (false !== $label) {
    ?>
<button class="btn btn-default" type="button" data-custom="<?=$k; ?>" <?=$disabled; ?>><?=$label; ?></button>
<?php
}