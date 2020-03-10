<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;
use Exception;

class File extends Component implements ComponentInterface
{
    public const IMAGE_TYPES = ['jpg', 'jpeg', 'gif', 'png'];
    public $previewUrl = '/admin?option=file&f=';

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
            $parts[] = '<a href="' . $this->previewUrl . $value . '">';
            $parts[] = '<img src="' . $this->previewUrl . $value . '" style="max-width: 100px; max-height: 100px;"><br>';
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
     * @param mixed $value
     * @param string $name
     * @throws Exception
     * @return string
     */
    public function value($value, string $name = ''): string
    {
        if ($value) {
            $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);

            if (in_array(file_ext($file['name']), self::IMAGE_TYPES)) {
                $value = '<img src="https://' . $_SERVER['HTTP_HOST'] . $this->previewUrl . $file['id'] . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
            }
            $value .= '<a href="https://' . $_SERVER['HTTP_HOST'] . $this->previewUrl . $file['id'] . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span>';
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @param string|null $fieldName
     * @throws Exception
     * @return int|mixed|string
     */
    public function formatValue($value, string $fieldName = null)
    {
        $fileId = (int) $this->cms->content[$fieldName];

        if (UPLOAD_ERR_OK === $_FILES[$fieldName]['error']) {
            sql_query("INSERT INTO files SET
                date=NOW(),
                name='" . escape($_FILES[$fieldName]['name']) . "',
                size='" . escape(filesize($_FILES[$fieldName]['tmp_name'])) . "',
                type='" . escape($_FILES[$fieldName]['type']) . "'
            ");
            $value = sql_insert_id();

            // move file
            $file_path = $this->vars['files']['dir'] . $value;
            rename($_FILES[$fieldName]['tmp_name'], $file_path)
            or trigger_error("Can't save " . $file_path, E_ERROR);
        } elseif (!$value && $fileId) {
            // delete file
            sql_query("DELETE FROM files
                WHERE
                    id='" . $fileId . "'
            ");

            $this->delete($this->vars['files']['dir'] . $fileId);
        }

        return $value;
    }

    /**
     * @param string $file
     * @return bool
     */
    public function delete(string $file): bool
    {
        if (true === file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @param mixed $value
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
     * @param mixed $value
     * @return string
     */
    public function searchField(string $name, $value): string
    {
        $fieldName = underscored($name);

        $html = [];
        $html[] = '<div>';
        $html[] = '<input type="checkbox" name="' . $fieldName . '" value="1" ' . ($_GET[$fieldName] ? 'checked' : '') . '>';
        $html[] = '<label for="' . underscored($name) . '" class="col-form-label">' . ucfirst($name) . '</label>';
        $html[] = '</div>';

        return implode(' ', $html);
    }
}
