<?php
//$json = wget('https://genieadmin.com/api/?host=lib.shiftcreate.com');
//$result = json_decode($json, true);
//redirect($result['redirect']);

$html = file_get_contents('https://admin.genieadmin.com/');

print $html;