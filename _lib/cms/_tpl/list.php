<?php
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
	$('.action').val('email');
	return true;
}

function export_selected(){
	$('.action').val('export');
	return true;
}
</script>

<div class="box" style="clear:both;">
<form method="post" id="cms_list_<?=underscored($this->section);?>" class="cms_list">
<input type="hidden" class="section" name="section" value="<?=$this->section;?>">
<input type="hidden" class="action" name="action" id="action" value="">
<input type="hidden" class="select_all_pages" name="select_all_pages" value="0">
<input type="hidden" name="custom_button" value="">
<?php
if( $sortable ){
?>
<input type="hidden" class="sortable" name="sortable" value="1">
<?php
}
?>

<?php
foreach( $where as $k=>$v ){
?>
<input type="hidden" name="<?=$k;?>" value="<?=$v;?>">
<?php
}
?>

<?php require(dirname(__FILE__).'/includes/list_actions.php'); ?>

<div style="min-height:300px; background:#fff;">
<table width="100%" cellspacing="0" cellpadding="5">
<tbody id="items_<?=underscored($this->section);?>">
<tr>
	<?php if( $sortable ){ ?>
		<th class="sorting-arrows">
			<i class="fa fa-arrow-up"></i>
			<i class="fa fa-arrow-down"></i>
		</th>
	<?php } ?>
	<th width="20" valign="top" align="center" style="text-align:center;">
		<input type="checkbox" class="toggle_select">
	</th>
	<?php
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
			<?php
			$label = $vars['label'][$this->section][$k];
			if(!$label) {
				$label = ucfirst(str_replace('_', ' ', $k));
			}
			?>
			<?=(
				$sortable or
				( $vars['fields'][$this->section][$k] == 'select-multiple' or $vars['fields'][$this->section][$k]=='checkboxes' )
			) ? ucfirst($label) : $p->col($order, ucfirst($label));?>
		</th>
	<?php
	}
	?>

	<?php if( $parent_field ){ ?>
		<th>&nbsp;</th>
	<?php } ?>
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
<?php
if( count($vars['content']) ){
?>
<?php
foreach( $vars['content'] as $v){
?>
<tr class="draggable list-row" id="tr_<?=$v['id'];?>" <?php if($v['read']==='0') { ?>style="font-weight: bold;"<?php } ?>>
	<?php if( $sortable ){ ?>
		<td valign="top"><div class="handle">&nbsp;</div></td>
	<?php } ?>
	<td width="20" valign="top" align="center" style="text-align:center;"><input type="checkbox" name="items[]" value="<?=$v['id'];?>"></td>
	<?php
	if( is_array($vars['labels'][$this->section]) ){
		foreach( $vars['labels'][$this->section] as $i=>$k ){
			$value = $v[underscored($k)];

			if( $vars['fields'][$this->section][$k] == 'select-multiple' or $vars['fields'][$this->section][$k]=='checkboxes' ){
			    $value = '';
			    foreach( $v[underscored($k)] as $val ){
			        $value .= current($val)."<br>\n";
			    }

			    /*
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
				*/
			}
	?>
		<td valign="top" <?php if( $i==0 ){ ?>style="white-space:nowrap;"<?php } ?>>
			<?php if( $i==0 ){ ?>
			<a href="?option=<?=$this->section;?>&view=true&id=<?=$v['id'];?>&<?=$qs;?>">
			<?php } ?>
			<?php
			$value=trim($value);

			if( $value!=='' ){
				if( $vars['fields'][$this->section][$k]=='date' ){
				?>
					<?php if( $value!='0000-00-00' ){ ?>
						<?=dateformat('d/m/Y',$value);?>
					<?php }else{ ?>
						&lt;blank&gt;
					<?php } ?>
				<?php
				}elseif( $vars['fields'][$this->section][$k] == 'datetime' ){
					if( $value!='0000-00-00 00:00:00' ){
						$date=explode(' ',$value);
						$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
					}
				?>
					<?=$value;?>
				<?php
				}elseif( $vars['fields'][$this->section][$k] == 'timestamp' ){
					if( $value!='0000-00-00 00:00:00' ){
						$date=explode(' ',$value);
						$value=dateformat('d/m/Y',$date[0]).' '.$date[1];
					}
				?>
					<?=$value;?>
				<?php
				}elseif( $vars['fields'][$this->section][$k] == 'month' ){
					if( $value!='0000-00-00' ){
						$date = explode(' ',$value);
						$value = dateformat('F Y',$date[0]).' '.$date[1];
					}
				?>
					<?=$value;?>
				<?php
				}elseif( $vars['fields'][$this->section][$k] == 'dob' ){
				?>
					<?php if( $value!='0000-00-00' ){ ?>
						<?=dateformat('d/m/Y',$value);?> (<?=age($value);?>)
					<?php }else{ ?>
						&lt;blank&gt;
					<?php } ?>
				<?php
				}elseif( $vars['fields'][$this->section][$k]=='file' ){
					if( $value ){
						$file=sql_query("SELECT * FROM files WHERE id='".escape($value)."'");
					}
				?>
					<?php if( $value ){ ?>

					<?php
						$image_types=array('jpg','jpeg','gif','png');
						if( in_array(file_ext($file[0]['name']),$image_types) ){
					?>
						<img src="/_lib/cms/file_preview.php?f=<?=$file[0]['id'];?>&w=100&h=100" /><br />
					<?php
						}
					?>
					<?=$file[0]['name'];?>
					<?php } ?>
				<?php
				}elseif( $vars['fields'][$this->section][$k]=='select' or $vars['fields'][$this->section][$k]=='combo' or $vars['fields'][$this->section][$k]=='radio' ){
					if( !is_array($vars['options'][$k]) ){
						?>
							<?=$v[underscored($k).'_label'];?>
						<?php
					}else{
						if( is_assoc_array($vars['options'][$k]) ){
						?>
							<?=$vars['options'][$k][$value];?>
						<?php
						}else{
						?>
							<?=$value;?>
						<?php
						}
					}
				}elseif( $vars['fields'][$this->section][$k]=='checkbox' ){
				?>
					<?=$value ? 'yes' : 'no';?>
				<?php
				}elseif( $vars['fields'][$this->section][$k]=='phpupload' ){
				?>
					<?=image($value,100,100);?><br />
					<label><?=$value;?></label>
				<?php
				}elseif( $vars['fields'][$this->section][$k]=='phpuploads' ){
					$images = explode("\n", $value);
					
					foreach($images as $image) {
				?>
					<?=image(trim($image), 100, 100);?><br />
					<label><?=$image;?></label><br>
				<?php
					}
				}elseif( $vars['fields'][$this->section][$k]=='textarea' ){
				?>
					<?=truncate($value,100);?>

				<?php
				}else{
				?>
					<?=$value;?>
				<?php
				}
			}else{
			?>
			&lt;blank&gt;
			<?php
			}
			?>
			<?php if( $i==0 ){ ?>
			</a>
			<a href="?option=<?=$this->section;?>&edit=true&id=<?=$v['id'];?>&<?=$qs;?>">
			    <i class="fa fa-pencil-square-o"></i>
			</a>
			<?php } ?>
		</td>
	<?php
		}
	}
	?>

	<?php
	if( is_array($vars['cols'][$this->section]) ){
		foreach( $vars['cols'][$this->section] as $title=>$link ){
	?>
		<td valign="top">
			<a href="<?=$link;?><?=$v['id'];?>"><?=$title;?></a>
		</td>
	<?php
		}
	}
	?>

	<?php if( $parent_field ){ ?>
		<td valign="top">
			<a href="?option=<?=$_GET['option'];?>&<?=underscored($parent_field);?>=<?=$v['id'];?>">Children</a>
		</td>
	<?php } ?>
</tr>
<?php
	}
}else{
?>
<tr>
	<td colspan="20" align="center" style="text-align:center;">no results found</td>
</tr>
<?php
}
?>
</tbody>
</table>
</div>

<?php require(dirname(__FILE__).'/includes/list_actions.php'); ?>

</form>
</div>
<table width="100%">
<tr>
	<td width="33%"><?=$p->get_results();?>&nbsp;</td>
	<td width="33%" style="text-align:center;"><?=$p->get_paging();?>&nbsp;</td>
	<td width="33%" style="text-align:right;"><?=$p->items_per_page();?>&nbsp;</td>
</tr>
</table>
