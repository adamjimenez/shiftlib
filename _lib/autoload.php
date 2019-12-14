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
        case 'Rmail':
            require(dirname(__FILE__) . '/modules/rmail/Rmail.php');
            return;
        break;
        case 'txtlocal':
            require(dirname(__FILE__) . '/modules/sms/sms.php');
            require(dirname(__FILE__) . '/modules/sms/txtlocal.php');
            return;
        break;
        case 'Hybrid_Auth':
            require(dirname(__FILE__) . '/modules/hybridauth/Hybrid/Auth.php');
            return;
        break;
    }

    //bit hacky
    if (function_exists('DOMPDF_autoload')) {
        DOMPDF_autoload($class);
        return;
    }

    //trigger_error('no such class: '.$class, E_USER_ERROR);
});
