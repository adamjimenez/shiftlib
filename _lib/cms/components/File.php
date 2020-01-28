<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;
use Exception;

class File extends Component implements ComponentInterface
{
    /**
     * @param string $fieldName
     * @param string $value
     * @param array $options
     * @throws Exception
     * @return string
     */
    public function field(string $fieldName, $value = '', array $options = []): string
    {
        $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);
        $parts = [];

        $parts[] = '<div>';

        if ($value) {
            $parts[] = '<input type="hidden" id="' . $fieldName . '" name="' . $fieldName . '" value="' . $value . '">';
            $parts[] = '<a href="/_lib/cms/file.php?f=' . $value . '">';
            $parts[] = '<img src="/_lib/cms/file.php?f=' . $value . '" style="max-width: 100px; max-height: 100px;"><br>';
            $parts[] = $file['name'];
            $parts[] = '</a>';
            $parts[] = '<a href="javascript:" onClick="clearFile(' . $fieldName . ')">clear</a>';
        } else {
            $parts[] = '<input type="file" id="' . $fieldName . '" name="' . $fieldName . '" ' . ($options['readonly'] ? 'disabled' : '') . ' ' . $options['attribs'] . '/>';
        }
        $parts[] = '</div>';
        return implode(' ', $parts);
    }

    /**
     * @param $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        global $auth;

        if ($value) {
            $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);

            $image_types = ['jpg', 'jpeg', 'gif', 'png'];
            if (in_array(file_ext($file['name']), $image_types)) {
                $value = '<img src="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file_preview.php?f=' . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
            }
            $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span>';

            $doc_types = ['pdf', 'doc', 'docx', 'xls', 'tiff'];
            if (in_array(file_ext($file['name']), $doc_types)) {
                $value .= '<a href="http://docs.google.com/viewer?url=' . rawurlencode('http://' . $_SERVER['HTTP_HOST'] . '/_lib/cms/file.php?f=' . $file['id'] . '&auth_user=' . $_SESSION[$auth->cookie_prefix . '_email'] . '&auth_pw=' . md5($auth->secret_phrase . $_SESSION[$auth->cookie_prefix . '_password'])) . '" target="_blank">(view)</a>';
            }
        }
        return $value;
    }

    /**
     * @param $value
     * @param string|null $fieldName
     * @throws Exception
     * @return int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        global $vars, $cms;

        $file_id = (int) $cms->content[$fieldName];

        if (UPLOAD_ERR_OK === $_FILES[$fieldName]['error']) {
            sql_query("INSERT INTO files SET
                date=NOW(),
                name='" . escape($_FILES[$fieldName]['name']) . "',
                size='" . escape(filesize($_FILES[$fieldName]['tmp_name'])) . "',
                type='" . escape($_FILES[$fieldName]['type']) . "'
            ");
            $value = sql_insert_id();

            // move file
            $file_path = $vars['files']['dir'] . $value;
            rename($_FILES[$fieldName]['tmp_name'], $file_path)
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

    /**
     * @param $file
     * @return bool
     */
    public function delete($file): bool
    {
        if (true === file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @param $value
     * @param string $func
     * @param string $tablePrefix
     * @return string|null
     */
    public function conditionsToSql(string $fieldName, $value, $func = '', string $tablePrefix = ''): ?string
    {
        return $tablePrefix . $fieldName . ' > 0';
    }

    /**
     * @param string $name
     * @param $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $field_name = underscored($name);

        $html = [];
        $html[] = '<div>';
        $html[] = '<input type="checkbox" name="' . $field_name . '" value="1" ' . ($_GET[$field_name] ? 'checked' : '') . '>';
        $html[] = '<label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '</div>';

        return implode(' ', $html);
    }
}
