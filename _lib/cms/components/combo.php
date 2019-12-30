<?php
namespace cms;

class combo extends component
{
	function field($field_name, $value = '', $options = []) {
	?>
        <input type="hidden" name="<?=$field_name; ?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs']; ?> value="<?=$value; ?>">
        <input type="text" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs']; ?> value="<?=$this->content[$field_name . '_label']; ?>" data-type="combo" data-field="<?=$field_name; ?>">
    <?php
	}
	
	function value($value, $name) {
		global $vars;
		
        if (!is_array($vars['options'][$name])) {
            if ('0' == $value) {
                $value = '';
            } else {
                $value = '<a href="?option=' . escape($vars['options'][$name]) . '&view=true&id=' . $value . '">' . $this->content[underscored($name) . '_label'] . '</a>';
            }
        } else {
            if (is_assoc_array($vars['options'][$name])) {
                $value = $vars['options'][$name][$value];
            }
        }
        
        return $value;
	}
}