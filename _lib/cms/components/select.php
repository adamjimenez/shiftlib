<?php
namespace cms;

class select extends component
{
	function field($field_name, $value = '', $options = []) {
		global $vars, $cms;
		
		$name = $field_name;

        if (!is_array($vars['options'][$name]) and in_array('parent', $vars['fields'][$vars['options'][$name]])) {
            ?>
        <div class="chained" data-name="<?=$field_name; ?>" data-section="<?=$vars['options'][$name]; ?>" data-value="<?=$value; ?>"></div>
        <?php
        } else {
            if (!is_array($vars['options'][$name])) {
                global $auth;
                
                $conditions = [];
                foreach ($auth->user['filters'][$cms->section] as $k => $v) {
                    $conditions[$k] = $v;
                }
                
                $vars['options'][$name] = $cms->get_options($name, $where);
            } ?>
        <select name="<?=$field_name; ?>" <?php if ($option['readonly']) { ?>disabled<?php } ?> <?=$options['attribs']; ?>>
        <option value=""><?=$placeholder ?: 'Choose'; ?></option>
            <?=html_options($vars['options'][$name], $value); ?>
        </select>
        <?php
        }
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