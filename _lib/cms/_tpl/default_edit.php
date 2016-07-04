<?
//check permissions
if( $auth->user['admin']!=1 and !$auth->user['privileges'][$this->section] ){
    die('access denied');
}

$this->set_section($this->section, $_GET['id']);
$this->trigger_event('beforeEdit', array($this->id));

//return url
$section='';
foreach( $vars['fields'][$this->section] as $name=>$type ){
	if( $_GET[$name] and $name!='id' ){
		$section=$name;
		break;
	}
}

if( $this->id and $section and in_array('id',$vars['fields'][$this->section]) ){
	$cancel_url='?option='.$this->section.'&view=true&id='.$this->id.'&'.$section.'='.$this->content[$section];
}elseif( $section and in_array('id',$vars['fields'][$this->section]) ){
	$cancel_url='?option='.$vars['options'][$section].'&view=true&id='.$this->content[$section];
}elseif( $this->id ){
	$cancel_url='?option='.$this->section.'&view=true&id='.$this->id;
}elseif( in_array('id',$vars['fields'][$this->section]) ){
	$cancel_url='?option='.$this->section;
}

if( count($languages) ){
	$languages=array_merge(array('en'),$languages);
}else{
	$languages=array('en');
}

if( isset($_POST['save']) ){
	$errors=$this->validate();

	if( count( $errors ) ){
		print json_encode($errors);
		exit;
	}elseif( $_POST['validate'] ){
		print 1;
		exit;
	}else{
		if( $auth->user['admin']==1 or $auth->user['privileges'][$this->section]==2 ){
			$id=$this->save();

			if( $_POST['add_another'] ){
				$qs=http_build_query($_GET);

				redirect('?'.$qs.'&add_another=1');
			}else{
				if( $section and in_array('id',$vars['fields'][$this->section]) ){
					$return_url='?option='.$this->section.'&view=true&id='.$id.'&'.$section.'='.$this->content[$section];
				}elseif( $this->id ){
					$return_url='?option='.$this->section.'&view=true&id='.$id;
				}elseif( in_array('id',$vars['fields'][$this->section]) ){
					$return_url='?option='.$this->section;
				}

				$_SESSION['message']='The item has been saved';
				redirect($return_url);
			}
			exit;
		}else{
			die('Permission denied, you have read-only access. <a href="?option='.$this->section.'&view=true&id='.$this->id.'">continue</a>');
		}
	}
}

//label
$label = $this->get_label();

$title=ucfirst($this->section).' | '.($label ? $label : '&lt;blank&gt; | Edit');

//increment value
if( $_GET["id"] ){
    $id = $_GET["id"];
}else{
    $row = sql_query("SHOW TABLE STATUS LIKE '".underscored($this->section)."'", 1);
    $id = $row['Auto_increment'];
}
?>

<script type="text/javascript">
<?
if($this->components){
?>
var components = <?=json_encode($this->components);?>;
<?
}
?>

$(function() {
    if( document.getElementById('language') ){
		$('#language').on('change',set_language);
	}

	init_tabs();

    $('form input:visible:first').focus();

    var phpupload_default_dir = '<?=$_GET["option"];?>/<?=$id;?>';
});
</script>

<form id="form" method="post" enctype="multipart/form-data" class="validate">
<!--<input type="hidden" name="UPLOAD_IDENTIFIER" value="<?=$uniq;?>"/>-->
<input type="hidden" name="save" value="1">

<!-- fake fields are a workaround for chrome autofill -->
<input style="display:none" type="text" name="fakeusernameremembered">
<input style="display:none" type="password" name="fakepasswordremembered">

<table width="100%">
<tr>
	<td>
    <div class="top-row">
			<button type="submit" class="btn btn-success">Save</button>
			<button type="button" class="btn btn-danger" onclick="window.location.href='<?=$cancel_url;?>';">Cancel</button>
		</div>
	</td>
</tr>
</table>

<?
if( in_array('language',$vars['fields'][$this->section]) ){
?>
<p>
Language:
<select id="language" name="language">
	<?=html_options($languages);?>
</select>
</p>
<?
}else{
	$languages=array('en');
}

foreach( $languages as $language ){
	$this->language=$language;
?>
	<div id="language_<?=$language;?>" <? if($language!='en'){ ?>style="display:none;"<? } ?> class="box">

		<?
		if( in_array('separator',$vars['fields'][$this->section]) ){
		?>
		<ul class="tabs">
		<?
			foreach( $vars['fields'][$this->section] as $name=>$type ){
				if( $type=='separator' ){
				?>
				<li><a href="javascript:;" target="section_<?=$name;?>" class="tab" onclick="return false;"><?=ucfirst($name);?></a></li>
				<?
				}
			}
		?>
		</ul>
		<?
		}
		?>

		<table border="1" cellspacing="0" cellpadding="5" width="100%">
		<?
		foreach( $vars['fields'][$this->section] as $name=>$type ){
			if( in_array($type,array('id','ip','position','timestamp','language','translated-from','hidden','deleted')) ){
				continue;
			}

			$label = $vars['label'][$this->section][$name];

			if(!$label) {
				$label = ucfirst(spaced($name));
			}

			if( $type=='select-multiple' ){
				$value=$id;
			}

			if( $type=='separator' ){
			?>
			</table>

			<? if( $separator_open ){ ?>
			</div>
			<? } ?>
			<div style="display:none;" id="section_<?=$name;?>">
			<table border="1" cellspacing="0" cellpadding="5" width="100%">
			<?
				$separator_open=true;
				continue;
			}
			?>

		<tr>
			<th align="left" valign="top"><?=$label;?></th>
			<td>
				<?
				if( is_array($type) ){
					foreach( $type as $k=>$v ){
				?>
					<?=$k;?><br />
					<? $this->get_field($k);?><br />
				<?
					}
				}else{
				?>
					<? $this->get_field($name);?>
				<? } ?>
			</td>
		</tr>
		<? } ?>
		</table>
		<? if( $separator_open ){ ?>
		</div>
		<? } ?>
	</div>
<?
}
?>

<? if( !$_GET['id'] and in_array('id',$vars['fields'][$this->section]) ){ ?>
<p><label><input type="checkbox" name="add_another" value="1" <? if( $_GET['add_another'] ){ ?>checked="checked"<? } ?> /> add another?</label></p>
<br />
<? } ?>

<table width="100%">
<tr>
	<td>
    <button type="submit" class="btn btn-success">Save</button>
  	<button type="button" class="btn btn-danger" onclick="window.location.href='<?=$cancel_url;?>';">Cancel</button>
	</td>
</tr>
</table>

</form>
