<?
if( !count($vars['labels'][$this->section]) ){
    reset($vars['fields'][$this->section]);
	$vars['labels'][$this->section][]=key($vars['fields'][$this->section]);
}

$sortable=in_array('position',$vars['fields'][$this->section]);

if( in_array('parent',$vars['fields'][$this->section]) ){
	$parent_field=array_search('parent',$vars['fields'][$this->section]);
}
?>

<script>
function delete_selected(){
	var result=confirm('Are you sure you want to delete?');

	if( result ){
		jQuery('.action').val('delete');
		return true;
	}else{
		jQuery('.action').val('');
		return false;
	}
}

function email_selected(){
	document.getElementById('action').value='email';
	return true;
}

function export_selected(){
	document.getElementById('action').value='export';
	return true;
}
</script>

<div class="box" style="clear:both;">
<form method="post" id="cms_list_<?=underscored($this->section);?>" class="cms_list">
<input type="hidden" class="section" name="section" value="<?=underscored($this->section);?>">
<input type="hidden" class="action" name="action" id="action" value="">
<input type="hidden" class="select_all_pages" name="select_all_pages" value="0">
<?
if( $sortable ){
?>
<input type="hidden" class="sortable" name="sortable" value="1">
<?
}
?>

<?
foreach( $where as $k=>$v ){
?>
<input type="hidden" name="<?=$k;?>" value="<?=$v;?>">
<?
}
?>

<? require(dirname(__FILE__).'/includes/list_actions.php'); ?>

<div style="min-height:300px; background:#fff;">
<table width="100%" cellspacing="0" cellpadding="5">
<tbody id="items_<?=underscored($this->section);?>">
<tr>
	<? if( $sortable ){ ?>
		<th width="16" style="text-align:center;">
			<img src="/_lib/images/sort_up.gif" vspace="5" />
			<img src="/_lib/images/sort_down.gif" vspace="5" />
		</th>
	<? } ?>
	<th width="20" valign="top" align="center" style="text-align:center;">
		<input type="checkbox" class="toggle_select">
	</th>
	<?
	foreach( $vars['labels'][$this->section] as $k ){
		$order=underscored('T_'.underscored($this->section).'.'.$k);
		if( $vars['fields'][$this->section][$k]=='select' or $vars['fields'][$this->section][$k]=='combo' or $vars['fields'][$this->section][$k]=='radio' ){
			if( !is_array($vars['options'][$k]) ){
				$option=key($vars['fields'][$vars['options'][$k]]);

				$order='T_'.underscored($k).'.'.underscored($option);
			}
		}
	?>
		<th>
			<?=($sortable) ? ucfirst($k) : $p->col($order,ucfirst($k));?>
		</th>
	<?
	}
	?>

	<? if( $parent_field ){ ?>
		<th>&nbsp;</th>
	<? } ?>
</tr>
<tr class="select_all_row" style="display:none;">
	<td align="center" colspan="20" bgcolor="#FFFF00" style="text-align:center; background:#FFFF00;">
		<a href="javascript:;" class="select_all_pages">Select all <?=$p->total;?> items</a>
	</td>
</tr>
<tr class="clear_all_row" style="display:none;">
	<td align="center" colspan="20" bgcolor="#FFFF00" style="text-align:center; background:#FFFF00;">
		<a href="javascript:;" class="clear_all_pages"><?=$p->total;?> items selected. Clear selection</a>
	</td>
