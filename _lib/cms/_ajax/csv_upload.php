<?
require('../../base.php');

$allowed_exts=array('csv');

foreach($_FILES as $file){
	//check for an error
	$error='';
	if( $file['error'] ){
		switch($file['error']){
			case 1:
				$error='The uploaded file exceeds the upload_max_filesize directive in php.ini.';
			break;

			case 2:
				$error='The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
			break;

			case 3:
				$error='The uploaded file was only partially uploaded.';
			break;

			case 4:
				$error='No file was uploaded.';
			break;
		}
	}

	if( $error ){
		break;
	}

	//check file is allowed
	$file_name_arr=explode('.',$file['name']);
	if(array_search( strtolower( end($file_name_arr) ),$allowed_exts) !== false ){
		$file_name=$file_name_arr[0].".".strtolower($file_name_arr[1]);
		$file_name=str_replace("&","_",$file_name);
	}else{
		$error="File type '.".end($file_name_arr)."' not allowed'";
	}

	if( $file['size'] == 0 ){
		$error='File size: 0 bytes';
	}

	if( !$error ){
		if(!move_uploaded_file($file['tmp_name'],dirname(__FILE__).'/../tmp/'.$_SERVER['HTTP_HOST'].'.csv')){
			$error="File upload error. Make sure folder is writeable.";
		}
	}
}

if( !$file_name ){
    $error = 'no file';
}

$result['error']=$error;
$result['file']=$file_name;

print json_encode($result);
exit;
?>