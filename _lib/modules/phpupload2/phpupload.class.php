<?
//ini_set("memory_limit", "64M"); // define in htaccess instead!

abstract class phpupload{
	function __construct()
	{
		global $upload_config;

		$this->version='1.0.1a';
		$this->cfg=$upload_config;
		
		$this->cfg['upload_dir']="uploads/";
		
		$this->cfg['web_path']='';
		$this->cfg['max_file_size']=16000000;
		//$this->cfg['user']=$auth->user['id'];
		
		$this->cfg['show_file_info']=false;
		$this->cfg['edit_files']=true;
		$this->cfg['overwrite_files']=true;
		
		$this->cfg['allowed_exts']=array('jpg','jpeg','jpe','gif','png','csv','txt','htm','html','wmv','pdf','doc','pps','xls','ppt','pub','zip','mp3','flv');
		
		$this->cfg['file_types']=array(
			'images' => array('jpg','jpeg','jpe','gif','png'),
			'text' => array('htm','html','txt','csv'),
			'html' => array('htm','html'),
			'csv' => array('csv'),
			'sound' => array('mp3','wav'),
			'video' => array('flv'),
		);
		
		if( $_GET['w'] and $_GET['h'] ){
			$this->cfg['preview_dimensions']=array($_GET['w'],$_GET['h']);
		}else{
			$this->cfg['preview_dimensions']=array(500,500);
		}
		
		$this->cfg['resize_images']=true;
		$this->cfg['resize_dimensions']=array(800,600);
		
		if( $upload_config ){
			foreach( $upload_config as $k=>$v ){
				$this->cfg[$k]=$v;
			}
		}
		
		if( strstr($_GET['file'],'/') ){
			$pos=strrpos($_GET['file'],'/');
			
			$this->dir=substr($_GET['file'],0,$pos).'/';
			
			$this->cfg['upload_dir'].=$this->dir;
		}
		
		$this->check_config();
		
		if( $_GET['file_type'] ){
			$_SESSION['file_type']=$_GET['file_type'];
		}
	
		if( count($_FILES) ){
			$this->file=$this->check_uploaded_file($this->cfg['upload_dir']);
		}
		
		if($_POST['action']=='delete'){
			$this->del_file(rawurldecode($_POST['filename']));
		}
		
		if($_POST['action']=='download'){
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".rawurldecode($_POST['filename']).'"');
			print $this->get_file_contents($_POST['filename']);
			exit;
		}
		
		if($_POST['action']=='rename'){
			//check if they changing the file type to banned file
			$file_name_arr=explode('.',$_POST['new_name']);		

			if( $this->rename_file($_POST['filename'], $_POST['new_name']) ){
				header('location:?field='.$_GET['field'].'&file='.$_POST['new_name']);
			}
		}
		
		if( $_POST['edit_text'] ){
			if( !$handle = fopen($this->cfg['upload_dir'].$_GET['file'], 'w') ){
				echo "Cannot open file";
				exit;
			}
		
			// Write $somecontent to our opened file.
			if( fwrite($handle, $_POST['content']) === FALSE ){
				 echo "Cannot write to file";
				 exit;
			}
		}
		
		if( $_POST['resize'] ){
			$image_path = $this->cfg['upload_dir'].$_GET['file'];
			
			$img = null;
			$ext = strtolower(end(explode('.', $image_path)));
			if ($ext == 'jpg' || $ext == 'jpeg') {
				 $img = imagecreatefromjpeg($image_path);
			} else if ($ext == 'png') {
				 $img = imagecreatefrompng($image_path);
			# Only if your version of GD includes GIF support
			} else if ($ext == 'gif') {
				 $img = imagecreatefromgif($image_path);
			}
			
			if ($img) {
				 $width = imagesx($img);
				 $height = imagesy($img);
			
				$tmp_img = imagecreatetruecolor($_POST['width'], $_POST['height']);
				
				imagecopyresized($tmp_img, $img, 0, 0, 0, 0, 
									 $_POST['width'], $_POST['height'], $width, $height);
				imagedestroy($img);
				$img = $tmp_img;
				
				
				if ($ext == 'jpg' || $ext == 'jpeg') {
					 imagejpeg($img,$this->cfg['upload_dir'].$_GET['file']);
				} else if ($ext == 'png') {
					 imagepng($img,$this->cfg['upload_dir'].$_GET['file']);
				} else if ($ext == 'gif') {
					 imagegif($img,$this->cfg['upload_dir'].$_GET['file']);
				}
			}
			
		}
		
		if($_GET['rotate']){
			if($_GET['rotate']=='left'){
				$angle=90;
			}elseif($_GET['rotate']=='right'){
				$angle=-90;
			}
		
			$image_path = $this->cfg['upload_dir'].$_GET['file'];
			
			$img = null;
			$ext = strtolower(end(explode('.', $image_path)));
			if ($ext == 'jpg' || $ext == 'jpeg') {
				 $img = imagecreatefromjpeg($image_path);
			} else if ($ext == 'png') {
				 $img = imagecreatefrompng($image_path);
			} else if ($ext == 'gif') {
				 $img = imagecreatefromgif($image_path);
			}
			
			if ($img) {
				$width = imagesx($img);
				$height = imagesy($img);
			
				$tmp_img = imagerotate ( $img, $angle, 0);
				
				$img = $tmp_img;
				
				if ($ext == 'jpg' || $ext == 'jpeg') {
					 imagejpeg($img,$this->cfg['upload_dir'].$_GET['file']);
				} else if ($ext == 'png') {
					 imagepng($img,$this->cfg['upload_dir'].$_GET['file']);
				} else if ($ext == 'gif') {
					 imagegif($img,$this->cfg['upload_dir'].$_GET['file']);
				}
			}
			
			header('location:?func=edit&file='.$_GET['file']);
			exit;
		}

			
		if($_GET['func']=='preview'){
			$this->preview();
		}elseif($_GET['func']=='edit'){
			$this->edit();
		}
	}
	
