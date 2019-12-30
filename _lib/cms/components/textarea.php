<?php
namespace cms;

class textarea extends component
{
	function field($field_name, $value = '', $options = []) {
		?>
		<textarea name="<?=$field_name;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?php if ($options['placeholder']) { ?>placeholder="<?=$options['placeholder'];?>"<?php } ?> <?=$options['attribs'];?>><?=$value;?></textarea>
		<?
	}
}