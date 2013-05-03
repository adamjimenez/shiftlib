<?
$upload_config['upload_dir']='';

mysql_connect(	$upload_config['mysql_host'], 
				$upload_config['mysql_user'], 
				$upload_config['mysql_pass']
) or die(mysql_error());
mysql_select_db($upload_config['mysql_db']) or die(mysql_error());

if($upload_config['user']){
	$upload_config['where']="AND user='".addslashes($upload_config['user'])."'";
	$upload_config['insert']=", user='".addslashes($upload_config['user'])."'";
}

//internal function
function get_files( $dirstr,$types='' ) 
{
	global $upload_config,$sid;

	$files = array();
	 
	if($types==''){
		$types=array('%');
	}

	foreach( $types as $type ){
	 	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." 
			WHERE name LIKE '%".$type."'
			".$upload_config['where']."
			ORDER BY NAME
		") or die(mysql_error());
		while( $row=mysql_fetch_array($select) ){
			array_push($files, $row['name']);
		}
	}
	 
	return $files;
}

function check_files_table()
{
	global $upload_config;
	
	$select=mysql_query("SHOW TABLES LIKE '".$upload_config['mysql_table']."'");
	
	if( mysql_num_rows($select)==0 ){
		mysql_query("CREATE TABLE `".$upload_config['mysql_table']."` (
		  `id` int(11) NOT NULL auto_increment,
		  `user` int(11) NOT NULL default 0,
		  `name` varchar(64) NOT NULL default '',
		  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
		  `size` varchar(16) NOT NULL default '',
		  `type` varchar(16) NOT NULL default '',
		  `data` mediumblob NOT NULL,
		  PRIMARY KEY  (`id`)
		  )
		") or die(mysql_error());
	}
}

function save_file($file_name,$file)
{
	global $upload_config, $sid;
	
	$content=file_get_contents($file['tmp_name']);
	
	if($file_name_arr[1]=="csv"){
		$content = str_replace("\r", "\n", $content);	//force mac breaks to windoze
	}
	
	//insert into db or update
	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." 
		WHERE name='".addslashes($file_name)."'
		".$upload_config['where']."
	");
	
	if( mysql_num_rows($select)==0 ){
		mysql_query("INSERT INTO ".$upload_config['mysql_table']." SET
			name='".addslashes($file_name)."',
			size='".addslashes( strlen($content) )."',
			type='".addslashes($file['type'])."',
			data='".addslashes($content)."'
			".$upload_config['insert']."
		") or die(mysql_error());
	}else{
		mysql_query("UPDATE ".$upload_config['mysql_table']." SET
			name='".addslashes($file_name)."',
			size='".addslashes( strlen($content) )."',
			type='".addslashes($file['type'])."',
			data='".addslashes($content)."'
			WHERE  name='".addslashes($file_name)."'
			".$upload_config['where']."
		") or die(mysql_error());
	}
}

function del_file($file)
{
	global $upload_config, $sid;
	
	$select=mysql_query("DELETE FROM ".$upload_config['mysql_table']." WHERE 
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
}

function rename_file($old,$new)
{
	global $upload_config, $sid;
	
	$select=mysql_query("UPDATE ".$upload_config['mysql_table']." SET
		name='".addslashes($new)."'
		WHERE name='".addslashes($old)."'
		".$upload_config['where']."
	") or die(mysql_error());
	
	return true;
}

function file_size($file)
{
	global $upload_config, $sid;

	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
	$row=mysql_fetch_array($select);
	
	$size=$row['size'];
	
	for($si = 0; $size >= 1024; $size /= 1024, $si++);
	return round($size, 1)." ".substr(' KMGT', $si, 1)."B";
}

function file_mtime($file)
{
	global $upload_config, $sid;

	$select=mysql_query("SELECT UNIX_TIMESTAMP(date) AS date FROM ".$upload_config['mysql_table']." 
		WHERE name='".addslashes($file)."'
		".$upload_config['where']."
	");
	$row=mysql_fetch_array($select);
	
	return date( 'd F Y', $row['date'] );
}

function get_dimensions($file)
{
	global $upload_config, $sid;
	
	define(MAX_WIDTH, 150);
	define(MAX_HEIGHT, 150);
	
	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE 
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
	$row=mysql_fetch_array($select);
	
	$img=imagecreatefromstring($row['data']);
	
	//$dimensions=getimagesize($path.'/file.php?f='.$file);
	return array( imagesx($img), imagesy($img) );
}

function check_file($file)
{
	global $upload_config, $sid;
	
	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE 
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
	
	if( mysql_num_rows($select) ){
		return true;
	}
}

function preview_image($file)
{
	global $upload_config, $sid;
	
	define(MAX_WIDTH, 150);
	define(MAX_HEIGHT, 150);
	
	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE 
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
	$row=mysql_fetch_array($select);
	
	$img=imagecreatefromstring($row['data']);
	
	thumb($img);
}

function preview_file($file)
{
	global $upload_config, $sid;

	$select=mysql_query("SELECT * FROM ".$upload_config['mysql_table']." WHERE 
		name='".addslashes($file)."'
		".$upload_config['where']."
	");
	$row=mysql_fetch_array($select);
	
	print $row['data'];
}

if( $upload_config['mysql_auto_create_table'] ){
	check_files_table();
}

?>