<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;

class Datetime extends Date implements ComponentInterface
{
    public $dateFormat = 'Y-m-d H:i:s';
    
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'DATETIME';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        return '<input type="datetime-local" name="' . $fieldName . '" value="' . $value . '" ' . ($options['readonly'] ? 'disabled' : '') . ' size="10" ' . $options['attribs'] . '>';
    }

    /**
     * @param mixed $value
     * @param string $name
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if (true === starts_with($value, '0000-00-00')) {
            $value = '';
        }

        return $value ?: '';
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @return mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        if ($value) {
            $value .= ':00';
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        return Component::isValid($value);
    }
    
    /**
     * @param $name
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        $html = [];
        $html[] = '<div>';
        $html[] = '<label class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '<div>';
        $html[] = '<div style="float:left">';
        $html[] = 'From&nbsp;';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="datetime-local" name="' . $field_name . '" value="' . $_GET[$field_name] . '" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '&nbsp;To&nbsp;';
        $html[] = '</div>';
        $html[] = '<div style="float:left">';
        $html[] = '<input type="datetime-local" name="func[' . $field_name . '][end]" value="' . $_GET['func'][$field_name]['end'] . '" autocomplete="off" class="form-control">';
        $html[] = '</div>';
        $html[] = '<br style="clear: both;">';
        $html[] = '</div>';
        $html[] = '</div>';
        $html[] = '<br>';

        return implode(' ', $html);
    }
}
