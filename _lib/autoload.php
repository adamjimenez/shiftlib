<?php
spl_autoload_register(function ($class) {
    switch ($class) {
        case 'paging':
            require(dirname(__FILE__) . '/core/paging.class.php');
            return;
        break;
        case 'blog':
            require(dirname(__FILE__) . '/core/blog.php');
            return;
        break;
        case 'auth':
            require(dirname(__FILE__) . '/core/auth.php');
            return;
        break;
        case 'cms':
            require(dirname(__FILE__) . '/cms/cms.php');
            return;
        break;
        case 'shop':
            require(dirname(__FILE__) . '/core/shop.php');
            return;
        break;
    }
});
