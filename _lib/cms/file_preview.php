<?
// configure these
$default_width=320;
$default_height=240;
$quality=85;

// end configure
$max_width = (isset($_GET['w'])) ? $_GET['w'] : $default_width;
$max_height = (isset($_GET['h'])) ? $_GET['h'] : $default_height;

function thumb_img($img,$dimensions,$output=true,$margin=false)
{
	$width = imagesx($img);
	$height = imagesy($img);
	$scale = min($dimensions[0]/$width, $dimensions[1]/$height);

	if ($scale < 1) {
		$new_width = floor($scale*$width);
		$new_height = floor($scale*$height);

		$tmp_img = imagecreatetruecolor($new_width, $new_height);

		imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		imagedestroy($img);
		$img = $tmp_img;
	} else{
		$new_width = $width;
		$new_height = $height;
	}

	if( $margin ){
		$dest = imagecreatetruecolor($dimensions[0], $dimensions[1]);
		imagecolorallocate($dest,255,255,255);

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

require_once(dirname(__FILE__).'/../base.php');

//$auth->check_admin();

$select = mysql_query("SELECT * FROM files WHERE
	id='".addslashes($_GET['f'])."'
");
$row = mysql_fetch_array($select);

if( $vars['files']['dir'] ){
    $video_types=array('f4v','mp4');
    if( in_array(file_ext($row['name']), $video_types) ){
	    $row['data'] = file_get_contents($vars['files']['dir'].$row['id'].'_thumb');
    }else{
	    $row['data'] = file_get_contents($vars['files']['dir'].$row['id']);
    }

}

$img = imagecreatefromstring($row['data']);
$img = thumb_img($img,array($max_width,$max_height),false);

switch( file_ext($row['name']) ){
	case 'jpg':
	case 'jpeg':
		header("Content-type: image/jpg");
		imagejpeg($img, NULL, 85);
	break;
	case 'png':
		header("Content-type: image/png");
		imagepng($img);
	break;
	case 'gif':
		header("Content-type: image/gif");
		imagegif($img);
	break;
	default:
		header("Content-type: image/jpg");
		imagejpeg($img, NULL, 85);
    break;
}
?>