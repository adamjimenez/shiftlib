<?php
require('base.php');

if( $auth->user['admin'] ){
    phpinfo();
}else{
    header('HTTP/1.1 403 Forbidden');
    print 'permission denied';
}
?>