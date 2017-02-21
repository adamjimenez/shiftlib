<?php
$ext_version = '4.1.1';
//$ext_version = '4.2.0';

require(dirname(__FILE__).'/../../../_lib/base.php');

function get_all_headers()
{
    $headers = '';
   foreach ($_SERVER as $name => $value)
   {
       if (substr($name, 0, 5) == 'HTTP_')
       {
           $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
       }
   }
   return $headers;
}

//check user permissions
$auth->check_login();

if( $auth->user and $upload_config['user_uploads'] ){
    $upload_config['user']=$auth->user['id'];
}elseif( $auth->user['admin']!=1 and $auth->user['privileges']['uploads']!=2 ){
	die('Permission denied.');
}

$root = $upload_config['upload_dir'].$upload_config['user'];

//append slash
if( substr($root, -1)!=='/' ){
    $root .= '/';
}

$upload_config['root'] = '/'.$root;

$path = $root;

if( $_GET["path"] ){
    $path .= $_GET["path"].'/';

    if( !file_exists($path) ){
        mkdir($path);
    }
}

$request = get_all_headers();

if( $request["path"] ){
    $path .= $request["path"];
}

if( $_GET["func"] == 'preview' ){
	$image = image($_GET["file"], $_GET["w"], $_GET["h"], false);
    if( $image ){
    	header("Content-type: image/jpeg");
        print file_get_contents('.'.$image);
    }
    exit;
}

if( $_POST["filename"] ){
    // Make sure file is not cached (as it happens for example on iOS devices)
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // 5 minutes execution time
    @set_time_limit(5 * 60);

    // Settings
    $targetDir = $path;

    //$targetDir = 'uploads';
    $cleanupTargetDir = true; // Remove old files
    $maxFileAge = 5 * 3600; // Temp file age in seconds

    // Create target dir
    if (!file_exists($targetDir)) {
        mkdir($targetDir);
    }

    //$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : uniqid("file_");

    $fileName = $_FILES["file"]['name'] ?: $_POST['filename'];
    $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
    $chunking = isset($_REQUEST["offset"]) && isset($_REQUEST["total"]);

    // Remove old temp files
    if ($cleanupTargetDir) {
        if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
    		die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
    	}

    	while (($file = readdir($dir)) !== false) {
    		$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

    		// If temp file is current file proceed to the next
    		if ($tmpfilePath == "{$filePath}.part") {
    			continue;
    		}

    		// Remove temp file if it is older than the max age and is not the current file
    		if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
    			unlink($tmpfilePath);
    		}
    	}
    	closedir($dir);
    }

    // Open temp file
    if (!$out = fopen("{$filePath}.part", $chunking ? "cb" : "wb")) {
    	die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream. '.$filePath.'.part"}, "id" : "id"}');
    }

    if (!empty($_FILES)) {
    	if ($_FILES['file']['error'] || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    		die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file. '.$_FILES['file']['error'].'"}, "id" : "id"}');
    	}

    	// Read binary input stream and append it to temp file
    	if (!$in = fopen($_FILES['file']['tmp_name'], "rb")) {
    		die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    	}
    } else {
    	if (!$in = fopen("php://input", "rb")) {
    		die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
    	}
    }

    if ($chunking) {
    	fseek($out, $_REQUEST["offset"]); // write at a specific offset
    }

    while ($buff = fread($in, 4096)) {
    	fwrite($out, $buff);
    }

    fclose($out);
    fclose($in);

    // Check if file has been uploaded
    if (!$chunking || filesize("{$filePath}.part") >= $_REQUEST["total"]) {
    	// Strip the temp .part suffix off
    	rename("{$filePath}.part", $filePath);
    }

    // Return Success JSON-RPC response
    die('{"jsonrpc" : "2.0", "result" : null, "id" : "id", "success": true}');
}

if($_GET["download"]){
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".rawurldecode($_GET['download']).'"');
	print file_get_contents($path.$_GET['download']);
    exit;
}

