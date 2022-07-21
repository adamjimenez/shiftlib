<?php

namespace cms\components;

use cms\ComponentInterface;
use Exception;

class SelectMultiple extends Select implements ComponentInterface
{
    public $idRequired = true;

    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'JSON';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @throws Exception
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        /** @var string $name */
        $name = spaced($fieldName);

        $value = json_decode($value, true);

        // get options from a section
        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $this->vars['options'][$name] = $this->get_options($name, false);
        }

        $isAssoc = is_assoc_array($this->vars['options'][$name]);

        $parts = [];
        $parts[] = '<ul class="checkboxes">';

        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $isAssoc ? $k : $v;
            $parts[] = '<li><label><input type="checkbox" name="' . $fieldName . '[]" value="' . $val . '" ' . ($options['readonly'] ? 'readonly' : '') . ' ' . (in_array($val, $value) ? 'checked="checked"' : '') . '>&nbsp;<span>' . $v . '</span></label></li>';
        }

        $parts[] = '</ul>';
        return implode('', $parts);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
    	$value = json_decode($value, true);
    	
        $items = [];
        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
           	$table = underscored($this->vars['options'][$name]);
			$fields = $this->cms->get_fields($this->vars['options'][$name]);
			
			// find text field
			$col = 'id';
			foreach($fields as $field) {
				if ($field['type'] === 'text') {
					$col = $field['column'];
					break;
				}
			}

            foreach ($value as $key) {
            	$row = sql_query("SELECT `$col` FROM $table WHERE id = '" . (int)$key . "'", 1);
            	
                $items[] = '<a href="?option=' . escape($this->vars['options'][$name]) . '&view=true&id=' . $key . '">' . current($row) . '</a>';
            }
        } else {
            $isAssoc = is_assoc_array($this->vars['options'][$name]);
            foreach ($value as $key) {
                $items[] = $isAssoc ? $this->vars['options'][$name][$key] : $key;
            }
        }

        return implode('<br>' . "\n", $items);
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @throws Exception
     * @return bool|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        return json_encode($value);
    }

    /**
     * Generates sql code for use in where statement
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        $conditions = [];
        
        foreach($value as $val) {
        	$conditions[] = 'JSON_SEARCH(' . $tablePrefix . $fieldName . ", 'all', '" . escape($val) . "') IS NOT NULL";
        }
        
        return '(
        	' . implode(" AND\n", $conditions) . '
        )';
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws Exception
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        if (false === is_array($this->vars['options'][$name]) and $this->vars['options'][$name]) {
            $this->vars['options'][$name] = $this->get_options($name);
        }

        $html = [];

        $html[] = '<div>';
        $html[] = '     <label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '</div>';
        $html[] = '<div style="max-height: 200px; width: 200px; overflow: scroll">';
        $isAssoc = is_assoc_array($this->vars['options'][$name]);
        foreach ($this->vars['options'][$name] as $k => $v) {
            $val = $isAssoc ? $k : $v;
            $html[] = '<label><input type="checkbox" name="' . $fieldName . '[]" value="' . $val . '" ' . (is_array($_GET[$fieldName]) && in_array($val, $_GET[$fieldName]) ? 'checked' : '') . '>&nbsp;' . $v . '</label><br>';
        }
        $html[] = '</div>';
        return implode(' ', $html);
    }
}
