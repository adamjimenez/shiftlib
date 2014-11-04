<?
if( $_GET['section'] ){
	$this->section=$_GET['section'];
}else{
	die('no section');
}

if( $_GET['choose_filter'] ){
	$qs=$_GET;
	unset($qs['page']);
	unset($qs['option']);
	unset($qs['section']);
	unset($qs['choose_filter']);

	foreach( $qs as $k=>$v ){
		if( $v==='' ){
			unset($qs[$k]);
		}
	}
?>
<script type="text/javascript">

window.opener.document.getElementById('filters_<?=underscored($_GET['section']);?>').value='<?=http_build_query($qs);?>';

window.close();
</script>
<?
}
?>

<form method="get">
<input type="hidden" name="option" value="choose_filter" />
<input type="hidden" name="choose_filter" value="1" />
<input type="hidden" name="section" value="<?=$_GET['section'];?>" />
<div id="advanced">
	<div style="padding:10px 0; margin:0 auto; text-align:center;">
		<fieldset>
		<legend>Create filter</legend>
		<table class="box" border="0" cellspacing="0" cellpadding="3">
		<?
		foreach( $vars['fields'][$this->section] as $name=>$type ){
			if( in_array($name,$vars["non_searchable"][$this->section]) ){
				continue;
			}

			$field_name=underscored($name);

			$label=ucfirst(spaced($name));
		?>
			<? if( $type == 'file' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<input type="checkbox" name="<?=$field_name;?>" value="1" <? if( $_GET[$field_name] ){ ?>checked<? } ?> />
				</td>
			</tr>
			<?
			}elseif( $type == 'select' or $type=='radio' ){
				$options=$vars['options'][$name];
				if( !is_array($vars['options'][$name]) ){
					$table=underscored($vars['options'][$name]);

					reset($vars['fields'][$vars['options'][$name]]);

					foreach( $vars['fields'][$vars['options'][$name]] as $k=>$v ){
						if( $v!='separator' ){
							$field=$k;
							break;
						}
					}

					$db_field_name=$this->db_field_name($vars['options'][$name],$field);

					$cols="".$db_field_name." AS `".underscored($field)."`"."\n";

					$rows = sql_query("SELECT id,$cols FROM $table ORDER BY `$db_field_name`");

					$options=array();
					foreach($rows as $row){
						$options[$row['id']]=$row[$field];
					}
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$name;?>">
					<option value=""></option>
					<?=html_options($options,$_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<?
			}elseif( $type == 'select-multiple' ){
				$value=array();
				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					$rows=sql_query("SELECT T1.value FROM cms_multiple_select T1
						INNER JOIN `".escape($vars['options'][$name])."` T2 ON T1.value=T2.$field_id
						WHERE
							field='".escape($name)."' AND
							item='".$id."'
					");

					$vars['options'][$name]=get_options($vars['options'][$name],key($vars['fields'][$vars['options'][$name]]));
				}else{
					$rows=sql_query("SELECT value FROM cms_multiple_select
						WHERE
							field='".escape($name)."' AND
							item='".$id."'
					");
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$field_name;?>[]" multiple="multiple" size="10" style="width:100%">
					<?=html_options($vars['options'][$name], $_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<?
			}elseif( $type == 'checkboxes' ){
				$value=array();
				if( !is_array($vars['options'][$name]) and $vars['options'][$name]  ){
					$rows=sql_query("SELECT T1.value FROM cms_multiple_select T1
						INNER JOIN `".escape($vars['options'][$name])."` T2 ON T1.value=T2.$field_id
						WHERE
							field='".escape($name)."' AND
							item='".$id."'
					");

					$vars['options'][$name]=get_options($vars['options'][$name],key($vars['fields'][$vars['options'][$name]]));
				}else{
					$rows=sql_query("SELECT value FROM cms_multiple_select
						WHERE
							field='".escape($name)."' AND
							item='".$id."'
					");
				}
			?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<? if( is_assoc_array($vars['options'][$name]) ){ ?>
						<? foreach( $vars['options'][$name] as  $k=>$v ){ ?>
						<label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$k;?>" <? if( in_array($k,$_GET[$field_name]) ){ ?>checked="checked"<? } ?> /> <?=$v;?></label><br />
						<? } ?>
					<? }else{ ?>
						<? foreach( $vars['options'][$name] as  $k=>$v ){ ?>
						<label><input type="checkbox" name="<?=$field_name;?>[]" value="<?=$v;?>" <? if( in_array($v,$_GET[$field_name]) ){ ?>checked="checked"<? } ?> /> <?=$v;?></label><br />
						<? } ?>
					<? } ?>
				</td>
			</tr>
			<? }elseif( $type == 'checkbox' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<select name="<?=$field_name;?>">
					<option value=""></option>
					<?=html_options(array(1=>'yes',0=>'no'),$_GET[$field_name]);?>
					</select>
				</td>
			</tr>
			<? }elseif( $type == 'date' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<div style="float:left">
						<select name="func[<?=$field_name;?>]">
						<option value=""></option>
						<?=html_options(array('='=>'On','>'=>'After','<'=>'Before'),$_GET['func'][$field_name]);?>
						</select>
					</div>
					<div style="float:left">
						<input type="text" id="<?=$name;?>" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" class="date" />
					</div>
				</td>
			</tr>
			<? }elseif( $type == 'postcode' ){ ?>
			<tr>
				<th align="left" valign="top">Distance from <?=$label;?></th>
				<td>
					Within
					<select name="func[<?=$field_name;?>]">
					<option value=""></option>
						<?=html_options($opts['distance'],$_GET['func'][$field_name]);?>
					</select>
					of
					<input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="7">
				</td>
			</tr>
			<? }elseif( $type == 'int' ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td>
					<div style="float:left">
						<select name="func[<?=$field_name;?>]">
						<option value=""></option>
						<?=html_options(array('='=>'=','>'=>'>','<'=>'<'),$_GET['func'][$field_name]);?>
						</select>
					</div>
					<div style="float:left">
						<input type="text" id="<?=$name;?>" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="8" />
					</div>
				</td>
			</tr>
			<? }elseif( $type == 'text' or $type == 'email' or $type == 'mobile' or ($type=='id' and $vars["settings"][$this->section]['show_id']) ){ ?>
			<tr>
				<th align="left" valign="top"><?=$label;?></th>
				<td><input type="text" name="<?=$field_name;?>" value="<?=$_GET[$field_name];?>" size="50"></td>
			</tr>
			<? } ?>
		<? } ?>
		</table>
		<br />
		<p align="center"><button type="submit">Create Filter</button> &nbsp; <button type="button" onclick="window.close();">Cancel</button></p>
		</fieldset>
		<br />
		<br />
	</div>
</div>


<?
exit;
?>