if( $_GET["cmd"] ){
    $file = json_decode(file_get_contents('php://input'), true);

    switch($_GET["cmd"]){
        case 'get':
            $files = array();

            foreach (glob($path.'*') as $pathname) {
    			$filename = basename($pathname);
    			if($filename!='.' and $filename!='..'){
        		    $file = array();
                    $file['name'] = $filename;
                    $file['leaf'] = !is_dir($pathname);
                    $file['id'] = substr($pathname, strlen($root));

                    if( !is_dir($pathname) ){
                        //$file['url'] = '/uploads/'.$file['id'];

                        $thumb = image($file['id'], 50 , 39, false);
                        $thumb_medium = image($file['id'], 98 , 76, false);

                        if( $thumb ){
                            $file['thumb'] = $thumb.'?m='.filemtime($path.$file['id']);
                            $file['thumb_medium'] = $thumb_medium.'?m='.filemtime($path.$file['id']);
                        }else{
                            $file['thumb'] = 'images/file_thumb.png';
                            $file['thumb_medium'] = 'images/file_thumb_medium.png';
                        }
                    }else{
                        $file['thumb'] = 'images/folder_thumb.png';
                        $file['thumb_medium'] = 'images/folder_thumb_medium.png';
                    }

                    $file['size'] = filesize($pathname);
                    $file['modified'] = filemtime($pathname);

                    $files[] = $file;
    			}
    		}

            $result['files'] = $files;
        break;

        case 'delete':
            if( $file['id'] ){
                $files = array($file);
            }

            foreach( $files as $file ){
                if( $file['leaf'] ){
                    if( unlink($path.$file['id']) ){
                        $result[] = array(
                            'data'=>array(
                                'id'=>$file["id"],
                                'name'=>$file["id"]
                            ),
                            'success'=>true
                        );
                    }else{
                        $result[] = array(
                            'data'=>array(
                                'id'=>$file["id"],
                                'name'=>$file["id"]
                            ),
                            'message'=>'delete failed',
                            'success'=>false
                        );
                    }
                }else{
                    if( rmdir($path.$file['id']) ){
                        $result[] = array(
                            'data'=>array(
                                'id'=>$file["id"],
                                'name'=>$file["id"]
                            ),
                            'success'=>true
                        );
                    }else{
                        $result[] = array(
                            'data'=>array(
                                'id'=>$file["id"],
                                'name'=>$file["id"]
                            ),
                            'message'=>'delete failed',
                            'success'=>false
                        );
                    }
                }
            }
        break;

        case 'create':
            //new folder
            if( $file["id"]==null and $file["name"] ){
                if( mkdir($path.$file["name"]) ){
                    $result = array(
                        'data'=>array(
                            'id'=>$file["name"],
                            'name'=>$file["name"]
                        ),
                        'message'=>'created folder',
                        'success'=>true
                    );
                }else{
                    $result = array(
                        'data'=>array(
                            'id'=>$file["name"],
                            'name'=>$file["name"]
                        ),
                        'message'=>'couldn\'t create folder',
                        'success'=>false
                    );
                }
            }
        break;

        case 'update':
            //rename
            //print_r($file);
            if( $file["name"] and $file['id'] ){
                //rename
                if( rename($path.$file['id'], $path.$file['name']) ){
                    $result[] = array(
                        'data'=>array(
                            'id'=>$file["name"],
                            'name'=>$file["name"]
                        ),
                        'message'=>'rename successful',
                        'success'=>true
                    );
                }else{
                    $result[] = array(
                        'data'=>array(
                            'id'=>$file["id"],
                            'name'=>$file["id"]
                        ),
                        'message'=>'rename failed',
                        'success'=>false
                    );
                }
            }
        break;

        case 'rotateLeft':
        case 'rotateRight':
            if( $_POST["file"] ){
                $angle = ($_GET["cmd"] == 'rotateLeft') ? 90 : -90;
                
                $img = imagecreatefromfile($path.$_POST["file"]);
                
                // rotate the image by $angle degrees
                $rotated = imagerotate($img, $angle, 0);
                
                $result['success'] = imagefile($rotated, $path.$_POST['file']);
            }
        break;

        default:
            print $_SERVER['REQUEST_METHOD'];
        break;
    }

    print json_encode($result);
    exit;
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">

    <title>File browser</title>

    <script>
    var config = <?=json_encode($upload_config);?>;
    </script>

    <!-- plupload -->
    <script type="text/javascript" src="js/ux/upload/plupload/js/plupload.js"></script>
    <script type="text/javascript" src="js/ux/upload/plupload/js/plupload.html4.js"></script>
    <script type="text/javascript" src="js/ux/upload/plupload/js/plupload.html5.js"></script>
    <script type="text/javascript" src="js/ux/upload/plupload/js/plupload.flash.js"></script>

    <!--ext start -->
    <link rel="stylesheet" type="text/css" href="https://extjs.cachefly.net/ext-<?=$ext_version;?>-gpl/resources/css/ext-all-gray.css" />
    <script type="text/javascript" src="https://extjs.cachefly.net/ext-<?=$ext_version;?>-gpl/bootstrap.js"></script>
    <!--ext end -->
    
	<? load_js(array('fontawesome')); ?>

    <link rel="stylesheet" type="text/css" href="js/ux/container/ButtonSegment.css" />
    <link rel="stylesheet" type="text/css" href="js/ux/grid/feature/Tileview.css" />

    <link rel="stylesheet" type="text/css" href="css/style.css" />

    <script type="text/javascript" src="js/LabelEditor.js"></script>
    <script type="text/javascript" src="js/upload.js"></script>
</head>
<body>
</body>
</html>