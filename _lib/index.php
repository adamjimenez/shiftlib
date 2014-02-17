<?php
require('base.php');
if( $auth->user['admin'] ){
    phpinfo();
}
?>