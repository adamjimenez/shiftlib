<?
class phpupload_db extends phpupload
{
	function __construct() {
		parent::__construct();
	}

	//internal function
	function check_config()
	{
		//check table?
	}

	function get_files( $dirstr,$types='' )
	{
		$files = array();

		if($types==''){
			$types=array('%');
		}

		foreach( $types as $type ){
			$select=mysql_query("SELECT * FROM ".$this->cfg['mysql_table']."
				WHERE
					name LIKE '%".$type."' AND
					user='".addslashes($this->cfg['user'])."'
				ORDER BY name
			") or trigger_error("SQL", E_USER_ERROR);
			while( $row=mysql_fetch_array($select) ){
				array_push($files, $row['name']);
			}
		}

		return $files;
	}

	function upload_file($file_name,$file)
	{
		$ext=file_ext($file_name);

		$content=file_get_contents($file['tmp_name']);

		mysql_query("INSERT INTO ".$this->cfg['mysql_table']." SET
			name='".addslashes($file_name)."',
			size='".addslashes( strlen($content) )."',
			type='".addslashes($file['type'])."',
			data='".addslashes($content)."',
			user='".addslashes($this->cfg['user'])."'
		") or trigger_error("SQL", E_USER_ERROR);

		if( array_search($ext, $this->cfg['file_types']['images'])!==false and $this->cfg['resize_images'] ){
			$this->resize_image($file_name);
		}

		return $file_name;
	}

	function del_file($file)
	{
		$select=mysql_query("DELETE FROM ".$this->cfg['mysql_table']." WHERE
			name='".addslashes($file)."' AND
			user='".addslashes($this->cfg['user'])."'
		");
	}

	function rename_file($old,$new)
	{
		$select=mysql_query("UPDATE ".$this->cfg['mysql_table']." SET
			name='".addslashes($new)."'
			WHERE
				name='".addslashes($old)."' AND
				user='".addslashes($this->cfg['user'])."'
		") or trigger_error("SQL", E_USER_ERROR);

		return true;
	}

	function get_file_size($file)
	{
		$select=mysql_query("SELECT * FROM ".$this->cfg['mysql_table']."
			WHERE
				name='".addslashes($file)."' AND
				user='".addslashes($this->cfg['user'])."'
		");
		$row=mysql_fetch_array($select);

		$size=$row['size'];

		for($si = 0; $size >= 1024; $size /= 1024, $si++);
		return round($size, 1)." ".substr(' KMGT', $si, 1)."B";
	}

	function file_mtime($file)
	{
		$select=mysql_query("SELECT UNIX_TIMESTAMP(date) AS date FROM ".$this->cfg['mysql_table']."
			WHERE
				name='".addslashes($file)."' AND
				user='".addslashes($this->cfg['user'])."'
		");
		$row=mysql_fetch_array($select);

		return date( 'd F Y', $row['date'] );
	}

	function get_dimensions($file)
	{
		$select=mysql_query("SELECT * FROM ".$this->cfg['mysql_table']."
			WHERE
				name='".addslashes($file)."' AND
				user='".addslashes($this->cfg['user'])."'
		");
		$row=mysql_fetch_array($select);

		$img=imagecreatefromstring($row['data']);

		//$dimensions=getimagesize($path.'/file.php?f='.$file);
		return array( imagesx($img), imagesy($img) );
	}

	function check_file($file)
	{
		$select=mysql_query("SELECT * FROM ".$this->cfg['mysql_table']." WHERE
			name='".addslashes($file)."' AND
			user='".addslashes($this->cfg['user'])."'
		");

		if( mysql_num_rows($select) ){
			return true;
		}
	}

	function preview_image($image_file)
	{
		$img=$this->get_image_data($image_file);
		$this->thumb($img,$this->cfg['preview_dimensions'],true,true);
	}

	function preview_file($file)
	{
		print $this->get_file_contents($file);
	}

	function get_image_data($image_file)
	{
		$image_path = $this->cfg['upload_dir'].$image_file;

		$img = NULL;

		switch( file_ext($image_path) ){
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

		return $img;
	}

	function resize_image($file)
	{
		$img=$this->get_image_data($file);
		$img=$this->thumb($img,$this->cfg['resize_dimensions'],false);

		ob_start();
		switch( file_ext($file) ){
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
		$select=mysql_query("UPDATE ".$this->cfg['mysql_table']."
			SET data='".escape($data)."'
			WHERE
				name='".addslashes($file)."' AND
				user='".addslashes($this->cfg['user'])."'
		");
	}

	function get_file_contents($file)
	{
		$select=mysql_query("SELECT * FROM ".$this->cfg['mysql_table']."
			WHERE
				name='".addslashes($file)."' AND
				user='".addslashes($this->cfg['user'])."'
		");
		$row=mysql_fetch_array($select);
		return $row['data'];
	}
}
?>