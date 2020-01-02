<?php

set_error_handler('error_handler');
register_shutdown_function('shutdown');

// create image resource from a file
function imagecreatefromfile($path)
{
    $info = getimagesize($path);

    switch ($info['mime']) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($path);
        break;
        case 'image/png':
            $img = imagecreatefrompng($path);
            imagesavealpha($img, true);
        break;
        case 'image/gif':
            $img = imagecreatefromgif($path);
        break;
        default:
            return false;
            //$img = imagecreatefromstring(file_get_contents($path));
        break;
    }
    
    return $img;
}

// rotate uploaded image from meta data
function imageorientationfix($path)
{
    $exif = exif_read_data($path);
    $orientation = $exif['Orientation'];
    
    $img = imagecreatefromfile($path);
    switch ($orientation) {
        case 3:
            $img = imagerotate($img, 180, 0);
        break;
        case 6:
            $img = imagerotate($img, -90, 0);
        break;
        case 8:
            $img = imagerotate($img, 90, 0);
        break;
    }
    return $img;
}

/*
function imagefile($img, $path): bool
{
    $ext = file_ext($path);

    if ('gif' == $ext) {
        $result = imagegif($img, $path);
    } elseif ('png' == $ext) {
        $result = imagepng($img, $path);
    } else {
        $result = imagejpeg($img, $path, 90);
    }

    return $result;
}
*/

