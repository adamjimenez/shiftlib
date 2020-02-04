<?php

namespace cms\components;

use cms\ComponentInterface;
use Exception;

class Files extends File implements ComponentInterface
{
    /**
     * @return string|null
     */
    public function getFieldSql(): ?string
    {
        return 'TEXT';
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
        if ($value) {
            $value = explode("\n", $value);
        }

        $parts = [];

        $parts[] = '<ul class="files">';

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1);
                $parts[] = '<li>';
                if ($file) {
                    $parts[] = '<input type="hidden" name="' . $fieldName . '[]" value="' . $val . '" ' . ($options['readonly'] ? 'readonly' : '') . '>';
                    $parts[] = '<a href="/_lib/cms/file.php?f=' . $val . '">';
                    $parts[] = '<img src="/_lib/cms/file.php?f=' . $val . '" style="max-width: 100px; max-height: 100px;"><br>';
                    $parts[] = $file['name'];
                    $parts[] = '</a>';
                    $parts[] = '<a href="javascript:" class="link" onClick="delItem(this)">delete</a>';
                }
                $parts[] = '</li>';
            }
        }

        $parts[] = '<li>';
        $parts[] = '<input type="file" name="' . $fieldName . '[]" multiple="multiple" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '/>';
        $parts[] = '</li>';
        $parts[] = '</ul>';

        return implode(' ', $parts);
    }

    /**
     * @param mixed $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
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

                if (in_array(file_ext($file['name']), parent::IMAGE_TYPES)) {
                    $value .= '<img src="http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                }

                $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span><br><br>';
            }
        }

        $value .= '</ul>';

        return $value;
    }

    /**
     * @param $files
     * @param string|null $fieldName
     * @throws Exception
     * @return int|mixed|string
     */
    public function formatValue($files, string $fieldName = null)
    {
        if (false === is_array($files)) {
            $files = [];
        }

        if (is_array($_FILES[$fieldName])) {
            foreach ($_FILES[$fieldName]['error'] as $key => $error) {
                if (UPLOAD_ERR_OK !== $error) {
                    continue;
                }

                sql_query("INSERT INTO files SET
                    date=NOW(),
                    name='" . escape($_FILES[$fieldName]['name'][$key]) . "',
                    size='" . escape(filesize($_FILES[$fieldName]['tmp_name'][$key])) . "',
                    type='" . escape($_FILES[$fieldName]['type'][$key]) . "'
                ");
                $value = sql_insert_id();

                $files[] = $value;

                // move file
                $filePath = $this->vars['files']['dir'] . $value;
                rename($_FILES[$fieldName]['tmp_name'][$key], $filePath)
                or trigger_error("Can't save " . $filePath, E_ERROR);
            }
        }

        if ($this->cms->id) {
            $oldFiles = explode("\n", $this->cms->content[$fieldName]);

            //clean up old files
            foreach ($oldFiles as $old_file) {
                $fileId = (int) $old_file['id'];

                if (!in_array($fileId, $files)) {
                    sql_query("DELETE FROM files
                        WHERE
                            id='" . $fileId . "'
                    ");

                    $this->delete($this->vars['files']['dir'] . $fileId);
                }
            }
        }

        return trim(implode("\n", $files));
    }
}
