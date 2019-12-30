<?php
namespace cms;

class phpuploads extends component
{
	public $field_sql = "TEXT";
	
	function field($field_name, $value = '', $options = []) {
        ?>
        <textarea name="<?=$field_name;?>" class="upload"><?=$value;?></textarea>
        <?php
	}
	
	function value($value, $name) {
		
        if ($value) {
            $files = explode("\n", $value);
        } 
        
        $value = '<ul id="'.$name.'_files">';
        $count = 0;

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $count++; 
            
	            $value .= '
	                <li id="item_<?=$name; ?>_'.$count.'">
	                    <img src="/_lib/modules/phpupload/?func=preview&file='.$val.'" id="file_'.$name.'_'.$count.'_thumb"><br>
	                    <label id="file_'.$name.'_'.$count.'_label">'.$val.'</label>
	                </li>
	            ';
            }
        }
        
        $value .= '</ul>';
        
        return $value;
	}
}