<?php
namespace cms;

class editor extends component
{
	public $field_sql = "TEXT";
	
	function field($field_name, $value = '', $options = []) {
		?>
		<textarea name="<?=$field_name;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'] ?: 'rows="25" style="width:100%; height: 400px;"';?> data-type="tinymce"><?=htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'utf-8');?></textarea>
		<?
	}
	
	function format_value($value) {
        $doc = new \DOMDocument();
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
}