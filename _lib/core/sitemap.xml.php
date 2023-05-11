<?php
function get_dir_contents($files, $dirstr = '_tpl/')
{
    if (!is_array(glob($dirstr . '*'))) {
        return;   
    }
    
    foreach (glob($dirstr . '*') as $pathname) {
        $filename = basename($pathname);

        if (
            'admin' == $filename
            or 'admin.php' == $filename
            or 'template.php' == $filename
            or '404.php' == $filename
            or strstr($pathname, 'dev')
            or strstr($pathname, 'old')
            or strstr($pathname, 'includes')
            or strstr($pathname, 'misc')
        ) {
            continue;
        }

        if ('.' != $filename and '..' != $filename and $pathname != $dirstr) {
            if (is_dir($pathname)) {
                $files = get_dir_contents($files, $pathname . '/');
            } else {
                $files[] = $pathname;
            }
        }
    }

    return $files;
}

$files = get_dir_contents([]);

$pages = [];
foreach ($files as $file) {
    $mtime = filemtime($file);
    $date = date('Y-m-d', $mtime);

    //remove extension
    if (ends_with($file, '.php')) {
        $file = substr($file, 0, -4);
    }

    //remove _tpl
    if (starts_with($file, '_tpl/')) {
        $file = substr($file, 5);
    }

    if (in_array($file, (array)$tpl_config['catchers'])) {
        continue;
    }

    if (ends_with($file, 'index')) {
        $file = substr($file, 0, -5);
    }

    if ($file) {
        $pages[] = $file;
    }
}

if (function_exists('get_pagenames')) {
    $pages = array_merge($pages, get_pagenames());
}

if ($sitemap_ignore) {
    $pages = array_diff($pages, $sitemap_ignore);
}

$host = $_SERVER['HTTP_HOST'];

$protocol = $_SERVER['HTTPS'] ? 'https' : 'http';

header('Content-Type: text/xml');
print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">

   <url>
      <loc>' . $protocol . '://' . $host . '/</loc>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
   </url>
';

//scan template folder
foreach ($pages as $page) {
    print '   <url>
      <loc>' . $protocol . '://' . $host . '/' . $page . '</loc>
   </url>
';
}

print '</urlset>';
