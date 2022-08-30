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
        /*
        if ($value) {
            $value = explode("\n", $value);
        }
        */

        $parts = [];

        $parts[] = '<ul class="files">';

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $previewUrl = $this->getPreviewUrl($val);
                
                $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1);
                $parts[] = '<li>';
                if ($file) {
                    $parts[] = '<input type="hidden" name="' . $fieldName . '[]" value="' . $val . '" ' . ($options['readonly'] ? 'readonly' : '') . '>';
                    $parts[] = '<a href="' . $previewUrl . '">';
                    $parts[] = '<img src="' . $previewUrl . '" style="max-width: 100px; max-height: 100px;"><br>';
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
        /*
        if ($value) {
            $files = explode("\n", $value);
        }
        */
        //debug($value);

        $html = '<ul id="' . $name . '_files" class="files">';

        $count = 0;

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $count++;

                if ($val) {
                    $file = sql_query("SELECT * FROM files WHERE id='" . escape($val) . "'", 1);
                }

                $previewUrl = $this->getPreviewUrl($file['id']);

                if (in_array(file_ext($file['name']), parent::IMAGE_TYPES)) {
                    $html .= '<img src="https://' . $_SERVER['HTTP_HOST'] . '/' . $previewUrl . '&w=320&h=240" id="' . $name . '_thumb" /><br />';
                }

                $html .= '<a href="https://' . $_SERVER['HTTP_HOST'] . '/' . $previewUrl . '">' . $file['name'] . '</a> <span style="font-size:9px;">' . file_size($file['size']) . '</span><br><br>';
            }
        }

        $html .= '</ul>';

        return $html;
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
        
        $file = $_FILES[$fieldName];

        if (is_array($file)) {
            foreach ($file['error'] as $key => $error) {
                $files[] = $this->processUpload($error, $file['name'][$key], $file['tmp_name'][$key], $file['type'][$key]);
            }
        }

        if ($this->cms->id) {
            $oldFiles = $this->cms->content[$fieldName];

            //clean up old files
            foreach ($oldFiles as $old_file) {
                $fileId = (int)$old_file['id'];

                if (!in_array($fileId, $files)) {
                    sql_query("DELETE FROM files
                        WHERE
                            id='" . $fileId . "'
                    ");

                    $this->delete($this->upload_dir . $fileId);
                }
            }
        }

        return trim(implode("\n", $files));
    }
}
