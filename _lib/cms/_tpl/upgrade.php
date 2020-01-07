<?php
if (1 != $auth->user['admin']) {
    die('permission denied');
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ('.' != $object && '..' != $object) {
                if (is_dir($dir . '/' . $object) && !is_link($dir . '/' . $object)) {
                    rrmdir($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }
        }
        rmdir($dir);
    }
}

$zip_file = 'latest.zip';

print '<pre>';

// get latest release
output('checking latest release');
$release_url = 'https://api.github.com/repos/adamjimenez/shiftlib/releases/latest';
$release = wget($release_url);

// download zip
output('downloading ' . $release['tag_name']);
file_put_contents($zip_file, wget($release['zipball_url'])) or die('failed to save ' . $zip_file);

// extract to new folder
output('extract zip');
rrmdir('_lib.new');
mkdir('_lib.new') or die('failed to create dir');

$zip = new ZipArchive;
$res = $zip->open($zip_file);
if (true === $res) {
    $zip->extractTo('_lib.new/') or die('failed to extract to _lib.new');
    $zip->close();
} else {
    die('failed to open zip');
}

// get folder name
$folder = end(scandir('_lib.new'));

// backup site
output('backup site');
rename('_lib', '_lib.bak') or die('failed to save backup site _lib.bak');

// rename lib folder
output('install update');
rename('_lib.new/' . $folder . '/_lib', '_lib') or die('failed to rename _lib.nwq');

// delete backup
output('clean up');
rrmdir('_lib.bak');

print '</pre>';

print 'Done! <a href="/admin">Reload</a>';

exit;
