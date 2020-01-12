<?php
spl_autoload_register(function (string $class) {
    switch ($class) {
        case 'paging':
            require(dirname(__FILE__) . '/core/paging.class.php');
            return;
        case 'blog':
            require(dirname(__FILE__) . '/core/blog.php');
            return;
        case 'auth':
            require(dirname(__FILE__) . '/core/auth.php');
            return;
        case 'cms':
            require(dirname(__FILE__) . '/cms/cms.php');
            return;
        case 'shop':
            require(dirname(__FILE__) . '/core/shop.php');
            return;
        case 'cms\Component':
            require_once(dirname(__FILE__) . '/cms/Component.php');
            return;
        case 'cms\ComponentInterface':
            require_once(dirname(__FILE__) . '/cms/ComponentInterface.php');
            return;
    }

    if (false !== strpos($class, 'cms\components', 0)) {
        $class = str_replace('cms\\components\\', '', $class);
        require_once(dirname(__FILE__) . '/cms/components/' . basename($class) . '.php');
        return;
    }

    throw new \Exception('Unable to identify class: ' . $class);
});