	function check_uploaded_file($path)
	{		
		for( $i=0; $i<count($_FILES['files']['name']); $i++ ){
			$file=array(
				'name'=>$_FILES['files']['name'][$i],
				'type'=>$_FILES['files']['type'][$i],
				'tmp_name'=>$_FILES['files']['tmp_name'][$i],
				'error'=>$_FILES['files']['error'][$i],
				'size'=>$_FILES['files']['size'][$i],
			);
			
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
				print "<script>alert('ERROR: $error')</script>";
			}
		
		
			$file_name_arr=explode('.',$file['name']);		
			if(array_search( strtolower( end($file_name_arr) ),$this->cfg['allowed_exts']) !== false ){
				$allowed=true;
				$file_name=ucwords($file_name_arr[0]).".".strtolower($file_name_arr[1]);
				$file_name=str_replace("&","_",$file_name);
			}else{
				print "<script>alert('ERROR: file type \".".end($file_name_arr)."\" not allowed')</script>";
			}
		
			if (!$allowed) {
				continue;
			}
			
			if ($file['size'] > 0) {			
				$file_name=$this->upload_file($file['name'],$file);
				
				//$content .= "Uploaded File: ".$location."\n";
				//$files[]=$file_name;
				
				$files[]=$file_name;
			}

		}
		
