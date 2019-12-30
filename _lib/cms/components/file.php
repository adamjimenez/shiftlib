<?php
namespace cms;

class file extends component
{
	function field($field_name, $value = '', $options = []) {
		$file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);
		
		if ($value) { ?>
            <input type="hidden" id="<?=$field_name;?>" name="<?=$field_name;?>" value="<?=$value;?>">
            <?=$file['name'];?>
            <a href="javascript:;" onClick="clearFile('<?=$field_name;?>')">clear</a>
        <?php 
        } else { 
        ?>
            <input type="file" id="<?=$field_name;?>" name="<?=$field_name;?>" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?=$options['attribs'];?> />
        <?php 
        }
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
	
	function conditions_to_sql($field_name, $value, $func = '', $table_prefix='') {
        return $table_prefix . $field_name . ' > 0';
	}
}