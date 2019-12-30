<?php
namespace cms;

class files extends file
{
	public $field_sql = "TEXT";
	
	function field($field_name, $value = '', $options = []) {
        if ($value) {
            $value = explode("\n", $value);
        }
		?>

        <ul class="files">
            <?php
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1); ?>
            <li>
            <?php if ($file) { ?>
                <input type="hidden" name="<?=$field_name;?>[]" value="<?=$val;?>" <?php if ($options['readonly']) { ?>readonly<?php } ?>>
                <a href="/_lib/cms/file.php?f=<?=$val;?>">
                    <img src="/_lib/cms/file.php?f=<?=$val;?>" style="max-width: 100px; max-height: 100px;"><br>
                    <?=$file['name'];?>
                </a>
                <a href="javascript:;" class="link" onClick="delItem(this)">Delete</a>
            <?php } ?>
            </li>
            <?php
                }
            }
            ?>

            <li>
                <input type="file" name="<?=$field_name;?>[]" multiple="multiple" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'];?> />
            </li>
        </ul>
    	<?php
	}
	
	function value($value, $name) {
        if ($value) {
            $files = explode("\n", $value);
        }
        
        $value = '<ul id="'.$name.'_files" class="files">';
            
        $count = 0;

        if (is_array($files)) {
            foreach ($files as $key => $val) {
                $count++;

                if ($val) {
                    $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1);
                }
                    
            	$image_types = ['jpg','jpeg','gif','png'];
                if (in_array(file_ext($file['name']), $image_types)) {
                    $value .= '<img src="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                }
                
                $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span><br><br>';
            }
        }
        
        $value .= '</ul>';
        
        return $value;
	}
}