</tr>
<?
if( count($vars['content']) ){
?>
<?
foreach( $vars['content'] as $v){
?>
<tr class="draggable row" id="tr_<?=$v['id'];?>">
	<? if( $sortable ){ ?>
		<td valign="top"><div class="handle">&nbsp;</div></td>
	<? } ?>
	<td width="20" valign="top" align="center" style="text-align:center;"><input type="checkbox" name="items[]" value="<?=$v['id'];?>"></td>
	<?
	if( is_array($vars['labels'][$this->section]) ){
		foreach( $vars['labels'][$this->section] as $i=>$k ){
			$value=$v[$k];

			if( $vars['fields'][$this->section][$k] == 'select-multiple' or $vars['fields'][$this->section][$k]=='checkboxes' ){
				if( !is_array($vars['options'][$k]) and $vars['options'][$k] ){
					$join_id=array_search('id',$vars['fields'][$vars['options'][$k]]);

					$rows=sql_query("SELECT `".key($vars['fields'][$vars['options'][$k]])."`,T1.value FROM cms_multiple_select T1
						INNER JOIN `".escape(underscored($vars['options'][$k]))."` T2 ON T1.value = T2.$join_id
						WHERE
							T1.section='".escape($this->section)."' AND
							T1.field='".escape($k)."' AND
							T1.item='".escape($v['id'])."'
						GROUP BY T1.value
						ORDER BY T2.".key($vars['fields'][$vars['options'][$k]])."
					");

					$value='';
					foreach( $rows as $row ){
						$value.=current($row).'<br>'."\n";
					}
				}else{
					$rows=sql_query("SELECT value FROM cms_multiple_select
						WHERE
							section='".escape($this->section)."' AND
							field='".escape($k)."' AND
							item='".$v['id']."'
						ORDER BY id
					");

					$value='';
					foreach( $rows as $row ){
						if( is_assoc_array($vars['options'][$k]) ){
							$value.=$vars['options'][$k][$row['value']].'<br>'."\n";
						}else{
							$value.=current($row).'<br>'."\n";
						}
					}
				}
			}
	?>
		<td valign="top" <? if( $i==0 ){ ?>style="white-space:nowrap;"<? } ?>>
			<? if( $i==0 ){ ?>
			<a href="?option=<?=$this->section;?>&view=true&id=<?=$v['id'];?>&<?=$qs;?>">
			<? } ?>
			<?
			$value=trim($value);

			if( $value ){
				if( $vars['fields'][$this->section][$k]=='date' ){
				?>
					<? if( $value!='0000-00-00' ){ ?>
						<?=dateformat('d/m/Y',$value);?>
					<? }else{ ?>
						&lt;blank&gt;
					<? } ?>
				<?
				}elseif( $vars['fields'][$this->section][$k] == 'datetime' ){
					if( $value!='0000-00-00 00:00:00' ){
						$date=explode(' ',$value);
						$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
					}
				?>
					<?=$value;?>
				<?
				}elseif( $vars['fields'][$this->section][$k] == 'timestamp' ){
					if( $value!='0000-00-00 00:00:00' ){
						$date=explode(' ',$value);
						$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
					}
				?>
					<?=$value;?>
				<?
				}elseif( $vars['fields'][$this->section][$k] == 'dob' ){
				?>
					<? if( $value!='0000-00-00' ){ ?>
						<?=dateformat('d/m/Y',$value);?> (<?=age($value);?>)
					<? }else{ ?>
						&lt;blank&gt;
					<? } ?>
				<?
				}elseif( $vars['fields'][$this->section][$k]=='file' ){
					if( $value ){
						$file=sql_query("SELECT * FROM files WHERE id='".escape($value)."'");
					}
				?>
					<? if( $value ){ ?>

					<?
						$image_types=array('jpg','jpeg','gif','png');
						if( in_array(file_ext($file[0]['name']),$image_types) ){
					?>
						<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=100&h=100" /><br />
					<?
						}
					?>
					<?=$file[0]['name'];?>
					<? } ?>
				<?
				}elseif( $vars['fields'][$this->section][$k]=='select' or $vars['fields'][$this->section][$k]=='combo' or $vars['fields'][$this->section][$k]=='radio' ){
					if( !is_array($vars['options'][$k]) ){
						?>
							<?=$v[underscored($k).'_label'];?>
						<?
					}else{
						if( is_assoc_array($vars['options'][$k]) ){
						?>
							<?=$vars['options'][$k][$value];?>
						<?
						}else{
						?>
							<?=$value;?>
						<?
						}
					}
				}elseif( $vars['fields'][$this->section][$k]=='checkbox' ){
				?>
					<?=$value ? 'yes' : 'no';?>
				<?
				}elseif( $vars['fields'][$this->section][$k]=='phpupload' ){
				?>
					<? image($value,100,100);?><br />
					<label><?=$value;?></label>
				<?
				}elseif( $vars['fields'][$this->section][$k]=='textarea' ){
				?>
					<?=truncate($value,100);?>

				<?
				}else{
				?>
					<?=$value;?>
				<?
				}
			}else{
			?>
			&lt;blank&gt;
			<?
			}
			?>
			<? if( $i==0 ){ ?>
			</a>
			<a href="?option=<?=$this->section;?>&edit=true&id=<?=$v['id'];?>&<?=$qs;?>"><img src="/_lib/images/icons/accessories-text-editor.png" title="Edit" align="top" /></a>
			<? } ?>
		</td>
	<?
		}
	}
	?>

	<?
	if( is_array($vars['cols'][$this->section]) ){
		foreach( $vars['cols'][$this->section] as $title=>$link ){
	?>
		<td valign="top">
			<a href="<?=$link;?><?=$v['id'];?>"><?=$title;?></a>
		</td>
	<?
		}
	}
	?>

	<? if( $parent_field ){ ?>
		<td valign="top">
			<a href="?option=<?=$_GET['option'];?>&parent=<?=$v['id'];?>">Children</a>
		</td>
	<? } ?>
</tr>
<?
	}
}else{
?>
<tr>
	<td colspan="20" align="center" style="text-align:center;">no results found</td>
</tr>
<?
}
?>
</tbody>
</table>
</div>

<? require(dirname(__FILE__).'/includes/list_actions.php'); ?>

</form>
</div>
<table width="100%">
<tr>
	<td width="33%"><?=$p->get_results();?>&nbsp;</td>
	<td width="33%" style="text-align:center;"><?=$p->get_paging();?>&nbsp;</td>
	<td width="33%" style="text-align:right;"><?=$p->items_per_page();?>&nbsp;</td>
</tr>
</table>
