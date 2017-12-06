<?php
#GENERAL SETTINGS
$db_config["host"] = "localhost";
$db_config["user"] = "";
$db_config["pass"] = "";
$db_config["name"] = "";

#TPL
$tpl_config["catchers"] = array();

#USER LOGIN
$auth_config = array();

//table where your users are stored
$auth_config["table"] = "users";

//required fields when registering and updating
$auth_config["required"] = array(

);

//automated emails will be sent from this address
$from_email="";
$auth_config["from_email"]="";

//specify pages where users are redirected
$auth_config["login"]="login";
$auth_config["register_success"]="thanks";
$auth_config["forgot_success"]="index";

//automatically generate a password
$auth_config["generate_password"]=true;
$auth_config["hash_password"]=true;

//use a secret term to encrypt cookies
$auth_config["secret_phrase"]="asdagre3";

//for use with session and cookie vars
$auth_config["cookie_prefix"]="site";

#UPLOADS
$upload_config=array();

// configure the variables before use.
$upload_config["upload_dir"]="uploads/";
$upload_config["web_path"]="";
$upload_config["max_file_size"]=16000000;
$upload_config["type"]="dir"; // db or dir
//$upload_config["user"]=$auth->user['id'];

$upload_config["mysql_table"]="files";

$upload_config["overwrite_files"]=true;

$upload_config["resize_images"]=true;
$upload_config["resize_dimensions"]=array(800,600);

$upload_config["allowed_exts"]=array(
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
);

#ADMIN AREA
$languages=array();

$vars["change_prefs"]=false;

$email_templates=array(
	"Password Reminder"=>'Dear {$name},

    You have requested a password reminder for your {$domain} member account.

	Your password is: {$password}

	Kind regards

	The {$domain} Team',
	"Registration Confirmation"=>'Dear {$name},

    Thank you for registering as a member of {$domain}.

    To login to your new member account, visit http://{$domain}/login and login using the following information:

    Username: {$email}
    Password: {$password}

	Kind regards
	The {$domain} Team',
);
$vars["email_templates"]=false;

// sections in menu
$vars["sections"]=array(
	'users',
);

//fields in each section
$vars["fields"]["users"]=array(
	"name"=>"text",
	"surname"=>"text",
	"email"=>"email",
	"password"=>"password",
	'address'=>'textarea',
	'city'=>'text',
	'postcode'=>'text',
	'tel'=>'text',
	'code'=>'text',
	'code expire'=>'datetime',
	"admin"=>"select",
	"id"=>"id",
);

$vars["required"]["users"]=array();

$vars["labels"]["users"]=array("name","surname","email",);

$vars["subsections"]["users"]=array();

$vars["files"]["dir"]="uploads/files/"; //folder to store files

#SHOP
$shop_enabled=false;
$shop_config["paypal_email"]="";

#OPTIONS
$opts["admin"]=array(
	'1'=>'admin',
	'2'=>'staff',
	'3'=>'guest',
);

$vars["options"]=$opts;
?>