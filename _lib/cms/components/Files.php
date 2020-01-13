<?php

namespace cms\components;

use cms\ComponentInterface;

class Files extends File implements ComponentInterface
{
    public function getFieldSql(): ?string
    {
        return 'TEXT';
    }

    public function field(string $field_name, $value = '', array $options = []): void
    {
        if ($value) {
            $value = explode("\n", $value);
        } ?>

        <ul class="files">
            <?php
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1); ?>
                    <li>
                        <?php if ($file) { ?>
                            <input type="hidden" name="<?= $field_name; ?>[]" value="<?= $val; ?>" <?php if ($options['readonly']) { ?>readonly<?php } ?>>
                            <a href="/_lib/cms/file.php?f=<?= $val; ?>">
                                <img src="/_lib/cms/file.php?f=<?= $val; ?>" style="max-width: 100px; max-height: 100px;"><br>
                                <?= $file['name']; ?>
                            </a>
                            <a href="javascript:" class="link" onClick="delItem(this)">delete</a>
                        <?php } ?>
                    </li>
                    <?php
                }
            } ?>

            <li>
                <input type="file" name="<?= $field_name; ?>[]" multiple="multiple" <?php if ($options['readonly']) { ?>disabled<?php } ?> <?= $options['attribs']; ?>/>
            </li>
        </ul>
        <?php
    }

    public function value($value, $name = '')
    {
        if ($value) {
            $files = explode("\n", $value);
        }

        $value = '<ul id="' . $name . '_files" class="files">';

        $count = 0;

        if (is_array($files)) {
            foreach ($files as $key => $val) {
                $count++;

                if ($val) {
                    $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1);
                }

                $image_types = ['jpg', 'jpeg', 'gif', 'png'];
                if (in_array(file_ext($file['name']), $image_types)) {
                    $value .= '<img src="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                }

                $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span><br><br>';
            }
        }

        $value .= '</ul>';

        return $value;
    }

    public function formatValue($files, $field_name = '')
    {
        global $vars, $cms;

        if (!is_array($files)) {
            $files = [];
        }

        if (is_array($_FILES[$field_name])) {
            foreach ($_FILES[$field_name]['error'] as $key => $error) {
                if (UPLOAD_ERR_OK !== $error) {
                    continue;
                }

                sql_query("INSERT INTO files SET
                    date=NOW(),
                    name='" . escape($_FILES[$field_name]['name'][$key]) . "',
                    size='" . escape(filesize($_FILES[$field_name]['tmp_name'][$key])) . "',
                    type='" . escape($_FILES[$field_name]['type'][$key]) . "'
                ");
                $value = sql_insert_id();

                $files[] = $value;

                // move file
                $file_path = $vars['files']['dir'] . $value;
                rename($_FILES[$field_name]['tmp_name'][$key], $file_path)
                or trigger_error("Can't save " . $file_path, E_ERROR);
            }
        }

        if ($cms->id) {
            $old_files = explode("\n", $cms->content[$field_name]);

            //clean up old files
            foreach ($old_files as $old_file) {
                $file_id = (int) $old_file['id'];

                if (!in_array($file_id, $files)) {
                    sql_query("DELETE FROM files
                        WHERE
                            id='" . $file_id . "'
                    ");

                    $this->delete($vars['files']['dir'] . $file_id);
                }
            }
        }

        return trim(implode("\n", $files));
    }
}
