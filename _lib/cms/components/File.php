<?php

namespace cms\components;

use cms\Component;
use cms\ComponentInterface;
use Exception;

class File extends Component implements ComponentInterface
{
    public const IMAGE_TYPES = ['jpg', 'jpeg', 'gif', 'png'];
    
    public function __construct(\cms $cms, \auth $auth, array $vars)
    {
        parent::__construct($cms, $auth, $vars);
        $this->upload_dir = 'uploads/files/';
    }
    
    public function getPreviewUrl($value) {
        global $auth;
        return '/_lib/api/v2/files.php?f=' . $value. '&hash=' . md5($auth->hash_salt . $value);
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
        $previewUrl = $this->getPreviewUrl($value);
        $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);
        
        $parts = [];

        $parts[] = '<div>';

        if ($value) {
            $parts[] = '<input type="hidden" id="' . $fieldName . '" name="' . $fieldName . '" value="' . $value . '">';
            $parts[] = '<a href="' . $previewUrl . '">';
            $parts[] = '<img src="' . $previewUrl . '" style="max-width: 100px; max-height: 100px;"><br>';
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
            $previewUrl = $this->getPreviewUrl($value);
            
            $file = sql_query("SELECT * FROM files WHERE id='" . escape($value) . "'", 1);

            if (in_array(file_ext($file['name']), self::IMAGE_TYPES)) {
                $value = '<img src="' . $previewUrl . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
            }
            
            $value .= '<a href="' . $previewUrl. '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size((int)$file['size']) . '</span>';
        }
        return $value ?: '';
    }
    
    public function processUpload($status, $name, $tmp, $type) {
        if (UPLOAD_ERR_OK !== $status) {
            //trigger_error("Can't save " . $file_path . '(' . $status . ')', E_USER_ERROR);
            return false;
        }
        
        sql_query("INSERT INTO files SET
            date=NOW(),
            name='" . escape($name) . "',
            size='" . escape(filesize($tmp)) . "',
            type='" . escape($type) . "'
        ");
        
        $value = sql_insert_id();

        // move file
        $file_path = $this->upload_dir . $value;
        
        // don't overwrote
        if (!file_exists($file_path)) {
            rename($tmp, $file_path) or throw new Exception("Can't rename " . $tmp . ' to ' . $file_path);
        } else {
            trigger_error("File already exists " . $file_path, E_USER_ERROR);
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
        
        $file = $_FILES[$fieldName];
        
        if (is_array($file['error'])) {
            $file['error'] = $file['error'][0];
            $file['name'] = $file['name'][0];
            $file['tmp_name'] = $file['tmp_name'][0];
            $file['type'] = $file['type'][0];
        }

        $upload_id = $this->processUpload($file['error'], $file['name'], $file['tmp_name'], $file['type']);

        if ($upload_id) {
            $value = $upload_id;
        } else if (!$upload_id && !$value && $fileId) {
            // delete file
            sql_query("DELETE FROM files
                WHERE
                    id='" . $fileId . "'
            ");

            $this->delete($this->upload_dir . $fileId);
        } else {
            $value = $fileId;
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
        $operator = $value == 0 ? '=' : '>';
        return $tablePrefix . $fieldName . ' ' . $operator . ' 0';
    }
}
