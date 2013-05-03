<?
global $upload_config;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />
	<title><?=$ver;?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <? load_js('jquery'); ?>

    <script type="text/javascript" src="/_lib/js/tinymce/tiny_mce_popup.js"></script>
	<link href="style.css" rel="stylesheet" type="text/css">

    <script>
    var field = '<?=$_GET['field'];?>';
    </script>

	<script type="text/javascript">
	function onOK()
	{
		var filename=document.getElementById('filename').value;

		if( filename.indexOf('.')==-1 || filename=='.' || filename=='..' ){
			if( filename=='.' || filename=='..' ){
				var pos=filename.lastIndexOf('/');

				if( pos ){
					dir=filename.substring(0,pos);
				}else{
					dir='';
				}
			}else{
				dir = filename;
			}

			location.href='?field='+field+'&file='+dir+'/';
			return;
		}

        if( !window.opener && window.parent.tinyMCE ){
            var URL = '/uploads/'+filename;
            var win = tinyMCEPopup.getWindowArg("window");

            // insert information now
            win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

            // are we an image browser
            if (typeof(win.ImageDialog) != "undefined") {
                // we are, so update image dimensions...
                if (win.ImageDialog.getImageData)
                    win.ImageDialog.getImageData();

                // ... and preview if necessary
                if (win.ImageDialog.showPreviewImage)
                    win.ImageDialog.showPreviewImage(URL);
            }

            // close popup window
            tinyMCEPopup.close();
            return;
        }

		if( window.opener.phpUploadCallback ){
			var sel=document.getElementById('filename');

			var files=[];

			var count=0;
			for (i=0; i<sel.options.length; i++) {
				if (sel.options[i].selected) {
					files[count] = '<?=$vars['dir'];?>'+sel.options[i].value;
					count++;
				}
			}

			window.opener.phpUploadCallback(files);
			window.close();
			return;
		}

		if( window.opener.document.getElementById(field) ){
			window.opener.document.getElementById(field).value='<?=$vars['dir'];?>'+filename;
		}

		if( window.opener.document.getElementById(field+'_thumb') ){
			window.opener.document.getElementById(field+'_thumb').src='_lib/modules/phpupload/?func=preview&file=<?=$vars['dir'];?>'+filename+'&w=320&h=240';
		}

		if( window.opener.document.getElementById(field+'_label') ){
			window.opener.document.getElementById(field+'_label').innerHTML='<?=$vars['dir'];?>'+filename;
		}

		window.close()
	}

	function rename()
	{
		var filename= prompt('Rename file:',$('#filename').val());

		if( (filename!='') && (filename!=null) ){
			document.getElementById('action').value='rename';
			document.getElementById('new_name').value=filename;
			document.form.submit();
		}
	}

	function edit()
	{
		window.open('?func=edit&file='+document.form.filename.options[document.form.filename.selectedIndex].value,'Edit','width=500,height=350,screenX=150,screenY=150,left=150,top=150,status,dependent,alwaysRaised');
	}

	function delete_file()
	{
		var is_confirmed = confirm('Are you sure you want to delete `'+$('#filename').val()+'`?');

		if(is_confirmed===true){
			document.getElementById('action').value='delete';
			document.form.submit();
		}
	}

	function download_file()
	{
		document.getElementById('action').value='download';
		document.form.submit();
	}

	function upload_file()
	{
		document.getElementById('loading').style.display='block';
		document.form.submit();
	}

	function checkForm()
	{
    	document.getElementById('uploadForm').submit();
	}

	function loadFile(file)
	{
		document.getElementById('preview').src='?func=preview&file=<?=$vars['dir'];?>'+file+'&margin=1';
		document.getElementById('preview_caption').innerHTML=file;
	}

	function filesStretch()
	{
		var screenHeight;

		if( window.innerHeight ){
			screenHeight=window.innerHeight;
		}else if( document.body.offsetHeight ){
			screenHeight=document.body.offsetHeight
		}

		selectHeight=screenHeight-50;

		if( selectHeight>0 ){
			size=Math.floor(selectHeight/18);
		}else{
			size=1;
		}

		document.getElementById('filename').size=size;

		<? if( !$_GET['field'] ){ ?>
		selectHeight = screenHeight-200;
		<? } ?>

		document.getElementById('preview').height=selectHeight;
	}

	function fileKeys(e)
	{
		switch(e.keyCode){
			case 113:
				rename();
				return false
			break;
			case 46:
				delete_file();
				return false
			break;
		}
	}

	function init()
	{
		filesStretch();

        document.getElementById('filename').addEventListener('keypress', function(e){ return fileKeys(e) });

    	document.getElementById('filename').focus();
	}

    window.onload = init;
	window.onresize = filesStretch;
	</script>
</head>
<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" >
<table width="100%" border="0" cellpadding="0" cellspacing="0" height="100%">
<tr>
	<td width="100$" align="center" valign="top">
		<table width="100%">
		<tr>
		  <td colspan="2">
				<form id="uploadForm" method="post" enctype="multipart/form-data" style="display:inline">
    				<input type="hidden" name="MAX_FILE_SIZE" value="<?=$upload_config['max_file_size'];?>" />
    				<input type="file" id="files" name="files[]" onChange="checkForm()" size="40" multiple>
				</form>
				<form method="post" name="form" style="display:inline">
				<input type="hidden" id="action" name="action" value="select">
				<input type="hidden" id="new_name" name="new_name" value="">
				<select name="filename" id="filename" size="10" onChange="loadFile(this.options[this.selectedIndex].value)" onDblClick="onOK()" multiple style="width:100%">
					<?php
					foreach($files as $file){
						if( in_array($file,$vars['file']) ){
							$selected='selected';
						}else{
							$selected='';
						}
						print '<option value="'.$file.'" '.$selected.'>'.$file.'</option>';
					}
					?>
				</select>
				<br>

				<table width="100%">
				<tr>
					<td>
						<button type="button" onClick="onOK();" style="font-weight:bold;">OK</button>
					</td>
					<td align="right">
						<? if( $upload_config['edit_files'] AND $upload_config['type']=='dir' ){ ?>
							<button id="editButton" type="button" onClick="edit()">Edit</button>
						<? } ?>

						<button id="downloadButton"  type="button" onClick="download_file()">Download</button>
						<button id="renameButton"  type="button" onClick="rename()">Rename</button>
						<button id="deleteButton"  type="button" onClick="delete_file()">Delete</button>
					</td>
				</tr>
				</table>
				</form>
			</td>
		</tr>
		</table>
	</td>
	<td valign="top" bgcolor="#cccccc">
		<table width="100%" cellpadding="5" height="100%">
		<tr>
			<td align="center">
    			<img id="preview" src="?func=preview&file=<?=$vars['dir'];?><?=$vars['file'][0];?>&margin=1"><br>
    			<span id="preview_caption"><?=truncate($vars['file'][0],20);?></span>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>

</body>
</html>