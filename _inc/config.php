<?php
# GENERAL SETTINGS
$db_config['host'] = 'localhost';
$db_config['user'] = '';
$db_config['pass'] = '';
$db_config['name'] = '';

# TPL
$tpl_config['catchers'] = [];

# USER LOGIN
$auth_config = [];

// table where your users are stored
$auth_config['table'] = 'users';

// automated emails will be sent from this address
$from_email = '';
$auth_config['from_email'] = '';

// automatically generate a password
$auth_config['hash_password'] = true;

// use a secret term to encrypt cookies
$auth_config['secret_phrase'] = 'asdagre3';

// for use with session and cookie vars
$auth_config['cookie_prefix'] = 'site';

# UPLOADS
$upload_config = [];

// configure the variables before use.
$upload_config['upload_dir'] = 'uploads/';
$upload_config['web_path'] = '';
$upload_config['resize_dimensions'] = [800,600];

$upload_config['allowed_exts'] = [
    'jpg',
    'jpeg',
    'gif',
    'png',
    'htm',
    'html',
    'txt',
    'csv',
    'pdf',
    'doc',
    'xls',
    'ppt',
    'zip',
    'mp4',
];

#ADMIN AREA
// sections in menu
$vars['sections'] = [
    'users',
];

//fields in each section
$vars['fields']['users'] = [
    'name' => 'text',
    'surname' => 'text',
    'email' => 'email',
    'password' => 'password',
    'email verified' => 'checkbox',
    'admin' => 'select',
    'id' => 'id',
];

$vars['required']['users'] = [];

$vars['subsections']['users'] = [];

$vars['files']['dir'] = 'uploads/files/'; //folder to store files

#SHOP
$shop_enabled = false;
$shop_config['paypal_email'] = '';

#OPTIONS
$vars['options']['admin'] = [
    '1' => 'admin',
    '2' => 'staff',
    '3' => 'guest',
];

