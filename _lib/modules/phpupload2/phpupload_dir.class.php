<?
class phpupload_dir extends phpupload
{
	function __construct() {
		parent::__construct();
	}
	
	//internal function
	function check_config()
	{
		//check folder exists
		if(!is_dir($this->cfg['upload_dir'].$this->cfg['user'])){
			$old_umask = umask(0); //to stop 755
			mkdir($this->cfg['upload_dir'].$this->cfg['user'], 0777);
		}
		
		//check folder is writable
		if(!is_writable($this->cfg['upload_dir'].$this->cfg['user'])){
			print "Folder is not writable: ".$this->cfg['upload_dir'].$this->cfg['user'];
			exit();
		}
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
		$dirs = array();
		$files = array();
		 
		 /*
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
		*/
		
		if ($handle = opendir($dirstr)) {
			while (false !== ($file = readdir($handle))) {
				if( is_dir($dirstr.$file)===true ){
					if( $file=='.' ){
						continue;
					}
					$dirs[]=$file;
				}else{
					$files[]=$file;
				}
			}
			closedir($handle);
		}
		
		sort($dirs);
		sort($files);
		
		$files=array_merge($dirs,$files);
		
		return $files;
	}
	
	function upload_file($file_name,$file)
	{
		//$this->make_writable( substr($path,0,-1) );
		
		$file_name=strtolower($file_name);
		
		$ext=file_ext($file_name);
		
		//check if file exists
		if( !$this->cfg['overwrite_files'] ){
			if( file_exists($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file_name) ){
				
				$i=1;
				while( file_exists($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file_name) ){
					$file_name=$file_name.'('.$i.').'.$ext;
					
					$i++;
				}
			}
		}
		
		if(!move_uploaded_file($file['tmp_name'],$this->cfg['upload_dir'].$this->cfg['user'].'/'.$file_name)){
			die("File upload error.");
		}

		if( 
			in_array($ext, $this->cfg['file_types']['images']) and $this->cfg['resize_images']  and
			($ext!='gif' or !is_animated($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file_name))
		){
			$this->resize_image($file_name);
		}
		
		if($file_name_arr[1]=="csv"){
			$content=file_get_contents($this->cfg['upload_dir'].$file_name);
			$content = str_replace("\r", "\n", $content);	//force mac breaks to windoze
	
			$handle=fopen($this->cfg['upload_dir'].$file_name,"w");
			fwrite($handle, $content);
			fclose($handle);
		}
		//$this->make_unwritable( substr($path,0,-1) );
		
		return $file_name;
	}
	
	function del_file($file)
	{
		if( file_exists($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file) ){
			unlink($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file);
		}
		
		mysql_query("INSERT INTO logs SET
			user='".$auth->user['id']."',
			action='deleted file: ".$file."'
		");
	}
	
	function rename_file($old,$new)
	{
		mysql_query("INSERT INTO logs SET
			user='".$auth->user['id']."',
			action='renamed file: ".$old.", to: ".$new."'
		");
		
		return rename($this->cfg['upload_dir'].$old, $this->cfg['upload_dir'].$new);
	}
	
	function get_file_size($file)
	{
		$size = filesize($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file);
		for($si = 0; $size >= 1024; $size /= 1024, $si++);
		return round($size, 1)." ".substr(' KMGT', $si, 1)."B";
	}
	
	function file_mtime($file)
	{
		return date( 'd F Y',filemtime($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file) );
	}
	
	function get_dimensions($file)
	{
		$dimensions=getimagesize($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file);
		return $dimensions;
	}
	
	function check_file($file)
	{
		if( file_exists($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file) ){
			return true;
		}
	}
	
	function preview_image($image_file)
	{
		$img=$this->get_image_data($image_file);
		$this->thumb($img,$this->cfg['preview_dimensions'],true);
	}
	
	function preview_file($file)
	{
		print $this->get_file_contents($file);
	}
	
	function get_image_data($image_file)
	{
		$image_path = $this->cfg['upload_dir'].$this->cfg['user'].'/'.basename($image_file);

		$img = NULL;

		switch( strtolower(file_ext($image_path)) ){
			case 'jpg':
			case 'jpeg':
				$img = imagecreatefromjpeg($image_path);
			break;
			case 'png':
				$img = imagecreatefrompng($image_path);
			break;
			case 'gif':
				$img = imagecreatefromgif($image_path);
			break;
		}
		
		return $img;
	}
	
	function resize_image($file)
	{
		$img=$this->get_image_data($file);
		$img=$this->thumb($img,$this->cfg['resize_dimensions'],false);
		
		ob_start();
		switch( strtolower(file_ext($file)) ){
			case 'jpg':
			case 'jpeg':
				imagejpeg($img, NULL, 85);
			break;
			case 'png':
				imagepng($img);	
			break;
			case 'gif':
				imagegif($img);
			break;
		}
		$data = ob_get_contents();
		ob_end_clean();
		
		$this->save_file($file,$data);		
	}
	
	function save_file($file,$data)
	{
		file_put_contents($this->cfg['upload_dir'].$this->cfg['user'].'/'.$file,$data);
	}
	
	function get_file_contents($file)
	{
		$filename = $this->cfg['upload_dir'].$file;
		$handle = fopen($filename, "r");
		$contents = fread($handle, 40000);
		fclose($handle);
		return $contents;
	}
}
?>