// create a thumbnail from an uploaded file
function image($file, $w = null, $h = null, $attribs = true, $crop = false)
{
    $file = trim($file);

    if (!$file) {
        return false;
    }

    if (starts_with($file, '//')) {
        $file = 'http:' . $file;
        return $file;
    }
    
    if (is_numeric($file)) {
        $file = 'files/' . $file;
    }
    
    //no resize
    if (!$w and !$h and !starts_with($file, 'http')) {
        $path = '/uploads/' . $file;
    }

    if (!$path) {
        if (
            !file_exists('uploads/' . $file) and
            !starts_with($file, 'http')
        ) {
            return false;
        }

        $upload_path = 'uploads/';
        $image_path = starts_with($file, 'http') ? $file : $upload_path . $file;

        $cached = 'uploads/cache/';

        $dirname = dirname(urlencode($file));
        if ($dirname and !starts_with($file, 'http')) {
            $cached .= $dirname . '/';
        }

        $cache_name = preg_replace("/[^A-Za-z0-9\-\.]/", '', urldecode($file));

        $cached .= $w . 'x' . $h . ($crop ? '(1)': '') . '-' . $cache_name;

        if (!file_exists($cached) or filemtime($cached) < filemtime('uploads/' . $file)) {
            ini_set('gd.jpeg_ignore_warning', 1); // fixes an issue when previewing corrupt jpegs
            ignore_user_abort();

            // configure these
            $quality = 90;
            $cache_dir = 'uploads/cache/';

            // end configure
            $max_width = isset($w) ? $w : null;
            $max_height = isset($h) ? $h : null;

            // Load image
            $img = null;
            $ext = file_ext($image_path);

            $img = imagecreatefromfile($image_path);
            
            if (!$img) {
                return false;
            }

            // If an image was successfully loaded, test the image for size
            if ($img) {
                // Get image size and scale ratio
                $width = imagesx($img);
                $height = imagesy($img);

                if (!$w and !$h) {
                    $scale = 1;
                } elseif ($crop) {
                    $scale = max($max_width / $width, $max_height / $height);
                } else {
                    $scale = min($max_width / $width, $max_height / $height);
                }

                // If the image is larger than the max shrink it
                if ($scale < 1) {
                    $new_width = floor($scale * $width) - 1;
                    $new_height = floor($scale * $height) - 1;

                    // Create a new temporary image
                    if ($crop) {
                        $tmp_img = imagecreatetruecolor($w, $h);
                    } else {
                        $tmp_img = imagecreatetruecolor($new_width, $new_height);
                    }

                    imagesavealpha($tmp_img, true);
                    imagealphablending($tmp_img, true);
                    $white = imagecolorallocate($tmp_img, 255, 255, 255);
                    imagefill($tmp_img, 0, 0, $white);

                    // Copy and resize old image into new image
                    imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                    if ('gif' == $ext) {
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

                $cache_image = true;
            } else {
                print 2;
                return false;
            }

            //check cache dir
            if ($cache_image) {
                if (!file_exists($cache_dir)) {
                    mkdir($cache_dir);
                }

                // check cache subfolders
                $dir = dirname($file);

                //security precaution
                $dir = str_replace('../', '', $dir);

                if ($dir) {
                    $dirs = explode('/', $dir);

                    $dir_str = '';
                    foreach ($dirs as $dir) {
                        $dir_str .= $dir . '/';

                        $subfolder = 'uploads/cache/' . $dir_str;

                        if (!file_exists($subfolder)) {
                            mkdir($subfolder);
                        }
                    }
                }

                // Display the image
                if ('gif' == $ext) {
                    $result = imagegif($img, $cached);
                } elseif ('png' == $ext) {
                    $result = imagepng($img, $cached);
                } else {
                    $result = imagejpeg($img, $cached, $quality);
                }

                if (!$result) {
                    //trigger_error("Can't write image ".$cached, E_USER_ERROR);
                }
            }
        }

        $path = '/' . $cached;
    }

    if (false !== $attribs) {
        $html = '<img src="http' . (('on' == $_SERVER['HTTPS'])?'s':'') . '://' . $_SERVER['HTTP_HOST'] . $path . '" ' . $attribs . ' />';
        echo $html;
    } else {
        return $path;
    }
}

// calculate age from dob
function age($dob)
{
    $dob = strtotime($dob);
    $y = date('Y', $dob);

    if (($m = (date('m') - date('m', $dob))) < 0) {
        $y++;
    } elseif (0 == $m && date('d') - date('d', $dob) < 0) {
        $y++;
    }

    return date('Y') - $y;
}

// google analytics
function analytics($id)
{
    ?>
	<script>
	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', '<?=$id; ?>']);
	  _gaq.push(['_trackPageview']);

	  (function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();
	</script>
	<?php
}

// sort multidimensional array
function array_orderby()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = [];
            
            foreach ($data as $key => $row) {
                $tmp[$key] = $row[$field];
            }
                
            $args[$n] = $tmp;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

// return all the bank holidays from a a year
function bank_holidays($yr): array
{
    $bankHols = [];
 
    // New year's:
    switch (date('w', strtotime("$yr-01-01 12:00:00"))) {
        case 6:
            $bankHols[] = "$yr-01-03";
            break;
        case 0:
            $bankHols[] = "$yr-01-02";
            break;
        default:
            $bankHols[] = "$yr-01-01";
    }
 
    // Good friday:
    $bankHols[] = date('Y-m-d', strtotime('+' . (easter_days($yr) - 2) . ' days', strtotime("$yr-03-21 12:00:00")));
 
    // Easter Monday:
    $bankHols[] = date('Y-m-d', strtotime('+' . (easter_days($yr) + 1) . ' days', strtotime("$yr-03-21 12:00:00")));
 
    // May Day:
    if (1995 == $yr) {
        $bankHols[] = '1995-05-08'; // VE day 50th anniversary year exception
    } else {
        switch (date('w', strtotime("$yr-05-01 12:00:00"))) {
            case 0:
                $bankHols[] = "$yr-05-02";
                break;
            case 1:
                $bankHols[] = "$yr-05-01";
                break;
            case 2:
                $bankHols[] = "$yr-05-07";
                break;
            case 3:
                $bankHols[] = "$yr-05-06";
                break;
            case 4:
                $bankHols[] = "$yr-05-05";
                break;
            case 5:
                $bankHols[] = "$yr-05-04";
                break;
            case 6:
                $bankHols[] = "$yr-05-03";
                break;
        }
    }
 
    // Whitsun:
    if (2002 == $yr) { // exception year
        $bankHols[] = '2002-06-03';
        $bankHols[] = '2002-06-04';
    } else {
        switch (date('w', strtotime("$yr-05-31 12:00:00"))) {
            case 0:
                $bankHols[] = "$yr-05-25";
                break;
            case 1:
                $bankHols[] = "$yr-05-31";
                break;
            case 2:
                $bankHols[] = "$yr-05-30";
                break;
            case 3:
                $bankHols[] = "$yr-05-29";
                break;
            case 4:
                $bankHols[] = "$yr-05-28";
                break;
            case 5:
                $bankHols[] = "$yr-05-27";
                break;
            case 6:
                $bankHols[] = "$yr-05-26";
                break;
        }
    }
 
    // Summer Bank Holiday:
    switch (date('w', strtotime("$yr-08-31 12:00:00"))) {
        case 0:
            $bankHols[] = "$yr-08-25";
            break;
        case 1:
            $bankHols[] = "$yr-08-31";
            break;
        case 2:
            $bankHols[] = "$yr-08-30";
            break;
        case 3:
            $bankHols[] = "$yr-08-29";
            break;
        case 4:
            $bankHols[] = "$yr-08-28";
            break;
        case 5:
            $bankHols[] = "$yr-08-27";
            break;
        case 6:
            $bankHols[] = "$yr-08-26";
            break;
    }
 
    // Christmas:
    switch (date('w', strtotime("$yr-12-25 12:00:00"))) {
        case 5:
            $bankHols[] = "$yr-12-25";
            $bankHols[] = "$yr-12-28";
            break;
        case 6:
            $bankHols[] = "$yr-12-27";
            $bankHols[] = "$yr-12-28";
            break;
        case 0:
            $bankHols[] = "$yr-12-26";
            $bankHols[] = "$yr-12-27";
            break;
        default:
            $bankHols[] = "$yr-12-25";
            $bankHols[] = "$yr-12-26";
    }
 
    return $bankHols;
}

/*
function basename_safe($path)
{
    if (false !== mb_strrpos($path, '/')) {
        return mb_substr($path, mb_strrpos($path, '/') + 1);
    }
    return $path;
}
*/

// get coords from a postcode
function calc_grids($pcodeA, $lat = false)
{
    $pos = strpos($pcodeA, ' ');
    if ($pos) {
        $pcodeA = substr($pcodeA, 0, $pos);
    }

    $row = sql_query("SELECT * FROM postcodes WHERE Pcode='$pcodeA' LIMIT 1", 1);

    if ($row) {
        if ($lat) {
            $grids[0] = $row['Latitude'];
            $grids[1] = $row['Longitude'];
        } else {
            $grids[0] = $row['Grid_N'];
            $grids[1] = $row['Grid_E'];
        }

        return $grids;
    }
    return false;
}

// get distance in miles between two postcods
function calc_distance($postcode_a, $postcode_b)
{
    if ($postcode_a == $postcode_b) {
        return 0;
    }
    
    $grid_a = calc_grids($postcode_a);
    $grid_b = calc_grids($postcode_b);
    
    return round(sqrt(pow($grid_a[0] - $grid_b[0], 2) + pow($grid_a[1] - $grid_b[1], 2)) * 0.000621371192);
}

// add active class to the active tab
function current_tab($tab, $class = ''): string
{
    global $sections, $request;

    $index = 0;

    $str = '';
    if ($sections[$index] == $tab or $tab == $request) {
        $str = ' class="active ' . $class . '"';
    } elseif ($class) {
        $str = ' class="' . $class . '"';
    }
    
    return $str;
}

// get difference between two dates in days
function datediff($endDate, $beginDate): int
{
    $date_parts1 = explode('-', $beginDate);
    $date_parts2 = explode('-', $endDate);
    $start_date = gregoriantojd($date_parts1[1], $date_parts1[2], $date_parts1[0]);
    $end_date = gregoriantojd($date_parts2[1], $date_parts2[2], $date_parts2[0]);
    return $end_date - $start_date;
}

// like date() but uses make_timestamp instead of strtotime
function dateformat($format, $date = null, $uk = true)
{
    if ('0000-00-00' == $date) {
        return false;
    }

    //assume uk input format
    if ($uk) {
        $date = str_replace('/', '-', $date);
    }

    return date($format, make_timestamp($date));
}

// show debug message to admin
function debug($var, $die = false)
{
    global $auth;

    if ($auth->user['admin']) {
        print '<hr><pre>';
        print_r($var);
        print '</pre>';

        if ($die) {
            exit;
        }
    }
}

// send an email
/*
    $opts = [
        from_email
        to_email
        subject
        content
        attachments
    ]
*/
function send_mail($opts = []): bool
{
    global $from_email;
    
    if (!$opts['from_email']) {
        $opts['from_email'] = $from_email;
    }
    
    $is_html = ($opts['content'] !== strip_tags($opts['content']));
    
    if (getenv('SENDGRID_API_KEY')) {
        $email = new \SendGrid\Mail\Mail();
        //$email->setFrom("test@example.com", "Example User");
        $email->setFrom($opts['from_email']);
        $email->setSubject($opts['subject']);
        //$email->addTo("adam.jimenez@gmail.com", "Example User");
        $email->addTo($opts['to_email']);
        
        if ($is_html) {
            $email->addContent(
                'text/html',
                $opts['content']
            );
        }
        
        $email->addContent('text/plain', strip_tags($opts['content']));
    
        $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
        try {
            $response = $sendgrid->send($email);
        } catch (Exception $e) {
            echo 'Caught exception: ' . $e->getMessage() . "\n";
        }
        
        return $response;
    } elseif (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->SetFrom($opts['from_email']);
        $mail->Subject = $opts['subject'];
        $mail->Body = $opts['content'];
        $mail->AddAddress($opts['to_email']);
        $mail->isHTML($is_html);
        
        if ($opts['attachments']) {
            $attachments = explode("\n", $opts['attachments']);
    
            foreach ($attachments as $attachment) {
                $mail->AddAttachment('uploads/' . $attachment, basename($attachment));
            }
        }
        
        return $mail->Send();
    }
    $headers = '';
        
    if ($is_html) {
        $headers = 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
    }
        
    if ($opts['from_email']) {
        $headers .= 'From: ' . $from_email . "\n";
    }
        
    return mail($opts['to_email'], $opts['subject'], $opts['content'], $headers);
}

// send an email form the CMS - TODO move to cms class
function email_template($email, $subject = null, $reps = null, $headers = null, $language = 'en')
{
    global $from_email, $email_templates, $cms;

    if (!$language) {
        $language = $cms->language;
    }

    if (!$from_email) {
        $from_email = 'auto@' . $_SERVER['HTTP_HOST'];
    }

    if (table_exists('email_templates')) {
        $conditions = is_numeric($subject) ? $subject : ['subject' => $subject];
        $template = $cms->get('email templates', $conditions, 1);

        if ('en' !== $language) {
            $template = $cms->get('email templates', ['translated from' => $template['id']], 1);
        }
    }

    if (!$template and $email_templates[$subject]) {
        $template['subject'] = $subject;
        $template['body'] = $email_templates[$subject];
    } elseif (!$template) {
        throw new Exception("Cannot find email template: '" . $subject . "'");
        return false;
    }

    $body = str_replace("\t", '', $template['body']);
    $body = str_replace("\r\n", "\n", $body);

    if (is_array($reps)) {
        foreach ($reps as $k => $v) {
            $body = str_replace('{$' . $k . '}', $v, $body);
        }
    }

    //replace empty tokens
    $body = preg_replace('/{\$[A-Za-z0-9]+}/', '', $body);

    //fix for outlook clipping new lines
    $body = str_replace("\n", "\t\n", $body);

    $opts = [];
    $opts['from_email'] = $from_email;
    $opts['to_email'] = $email;
    $opts['subject'] = $template['subject'];
    $opts['content'] = $body;
    $opts['attachments'] = $template['attachments'];
    send_mail($opts);
}

// check if haystack starts with needle
function starts_with($haystack, $needle): bool
{
    return '' === $needle || 0 === strpos($haystack, $needle);
}

// checks if haystack ends with needle
function ends_with($haystack, $needle): bool
{
    return '' === $needle || substr($haystack, -strlen($needle)) === $needle;
}

// trigger error handler on shutdown
function shutdown()
{
    if ($error = error_get_last()) {
        error_handler($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

// output error and email them to admin
function error_handler($errno, $errstr, $errfile, $errline, $errcontext = '')
{
    global $db_connection, $debug_ip, $auth, $admin_email;

    switch ($errno) {
        case E_USER_NOTICE:
        case E_WARNING:
        case E_NOTICE:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
            break;
        case E_USER_WARNING:
        case E_USER_ERROR:
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
            if (mysqli_error($db_connection)) {
                $ERRNO = mysqli_errno($db_connection);
                $ERROR = mysqli_error($db_connection);
                $errstr .= "\nMySQL error: $ERRNO : $ERROR";
            } else {
                $query = null;
            }

            $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            if ($_SERVER['QUERY_STRING']) {
                $url .= '?' . $_SERVER['QUERY_STRING'];
            }

            $errorstring .= "<p>Fatal Error: $errstr (# $errno).</p>\n";

            if ($query) {
                $errorstring .= "<p>SQL query: $query</p>\n";
            }

            $errorstring .= "<p>Error in line $errline of file '$errfile'.</p>\n";
            $errorstring .= "<p>Script: '{$_SERVER['PHP_SELF']}'.</p>\n";
            $errorstring .= '<p><a href="' . $url . '">' . $url . '</a></p>' . "\n";

            $var_dump = print_r($_GET, true);
            $var_dump .= print_r($_POST, true);
            $var_dump .= print_r($_SESSION, true);
            $var_dump .= print_r($_SERVER, true);

            if (isset($errcontext['this'])) {
                if (is_object($errcontext['this'])) {
                    $classname = get_class($errcontext['this']);
                    $parentclass = get_parent_class($errcontext['this']);
                    $errorstring .= "<p>Object/Class: '$classname', Parent Class: '$parentclass'.</p>\n";
                }
            }

            $images = [
                'http://i.huffpost.com/gen/1735626/thumbs/o-GRUMPY-PHARRELL-facebook.jpg',
            ];
            shuffle($images);

            //$image = date('m')==12 ? 'http://my.churpchurp.com/images/stories/thumbnails/204423.jpg' : current($images);

            echo '
			<div style="text-align: center;">
			<h2>Something went wrong</h2>
			<p>The webmaster has been notified.</p>
			<p><img src="' . $image . '"></p>
			</div>
			';

            if ($_SERVER['REMOTE_ADDR'] == $debug_ip or $auth->user['admin']) {
                echo "<p>The following has been reported to the administrator:</p>\n";
                echo "<strong><pre>$errorstring\n</pre></strong>";
            }

            $headers = 'MIME-Version: 1.0' . "\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
            $headers .= 'From: auto@shiftcreate.com' . "\n";

            $body = $errorstring;
            $body .= '<pre>' . $var_dump . '</pre>';

            if ($admin_email) {
                mail($admin_email, 'PHP Error ' . $_SERVER['HTTP_HOST'], $body, $headers);
            }

            //error_log($errorstring, 1, $_SERVER['SERVER_ADMIN']);
            die();

        default:
            break;
    }
}

// prevent sql injectoin
function escape($string): ?string
{
    global $db_connection;
    return mysqli_real_escape_string($db_connection, $string);
}

// retun the file extension part of a file name
function file_ext($file): string
{
    return strtolower(end(explode('.', $file)));
}

// retun file size abbreviation e.g. 10K
function file_size($size): string
{
    for ($si = 0; $size >= 1024; $size /= 1024, $si++);
    return round($size) . substr(' KMGT', $si, 1);
}

// return number abbreviaion e.g. 10K
function number_abbr($size, $dp = null): string
{
    for ($si = 0; $size >= 1000; $size /= 1000, $si++);
    
    if (null == $dp and 2 == $si) {
        $dp = 1;
    }
    
    return number_format($size, $dp) . substr(' KMBT', $si, 1);
}

// format mobile number
function format_mobile($mobile)
{
    $mobile = preg_replace('%[^0-9]%', '', $mobile);

    if ('0' === substr($mobile, 0, 1)) {
        $mobile = '44' . substr($mobile, 1);
    }

    if ('7' === substr($mobile, 0, 1)) {
        $mobile = '44' . $mobile;
    }

    if (strlen($mobile) <= 10) {
        return false;
    }
    return $mobile;
}

// format postcode e.g. sg64lz -> SG6 4LZ
function format_postcode($postcode)
{
    $postcode = strtoupper($postcode);

    if (!strstr($postcode, ' ')) {
        $part1 = substr($postcode, 0, -3);
        $part2 = substr($postcode, -3);

        $postcode = $part1 . ' ' . $part2;
    }

    if (is_postcode($postcode)) {
        return $postcode;
    }
    return false;
}

// generate a random postcode
function generate_password($length = 8): string
{
    $password = '';

    // define possible characters
    $possible = '0123456789bcdfghjkmnpqrstvwxyz';

    $i = 0;
    while ($i < $length) {
        // pick a random character from the possible ones
        $password .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
        $i++;
    }

    return $password;
}

// get browser language
function get_client_language($availableLanguages, $default = 'en')
{
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        //start going through each one
        foreach ($langs as $value) {
            $choice = substr($value, 0, 2);
            if (in_array($choice, $availableLanguages)) {
                return $choice;
            }
        }
    }
    return $default;
}

// get options from a table DEPRECATED
function get_options($table, $field, $where = false): array
{
    $qry = "SELECT id, $field FROM $table";

    if ($where) {
        $qry .= ' WHERE ' . $where;
    }

    $qry .= " ORDER BY `$field`";

    $rows = sql_query($qry);

    $options = [];
    foreach ($rows as $row) {
        $options[$row['id']] = $row[$field];
    }
    return $options;
}

// convert array to html options
function html_options($opts, $selected = [], $force_assoc = false, $disabled = [])
{
    $params = [
        'options' => $options,
        'values' => $values,
        'output' => $output,
        'selected' => $selected,
        'disabled' => $disabled,
    ];

    if ($force_assoc or is_assoc_array($opts)) {
        $params['options'] = $opts;
    } else {
        $params['values'] = $opts;
        $params['output'] = $opts;
    }

    foreach ($params as $_key => $_val) {
        switch ($_key) {
            case 'options':
                if ($_val) {
                    $$_key = (array) $_val;
                }
                break;

            case 'values':
            case 'output':
                $$_key = array_values((array) $_val);
                break;

            case 'selected':
            case 'disabled':
                $$_key = array_map('strval', array_values((array) $_val));
                break;
        }
    }

    if (!isset($options) && !isset($values)) {
        return '';
    } /* raise error here? */

    $_html_result = '';

    if (isset($options)) {
        foreach ($options as $_key => $_val) {
            $_html_result .= html_options_optoutput($_key, $_val, $selected, $disabled);
        }
    } else {
        foreach ($values as $_i => $_key) {
            $_val = isset($output[$_i]) ? $output[$_i] : '';
            $_html_result .= html_options_optoutput($_key, $_val, $selected, $disabled);
        }
    }

    return $_html_result;
}

// used by html_options
function html_options_optoutput($key, $value, $selected, $disabled)
{
    if (!is_array($value)) {
        $_html_result = '<option label="' . htmlspecialchars($value) . '" value="' .
            htmlspecialchars($key) . '"';
        if (in_array((string) $key, $selected)) {
            $_html_result .= ' selected="selected"';
        }
        if (in_array((string) $key, $disabled)) {
            $_html_result .= ' disabled="disabled"';
        }
        $_html_result .= '>' . ($value) . '</option>' . "\n";
    } else {
        $_html_result = html_options_optgroup($key, $value, $selected);
    }
    return $_html_result;
}

// used by html_options
function html_options_optgroup($key, $values, $selected): string
{
    $optgroup_html = '<optgroup label="' . htmlspecialchars($key) . '">' . "\n";

    if (is_assoc_array($opts)) {
        foreach ($values as $key => $value) {
            $optgroup_html .= html_options_optoutput($key, $value, $selected);
        }
    } else {
        foreach ($values as $key => $value) {
            $optgroup_html .= html_options_optoutput($value, $value, $selected);
        }
    }

    $optgroup_html .= "</optgroup>\n";
    return $optgroup_html;
}

function is_alphanumeric($string): bool
{
    if ('' == $string) {
        return false;
    }

    preg_match_all("^([a-zA-Z0-9\s\-]+)^", $string, $matches);

    if ($matches[0][0] != $string) {
        return false;
    }
    return true;
}

function is_assoc_array($var): bool
{
    if (!is_array($var)) {
        return false;
    }
    return array_keys($var) !== range(0, sizeof($var) - 1);
}

function is_domain($domain): bool
{
    if (!$domain) {
        return false;
    }

    preg_match_all("^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}^", $domain, $matches);

    return $matches[0][0] == $domain;
}

function is_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// is valid national insurance number
function is_nino($code)
{
    return preg_match('^^((A[ABEHKLMPRSTWXYZ])|(B[ABEHKLMT])|(C[ABEHKLR])|(E[ABEHKLMPRSTWXYZ])|(GY)|(H[ABEHKLMPRSTWXYZ])|(J[ABCEGHJKLMNPRSTWXYZ])|(K[ABEHKLMPRSTWXYZ])|(L[ABEHKLMPRSTWXYZ])|(M[AWX])|(N[ABEHLMPRSWXYZ])|(O[ABEHKLMPRSX])|(P[ABCEGHJLMNPRSTWXY])|(R[ABEHKMPRSTWXYZ])|(S[ABCGHJKLMNPRSTWXYZ])|(T[ABEHKLMPRSTWXYZ])|(W[ABEKLMP])|(Y[ABEHKLMPRSTWXYZ])|(Z[ABEHKLMPRSTWXY]))\d{6}([A-D]|\s)$^', $code);
}

function is_odd($number): int
{
    return $number & 1; // 0 = even, 1 = odd
}

function is_postcode($code): bool
{
    if (!preg_match('/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z])))) [0-9][A-Za-z]{2})$/', $code)) {
        return false;
    }
    
    if (table_exists('postcodes')) {
        if (false === calc_grids($code)) {
            return false;
        }
    }
    
    return true;
}

function is_tel($string)
{
    return preg_match("/^[0-9\-\s]+$/", $string);
}

function is_url($str)
{
    return preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $str);
    //return filter_var($email, FILTER_VALIDATE_URL);
}

function load_js($libs)
{
    if (!is_array($libs)) {
        $libs = [$libs];
    }

    //work out dependencies
    $deps = [];
    foreach ($libs as $lib) {
        switch ($lib) {
            case 'jqueryui':
            case 'cycle':
            case 'colorbox':
            case 'lightbox':
            case 'cms':
                $deps['jquery'] = true;
            break;
        }

        $deps[$lib] = true;
    }

    //load the js and css in the right order
    if ($deps['google']) {
        ?>
	<script src="//www.google.com/jsapi"></script>
	<?php
    }

    if ($deps['jquery']) {
        ?>
		<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
	<?php
    }

    if ($deps['cms']) {
        ?>
		<script src="/_lib/cms/js/cms.js?v=3" async></script>
	<?php
    }

    if ($deps['jqueryui']) {
        $jqueryui_version = '1.11.4'; ?>
		<link href="//ajax.googleapis.com/ajax/libs/jqueryui/<?=$jqueryui_version; ?>/themes/smoothness/jquery-ui.css" rel="stylesheet" type="text/css"/>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/<?=$jqueryui_version; ?>/jquery-ui.min.js"></script>
	<?php
    }

    if ($deps['lightbox']) {
        ?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/css/lightbox.min.css" type="text/css" media="screen" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/js/lightbox.min.js" async></script>
	<?php
    }

    if ($deps['cycle']) {
        ?>
		<script src="//cdn.jsdelivr.net/cycle/3.0.2/jquery.cycle.all.js"></script>
	<?php
    }

    if ($deps['bootstrap']) {
        $version = '4.4.1'; 
    ?>
	    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/<?=$version; ?>/css/bootstrap.min.css">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/<?=$version; ?>/js/bootstrap.bundle.min.js" async></script>
	<?php
    }

    if ($deps['bootstrap3']) {
    	$version = '3.3.7';
    ?>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/<?=$version;?>/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/<?=$version;?>/css/bootstrap-theme.min.css">
        <script src="//netdna.bootstrapcdn.com/bootstrap/<?=$version;?>/js/bootstrap.min.js"></script>
    <?php
    }

    if ($deps['fontawesome']) {
        ?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css">
	<?php
    }
}

function make_timestamp($string)
{
    if (empty($string)) {
        // use "now":
        $time = time();
    } elseif ('0000-00-00' === $string) {
        return false;
    } elseif (preg_match('/^\d{14}$/', $string)) {
        // it is timestamp format of YYYYMMDDHHMMSS
        $time = mktime(
            substr($string, 8, 2),
            substr($string, 10, 2),
            substr($string, 12, 2),
            substr($string, 4, 2),
            substr($string, 6, 2),
            substr($string, 0, 4)
        );
    } elseif (is_numeric($string)) {
        // it is a numeric string, we handle it as timestamp
        $time = (int) $string;
    } else {
        // strtotime should handle it
        $time = strtotime($string);
        if (-1 == $time || false === $time) {
            return false;
        }
    }
    return $time;
}

function array_to_csv_file($rows, $filename = 'data', $add_header = true)
{
    $i = 0;
    foreach ($rows as $row) {
        if (0 == $i and $add_header) {
            foreach ($row as $k => $v) {
                $data .= "\"$k\",";
            }
            $data = substr($data, 0, -1);
            $data .= "\n";
        }
        foreach ($row as $k => $v) {
            $data .= '"' . str_replace('"', '', $v) . '",';
        }
        $data = substr($data, 0, -1);
        $data .= "\n";
        $i++;
    }

    header('Pragma: cache');
    header('Content-Type: text/comma-separated-values');
    header("Content-Disposition: attachment; filename=$filename.csv");

    print $data;
    exit;
}

function options($options, $selected = '')
{
    if (is_assoc_array($options)) {
        foreach ($options as $k => $v) {
            if ($k == $selected) {
                $output .= '<option value="' . $k . '" selected>' . $v . '</option>';
            } else {
                $output .= '<option value="' . $k . '">' . $v . '</option>';
            }
        }
    } else {
        foreach ($options as $k => $v) {
            if ($v == $selected) {
                $output .= '<option value="' . $v . '" selected>' . $v . '</option>';
            } else {
                $output .= '<option value="' . $v . '">' . $v . '</option>';
            }
        }
    }

    echo $output;
}

function parse_links($text)
{
    $pattern = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
    $callback = create_function('$matches', '
	   $url	   = array_shift($matches);
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

function redirect($url, $http_response_code = null)
{
    if (true === $http_response_code) {
        $http_response_code = 301;
    }

    header('location:' . $url, true, $http_response_code);
    exit;
}

function sec2hms($sec, $padHours = false): string
{
    // holds formatted string
    $hms = '';

    $hours = intval(intval($sec) / 3600);

    // add to $hms, with a leading 0 if asked for
    $hms .= ($padHours)
         ? str_pad($hours, 2, '0', STR_PAD_LEFT) . ':'
         : $hours . ':';

    $minutes = intval(($sec / 60) % 60);

    // then add to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':';

    $seconds = intval($sec % 60);

    // add to $hms, again with a leading 0 if needed
    $hms .= str_pad($seconds, 2, '0', STR_PAD_LEFT);

    return $hms;
}

function spaced($str) // also see underscored
{
    return str_replace('_', ' ', $str);
}

function cache_query($query, $single = false, $expire = 3600)
{
    $memcache = new Memcache;
    $memcache->connect('localhost', 11211) or trigger_error('Could not connect', E_USER_ERROR);
    
    $result = $memcache->get(md5($query));
    
    if (!$result) {
        $result = sql_query($query);
        $memcache->set(md5($query), $result, false, $expire) or trigger_error('Failed to save data at the server', E_USER_ERROR);
    }
    
    return $single ? $result[0] : $result;
}

function send_html_email($user, $html, $reps)
{
    // get subject
    if (preg_match("/<title>(.*)<\/title>/siU", $html, $title_matches)) {
        $title = preg_replace('/\s+/', ' ', $title_matches[1]);
        $subject = trim($title);
    }
    
    if (!$subject) {
        die('missing subject');
    }
    
    // get from name
    $pos = strpos($_SERVER['HTTP_HOST'], '.');
    $from = substr($_SERVER['HTTP_HOST'], 9, $pos);
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=iso-8859-1',
        'From: ' . $from . ' <auto@' . $_SERVER['HTTP_HOST'] . '>',
    ];
    $headers = implode("\r\n", $headers);
    
    $hash = md5($user['id'] . 'jhggh6tj^999£$£%k77');
    $reps['unsubscribe'] = 'https://' . $_SERVER['HTTP_HOST'] . '/unsubscribe?u=' . $user['id'] . '&h=' . $hash;
    
    foreach ($reps as $k => $v) {
        $html = str_replace('{$' . $k . '}', $v, $html);
    }
    
    mail($user['email'], $subject, $html, $headers);
}

function sql_affected_rows(): int
{
    global $db_connection;
    return mysqli_affected_rows($db_connection);
}

function sql_insert_id()
{
    global $db_connection;
    return mysqli_insert_id($db_connection);
}

function sql_num_rows($result): int
{
    return mysqli_num_rows($result);
}

function sql_query($query, $single = false)
{
    global $db_connection;
    
    if ($_GET['debug']) {
    	debug($query);
    }

    $result = mysqli_query($db_connection, $query);

    if (false === $result) {
        throw new Exception($query);
    } else if (true === $result) {
        return true;
    }
    
    $return_array = [];
    while ($row = mysqli_fetch_assoc($result)) {
        array_push($return_array, $row);
    }
    
    mysqli_free_result($result);

    return $single ? $return_array[0] : $return_array;
}

function str_to_pagename($page_name): string
{
    //remove odd chars
    $page_name = preg_replace("/[^\sA-Za-z0-9\.\-\/>()]/", '', $page_name);

    //replace > with /
    $page_name = str_replace('>', '/', $page_name);

    //lowercase
    $page_name = strtolower($page_name);

    //trim
    $page_name = trim($page_name);

    //replace spaces with dashes
    $page_name = preg_replace("/\s+/", '-', $page_name);

    //trailing .
    $page_name = rtrim($page_name, '.');

    return $page_name;
}

function table_exists($table): bool
{
    $rows = sql_query("SHOW TABLES LIKE '$table'");
    return count($rows) ? true : false;
}

function thumb($file, $max_width = 200, $max_height = 200, $default = null, $save = null, $output = true)
{
    $image_path = (file_exists($file) and $file) ? $file: $default;

    $ext = strtolower(end(explode('.', $image_path)));

    switch ($ext) {
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

    if ($img) {
        $width = imagesx($img);
        $height = imagesy($img);
        $scale = min($max_width / $width, $max_height / $height);

        if ($scale < 1) {
            $new_width = floor($scale * $width) - 1;
            $new_height = floor($scale * $height) - 1;

            $tmp_img = imagecreatetruecolor($new_width, $new_height);

            imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($img);
            $img = $tmp_img;
        }
    } else {
        $img = imagecreate($max_width, $max_height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagestring($img, 5, 3, 3, 'Image Missing', $black);
    }

    if ($output) {
        header('Content-type: image/jpg');
        imagejpeg($img, null, 85);
    }

    if ($save) {
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($img, $save, 85);
            break;
            case 'png':
                imagepng($img, $save, 85);
            break;
            case 'gif':
                imagegif($img, $save);
            break;
        }
    }
}

function time_elapsed($ptime): string
{
    $etime = time() - make_timestamp($ptime);

    $a = [ 12 * 30 * 24 * 60 * 60 => ' years',
                30 * 24 * 60 * 60 => ' months',
                24 * 60 * 60 => ' days',
                60 * 60 => 'h',
                60 => 'm',
                1 => 's',
                ];

    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if (abs($d) >= 1) {
            $r = round($d);
            return $r . $str . (abs($r) > 1 ? '' : '');
        }
    }
}

function truncate($string, $max = 50, $rep = '..'): string
{
    $string = strip_tags($string);

    if (strlen($string) > $max) {
        $leave = $max - strlen($rep);
        return substr_replace($string, $rep, $leave);
    }
    return $string;
}

function underscored($str) // also see spaced
{
    return str_replace(' ', '_', $str);
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

function validate($fields, $required, $array = true)
{
    $errors = [];
    foreach ($required as $v) {
        switch ($v) {
            case 'email':
                if (!is_email($fields[$v])) {
                    $errors[] = $v;
                }
            break;
            case 'tel':
                if (!is_tel($fields[$v])) {
                    $errors[] = $v;
                }
            break;
            case 'postcode':
                if ('' == $fields[$v] or ((!$fields['country'] or 'UK' == $fields['country']) and !format_postcode($fields[$v]))) {
                    $errors[] = $v;
                }
            break;
            case 'confirm':
                if ($fields['confirm'] != $fields['password']) {
                    $errors[] = $v;
                }
            break;
            default:
                if ('' == $fields[$v]) {
                    $errors[] = $v;
                }
            break;
        }
    }

    if ($array) {
        return $errors;
    }
    return implode("\n", $errors);
}

function wget($url)
{
    $ch = curl_init();
    
    // set url
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    
    return $data;
}

function video_info($url): array
{
    $data = [];

    if (stristr($url, 'vimeo.com')) {
        $data['source'] = 'vimeo';

        $pattern = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/';
        preg_match($pattern, $url, $matches);

        $data['id'] = end($matches);
        $data['url'] = 'https://player.vimeo.com/video/' . $data['id'];

        //not working?
        $hash = json_decode(file_get_contents('http://vimeo.com/api/v2/video/' . $data['id'] . '.json'), true);
        $data['thumb'] = $hash[0]['thumbnail_medium'];
    } elseif (stristr($url, 'youtu')) {
        $data['source'] = 'youtube';

        $pattern = '#^(?:https?:)?(?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
        preg_match($pattern, $url, $matches);

        $data['id'] = (isset($matches[1])) ? $matches[1] : false;
        $data['thumb'] = 'https://img.youtube.com/vi/' . $data['id'] . '/0.jpg';
        $data['url'] = 'https://www.youtube.com/embed/' . $data['id'] . '?rel=0';
    } else {
        $data['url'] = $url;
    }

    if (ends_with($data['url'], '.mp4')) {
        $data['tag'] = '<video controls>
			<source src="' . $data['url'] . '" type="video/mp4">
		</video>';
    } else {
        $data['tag'] = '<iframe src="' . $data['url'] . '" frameborder="0" allowfullscreen></iframe>';
    }

    return $data;
}
