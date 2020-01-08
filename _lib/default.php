<?php
/*
File:		default.php
Author:		Adam Jimenez
*/

require(dirname(__FILE__) . '/base.php');

function get_tpl_catcher($request)
{
    global $tpl_config;

    if (is_array($tpl_config['catchers'])) {
        foreach ($tpl_config['catchers'] as $catcher) {
            if (substr($request, 0, strlen($catcher) + 1) == $catcher . '/') {
                return $catcher;
            }
        }
    }

    return false;
}

function trigger_404()
{
    throw new Exception(404);
}

function parse_request(): string
{
    $script_url = rawurldecode($_SERVER['REQUEST_URI']);
    $pos = strpos($script_url, '?');

    if ($pos) {
        $script_url = substr($script_url, 0, $pos);
    }

    if (ends_with($script_url, '/index')) { //redirect /index to /
        redirect(substr($script_url, 0, -5), true);
    }

    $request = $script_url ?: 'index';

    if (ends_with($request, '/')) {
        $request .= 'index';
    }

    // strip prepending slash
    if (starts_with($request, '/')) {
        $request = (substr($request, 1));
    }

    return $request;
}

function get_include($request)
{
    global $tpl_config, $root_folder, $catcher, $vars, $cms, $content;

    $include_file = false;

    if (in_array($request, $tpl_config['catchers']) or file_exists($root_folder . '/_tpl/' . $request . '/index.php')) {
        redirect('/' . $request . '/', 301);
    } elseif ($tpl_config['redirects']['http://' . $_SERVER['HTTP_HOST'] . '/']) {
        $redirect = $tpl_config['redirects']['http://' . $_SERVER['HTTP_HOST'] . '/'];
        redirect($redirect, 301);
    } elseif (file_exists($root_folder . '/_tpl/' . $request . '.php')) {
        $include_file = $root_folder . '/_tpl/' . $request . '.php';
    //check redirects
    } elseif ($tpl_config['redirects'][$request]) {
        $redirect = $tpl_config['redirects'][$request];
        redirect($redirect, 301);
    } elseif ($catcher = get_tpl_catcher($request)) {
        $include_file = $root_folder . '/_tpl/' . $catcher . '.php';
    } else {
        // check pages
        if (in_array('pages', $vars['sections'])) {
            $content = $cms->get('pages', $request);

            if ($content and file_exists('_tpl/' . $request)) {
                return $root_folder . '/_tpl/page.php';
            }
        }

        if (file_exists('_tpl/' . $request) and !is_dir('_tpl/' . $request)) {
            $url = 'http://' . $_SERVER['SERVER_NAME'] . '/' . str_replace('.php', '', $request);

            unset($_GET['page']);

            if (count($_GET)) {
                $url .= '?' . http_build_query($_GET);
            }

            redirect($url, 301);
        }

        //check if using urlencode
        $decoded = urldecode($request);
        if (!file_exists($request) and file_exists($decoded)) {
            redirect('/' . $decoded);
        }

        if (file_exists($root_folder . '/_inc/catch_all.php')) {
            $include_file = $root_folder . '/_inc/catch_all.php';
        } else {
            $trigger_404 = true;
        }
    }

    return $include_file;
}

$request = parse_request();

//enforce ssl
if (!$_SERVER['HTTPS'] and ($tpl_config['ssl'] or in_array($request, $tpl_config['secure']))) {
    if ('index' == substr($request, -5)) {
        $request = substr($request, 0, -5);
    }

    if ($_SERVER['QUERY_STRING']) {
        $request .= '?' . $_SERVER['QUERY_STRING'];
    }

    redirect('https://' . $_SERVER['HTTP_HOST'] . '/' . $request);
} elseif ($_SERVER['HTTPS'] and (!$tpl_config['ssl'] and !in_array($request, $tpl_config['secure']))) {
    if ('index' == substr($request, -5)) {
        $request = substr($request, 0, -5);
    }

    if ($_SERVER['QUERY_STRING']) {
        $request .= '?' . $_SERVER['QUERY_STRING'];
    }

    redirect('http://' . $_SERVER['HTTP_HOST'] . '/' . $request);
}

$time_start = microtime(true);

//check for predefined pages
switch ($request) {
    case 'admin':
        if (!$cms) {
            die('Error: db is not configured');
        }

        $cms->admin();
        exit;
    break;
    case 'sitemap.xml':
        if (!file_exists($root_folder . '/_tpl/sitemap.xml.php')) {
            require(dirname(__FILE__) . '/sitemap.xml.php');
            exit;
        }
    break;
    case 'logout':
        $auth->logout();
        redirect('/');
    break;
}

//current tab
$sections = explode('/', $request);

//templates
$catcher = '';
$include_file = get_include($request);

// get include content
if ('template' == end($sections) or false === $include_file) {
    $trigger_404 = true;
} elseif ($include_file) {
    ob_start();
    try {
        require($include_file);
    } catch (Exception $e) {
        switch ($e->getMessage()) {
            case 404:
                $trigger_404 = true;
            break;
            default:
                $msg = $e->getMessage() . "\n" . $e->getTraceAsString();
                $msg = nl2br($msg);
                error_handler(E_USER_ERROR, $msg, $e->getFile(), $e->getLine());
            break;
        }
    }
    $include_content = ob_get_contents();
    ob_end_clean();
}

// handle 404
if ($trigger_404) {
    header('HTTP/1.0 404 Not Found');

    if (file_exists($root_folder . '/_tpl/404.php')) {
        ob_start();
        require($root_folder . '/_tpl/404.php');
        $include_content = ob_get_contents();
        ob_end_clean();
    } else {
        $include_content = '<h1>404 - Page can not be found</h1>';
    }
}

// get page title from h1 tag if it doesn't exist
if (!$title and preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $include_content, $matches)) {
    $title = trim(preg_replace("/\r|\n/", '', strip_tags($matches[1])));
}

/*
find template file
- check current folder
- check for any catchers
- fall back to root folder
*/
if (file_exists($root_folder . '/_tpl/' . dirname($request) . '/template.php')) {
    require($root_folder . '/_tpl/' . dirname($request) . '/template.php');
} elseif ($catcher and file_exists($root_folder . '/_tpl/' . dirname($catcher) . '/template.php')) {
    require($root_folder . '/_tpl/' . dirname($catcher) . '/template.php');
} else {
    require($root_folder . '/_tpl/template.php');
}

// debug page speed
if ($auth->user['admin'] and $_GET['debug']) {
    $time_end = microtime(true);
    $time = $time_end - $time_start;
    echo '<span style="color:yellow; background: red; position:absolute; top:0; left:0; z-index: 100;">Loaded in ' . number_format($time, 3) . 's</span>';
}
