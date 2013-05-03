<?
//check folder exists
if(!is_dir($upload_config['upload_dir'].$upload_config['user'])){
	mkdir($upload_config['upload_dir'].$upload_config['user']);
}

//check folder is writable
if(!is_writable($upload_config['upload_dir'].$upload_config['user'])){
	print "Folder is not writable: ".$upload_config['upload_dir'];
	exit();
}

//internal function
function make_writable($path)// won't work with safe mode :0(
{
	chmod($path,0777);
}

//internal function
function make_unwritable($path)// won't work with safe mode :0(
{
	chmod($path,0755);
}

function get_files( $dirstr,$types='' ) 
{
	global $upload_config;
	
	$files = array();
	 
	if($types==''){
		$types=array('*');
	}
	
	foreach( $types as $type ){
		foreach (glob($dirstr.'*.'.$type) as $pathname) {
			$filename=basename($pathname);
			if($filename!='.' AND $filename!='..'){
				array_push($files, $filename);
			}
		}
	}
	 
	return $files;
}

function save_file($file_name,$file)
{
	global $upload_config,$auth,$to_email, $from_email;
	
	//$this->make_writable( substr($path,0,-1) );
	
	//check if file exists
	if( file_exists($upload_config['upload_dir'].$upload_config['user'].'/'.$file_name) ){
		$pos=strrpos($file_name, '.');
		$name=substr($file_name,0,$pos);
		$ext=substr($file_name,$pos);
		
		$i=1;
		while( file_exists($upload_config['upload_dir'].$upload_config['user'].'/'.$file_name) ){
			$file_name=$name.'('.$i.')'.$ext;
			
			$i++;
		}
	}
	
	if(!move_uploaded_file($file['tmp_name'],$upload_config['upload_dir'].$upload_config['user'].'/'.strtolower($file_name))){
		print "File upload error. Make sure folder is writeable.";
	}
	
	if($file_name_arr[1]=="csv"){
		$content=file_get_contents($upload_config['upload_dir'].$file_name);
		$content = str_replace("\r", "\n", $content);	//force mac breaks to windoze

		$handle=fopen($upload_config['upload_dir'].$file_name,"w");
		fwrite($handle, $content);
		fclose($handle);
	}
	//$this->make_unwritable( substr($path,0,-1) );
	
	mysql_query("INSERT INTO logs SET
		user='".$auth->user['id']."',
		action='file uploaded: ".$file_name."'
	");
	
	$msg='New file uploaded by: '.$auth->user['email']."\n\n";
	$msg.='file: '.$file_name."\n\n";

	//$msg.='download: http://abdftp.co.uk/uploads/'.$auth->user['id'].'/'.urlencode($file_name)."\n\n";
	
	//mail($to_email,'New file uploaded',$msg,"From: ".$from_email."\n");
	
	return $file_name;
}

function del_file($file)
{
	global $upload_config;
	
	if( file_exists($upload_config['upload_dir'].$upload_config['user'].'/'.$file) ){
		unlink($upload_config['upload_dir'].$upload_config['user'].'/'.$file);
	}
	
	mysql_query("INSERT INTO logs SET
		user='".$auth->user['id']."',
		action='deleted file: ".$file."'
	");
}

function rename_file($old,$new)
{
	global $upload_config;
	
	mysql_query("INSERT INTO logs SET
		user='".$auth->user['id']."',
		action='renamed file: ".$old.", to: ".$new."'
	");
	
	return rename($upload_config['upload_dir'].$old, $upload_config['upload_dir'].$new);
}

function get_file_size($file)
{
	global $upload_config;
	
	$size = filesize($upload_config['upload_dir'].$upload_config['user'].'/'.$file);
	for($si = 0; $size >= 1024; $size /= 1024, $si++);
	return round($size, 1)." ".substr(' KMGT', $si, 1)."B";
}

function file_mtime($file)
{
	global $upload_config;
	
	return date( 'd F Y',filemtime($upload_config['upload_dir'].$upload_config['user'].'/'.$file) );
}

function get_dimensions($file)
{
	global $upload_config;
	
	$dimensions=getimagesize($upload_config['upload_dir'].$upload_config['user'].'/'.$file);
	return $dimensions;
}

function check_file($file)
{
	global $upload_config;
	
	if( file_exists($upload_config['upload_dir'].$upload_config['user'].'/'.$file) ){
		return true;
	}
}

function preview_image($image_file)
{
	global $upload_config;
	
	define(MAX_WIDTH, 150);
	define(MAX_HEIGHT, 150);

	$image_path = $upload_config['upload_dir'].$image_file;
	
	$img = null;
	$ext = strtolower(end(explode('.', $image_path)));
	
	switch( $ext ){
		case 'jpg':
		case 'jpeg':
			$img = @imagecreatefromjpeg($image_path);
		break;
		case 'png':
			$img = @imagecreatefrompng($image_path);
		break;
		case 'gif':
			$img = @imagecreatefromgif($image_path);
		break;
	}
	
	thumb($img);
}

function preview_file($file)
{
	global $upload_config;
	
	$filename = $upload_config['upload_dir'].$file;
	$handle = fopen($filename, "r");
	$contents = fread($handle, 40000);
	fclose($handle);
	print $contents;
}

?>