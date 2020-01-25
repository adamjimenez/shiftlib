<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;
use DOMDocument;

class Editor extends Component implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'TEXT';
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @return string
     */
    public function field(string $fieldName, $value = '', $options = []): string
    {
        return '<textarea name="' . $fieldName . '"'
            . ($options['readonly'] ? 'disabled' : '') . ' '
            . $options['attribs'] . ' 
                  rows="25" 
                  style="width:100%; height: 400px;"
                  data-type="tinymce">' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8') . '</textarea>';
    }

    public function formatValue($value, $field_name = '')
    {
        $doc = new DOMDocument();
        $doc->loadHTML('<div>' . $value . '</div>');

        $container = $doc->getElementsByTagName('div')->item(0);
        $container = $container->parentNode->removeChild($container);
        while ($doc->firstChild) {
            $doc->removeChild($doc->firstChild);
        }

        while ($container->firstChild) {
            $doc->appendChild($container->firstChild);
        }

        // remove script tags
        $script = $doc->getElementsByTagName('script');
        foreach ($script as $item) {
            $item->parentNode->removeChild($item);
        }

        $value = $doc->saveHTML();
        return $value;
    }

    public function searchField(string $name, $value): string
    {
        return '';
    }
}
