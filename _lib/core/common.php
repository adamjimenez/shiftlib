<?php
$debug_ip='';

error_reporting(E_ALL ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_NOTICE);

if( $_SERVER['REMOTE_ADDR']==$debug_ip ){
    ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
}else{
	ini_set('display_errors', '0');
	ini_set('display_startup_errors', '0');
}

set_error_handler('error_handler');
register_shutdown_function('shutdown');

ini_set('include_path', '.:/usr/share/pear/:');

if (get_magic_quotes_gpc()) {
    function stripslashes_gpc(&$value)
    {
        $value = stripslashes($value);
    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

function image($file, $w, $h, $attribs=true, $crop=false)
{
    $file = trim($file);

    if( starts_with($file, '//') ){
        $file = 'http:'.$file;
        die($file);
    }

    //no resize
    if( !$w and !$h ){
        $path = '/uploads/'.$file;
    }

	if( !$path ){
		if(
	        !file_exists('uploads/'.$file) and
	        !starts_with($file, 'http')
		){
			return false;
		}

		$cached = 'uploads/cache/';

		$dirname = dirname(urlencode($file));
		if( $dirname and !starts_with($file, 'http') ){
		    $cached .= $dirname.'/';
		}

		$cache_name = preg_replace("/[^A-Za-z0-9\-\.\/]/", '', basename(urldecode($file)));

		$cached .= $w.'x'.$h.($crop ? '(1)': '').'-'.$cache_name;

		if( !file_exists($cached) or filemtime($cached)<filemtime('uploads/'.$file) ){
			ini_set('gd.jpeg_ignore_warning', 1); // fixes an issue when previewing corrupt jpegs
			ignore_user_abort();

			// configure these
			$upload_path = 'uploads/';
			$quality = 90;
			$cache_dir = 'uploads/cache/';

			// end configure
			$max_width = isset($w) ? $w : null;
			$max_height = isset($h) ? $h : null;

			$image_path = starts_with($file, 'http') ? $file : $upload_path.$file;

			// Load image
			$img = null;
			$ext = $image_path;

			switch( $ext ){
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
				default:
					$img = imagecreatefromstring(file_get_contents($image_path));
			    break;
			}

			// If an image was successfully loaded, test the image for size
			if( $img ){
				// Get image size and scale ratio
				$width = imagesx($img);
				$height = imagesy($img);

                if( $crop ){
				    $scale = max($max_width/$width, $max_height/$height);
                }else{
    			    $scale = min($max_width/$width, $max_height/$height);
                }

				 // If the image is larger than the max shrink it
				if ($scale < 1) {
					$new_width = floor($scale*$width)-1;
					$new_height = floor($scale*$height)-1;

					// Create a new temporary image
                    if( $crop ){
    				    $tmp_img = imagecreatetruecolor($w, $h);
                    }else{
    				    $tmp_img = imagecreatetruecolor($new_width, $new_height);
                    }

					imagesavealpha($tmp_img, true);
					imagealphablending($tmp_img, true);
					$white=imagecolorallocate($tmp_img, 255, 255, 255);
					imagefill($tmp_img, 0, 0, $white);

					// Copy and resize old image into new image
					imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

					if( $ext=='gif' ){
						$transparencyIndex = imagecolortransparent($img);

						if ($transparencyIndex >= 0) {
							$transparencyColor = imagecolorsforindex($img, $transparencyIndex);
							$transparencyIndex = imagecolorallocate($tmp_img, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
							imagecolortransparent($tmp_img, $transparencyIndex);
						}
					}

					imagedestroy($img);
					$img = $tmp_img;
				}

				$cache_image=true;
			}

			//check cache dir
			if( $cache_image ){
				if( !file_exists($cache_dir) ){
					mkdir($cache_dir);
				}

				// check cache subfolders
				$dir=dirname($file);

				//security precaution
				$dir=str_replace('../','',$dir);

				if( $dir ){
					$dirs=explode('/',$dir);

					$dir_str='';
					foreach( $dirs as $dir ){
						$dir_str.=$dir.'/';

						$subfolder='uploads/cache/'.$dir_str;

						if( !file_exists($subfolder) ){
							mkdir($subfolder);
						}
					}
				}

				// Display the image
				if( $ext=='gif' ){
					$result = imagegif($img, $cached);
				}elseif( $ext=='png' ){
					$result = imagepng($img, $cached);
				}else{
					$result = imagejpeg($img, $cached, $quality);
				}

                if( !$result ){
                    //trigger_error("Can't write image ".$cached, E_USER_ERROR);
                }
			}
		}

		$path='/'.$cached;
	}

	if($attribs!==false){
        $html = '<img src="'.$path.'" '.$attribs.' />';
        echo $html;
	}else{
		return $path;
	}
}

//calculate age from dob
function age($dob)
{
    $dob = strtotime($dob);
    $y = date('Y', $dob);

    if (($m = (date('m') - date('m', $dob))) < 0) {
        $y++;
    } elseif ($m == 0 && date('d') - date('d', $dob) < 0) {
        $y++;
    }

    return date('Y') - $y;
}

function alert($error)
{
    //deprecated
	$error=str_replace("\n",'\n',$error);

	echo '<script type="text/javascript">
			alert("'.$error.'");
	</script>';
}

function analytics($id){
    ?>
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', '<?=$id;?>']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
    <?
}

function calc_grids($pcodeA)
{
	$pos=strpos($pcodeA,' ');
	if($pos){
		$pcodeA=substr($pcodeA,0,$pos);
	}

	$result=mysql_query("SELECT * FROM postcodes WHERE Pcode='$pcodeA' LIMIT 1") or trigger_error("SQL", E_USER_ERROR);

	if( mysql_num_rows($result) ){
		$row=mysql_fetch_array($result);

		$grids[0]=$row['Grid_N'];
		$grids[1]=$row['Grid_E'];

		return $grids;
	}else{
		return false;
	}
}

function check_table($table,$fields){
	$select=mysql_query("SHOW TABLES LIKE '$table'") or trigger_error("SQL", E_USER_ERROR);
	if( mysql_num_rows($select)==0 ){
		//build table query
		$query='';
		foreach($fields as $name=>$type){
			$name=underscored(trim($name));

			$db_field=form_to_db($type);

			if( $db_field ){
				$query.='`'.$name.'` '.$db_field.' NOT NULL,';
			}
		}

		mysql_query("CREATE TABLE `$table` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			$query
			PRIMARY KEY ( `id` )
			)
		") or trigger_error("SQL", E_USER_ERROR);
	}
}

function clean($string)
{
	return strip_tags($string);
}

function current_tab( $tab, $index=0 )
{
	global $sections, $request;

	if( $sections[$index]==$tab or $tab == $request ){
		echo ' class="current active"';
	}
}

function datediff($endDate, $beginDate)
{
	$date_parts1 = explode('-', $beginDate);
	$date_parts2 = explode('-', $endDate);
	$start_date = gregoriantojd($date_parts1[1], $date_parts1[2], $date_parts1[0]);
	$end_date = gregoriantojd($date_parts2[1], $date_parts2[2], $date_parts2[0]);
	return $end_date - $start_date;
}

function dateformat( $format, $date, $uk=true )
{
    if( $date=='0000-00-00' ){
		return false;
	}

    //assume uk input format
    if( $uk ){
        $date = str_replace('/','-',$date);
    }

	return date( $format, make_timestamp($date) );
}

function debug($var, $die=false)
{
	global $auth;

	if( $auth->user['admin'] ){
	    print '<hr><pre>';
		print_r($var);
	    print '</pre>';

    	if( $die ){
    		exit;
    	}
	}
}

function shutdown()
{
	if( $error=error_get_last() ){
		error_handler($error['type'],$error['message'],$error['file'],$error['line']);
	}
}

function email_template($email,$subject,$reps,$headers,$language='en')
{
	global $from_email, $email_templates, $cms, $lang;

	if( !$language ){
		$language=$cms->language;
	}

	if( !$from_email ){
		$from_email='auto@'.$_SERVER['HTTP_HOST'];
	}

	if( table_exists('email_templates') ){
		$template=$cms->get('email templates',array('subject'=>$subject),1);

		if( $language!=='en' ){
			$template=$cms->get('email templates',array('translated from'=>$template['id']),1);
		}
	}

	if( !$template and $email_templates[$subject]){
		$template['subject']=$subject;
		$template['body']=$email_templates[$subject];
	}elseif( !$template ){
		throw new Exception("Cannot find email template: '".$subject."'");
		return false;
	}

	$template['body']=str_replace("\t",'',$template['body']);

	$body=str_replace("\r\n","\n",$template['body']);

	if( is_array($reps) ){
		foreach( $reps as $k=>$v ){
			$body=str_replace('{$'.$k.'}',$v,$body);
		}
	}

	if( $from_email ){
		$headers.="From: ".$from_email."\n";
	}

	mail($email,$template['subject'],$body,$headers);
}

function starts_with($haystack, $needle){
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function ends_with($haystack, $needle){
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function error($error){
	echo $error;
	exit;
}

function error_handler ($errno, $errstr, $errfile, $errline, $errcontext='')
{
	switch ($errno)
	{
		case E_USER_WARNING:
		case E_USER_NOTICE:
		case E_WARNING:
		case E_NOTICE:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
			break;
		case E_USER_ERROR:
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:

			global $query;

			if( substr($errstr,0,3)=='SQL' ){
				$MYSQL_ERRNO = mysql_errno();
				$MYSQL_ERROR = mysql_error();
				$errstr .= "\nMySQL error: $MYSQL_ERRNO : $MYSQL_ERROR";
			}else{
				$query = NULL;
			}

			$url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

			if( $_SERVER['QUERY_STRING'] ) {
				$url.='?'.$_SERVER['QUERY_STRING'];
			}

			$errorstring .= "<p>Fatal Error: $errstr (# $errno).</p>\n";

			if ($query) $errorstring .= "<p>SQL query: $query</p>\n";

			$errorstring .= "<p>Error in line $errline of file '$errfile'.</p>\n";
			$errorstring .= "<p>Script: '{$_SERVER['PHP_SELF']}'.</p>\n";

			$errorstring .= '<p><a href="'.$url.'">'.$url.'</a></p>'."\n";

			$var_dump=print_r($_GET,true);
			$var_dump.=print_r($_POST,true);
			$var_dump.=print_r($_SESSION,true);
			$var_dump.=print_r($_SERVER,true);

			if (isset($errcontext['this'])) {
				if (is_object($errcontext['this'])) {
					$classname = get_class($errcontext['this']);
					$parentclass = get_parent_class($errcontext['this']);
					$errorstring .= "<p>Object/Class: '$classname', Parent Class: '$parentclass'.</p>\n";
				}
			}

			$image = date('m')==12 ? 'http://my.churpchurp.com/images/stories/thumbnails/204423.jpg' : 'http://cdn.grumpycats.com/wp-content/uploads/2012/09/GC-Gravatar-copy.png';

    		echo '
            <div style="text-align: center;">
            <h2>Something went wrong</h2>
            <p>The webmaster has been notified.</p>
            <p><img src="'.$image.'"></p>
            </div>
            ';

			global $debug_ip, $auth;

			if( $_SERVER['REMOTE_ADDR']==$debug_ip or $auth->user['admin'] ){
				echo "<p>The following has been reported to the administrator:</p>\n";
				echo "<b>$errorstring\n</b></font>";
			}

			$headers  = 'MIME-Version: 1.0' . "\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
			$headers .='From: auto@shiftcreate.com' . "\n";

			$body=$errorstring;
			$body.='<pre>'.$var_dump.'</pre>';

            global $admin_email;
			mail($admin_email,'PHP Error '.$_SERVER['HTTP_HOST'], $body, $headers );

			//error_log($errorstring, 1, $_SERVER['SERVER_ADMIN']);
			die();

		default:
			break;
	}
}

function escape($string)
{
	return mysql_real_escape_string($string);
}

function escape_strip($string)
{
	return mysql_real_escape_string(strip_tags($string));
}

function escape_clean($string)
{
	return mysql_real_escape_string(clean($string));
}

function file_ext($file)
{
	return strtolower(end(explode('.', $file)));
}

function file_size($size)
{
	for($si = 0; $size >= 1024; $size /= 1024, $si++);
	return round($size).substr(' KMGT', $si, 1);
}

function form_to_db($type)
{
	switch($type){
		case 'id':
		case 'select-multiple':
		case 'checkboxes':
		case 'separator':
		case 'array':
		break;
		case 'textarea':
		case 'editor':
		case 'files':
		case 'phpuploads':
			return 'TEXT';
		break;
		case 'checkbox':
		case 'rating':
			return 'TINYINT';
		break;
		case 'int':
		case 'parent':
		case 'position':
		case 'translated-from':
			return 'INT';
		break;
		case 'datetime':
			return 'DATETIME';
		break;
		case 'timestamp':
			return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
		break;
		case 'date':
		case 'dob':
			return 'DATE';
		break;
		case 'time':
			return 'TIME';
		break;
		case 'blob':
			return 'BLOB';
		break;
		case 'polygon':
			return 'POLYGON';
		break;
		case 'language':
			return 'VARCHAR( 32 )';
			//$query.='`translated_from` INT NOT NULL';
		break;
		case 'select':
		case 'radio':
		case 'combo':
			return 'VARCHAR( 64 )';
			//$query.='`translated_from` INT NOT NULL';
		break;
		default:
			return 'VARCHAR( 140 )';
		break;
	}
}

function format_mobile($mobile)
{
	$mobile=preg_replace("%[^0-9]%",'',$mobile);

	if( substr($mobile,0,1)==='0' ){
		$mobile='44'.substr($mobile,1);
	}

	if( substr($mobile,0,1)==='7' ){
		$mobile='44'.$mobile;
	}

	if( strlen($mobile)<=10 ){
		return false;
	}else{
		return $mobile;
	}
}

function format_postcode($postcode)
{
	$postcode=strtoupper($postcode);

	if( !strstr($postcode,' ') ){
		$part1=substr($postcode,0,-3);
		$part2=substr($postcode,-3);

		$postcode=$part1.' '.$part2;
	}

	if( is_postcode($postcode) ){
		return $postcode;
	}else{
		return false;
	}
}

function generate_password()
{
	$length=8;
	$password = "";

	// define possible characters
	$possible = "0123456789bcdfghjkmnpqrstvwxyz";

	$i = 0;
	while ($i < $length) {
		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

		// we don't want this character if it's already in the password
		if (!strstr($password, $char)) {
		  $password .= $char;
		  $i++;
		}
	}

	return $password;
}

function get_client_language($availableLanguages, $default='en')
{
	if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

		//start going through each one
		foreach ($langs as $value){
			$choice=substr($value,0,2);
			if(in_array($choice, $availableLanguages)){
				return $choice;
			}
		}
	}
	return $default;
}

function get_options($table,$field,$where=false)
{
	$qry="SELECT id,$field FROM $table";

	if( $where ){
		$qry.=' WHERE '.$where;
	}

	$qry.=" ORDER BY `$field`";

	$select=mysql_query($qry) or trigger_error("SQL", E_USER_ERROR);

	$options=array();
	while( $row=@mysql_fetch_array($select) ){
		$options[$row['id']]=$row[$field];
	}
	return $options;
}

function html($string)
{
	return htmlentities($string,ENT_COMPAT,'UTF-8');
}

function html_options($opts, $selected=array(), $force_assoc = false, $disabled=array())
{
    $params = array(
    	'options'=>$options,
		'values'=>$values,
		'output'=>$output,
		'selected'=>$selected,
    	'disabled'=>$disabled,
	);

    if( $force_assoc or is_assoc_array($opts) ){
		$params['options']=$opts;
	}else{
		$params['values']=$opts;
		$params['output']=$opts;
	}

	foreach($params as $_key => $_val) {
		switch($_key) {
			case 'options':
				if( $_val ){
					$$_key = (array)$_val;
				}
				break;

			case 'values':
			case 'output':
				$$_key = array_values((array)$_val);
				break;

			case 'selected':
    		case 'disabled':
				$$_key = array_map('strval', array_values((array)$_val));
				break;
		}
	}

	if (!isset($options) && !isset($values))
		return ''; /* raise error here? */

	$_html_result = '';

	if (isset($options)) {
		foreach ($options as $_key=>$_val){
			$_html_result .= html_options_optoutput($_key, $_val, $selected, $disabled);
		}
	} else {
		foreach ($values as $_i=>$_key) {
			$_val = isset($output[$_i]) ? $output[$_i] : '';
			$_html_result .= html_options_optoutput($_key, $_val, $selected, $disabled);
		}
	}

	return $_html_result;
}

function html_options_optoutput($key, $value, $selected, $disabled)
{
	if(!is_array($value)) {
		$_html_result = '<option label="' . htmlspecialchars ($value) . '" value="' .
			htmlspecialchars($key) . '"';
		if (in_array((string)$key, $selected))
			$_html_result .= ' selected="selected"';
    	if (in_array((string)$key, $disabled))
			$_html_result .= ' disabled="disabled"';
		$_html_result .= '>' .  ($value) . '</option>' . "\n";
	} else {
		$_html_result = html_options_optgroup($key, $value, $selected);
	}
	return $_html_result;
}

function html_options_optgroup($key, $values, $selected)
{
	$optgroup_html = '<optgroup label="' . htmlspecialchars($key) . '">' . "\n";

	if( is_assoc_array($opts) ){
		foreach ($values as $key => $value) {
			$optgroup_html .= html_options_optoutput($key, $value, $selected);
		}
	}else{
		foreach ($values as $key => $value) {
			$optgroup_html .= html_options_optoutput($value, $value, $selected);
		}
	}

	$optgroup_html .= "</optgroup>\n";
	return $optgroup_html;
}

function html_to_absolute($txt, $base_url)
{
	$needles = array('href="', 'src="', 'background="');
	$new_txt = '';
	if(substr($base_url,-1) != '/') $base_url=dirname($base_url).'/';
	$new_base_url = $base_url;
	$base_url_parts = parse_url($base_url);

	foreach($needles as $needle){
		while($pos = strpos($txt, $needle)){
		$pos += strlen($needle);
		if(substr($txt,$pos,7) != 'http://' && substr($txt,$pos,8) != 'https://' && substr($txt,$pos,6) != 'ftp://' && substr($txt,$pos,9) != 'mailto://'){
			if(substr($txt,$pos,1) == '/') $new_base_url = $base_url_parts['scheme'].'://'.$base_url_parts['host'];
				$new_txt .= substr($txt,0,$pos).$new_base_url;
			} else {
				$new_txt .= substr($txt,0,$pos);
			}
			$txt = substr($txt,$pos);
		}
		$txt = $new_txt.$txt;
		$new_txt = '';
	}
	return $txt;
}

function is_alphanumeric($string)
{
	if($string==''){
		return false;
	}

	preg_match_all("^([a-zA-Z0-9\s\-]+)^",$string,$matches);

	if($matches[0][0]!=$string){
		return false;
	}else{
		return true;
	}
}

function is_animated($filename) {
    if(!($fh = @fopen($filename, 'rb')))
        return false;
    $count = 0;
    //an animated gif contains multiple "frames", with each frame having a
    //header made up of:
    // * a static 4-byte sequence (\x00\x21\xF9\x04)
    // * 4 variable bytes
    // * a static 2-byte sequence (\x00\x2C)

    // We read through the file til we reach the end of the file, or we've found
    // at least 2 frame headers
    while(!feof($fh) && $count < 2) {
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
    }

    fclose($fh);
    return $count > 1;
}

function is_assoc_array($var)
{
   if (!is_array($var)) {
       return false;
   }
   return array_keys($var)!==range(0,sizeof($var)-1);
}

function is_domain($domain){
	$whois_exts = array('com', 'co.uk', 'net', 'org', 'org.uk', 'tv');

	$d = explode('.', $domain);
	if($d[2]) {
	    $ext = $d[1].'.'.$d[2];
	}else{
	    $ext = $d[1];
	}
	$domainname = $d[0];

	preg_match('`([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}`', $domainname.'.'.$ext, $match);

	if( $match[0]==$domain ){
		return in_array($ext, $whois_exts);
	}else{
		return false;
	}
}

function is_email($email)
{
	if( !$email ){
		return false;
	}

	preg_match_all("^([a-zA-Z0-9_\-\.\+]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})^",$email,$matches);

	return $matches[0][0] == $email;
}

// is valid national insurance number
function is_nino($code){
	return preg_match('^^((A[ABEHKLMPRSTWXYZ])|(B[ABEHKLMT])|(C[ABEHKLR])|(E[ABEHKLMPRSTWXYZ])|(GY)|(H[ABEHKLMPRSTWXYZ])|(J[ABCEGHJKLMNPRSTWXYZ])|(K[ABEHKLMPRSTWXYZ])|(L[ABEHKLMPRSTWXYZ])|(M[AWX])|(N[ABEHLMPRSWXYZ])|(O[ABEHKLMPRSX])|(P[ABCEGHJLMNPRSTWXY])|(R[ABEHKMPRSTWXYZ])|(S[ABCGHJKLMNPRSTWXYZ])|(T[ABEHKLMPRSTWXYZ])|(W[ABEKLMP])|(Y[ABEHKLMPRSTWXYZ])|(Z[ABEHKLMPRSTWXY]))\d{6}([A-D]|\s)$^', $code);
}

function is_odd($number)
{
	return $number & 1; // 0 = even, 1 = odd
}

function is_postcode($code)
{
	return preg_match('^([A-PR-UWYZ0-9][A-HK-Y0-9][AEHMNPRTVXY0-9]?[ABEHMNPRVWXY0-9]? {1,2}[0-9][ABD-HJLN-UW-Z]{2}|GIR 0AA)^', $code);
}

function is_url($str)
{
	return preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $str);
}

function load_js($libs)
{
	if( !is_array($libs) ){
		$libs=array($libs);
	}

	$ssl=false;
	if( $_SERVER['SERVER_PORT']==443 or $_SERVER['HTTPS'] or $_SERVER['HTTP_X_FORWARDED_PROTO']=='https' ){
		$ssl=true;
	}

	// prototype jquery lightbox swfobject cms

	//work out dependencies
	$deps=array();
	foreach( $libs as $lib ){
		switch( $lib ){
			case 'jqueryui':
			case 'cycle':
			case 'cycle2':
			case 'colorbox':
			case 'lightbox':
				$deps['jquery']=true;
			break;
			case 'jquery':
			case 'prototype';
			break;
			case 'cms':
				$deps['jqueryui']=true;
				$deps['jquery']=true;
			break;
		}

		$deps[$lib]=true;
	}

	//load the js and css in the right order
	if( $deps['google'] ){
	?>
	<script type="text/javascript" src="//www.google.com/jsapi"></script>
	<?php
	}

	if( $deps['prototype'] ){
	?>
		<script src="//ajax.googleapis.com/ajax/libs/prototype/1.7.0.0/prototype.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['jquery'] ){
	?>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<?php
		if( $deps['prototype'] ){
		?>
		<script type="text/javascript">
		jQuery.noConflict();
		</script>
	<?php
		}
	}

	if( $deps['cms'] ){
	?>
		<script type="text/javascript" src="/_lib/cms/js/cms.min.js"></script>
	<?php
	}

	if( $deps['jqueryui'] ){
		$jqueryui_version='1.9.2';
	?>
		<link href="//ajax.googleapis.com/ajax/libs/jqueryui/<?=$jqueryui_version;?>/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/<?=$jqueryui_version;?>/jquery-ui.min.js"></script>
	<?php
	}

	if( $deps['lightbox'] ){
	?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/lightbox2/2.6/css/lightbox.css" type="text/css" media="screen" />
		<script src="//cdn.jsdelivr.net/lightbox2/2.6/js/lightbox-2.6.min.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['cycle'] ){
	?>
		<script src="//cdn.jsdelivr.net/cycle/3.0.2/jquery.cycle.all.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['cycle2'] ){
	?>
		<? /*<script src="//cdn.jsdelivr.net/cycle2/20130502/jquery.cycle2.js" type="text/javascript"></script>*/ ?>
		<script src="/_lib/js/jquery.cycle2.js" type="text/javascript"></script>
		<script src="/_lib/js/jquery.cycle2.carousel.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['colorbox'] ){
	?>
		<link rel="stylesheet" href="/_lib/js/jquery.colorbox/colorbox.css" type="text/css" media="screen" />
		<script src="/_lib/js/jquery.colorbox/jquery.colorbox.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['placeholder'] ){
	?>
		<script src="//cdn.jsdelivr.net/placeholder-shiv/0.2/placeholder-shiv.jquery.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['equalheights'] ){
	?>
		<script src="//cdn.jsdelivr.net/jquery.equalheights/1.3/jquery.equalheights.min.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['responsive-nav'] ){
	?>
		<link rel="stylesheet" href="//cdn.jsdelivr.net/responsive-nav/1.0.15/responsive-nav.css" type="text/css" />
		<script src="//cdn.jsdelivr.net/responsive-nav/1.0.15/responsive-nav.js"></script>
	<?php
	}

	if( $deps['swfobject'] ){
	?>
		<script src="//ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js" type="text/javascript"></script>
	<?php
	}

	if( $deps['bootstrap'] ){
	?>
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">

        <!-- Optional theme -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">

        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
    <?php
	}

	if( $deps['syntaxhighlighter'] ){
	?>

	<link href="//agorbatchev.typepad.com/pub/sh/3_0_83/styles/shCore.css" rel="stylesheet" type="text/css" />
	<link href="//alexgorbatchev.com/pub/sh/current/styles/shThemeRDark.css" rel="stylesheet" type="text/css" />
    <script src="//alexgorbatchev.com/pub/sh/current/scripts/shCore.js" type="text/javascript"></script>
    <script src="//alexgorbatchev.com/pub/sh/current/scripts/shAutoloader.js" type="text/javascript"></script>
    <script src="//agorbatchev.typepad.com/pub/sh/3_0_83/scripts/shBrushPHP.js"></script>
    <script src="//agorbatchev.typepad.com/pub/sh/3_0_83/scripts/shBrushJScript.js"></script>
    <script src="//agorbatchev.typepad.com/pub/sh/3_0_83/scripts/shBrushCss.js"></script>
    <style>
        .toolbar{
            display: none;
        }
    </style>
	<?
	}
}

function make_timestamp($string)
{
    if(empty($string)) {
        // use "now":
        $time = time();

    } elseif (preg_match('/^\d{14}$/', $string)) {
        // it is mysql timestamp format of YYYYMMDDHHMMSS?
        $time = mktime(substr($string, 8, 2),substr($string, 10, 2),substr($string, 12, 2),
                       substr($string, 4, 2),substr($string, 6, 2),substr($string, 0, 4));

    } elseif (is_numeric($string)) {
        // it is a numeric string, we handle it as timestamp
        $time = (int)$string;

    } else {
        // strtotime should handle it
        $time = strtotime($string);
        if ($time == -1 || $time === false) {
            // strtotime() was not able to parse $string, use "now":
            $time = time();
        }
    }
    return $time;
}

function mysql2csv($query,$filename='data')
{
	$select=mysql_query($query) or trigger_error("SQL", E_USER_ERROR);

	$i=0;
	while($row=mysql_fetch_array($select,MYSQL_ASSOC)){
		if($i==0){
			foreach($row as $k=>$v){
				$data.="\"$k\",";
			}
			$data=substr($data,0,-1);
			$data.="\n";
		}
		foreach($row as $k=>$v){
			$data.="\"".str_replace('"','',$v)."\",";
		}
		$data=substr($data,0,-1);
		$data.="\n";
		$i++;
	}

	header("Pragma: cache");
	header("Content-Type: text/comma-separated-values");
	header("Content-Disposition: attachment; filename=$filename.csv");

	print $data;
	exit;
}

function num2alpha($n) {
    $r = '';
    for ($i = 1; $n >= 0 && $i < 10; $i++) {
        $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
        $n -= pow(26, $i);
    }
    return $r;
}

function options($options,$selected='')
{
	if( is_assoc_array($options) ){
		foreach($options as $k=>$v){
			if( $k==$selected ){
				$output.='<option value="'.$k.'" selected>'.$v.'</option>';
			}else{
				$output.='<option value="'.$k.'">'.$v.'</option>';
			}
		}
	}else{
		foreach($options as $k=>$v){
			if( $v==$selected ){
				$output.='<option value="'.$v.'" selected>'.$v.'</option>';
			}else{
				$output.='<option value="'.$v.'">'.$v.'</option>';
			}
		}
	}

	echo $output;
}

function parse_links($text){
   $pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
   $callback = create_function('$matches', '
       $url       = array_shift($matches);
       $url_parts = parse_url($url);

       $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
       $text = preg_replace("/^www./", "", $text);

       $last = -(strlen(strrchr($text, "/"))) + 1;
       if ($last < 0) {
           $text = substr($text, 0, $last) . "&hellip;";
       }

       return sprintf(\'<a rel="nowfollow" href="%s" target="_blank">%s</a>\', $url, $text);
   ');

   return preg_replace_callback($pattern, $callback, $text);
}

function redirect($url,$_301=false) {
	$http_response_code= ($_301) ? 301 : NULL;

	header("location:".$url,true,$http_response_code);
	exit;
}

function sec2hms ($sec, $padHours = false)
{
	// holds formatted string
	$hms = "";

	$hours = intval(intval($sec) / 3600);

	// add to $hms, with a leading 0 if asked for
	$hms .= ($padHours)
		 ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
		 : $hours. ':';

	$minutes = intval(($sec / 60) % 60);

	// then add to $hms (with a leading 0 if needed)
	$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

	$seconds = intval($sec % 60);

	// add to $hms, again with a leading 0 if needed
	$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

	return $hms;
}

function spaced($str) // also see underscored
{
	return str_replace('_',' ',$str);
}

function sql_query($query,$single=false)
{
	$result = mysql_query($query);

    if( !$result ){
        throw new Exception(mysql_error());
    }

	$return_array = array();
	while ($row = mysql_fetch_assoc($result)) {
		array_push($return_array, $row);
	}
	mysql_free_result($result);

    return $single ? $return_array[0] : $return_array;
}

function str_to_pagename($str){
	$str = strtolower(str_replace(' ','-', $str));
    return preg_replace("/[^A-Za-z0-9\-\/]/", '', $str);
}

function table_exists($table)
{
	$select=mysql_query("SHOW TABLES LIKE '$table'") or trigger_error("SQL", E_USER_ERROR);
	return mysql_num_rows($select) ? true : false;
}

function thumb($file,$max_width=200,$max_height=200,$default=NULL,$save=NULL,$output=true)
{
	$image_path = (file_exists($file) and $file) ? $file: $default;

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

	if( $img ){
		$width = imagesx($img);
		$height = imagesy($img);
		$scale = min($max_width/$width, $max_height/$height);

		if ($scale < 1) {
			$new_width = floor($scale*$width)-1;
			$new_height = floor($scale*$height)-1;

			$tmp_img = imagecreatetruecolor($new_width, $new_height);

			imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
			imagedestroy($img);
			$img = $tmp_img;
		}
	}else{
		$img = imagecreate($max_width, $max_height);
		$white = imagecolorallocate($img, 255,255,255);
		$black = imagecolorallocate($img, 0,0,0);

		imagestring($img, 5, 3, 3, 'Image Missing', $black);
	}

	if( $output ){
		header("Content-type: image/jpg");
		imagejpeg($img,NULL,85);
	}

	if( $save ){
		switch( $ext ){
			case 'jpg':
			case 'jpeg':
				imagejpeg($img,$save,85);
			break;
			case 'png':
				imagepng($img,$save,85);
			break;
			case 'gif':
				imagegif($img,$save);
			break;
		}
	}
}

function truncate($string, $max = 50, $rep = '..')
{
    $string = strip_tags($string);

	if(  strlen($string)>$max ){
		$leave = $max - strlen ($rep);
		return substr_replace($string, $rep, $leave);
	}else{
		return $string;
	}
}

function underscored($str) // also see spaced
{
	return str_replace(' ','_',$str);
}

function url_grab_title($url)
{
	$fp = @fopen($url, 'r');
	if (!$fp) {
		$title = (!$text ? $link : $text);
		return false;
	}

	// How many bytes to grab in one chunk.
	// Most sites seem to have <title> within 512
	$chunk_size = 1024;

	$chunk = fread($fp, $chunk_size);
	$chunk = preg_replace("/(\n|\r)/", '', $chunk);

	// Look for <title>(.*?)</title> in the text
	if (preg_match('/<title>(.*?)<\/title>/i', $chunk, $matches)) {
		return $matches[1];
	}

	return null;
}

function validate($fields, $required, $array=true)
{
	$errors=array();
	foreach( $required as $v ){
		switch( $v ){
			case 'email':
				if( !is_email($fields[$v]) ){
					$errors[]=$v;
				}
			break;
			case 'postcode':
				if( !format_postcode($fields[$v]) ){
					$errors[]=$v;
				}
			break;
			case 'confirm':
				if( $fields['confirm']!=$fields['password'] ){
					$errors[]=$v;
				}
			break;
			default:
				if( $fields[$v]=='' ){
					$errors[]=$v;
				}
			break;
		}
	}

	if( $array ){
		return $errors;
	}else{
		return implode("\n",$errors);
	}
}

function youtube_id_from_url($url) {
    $bits = parse_url($url);
    $query = parse_str($bits['query']);

    return $v;
}
?>