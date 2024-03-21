<?php
require(__DIR__ . '/../../base.php');

$file_id = $_GET['f'];

if (1 != $auth->user['admin'] and !$auth->user['privileges']['uploads'] and $_GET['hash'] !== md5($auth->hash_salt . $file_id)) {
    die('access denied');
}

$row = sql_query("SELECT * FROM files
    WHERE
	    id='" . escape($file_id) . "'
", 1) or die('file not found');

header('Content-type: ' . $row['type']);
header('Content-Disposition: inline; filename="' . $row['name'] . '"');

if (!$_GET['w'] && !$_GET['h']) {
    print file_get_contents('uploads/files/' . $row['id']);
} else {
    // end configure
    $max_width = $_GET['w'] ?: 320;
    $max_height = $_GET['h'] ?: 240;

    $img = imageorientationfix('/uploads/files/' . $row['id']);
    $img = thumb_img($img, [$max_width, $max_height], false);
    $ext = file_ext($row['name']);

    switch ($ext) {
        case 'png':
            imagepng($img);
            break;
        case 'gif':
            imagegif($img);
            break;
        default:
            imagejpeg($img, null, 85);
            break;
    }
}