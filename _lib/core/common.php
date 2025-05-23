<?php
set_error_handler('error_handler');
register_shutdown_function('shutdown');

/**
 * @param string $string
 * @return string
 */
function camelCaseToSnakeCase(string $string): string
{
    // Replace uppercase letters with underscore and lowercase
    $converted = preg_replace_callback('/([A-Z])/', function ($c) {
        return "_" . strtolower($c[1]);
    }, $string);

    // Remove _ prefix and lowercase
    return ltrim(strtolower($converted), '_');
}

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
        case 'image/webp':
            $img = imagecreatefromwebp($path);
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

function thumb_img($img, $dimensions, $output = true, $margin = false)
{
    $width = imagesx($img);
    $height = imagesy($img);
    $scale = min($dimensions[0] / $width, $dimensions[1] / $height);

    if ($scale < 1) {
        $new_width = floor($scale * $width);
        $new_height = floor($scale * $height);

        $tmp_img = imagecreatetruecolor($new_width, $new_height);

        imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($img);
        $img = $tmp_img;
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    if ($margin) {
        $dest = imagecreatetruecolor($dimensions[0], $dimensions[1]);
        imagecolorallocate($dest, 255, 255, 255);

        $padding_left = ($dimensions[0] - $new_width) / 2;
        $padding_top = ($dimensions[1] - $new_height) / 2;

        imagecopy($dest, $img, $padding_left, $padding_top, 0, 0, $new_width, $new_height);
        $img = $dest;
    }

    if ($output) {
        header('Content-type: image/jpeg');
        imagejpeg($img, null, 85);
    } else {
        return $img;
    }
}

// used by phpupload
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

// create a thumbnail from an uploaded file
function image($file, $w = null, $h = null, $attribs = '', $crop = false)
{
    if (is_array($file)) {
        $file = current($file);
    }
    
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

        $cached .= $w . 'x' . $h . ($crop ? '_1': '') . '-' . $cache_name;

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

            $img = imageorientationfix($image_path);
            
            if (!$img) {
                return false;
            }

            // If an image was successfully loaded, test the image for size
            if ($img) {
                // Get image size and scale ratio
                $src_width = imagesx($img);
                $src_height = imagesy($img);

                if (!$w and !$h) {
                    $scale = 1;
                } elseif ($crop) {
                    $scale = max($max_width / $src_width, $max_height / $src_height);
                } else {
                    $scale = min($max_width / $src_width, $max_height / $src_height);
                }

                // If the image is larger than the max shrink it
                if ($scale < 1) {
                    $new_width = floor($scale * $src_width) - 1;
                    $new_height = floor($scale * $src_height) - 1;

                    // Create a new temporary image
                    
                    $offset_y = 0;
                    $offset_x = 0;
                    if ($crop) {
                        $tmp_img = imagecreatetruecolor($w, $h);
                        
                        if (($max_width / $src_width) > ($max_height / $src_height)) {
                            $offset_y = ($src_height - ($max_height / $scale)) / 2;
                        } else {
                            $offset_x = ($src_width - ($max_width / $scale)) / 2;
                        }
                    } else {
                        $tmp_img = imagecreatetruecolor($new_width, $new_height);
                    }

                    imagesavealpha($tmp_img, true);
                    imagealphablending($tmp_img, true);
                    $white = imagecolorallocate($tmp_img, 255, 255, 255);
                    imagefill($tmp_img, 0, 0, $white);

                    // Copy and resize old image into new image
                    imagecopyresampled($tmp_img, $img, 0, 0, $offset_x, $offset_y, $new_width, $new_height, $src_width, $src_height);

                    if ('gif' == $ext) {
                        $transparencyIndex = imagecolortransparent($img);

                        if ($transparencyIndex >= 0) {
                            $palletsize = imagecolorstotal($img);
                            
                            if ($transparencyIndex >= 0 && $transparencyIndex < $palletsize) {
                                $transparencyColor = imagecolorsforindex($img, $transparencyIndex);
                                $transparencyIndex = imagecolorallocate($tmp_img, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
                                imagecolortransparent($tmp_img, $transparencyIndex);
                            }
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

function anonymize_email($email) {
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];
    $length = strlen($username);
    $first_char = substr($username, 0, 1);
    $last_char = substr($username, -1);
    $replacement = str_repeat('*', $length - 2);
    return $first_char . $replacement . $last_char . '@' . $domain;
}

function anonymize_phone($phone) {
    if (strlen($phone) < 6) {
        return '';
    }
    
    $visible_digits = substr($phone, -4);
    $replacement = str_repeat('*', strlen($phone) - 4);
    return $replacement . $visible_digits;
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
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?=$id; ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', '<?=$id; ?>');
    </script>
	<?php
}

// sort multidimensional array, Pass the array, followed by the column names and sort flags
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

function array_rorderby()
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
            $args[] = SORT_DESC;
        }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

// return all the bank holidays from a a year
function bank_holidays($yr = null): array
{
    if (!$yr) {
        $yr = date('Y');
    }
    
    $bankHols = [];

    // New Year's Day (substitute if weekend)
    $newYears = strtotime("$yr-01-01");
    $w = date('w', $newYears);
    if ($w == 6) {
        $bankHols[] = date('Y-m-d', strtotime("$yr-01-03"));
    } elseif ($w == 0) {
        $bankHols[] = date('Y-m-d', strtotime("$yr-01-02"));
    } else {
        $bankHols[] = date('Y-m-d', $newYears);
    }

    // Good Friday and Easter Monday
    $easter = strtotime("$yr-03-21 +".easter_days($yr)." days");
    $bankHols[] = date('Y-m-d', strtotime('-2 days', $easter)); // Good Friday
    $bankHols[] = date('Y-m-d', strtotime('+1 day', $easter));  // Easter Monday

    // Early May bank holiday
    if ($yr == 1995) {
        $bankHols[] = '1995-05-08'; // VE Day exception
    } elseif ($yr == 2020) {
        $bankHols[] = '2020-05-08'; // VE Day 75th anniversary
    } else {
        $bankHols[] = date('Y-m-d', strtotime("first monday of May $yr"));
    }

    // Spring bank holiday (last Monday in May)
    if ($yr == 2002) {
        $bankHols[] = '2002-06-03';
        $bankHols[] = '2002-06-04';
    } elseif ($yr == 2012) {
        $bankHols[] = '2012-06-04';
        $bankHols[] = '2012-06-05';
    } else {
        $bankHols[] = date('Y-m-d', strtotime("last monday of May $yr"));
    }

    // Summer bank holiday (England/Wales: last Monday in August)
    $bankHols[] = date('Y-m-d', strtotime("last monday of August $yr"));

    // Christmas Day and Boxing Day with substitutions
    $xmas = strtotime("$yr-12-25");
    $boxing = strtotime("$yr-12-26");
    $xmas_w = date('w', $xmas);
    $boxing_w = date('w', $boxing);

    if ($xmas_w == 6) {
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-27")); // Monday
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-28")); // Tuesday
    } elseif ($xmas_w == 0) {
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-26")); // Monday
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-27")); // Tuesday
    } elseif ($boxing_w == 6) {
        $bankHols[] = date('Y-m-d', $xmas); // Friday
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-28")); // Monday
    } elseif ($boxing_w == 0) {
        $bankHols[] = date('Y-m-d', $xmas); // Saturday
        $bankHols[] = date('Y-m-d', strtotime("$yr-12-27")); // Monday
    } else {
        $bankHols[] = date('Y-m-d', $xmas);
        $bankHols[] = date('Y-m-d', $boxing);
    }

    sort($bankHols);
    return $bankHols;
}

function is_bank_holiday($date) {
    $bank_holidays = bank_holidays(dateformat('Y', $date));
    return in_array(dateformat('Y-m-d', $date), $bank_holidays);
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
    $start_date = gregoriantojd($date_parts1[1], (int)$date_parts1[2], $date_parts1[0]);
    $end_date = gregoriantojd($date_parts2[1], (int)$date_parts2[2], $date_parts2[0]);
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
function debug($log = '')
{
    $bt = debug_backtrace();
    $caller = array_shift($bt);
    
    $log = [
        'log' => $log,
        'backtrace' => $bt,
    ];
    
    print '<script>console.log("PHP DEBUG '.$caller['file'].': '.$caller['line'].'"); console.log(' . json_encode($log) . ');</script>';
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
        
        if ($opts['reply_to']) {
            $email->setReplyTo($opts['reply_to']);
        }
        
        $email->setFrom($opts['from_email']);
        $email->setSubject($opts['subject']);
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
        
        //return $response;
        return true;
    } elseif (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        
        if ($opts['reply_to']) {
            $mail->AddReplyTo($opts['reply_to']);
        }
        
        if ($opts['cc']) {
            $mail->AddCC($opts['cc']);
        }
        
        $mail->SetFrom($opts['from_email']);
        $mail->AddAddress($opts['to_email']);
        
        $mail->Subject = $opts['subject'];
        $mail->Body = $opts['content'];
        $mail->isHTML($is_html);
        $mail->CharSet = "UTF-8";
        
        if ($opts['attachments']) {
            $attachments = $opts['attachments'];
            if (is_string($opts['attachments'])) {
                $attachments = explode("\n", $opts['attachments']);    
            }

            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $name = $attachment['name'];
                    $path = $attachment['path'];
                } else {
                    $name = basename($attachment);
                    $path = 'uploads/' . $attachment;
                }

                $mail->AddAttachment($path, $name);
            }
        }
        
        return $mail->Send();
    }
    
    $headers = '';
        
    if ($is_html) {
        $headers = 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\n";
    }
        
    if ($opts['from_email']) {
        $headers .= 'From: ' . $opts['from_email'] . "\n";
    }
        
    if ($opts['reply_to']) {
        $headers .= 'Reply-to: ' . $opts['reply_to'] . "\n";
    }
        
    return mail($opts['to_email'], $opts['subject'], $opts['content'], $headers);
}

// send an email form the CMS - TODO move to cms class
function email_template($email, $subject = null, $reps = null, $opts = [])
{
    global $from_email, $email_templates, $cms;

    if (!$from_email) {
        $from_email = 'auto@' . $_SERVER['HTTP_HOST'];
    }

    if (table_exists('email_templates')) {
        $conditions = is_numeric($subject) ? $subject : ['subject' => $subject];
        $template = $cms->get('email templates', $conditions, 1);
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
            if (!is_string($v)) {
                continue;
            }
            
            $template['subject'] = str_replace('{$' . $k . '}', $v, $template['subject']);
            
            $body = str_replace('{$' . $k . '}', $v, $body);
            
            // replace vars in links
            $body = str_replace(urlencode('{$' . $k . '}'), $v, $body);
        }
    }

    //replace empty tokens
    $body = preg_replace('/{\$[A-Za-z0-9]+}/', '', $body);

    //fix for outlook clipping new lines
    $body = str_replace("\n", "\t\n", $body);

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
    global $db_connection, $auth, $admin_email, $die_quietly;

    switch ($errno) {
        case E_USER_WARNING:
        case E_USER_ERROR:
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
            if ($die_quietly) {
                die();
            }
            
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

            $errorstring = $errstr . "\n";

            if ($query) {
                $errorstring .= "SQL query: $query\n";
            }

            if (isset($errcontext['this'])) {
                if (is_object($errcontext['this'])) {
                    $classname = get_class($errcontext['this']);
                    $parentclass = get_parent_class($errcontext['this']);
                    $errorstring .= "Object/Class: '$classname', Parent Class: '$parentclass'.\n";
                }
            }

            // check whether to return as json or console log
            if (in_array('Content-Type: application/json, charset=utf-8', headers_list())) {
                $response = [
                    'error' => $errorstring
                ];
                print json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
            } else {
                echo '<script>console.error(`' . $errorstring . '`);</script>';
            }
            
            if (!$admin_email) {
                return;
            }

            $headers = 'MIME-Version: 1.0' . "\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
            $headers .= 'From: auto@shiftcreate.com' . "\n";
            
            $errorstring .= "Script: '{$_SERVER['PHP_SELF']}'.\n";
            $errorstring .= $url . "\n";

            $var_dump = print_r($_GET, true);
            $var_dump .= print_r($_POST, true);
            $var_dump .= print_r($_SESSION, true);
            $var_dump .= print_r($_SERVER, true);

            $body = $errorstring;
            $body .= '<pre>' . $var_dump . '</pre>';
            
            // remove null character
            $body = str_replace("\0", "", $body);
            mail($admin_email, 'PHP Error ' . $_SERVER['HTTP_HOST'], $body, $headers);

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
function number_abbr($size, $dp = 1): string
{
    for ($si = 0; $size >= 1000; $size /= 1000, $si++);
    return number_format($size, $dp) . substr(' KMBT', $si, 1);
}

// format tel number
function format_tel($tel)
{
    $tel = str_replace('+44', '0', $tel);
    
    if (strlen($tel) < 11 || strlen($tel) > 12) {
        return false;
    }
    
    $tel = preg_replace('%[^0-9]%', '', $tel);

    if ('0' === substr($tel, 0, 1)) {
        $tel = '44' . substr($tel, 1);
    }
    
    $tel = '+' . $tel;

    return $tel;
}

// format postcode e.g. sg64lz -> SG6 4LZ
function format_postcode($postcode)
{
    // force uppercase
    $postcode = strtoupper(trim($postcode));
    
    // replace spaces with single space
    $postcode = preg_replace('!\s+!', ' ', $postcode);

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
        'options' => null,
        'values' => null,
        'output' => null,
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
    if (false === is_array($value)) {
        $_html_result = '<option label="' . htmlspecialchars($value) . '" value="' .
            htmlspecialchars($key) . '"';
        if (in_array((string) $key, $selected)) {
            $_html_result .= ' selected="selected"';
        }
        if (in_array((string) $key, $disabled)) {
            $_html_result .= ' disabled="disabled"';
        }
        $_html_result .= '>' . ($value) . '</option>';
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
    if (false === is_array($var)) {
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

function is_hostname($value){
	return filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
}

function is_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_tel($mobile)
{
    return preg_match('/^\+[0-9]{10,12}+$/', $mobile) ? true : false;
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
    if (strlen($code) > 8 || strlen($code) < 6 || !preg_match('/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\s?[0-9][A-Za-z]{2})$/', $code)) {
        return false;
    }
    
    return true;
}

function load_js($libs)
{
    if (false === is_array($libs)) {
        $libs = [$libs];
    }

    //work out dependencies
    $deps = [];
    foreach ($libs as $lib) {
        switch ($lib) {
            case 'jqueryui':
            case 'cycle':
            case 'lightbox':
            case 'bootstrap':
                $deps['jquery'] = true;
            break;
            case 'vuetify':
            case 'vuetify2':
                $deps['vue2'] = true;
            break;
            case 'vuetify3':
                $deps['vue3'] = true;
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

    if ($deps['vue2']) {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
	<?php
    }

    if ($deps['vue3']) {
        ?>
        <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
	<?php
    }

    if ($deps['vuetify2']) {
        ?>
        <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
	<?php
    }

    if ($deps['vuetify3']) {
        ?>
        <link href="https://unpkg.com/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet"> 
        <link href="https://cdn.jsdelivr.net/npm/vuetify@3.5.3/dist/vuetify-labs.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/vuetify@3.5.3/dist/vuetify-labs.min.js"></script>
	<?php
    }

    if ($deps['jquery']) {
        ?>
		<script src="https://code.jquery.com/jquery-3.3.1.js"></script>
	<?php
    }

    if ($deps['cms']) {
        ?>
		<script src="/_lib/cms/assets/js/cms.js?v=<?=time();?>"></script>
	<?php
    }

    if ($deps['shiftlib']) {
        ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.7.8/handlebars.min.js"></script>
		<script src="/_lib/cms/assets/js/shiftlib.js?v=<?=time();?>"></script>
	<?php
    }

    if ($deps['jqueryui']) {
        $jqueryui_version = '1.12.1'; ?>
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
        $version = '4.6.2'; ?>
	    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@<?=$version; ?>/dist/css/bootstrap.min.css">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@<?=$version; ?>/dist/js/bootstrap.bundle.min.js"></script>
	<?php
    }

    if ($deps['bootstrap3']) {
        $version = '3.3.7'; ?>
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/<?=$version; ?>/css/bootstrap.min.css">
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/<?=$version; ?>/css/bootstrap-theme.min.css">
        <script src="//netdna.bootstrapcdn.com/bootstrap/<?=$version; ?>/js/bootstrap.min.js"></script>
    <?php
    }

    if ($deps['fontawesome']) {
        ?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css">
	<?php
    }

    if ($deps['fontawesome6']) {
        ?>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
	<?php
    }

    if ($deps['recaptcha']) {
        ?>
		<script src='https://www.google.com/recaptcha/api.js'></script>
	<?php
    }

    if ($deps['recaptchav3']) {
        global $auth_config;
        ?>
        <script id="recaptcha" data-key="<?=$auth_config['recaptcha_key'];?>" src="https://www.google.com/recaptcha/api.js?render=<?=$auth_config['recaptcha_key'];?>"></script>
	<?php
    }

    if ($deps['tinymce']) {
        ?>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.0.1/tinymce.min.js"></script>
	<?php
    }
    
    if ($deps['editorjs']) {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
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

    if ($filename === false) {
        return $data;        
    }

    header('Pragma: cache');
    header("Content-Type: text/csv");
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

/* used to flush output to the browser */
function output($str)
{
    if (php_sapi_name() == "cli") {
        echo $str . "\n";
    } else {
        echo trim($str);
        echo str_pad('', 4096) . "\n";
        ob_flush();
        flush();
    }
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

	   return sprintf(\'<a rel="nofollow" href="%s" target="_blank">%s</a>\', $url, $text);
   ');

    return preg_replace_callback($pattern, $callback, $text);
}

function recaptcha() {
    global $auth_config;
    print '<div class="g-recaptcha" data-sitekey="' . $auth_config['recaptcha_key'] . '"></div>';
}

function reload() {
    redirect($_SERVER['REQUEST_URI']);
}

function redirect($url, $http_response_code = null)
{
    if (php_sapi_name() == 'cli') {
        print 'redirect to ' . $url . PHP_EOL;
        return false;   
    }
    
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

function cache($key, $value = null, $expire = 3600)
{
    $memcache = new Memcached;
    $memcache->addServer("localhost", 11211) or trigger_error('Could not connect', E_USER_ERROR);
    
    if ($value === null) {
        return $memcache->get($key);
    }
    
    $memcache->set($key, $value, $expire) or trigger_error('Failed to save data at the server', E_USER_ERROR);
}

function cache_query($query, $single = false, $expire = 3600)
{
    if (!class_exists('Memcached')) {
        return sql_query($query, $single);
    }
    
    $memcache = new Memcached;
    $memcache->addServer("localhost", 11211) or trigger_error('Could not connect', E_USER_ERROR);

    $hash = md5($_SERVER['DB_NAME'] . '' . $query);

    $result = $memcache->get($hash);
    
    if ($result === false || $expire === false) {
        $result = sql_query($query);
        $memcache->set($hash, $result, $expire ?: 1) or trigger_error('Failed to save data at the server', E_USER_ERROR);
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

// used with server sent events
function send_msg($msg) {
	global $msg_id;
	
	if (php_sapi_name() === 'cli') {
	    echo $msg . "\n";
	    return;
	}
	
	if (!$msg_id) {
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');
		}
		
		ignore_user_abort();
		$msg_id = time();
	}
	
	$data = is_array($msg) ? $msg  : ['msg' => $msg];
	$data['id'] = $msg_id;
	
	$line = 'data: ' . json_encode($data) . PHP_EOL;
    print_flush($line);
}

function print_flush($line) {
    if(!headers_sent()) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');        
    }
    
    if (ob_get_level() !== 0) {
        ob_end_clean();
    }
    
    if (is_array($line)) {
        $line = implode(',', $line);
    }
    
	ob_end_clean();
    print $line . PHP_EOL;
	ob_flush();
	flush();
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

function sql_num_rows($result): ?int
{
    return mysqli_num_rows($result);
}

function sql_query($query, $single = false)
{
    global $db_connection, $auth;
    
    if (is_null($db_connection)) {
        die('no database connection');
    }
    
    $debug = $_GET['debug'] && $auth->user['admin'] == 1;
    
    if ($debug) {
    	if (!$GLOBALS['debug_init']) {
    		register_shutdown_function(function() {
    			print '<script>
    					var queries = ' . json_encode($GLOBALS['debug']['queries']) . ';
						    					
						function compare( a, b ) {
						  if ( a.duration > b.duration ){
						    return -1;
						  }
						  if ( a.duration < b.duration ){
						    return 1;
						  }
						  return 0;
						}
						
						queries.sort( compare );
						
						console.log(queries);
    				</script>';
    		});
    		
    		$GLOBALS['debug']['queries'] = [];
    		$GLOBALS['debug_init'] = true;
    	}
    	
        $timer_start = microtime(true);
    }

    $result = mysqli_query($db_connection, $query);
    
    if ($debug) {
        $timer_now = microtime(true);
        $diff = $timer_now - $timer_start;
        
        $bt = debug_backtrace();
        $caller = array_shift($bt);
        
        $GLOBALS['debug']['queries'][] = [
        	'query' => $query,
        	'duration' => $diff,
        	'file' => $caller['file'],
        	'line' => $caller['line'],
        ];
    }

    if (false === $result) {
        $error = mysqli_error($db_connection);
        throw new Exception($error . ' : ' . $query);
    } elseif (true === $result) {
        return true;
    }
    
    $return_array = [];
    while ($row = mysqli_fetch_assoc($result)) {
        array_push($return_array, $row);
    }
    
    mysqli_free_result($result);

    return $single ? $return_array[0] : $return_array;
}

// polyfill for PHP < 8
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

/**
 * Calculates the great-circle distance between two points, with
 * the Haversine formula.
**/
function st_distance($coord1, $coord2, $earthRadius = 6371000)
{
    // convert from degrees to radians
    if (is_string($coord1)) {
        $coord1 = explode(' ', $coord1);
    }
    if (is_string($coord2)) {
        $coord2 = explode(' ', $coord2);
    }
    
    $latFrom = deg2rad((float)$coord1[0]);
    $lonFrom = deg2rad((float)$coord1[1]);
    $latTo = deg2rad((float)$coord2[0]);
    $lonTo = deg2rad((float)$coord2[1]);
    
    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;
    
    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
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

function time_elapsed($ptime): ?string
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
    
    return '';
}

function timer($label='')
{
    global $auth;

    if (!$auth->user['admin']) {
        return;
    }

    global $timer_start;

    if (!$timer_start) {
        $timer_start = microtime(true);
        $timer_now = $timer_start;
    } else {
        $timer_now = microtime(true);
    }

    $diff = $timer_now - $timer_start;

    $timer_start = $timer_now;

    print '<p>' . $label . ': ' . round($diff, 4) . '</p>';
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

function wget($url, $post_array = null, $cache_expiration = 0)
{
    $cache_name = md5($url.json_encode($post_array));
    
    if ($cache_expiration) {
        $memcache = new Memcached;
        $memcache->addServer("localhost", 11211) or trigger_error('Could not connect', E_USER_ERROR);
        $result = $memcache->get($cache_name);
        if ($result) {
            return $result;
        }
    }
    
    global $tmp_fname;
    
    $ch = curl_init();
    	
	if (!$tmp_fname) {
        $tmp_fname = tempnam(__DIR__ . "/tmp", "COOKIE");
	}
	
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:86.0) Gecko/20100101 Firefox/86.0');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
    if ($post_array) {
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query((array)$post_array)); 
    }
    
    // cookies
    curl_setopt($ch, CURLOPT_COOKIEJAR, $tmp_fname);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $tmp_fname);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($cache_expiration) {
        $memcache->set($cache_name, $result, $cache_expiration) or trigger_error('Failed to save data at the server', E_USER_ERROR);
    }
    
    return $result;
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

function finish_request($content = '') {
    // Buffer all upcoming output...
    if (ob_get_level() !== 0) {
        ob_end_clean();
    }
    ob_start();
    
    if ($content) {
        print $content;
    }

    // Get the size of the output.
    $size = ob_get_length();

    // Disable compression (in case content length is compressed).
    header("Content-Encoding: none");

    // Set the content length of the response.
    header("Content-Length: {$size}");

    // Close the connection.
    header("Connection: close");

    // Flush all output.
    ob_end_flush();
    ob_flush();
    flush();
    
    if(session_id()) session_write_close();
}

function get_icon($key) {
    $icons = [
        'users' => 'mdi-account',
        'orders' => 'mdi-basket',
        'pages' => 'mdi-file-document-edit',
        'settings' => 'mdi-cog',
        'enquiries' => 'mdi-email-box',
        'email templates' => 'mdi-email-box',
        'news' => 'mdi-newspaper',
    ];
    
    return $icons[$key];
}