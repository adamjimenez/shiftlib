<?php
namespace cms;

class editor extends component
{
	function field($field_name, $value = '', $options = []) {
		?>
		<textarea name="<?=$field_name;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'] ?: 'rows="25" style="width:100%; height: 400px;"';?> data-type="tinymce"><?=htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');?></textarea>
		<?
	}
}