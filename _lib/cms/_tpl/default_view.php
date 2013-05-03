<?
//check permissions
if( $auth->user['admin']!=1 and !$auth->user['privileges'][$this->section] ){
    die('access denied');
}

$this->set_section($this->section,$_GET['id']);

$content = $this->get($this->section,$_GET['id']);

//get id if it exists
$id = $content['id'];

//print_r($content);

if( isset($_POST['custom_button']) ){
    $cms_buttons[$_POST['custom_button']]['handler']($_GET['id']);

	$content = $this->get($this->section,$_GET['id']);
}

//subsections delete
if( $_POST['action']=='delete' ){
	$this->delete_items($_POST['section'],$_POST['items']);
}

//subsections delete all
if( $_POST['select_all_pages'] and $_POST['section'] and $_POST['action']=='delete' ){
	$this->delete_all_pages($_POST['section']); //FIXME - should only delete related items
}

//save privileges
if(
	$auth->user['admin']==1 and
	underscored($this->section)==$auth->table and
	( $content['admin']==2 or $content['admin']==3 ) and
	$_POST['privileges']
){
	mysql_query("DELETE FROM cms_privileges WHERE user='".$this->id."'") or trigger_error("SQL", E_USER_ERROR);

	foreach( $_POST['privileges'] as $k=>$v ){
		mysql_query("INSERT INTO cms_privileges SET
			user='".escape($this->id)."',
			section='".escape($k)."',
			access='".escape($v)."',
			filter='".escape($_POST['filters'][$k])."'
		") or trigger_error("SQL", E_USER_ERROR);
	}
}

if( $languages ){
	$languages = array_merge(array('en'),$languages);
}else{
	$languages=array('en');
}

if( $_POST['delete'] and $this->id ){
	$this->delete_items($this->section,$this->id);

	redirect('?option='.$this->section);
}

if( $_POST['sms'] ){
	$users[]=$content;

	require(dirname(__FILE__).'/sms.php');
}else{
	//label
	$label=$this->get_label();

	$title = ucfirst($this->section).' | '.($label ? $label : '&lt;blank&gt;');

    // previous / next links
    if( isset($content['position']) ){
        $conditions = $_GET;
        unset($conditions['id']);

        $qs = http_build_query($conditions);

        $conditions['position'] = $content['position'];
        $conditions['func']['position'] = '<';

        $prev = $this->get($this->section, $conditions, 1, null, false);

        $conditions['func']['position'] = '>';

        $next = $this->get($this->section, $conditions, 1);

        //var_dump($prev); exit;

        if( $prev ){
            $prev_link = '?id='.$prev['id'].'&'.$qs;
        }

        if( $next ){
            $next_link = '?id='.$next['id'].'&'.$qs;
        }
    }
?>


<script type="text/javascript">
function init()
{
	if( jQuery('#language') ){
		jQuery('#language').on('change',set_language);
	}

	init_tabs();
}

function set_language()
{
	var option=document.getElementById('language');

	for( j=0; j<option.options.length; j++ ){;
		if( document.getElementById('language_'+option.options[j].value).style.display!='none' ){
			document.getElementById('language_'+option.options[j].value).style.display='none';
		}
	}

	document.getElementById('language_'+jQuery('#language').val()).style.display='block';
}

function choose_filter(field)
{
	window.open('/admin?option=choose_filter&section='+field,'Insert','width=700,height=450,screenX=100,screenY=100,left=100,top=100,status,dependent,alwaysRaised,resizable,scrollbars')
}

window.onload=init;
</script>

<table width="100%">
<tr>
    <td>
		<?
		$qs=array();
		foreach( $vars['fields'][$this->section] as $k=>$v ){
			if( ($v=='select' or $v=='radio') and $_GET[$k] ){
				$qs[$k]=$_GET[$k];
				break;
			}
		}
		$qs = http_build_query($qs);

		$section='';
		foreach( $vars['fields'][$this->section] as $name=>$type ){
			if( $_GET[$name] and $name!='id' and $type=='select' ){
				$section=$name;
				break;
			}
		}
		?>

		<? if( $section and in_array('id',$vars['fields'][$this->section]) ){ ?>
		<a href="?option=<?=$vars['options'][$section];?>&view=true&id=<?=$content[$section];?>">&laquo; Back to <?=ucfirst($section);?></a>
		&nbsp;
		<? }elseif( in_array('id',$vars['fields'][$this->section]) ){ ?>
		<a href="?option=<?=$this->section;?>">&laquo; Back to <?=ucfirst($this->section);?></a>
		&nbsp;
		<? } ?>

        <button type="button" onclick="location.href='?option=<?=$this->section;?>&edit=true&id=<?=$id;?>&<?=$qs;?>'" style="font-weight:bold;">Edit</button>


		<? if( $vars['settings'][$this->section]['sms'] and is_numeric($content['mobile']) ){ ?>
		<form method="post" style="display:inline">
		<input type="hidden" name="sms" value="1">
			<button type="submit">Send SMS text</button>
		</form>
		<? } ?>

		<?
		foreach( $cms_buttons as $k=>$button ){
			if( $this->section==$button['section'] and $button['page']=='view' ){
    		    if( $button['submit']===false ){
            	?>
        		<button type="button" onclick="<?=$button['handler'];?>"><?=$button['label'];?></button>
        		<?
    		    }else{
        		?>
        		<form method="post" style="display:inline">
        		<input type="hidden" name="custom_button" value="<?=$k;?>">
        			<button type="submit"><?=$button['label'];?></button>
        		</form>
        		<?
			    }
			}
		}
		?>

		&nbsp;&nbsp;&nbsp;

        <form method="post" style="display:inline;">
        <input type="hidden" name="delete" value="1">
        <button type="submit" onclick="return confirm('are you sure you want to delete?');">Delete</button>
        </form>
	</td>
    <td style="text-align: right;">
        <? if( $prev_link ){ ?>
        <a href="<?=$prev_link;?>">Prev</a>
        <? } ?>
        <? if( $prev_link and $next_link ){ ?>
        |
        <? } ?>
        <? if( $next_link ){ ?>
        <a href="<?=$next_link;?>">Next</a>
        <? } ?>
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
}
?>

<?
foreach( $languages as $language ){
	if( $language!=='en' and in_array('language', $vars['fields'][$this->section]) ){
		$this->language = $language;
		$content = $this->get($this->section,$_GET['id']);
	}
?>
	<div id="language_<?=$language;?>" <? if($language!='en'){ ?>style="display:none;"<? } ?> class="box">
	<table border="0" cellspacing="0" cellpadding="5" width="100%">
	<?
	foreach( $vars['fields'][$this->section] as $name=>$type ){
		$label=ucfirst(str_replace('_',' ',$name));

		$value=$content[$name];
		$name=$name;

		if( $type=='select-multiple' or $type=='checkboxes' ){
			if( !is_array($vars['options'][$name]) and $vars['options'][$name] ){
				$join_id=array_search('id',$vars['fields'][$vars['options'][$name]]);

				if( !$join_id ){
					 trigger_error("No Join id", E_USER_ERROR);
				}

				$rows=sql_query("SELECT `".underscored(key($vars['fields'][$vars['options'][$name]]))."`,T1.value FROM cms_multiple_select T1
					INNER JOIN `".escape(underscored($vars['options'][$name]))."` T2 ON T1.value = T2.$join_id
					WHERE
						T1.field='".escape($name)."' AND
						T1.item='".$id."' AND
						T1.section='".$this->section."'
					GROUP BY T1.value
					ORDER BY T2.".underscored(key($vars['fields'][$vars['options'][$name]]))."
				");

				$value='';
				foreach( $rows as $row ){
					$value.='<a href="?option='.escape($vars['options'][$name]).'&view=true&id='.$row['value'].'">'.current($row).'</a><br>'."\n";
				}
			}else{
				$rows=sql_query("SELECT value FROM cms_multiple_select
					WHERE
						field='".escape($name)."' AND
						item='".$id."'
					ORDER BY id
				");

				$value='';
				foreach( $rows as $row ){
					if( is_assoc_array($vars['options'][$name]) ){
						$value.=$vars['options'][$name][$row['value']].'<br>'."\n";
					}else{
						$value.=current($row).'<br>'."\n";
					}
				}
			}
		}

		if( (!$value or $value=='0000-00-00' or $value=='00:00:00' or $type=='password') and $type != 'separator' and !is_array($type) ){
			continue;
		}

		if( in_array($type,array('position','language','translated-from')) ){
			continue;
		}

		if( $type=='id' and !$vars["settings"][$this->section]['show_id'] ){
			continue;
		}

		if( $type=='separator' ){
		?>
		<tr>
			<td colspan="2">
				<br />
				<h2><?=ucfirst($name);?></h2>
			</td>
		</tr>
		<?
		}else{
		?>

		<tr>
			<th align="left" valign="top" width="20%"><?=$label;?></th>
			<td>
		<?
		if( in_array($type,array('text','float','decimal','int','id','page-name','hidden')) ){
			if( is_numeric($value) and $value==0 ){
				continue;
			}
		?>
			<?=$value;?>
		<? }elseif( is_array($type) ){ ?>
			<?=$value;?>
		<? }elseif( $type == 'mobile' || $type == 'tel' ){ ?>
			<?=$value;?>
		<? }elseif( $type == 'url' ){ ?>
			<a href="<?=$value;?>" target="_blank"><?=$value;?></a>
		<? }elseif( $type == 'email' ){ ?>
			<a href="mailto:<?=$value;?>" target="_blank"><?=$value;?></a>
		<? }elseif( $type == 'postcode' ){ ?>
			<?=$value;?>
			<a href="http://maps.google.co.uk/maps?f=q&source=s_q&hl=en&geocode=&q=<?=$value;?>" target="_blank">(view map)</a>
		<? }elseif( $type == 'coords' ){ ?>
			<?=$value;?>
		<? }elseif( $type == 'textarea' ){ ?>
			<?=nl2br(strip_tags($value));?>
		<? }elseif( $type == 'editor' ){ ?>
			<?=$value;?>
		<?
		}elseif( $type == 'file' ){
			if( $value ){
				$file=sql_query("SELECT * FROM files WHERE id='".escape($value)."'");
			}
		?>
				<?
				$image_types=array('jpg','jpeg','gif','png');
				if( in_array(file_ext($file[0]['name']),$image_types) ){
				?>
				<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
				<? } ?>
				<a href="/_lib/cms/file.php?f=<?=$file[0]['id'];?>"><?=$file[0]['name'];?></a> <span style="font-size:9px;"><?=file_size($file[0]['size']);?></span>

				<?
				$doc_types=array('pdf','doc','docx','xls','tiff');
				if( in_array(file_ext($file[0]['name']),$doc_types) ){
				?>
				<a href="http://docs.google.com/viewer?url=<?=rawurlencode('http://'.$_SERVER['HTTP_HOST'].'/_lib/cms/file.php?f='.$file[0]['id'].'&auth_user='.$_SESSION[$auth->cookie_prefix.'_email'].'&auth_pw='.md5($auth->secret_phrase.$_SESSION[$auth->cookie_prefix.'_password']));?>" target="_blank">(view)</a>
				<? } ?>
		<? }elseif( $type == 'phpupload' ){ ?>
            <a href="/uploads/<?=$value;?>" rel="lightbox">
			    <img src="_lib/modules/phpupload/?func=preview&file=<?=$value;?>&w=320&h=240" id="<?=$name;?>_thumb" />
            </a>
            <br />
			<label id="<?=$name;?>_label"><?=$value;?></label>
		<?
		}elseif( $type == 'select' or $type == 'combo' or $type == 'radio' or $type == 'select-distance' ){

			if( !is_array($vars['options'][$name]) ){
				if( $value=='0' ){
					$value='';
				}else{
					$value='<a href="?option='.escape($vars['options'][$name]).'&view=true&id='.$value.'">'.$content[underscored($name).'_label'].'</a>';
				}
			}else{
				if( is_assoc_array($vars['options'][$name]) ){
					$value=$vars['options'][$name][$value];
				}
			}
		?>
			<?=$value;?>
		<?
		}elseif( $type == 'parent' ){
			reset($vars['fields'][$this->section]);

			$field=key($vars['fields'][$this->section]);

			$row=sql_query("SELECT id,`$field` FROM `".$this->table."` WHERE id='".escape($value)."' ORDER BY `$label`");

			$value='<a href="?option='.escape($this->section).'&view=true&id='.$value.'">'.($row[0][$field]).'</a>';

		?>
			<?=$value;?>
		<?
		}elseif( $type == 'select-multiple' or $type=='checkboxes' ){
		?>
			<?=$value;?>
		<? }elseif( $type == 'checkbox' ){ ?>
			<?=$value ? 'Yes' : 'No' ;?>
		<? }elseif( $type == 'files' ){

		if( $value ){
			$value=explode("\n",$value);
		}
		?>
				<ul id="<?=$name;?>_files" class="files">
				<?
				$count=0;

				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$count++;

						if( $value ){
							$file=sql_query("SELECT * FROM files WHERE id='".escape($val)."'");
						}
				?>
				<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=320&h=240" id="<?=$name;?>_thumb" /><br />
				<a href="/_lib/cms/file.php?f=<?=$file[0]['id'];?>"><?=$file[0]['name'];?></a><br />
				<br />
				<?
					}
				}
				?>
				</ul>
		<? }elseif( $type == 'phpuploads' ){

		if( $value ){
			$value=explode("\n",$value);
		}
		?>
				<ul id="<?=$name;?>_files">
				<?
				$count=0;

				if( is_array($value) ){
					foreach( $value as $key=>$val ){
						$count++;
				?>
				<li id="item_<?=$name;?>_<?=$count;?>">
                    <a href="/uploads/<?=$val;?>" rel="lightbox">
					    <img src="_lib/modules/phpupload/?func=preview&file=<?=$val;?>" id="file_<?=$name;?>_<?=$count;?>_thumb" /><br />
                    </a>
					<label id="file_<?=$name;?>_<?=$count;?>_label"><?=$val;?></label>
				</li>
				<?
					}
				}
				?>
				</ul>
		<?
		}elseif( $type == 'date' ){
			if( $value!='0000-00-00' and $value!='' ){
				$value=dateformat('d/m/Y',$value);
			}
		?>
			<?=$value;?>
		<?
		}elseif( $type=='dob' ){
			if( $value!='0000-00-00' and $value!='' ){
				$age=age($value);
				$value=dateformat('d/m/Y',$value);
			}
		?>
			<?=$value;?> (<?=$age;?>)
		<?
		}elseif( $type == 'time' ){
		?>
			<?=$value;?>
		<?
		}elseif( $type == 'datetime' ){
			if( $value!='0000-00-00 00:00:00' ){
				$date=explode(' ',$value);
				$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
			}
		?>
			<?=$value;?>
		<?
		}elseif( $type == 'timestamp' ){
			if( $value!='0000-00-00 00:00:00' ){
				$date=explode(' ',$value);
				$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
			}
		?>
			<?=$value;?>
		<? }elseif( $type == 'rating' ){
			$opts['rating']=array(
					1=>'Very Poor',
					2=>'Poor',
					3=>'Average',
					4=>'Good',
					5=>'Excellent',
				);
		?>
			<select name="<?=$field_name;?>" class="rating" disabled="disabled">
				<?=html_options($opts['rating'],$value);?>
			</select>
		<? }elseif( $type == 'number' ){ ?>
			<?=number_format($value,2);?>
		<? } ?>

			</td>
		</tr>
		<? } ?>
	<? } ?>
	</table>
	</div>
<?
}
?>


<?
//logs
if( table_exists('cms_logs') ){
	if( $_GET['option']=='users' ){
		$logs=sql_query("SELECT *,L.date FROM cms_logs L
			INNER JOIN users U ON L.user=U.id
			WHERE
				user='".escape($_GET['id'])."'
			ORDER BY L.date DESC
			LIMIT 5
		");
	}else{
		$logs=sql_query("SELECT *,L.date FROM cms_logs L
			INNER JOIN users U ON L.user=U.id
			WHERE
				section='".escape($this->section)."' AND
				item='".escape($_GET['id'])."'
			ORDER BY L.date DESC
			LIMIT 5
		");
	}

	if( count($logs) ){
?>
<table border="1" cellspacing="0" cellpadding="5" class="box">
<tr>
	<th>Logs</th>
</tr>
<?
	foreach( $logs as $k=>$v ){
		switch( $v['task'] ){
			case 'edit':
				$action='edited';
			break;
			case 'delete':
				$action='deleted';
			break;
			case 'add':
				$action='created';
			break;
		}

		if( $_GET['option']=='users' ){
			$item_table=underscored($v['section']);
			$item=sql_query("SELECT * FROM `$item_table` WHERE id='".escape($v['item'])."'");

			$label=$vars['labels'][$v['section']][0];

			$item_name=$item[0][$label];
		}

		$name=$v['name'] ? $v['name'].' '.$v['surname'] : $v['email'];
?>
<tr>
	<td>
		<a href="?option=<?=$v['section'];?>&view=true&id=<?=$v['item'];?>"><?=$item_name;?></a> <?=$action;?> by <a href="?option=users&view=true&id=<?=$v['user'];?>"><?=$name;?></a> on <?=dateformat('d-m-Y H:i:s',$v['date']);?>
	</td>
</tr>
<? 	} ?>
</table>
<br />
<br />
<?
	}
}
?>

<?
//privileges
if(
	$auth->user['admin']==1 and
	underscored($this->section)==$auth->table and
	( $content['admin']==2 or $content['admin']==3 )
 ){
	$cms_privileges_fields=array(
		'user'=>'int',
		'section'=>'text',
		'access'=>'int',
		'filter'=>'text',
		'id'=>'id',
	);

 	check_table('cms_privileges',$cms_privileges_fields);

	$select_priveleges=mysql_query("SELECT * FROM cms_privileges WHERE user='".$this->id."'") or trigger_error("SQL", E_USER_ERROR);
	while( $row=mysql_fetch_array($select_priveleges) ){
		$privileges[$row['section']]=$row;
	}
?>
<h2>Privileges</h2>
<form method="post">
<table class="box">
<tr>
	<th>Section</th>
	<th>Access</th>
	<th>Filter</th>
</tr>
<?
	foreach( $vars["fields"] as $section=>$fields ){
?>
<tr>
	<td><?=$section;?></td>
	<td>
		<select name="privileges[<?=$section;?>]">
			<option value=""></option>
			<?=html_options(array(1=>'Read',2=>'Write'),$privileges[$section]['access']);?>
		</select>
	</td>
	<td>
		<input type="text" id="filters_<?=underscored($section);?>" name="filters[<?=$section;?>]" value="<?=$privileges[$section]['filter'];?>" />
		<button type="button" onclick="choose_filter('<?=$section;?>');">Choose Filter</button>
	</td>
</tr>
<?
	}
?>
<tr>
	<td>Email Templates</td>
	<td>
		<select name="privileges[email_templates]">
			<option value=""></option>
			<?=html_options(array(1=>'Read',2=>'Write'),$privileges['uploads']['access']);?>
		</select>
	</td>
</tr>
<tr>
	<td>Uploads</td>
	<td>
		<select name="privileges[uploads]">
			<option value=""></option>
			<?=html_options(array(1=>'Read',2=>'Write'),$privileges['uploads']['access']);?>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2" align="right">
		<button type="submit">Save</button>
	</td>
</tr>
</table>
</form>
<br />
<?
}
?>


<?
if( is_array($vars['subsections'][$this->section]) ){
?>
	<br>
	<ul class="tabs">
<?
	foreach( $vars['subsections'][$this->section] as $count=>$subsection ){
?>
	<li><a href="javascript:;" target="subsection_<?=$count;?>" class="tab" onclick="return false;"><?=ucfirst($subsection);?></a></li>
<?
	}
?>
	</ul>
<?
	foreach( $vars['subsections'][$this->section] as $count=>$subsection ){
		$this->section=$subsection;

		$table=underscored($this->section);

		if( !count($vars['labels'][$this->section]) ){
			reset($vars['fields'][$this->section]);
			$vars['labels'][$this->section][]=key($vars['fields'][$this->section]);
		}

		if( in_array('position',$vars['fields'][$this->section]) ){
			$order='position';
			$asc=true;
			$limit=NULL;
		}else{
			$label=$vars['labels'][$this->section][0];

			$type=array_search($label,$vars['fields'][$this->section]);
			if( ($typw=='select' or $typw=='combo') and !is_array($vars['opts'][$label]) ){
				$order='T_'.$label.'.'.underscored(key($vars['fields'][$vars['options'][$label]]));
			}else{
				$order="T_$table.".underscored($vars['labels'][$this->section][0]);
			}
			$limit=10;
		}



		$conditions=array();
		$qs=array();

		foreach( $vars['fields'][$this->section] as $k=>$v ){
			if( ($v=='select' or $v=='combo' or $v=='radio') and $vars['options'][$k]==$_GET['option'] ){
				$conditions[$k]=escape($this->id);

				$qs[$k]=$this->id;

				break;
			}
		}

		$qs=http_build_query($qs);

		$vars['content']=$this->get($subsection,$conditions,NULL,$order,$asc,$table);
		$p=$this->p;
?>

<div style="display:none;" id="subsection_<?=$count;?>">
	<? require(dirname(__FILE__).'/list.php'); ?>
</div>


<?
	}
}

}
?>