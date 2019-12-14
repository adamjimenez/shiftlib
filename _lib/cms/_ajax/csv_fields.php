<?php
require('../../base.php');

ini_set('auto_detect_line_endings', '1');

$handle = fopen('uploads/' . $_SERVER['HTTP_HOST'] . '.csv', 'r');
if (false === $handle) {
    die('error opening ' . $_SERVER['HTTP_HOST'] . '.csv');
}
while (false !== ($data = fgetcsv($handle, 1000, ',')) and $i < 3) {
    for ($j = 0; $j < count($data); $j++) {
        if (0 == $i) {
            if ($data[$j]) {
                $options[$j] = $data[$j];
            } else {
                $options[$j] = 'Col ' . num2alpha($j + 1);
            }
        }
        if (strstr($data[$j], '@')) {
            $vars['email'] = $j;
        }
        $rows[$i][] = $data[$j];
    }

    if (0 == $i) {
        break;
    }

    $i++;
}

print json_encode($options);
