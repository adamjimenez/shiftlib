<?php
spl_autoload_register(function (string $class) {
    switch ($class) {
        case 'paging':
            require(__DIR__ . '/core/paging.php');
            return;
        case 'blog':
            require(__DIR__ . '/core/blog.php');
            return;
        case 'auth':
            require(__DIR__ . '/core/auth.php');
            return;
        case 'cms':
            require(__DIR__ . '/cms/cms.php');
            return;
        case 'shop':
            require(__DIR__ . '/core/shop.php');
            return;
        case 'cms\Component':
            require_once(__DIR__ . '/cms/Component.php');
            return;
        case 'cms\ComponentInterface':
            require_once(__DIR__ . '/cms/ComponentInterface.php');
            return;
    }
    
    // Classes in _inc
    $desiredClass = dirname(__FILE__) . '/../_inc/' . $class . '.class.php';
    if (true === file_exists($desiredClass)) {
        require $desiredClass;
        return;
    }

    // Classes in _inc, no class postfix
    $desiredClass = dirname(__FILE__) . '/../_inc/' . $class . '.php';
    if (true === file_exists($desiredClass)) {
        require $desiredClass;
        return;
    }

    if (false !== strpos($class, 'cms\components', 0)) {
        $class = str_replace('cms\\components\\', '', $class);
        require_once(__DIR__ . '/cms/components/' . basename($class) . '.php');
        return;
    }
});