		return $files;
	}
	
	
	function preview()
	{
		//get file ext
		$file=urldecode($_GET['file']);
		
		$ext=file_ext($file);
		
		//check file type
		if( in_array($ext, $this->cfg['file_types']['images']) ){
			$this->preview_image($file);
		}elseif( array_search($ext, $this->cfg['file_types']['text'])!==false ) {
			$this->preview_file($file);
		}elseif( $ext=='wmv' ){
			print '<embed src=../test/'.$file.' height="150" width="150" showcontrols="1"></embed>';
		}else{ //show blank pixel
			$img = imagecreate(1, 1);
			$white = imagecolorallocate($img, 255,255,255);
			
			// Display the image
			header("Content-type: image/jpeg");
			imagejpeg($img);
		}
		exit;
	}
	
	
	function edit()
	{
		//get file ext
		$ext=substr( $_GET['file'], strrpos( $_GET['file'], '.' ) +1 );
		
		//check file type
		if( $ext=='wmv'){
			print $ext.' format not yet supported. <a href="javascript:window.close()">Close</a>';
		}elseif( array_search($ext, $this->cfg['file_types']['images'])!==false ){ //image
			$this->edit_image($_GET['file']);
		}else{
			$this->edit_text($_GET['file']);
		}
	}
	
	function edit_image($file)
	{
		$vars['file']=$file;
		$vars['dimensions']=getimagesize($this->cfg['upload_dir'].$_GET['file']);
		$vars['ratio']=$vars['dimensions'][0]/$vars['dimensions'][1];
		
		require('edit_image.html.php');
	}
	
	function edit_text($file)
	{
		$vars['content']=file_get_contents( $this->cfg['upload_dir'].$_GET['file'] );
		require('edit_text.html.php');
	}
	
	function browse()
	{
		// get contents of directory.
		if($_SESSION['file_type']=='all' OR !$_SESSION['file_type']){
			$types='';
		}elseif( $_SESSION['file_type'] ){
			$types=$this->cfg['file_types'][$_SESSION['file_type']];
		}
		
		$files=$this->get_files($this->cfg['upload_dir'].$this->cfg['user'].'/',$types);
		//sort($files);
		
		if( !$this->file AND $this->check_file(basename($_GET['file'])) ){
			$this->file=basename($_GET['file']);
		}
		
		if( $this->file ){
			$vars['info']['Size']=$this->get_file_size($this->file);
			$vars['info']['Modified']=$this->file_mtime($this->file);
		}
				
		//get file ext
		$ext=substr( $this->file, strrpos( $this->file, '.' ) +1 );
		
		//check file type
		if( array_search($ext, $this->cfg['file_types']['images'])!==false ){
			$vars['dimensions']=$this->get_dimensions($this->file);
		}
	
		$vars['query_string']=$_SERVER['QUERY_STRING'];
	
		if( strstr($_SERVER['QUERY_STRING'],'file=') ){
			$vars['query_string']=substr( $vars['query_string'],0,strpos($vars['query_string'],'&file=') );
		}
		
		if( strstr($_SERVER['QUERY_STRING'],'file_type=') ){
			$vars['query_string']=substr( $vars['query_string'],0,strpos($vars['query_string'],'&file_type=') );
		}

		$vars['file']=$this->file;
		
		if( strlen($this->cfg['upload_dir'])>30 ){
			$vars['path']='...'.substr($this->cfg['upload_dir'],-30);
		}else{
			$vars['path']=$this->cfg['upload_dir'];
		}
		
		$vars['upload_dir']=$this->cfg['upload_dir'];
		
		$ver=$this->version;
		$vars['files']=$files;
		
		$vars['dir']=$this->dir;
		
		$upload_config=$this->cfg;
		
		require('phpUpload.html.php');
	}
	
	function thumb($img,$dimensions,$output=true,$margin=false)
	{
		$width = imagesx($img);
		$height = imagesy($img);
		$scale = min($dimensions[0]/$width, $dimensions[1]/$height);
		
		if ($scale < 1) {
			$new_width = floor($scale*$width);
			$new_height = floor($scale*$height);
			
			$tmp_img = imagecreatetruecolor($new_width, $new_height);
			
			imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagedestroy($img);
			$img = $tmp_img;        
		} else{
			$new_width = $width;
			$new_height = $height;
		}
		
		if( $_GET['margin'] ){
			$dest = imagecreatetruecolor($dimensions[0], $dimensions[1]);
			$white = imagecolorallocate($dest,255,255,255);
			imagefill($dest, 0, 0, $white);
			
			$padding_left=($dimensions[0]-$new_width)/2;
			$padding_top=($dimensions[1]-$new_height)/2;
			
			imagecopy($dest, $img, $padding_left, $padding_top, 0, 0, $new_width, $new_height);
			$img=$dest;
		}
		
		if( $output ){
			header("Content-type: image/jpeg");
			imagejpeg($img,NULL,85);
		}else{
			return $img;
		}
	}
}
?>