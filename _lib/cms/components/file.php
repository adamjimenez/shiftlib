<?php
namespace cms;

class file extends component
{
    function delete($file) {
        if (file_exists($file)) {
            return unlink($file);
        }
    }
    
	function field($field_name, $value = '', $options = []) {
		$file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);
		?>
        <div>
        <?php
		if ($value) { 
		?>
            <input type="hidden" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=$value;?>">
            <a href="/_lib/cms/file.php?f=<?=$value;?>">
                <img src="/_lib/cms/file.php?f=<?=$value;?>" style="max-width: 100px; max-height: 100px;"><br>
                <?=$file['name'];?>
            </a>
            <a href="javascript:;" onClick="clearFile('<?=$field_name;?>')">clear</a>
        <?php 
        } else { 
        ?>
            <input type="file" id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'];?> />
        <?php 
        }
        ?>
        </div>
        <?php
	}
	
	function value($value, $name) {
        if ($value) {
            $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);

            $image_types = ['jpg','jpeg','gif','png'];
            if (in_array(file_ext($file['name']), $image_types)) {
                $value = '<img src="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
            }
            $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span>';

            $doc_types = ['pdf','doc','docx','xls','tiff'];
            if (in_array(file_ext($file['name']), $doc_types)) {
                $value .= '<a href="http://docs.google.com/viewer?url=' . rawurlencode('http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '&auth_user=' . $_SESSION[$auth->cookie_prefix . '_email'] . '&auth_pw=' . md5($auth->secret_phrase . $_SESSION[$auth->cookie_prefix . '_password'])) . '" target="_blank">(view)</a>';
            }
        }
        return $value;
	}
	
	function format_value($value, $field_name) {
	    global $vars, $cms;
	    
	    $file_id = (int)$cms->content[$field_name];

        if (UPLOAD_ERR_OK === $_FILES[$field_name]['error']) {
            sql_query("INSERT INTO files SET
                date=NOW(),
                name='" . escape($_FILES[$field_name]['name']) . "',
                size='" . escape(filesize($_FILES[$field_name]['tmp_name'])) . "',
                type='" . escape($_FILES[$field_name]['type']) . "'
            ");
            $value = sql_insert_id();

            // move file
            $file_path = $vars['files']['dir'] . $value;
            rename($_FILES[$field_name]['tmp_name'], $file_path) 
                or trigger_error("Can't save " . $file_path, E_ERROR);
        } elseif (!$value && $file_id) {
            // delete file
            sql_query("DELETE FROM files
                WHERE
                    id='" . $file_id . "'
            ");

            $this->delete($vars['files']['dir'] . $file_id);
        }

        return $value;
	}
	
	function conditions_to_sql($field_name, $value, $func = '', $table_prefix='') {
        return $table_prefix . $field_name . ' > 0';
	}
	
	function search_field($name, $value) {
		$field_name = underscored($name);
	?>
	    <div>
	    	<input type="checkbox" name="<?=$field_name;?>" value="1" <?php if ($_GET[$field_name]) { ?>checked<?php } ?>>
	    	<label for="<?=underscored($name);?>" class="col-form-label"><?=$label;?></label>
	    </div>
	<?php
	}
}