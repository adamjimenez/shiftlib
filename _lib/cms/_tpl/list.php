<? /*

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

</form>
</div>
<table width="100%">
<tr>
	<td width="33%"><?=$p->get_results();?>&nbsp;</td>
	<td width="33%" style="text-align:center;"><?=$p->get_paging();?>&nbsp;</td>
	<td width="33%" style="text-align:right;"><?=$p->items_per_page();?>&nbsp;</td>
</tr>
</table>
*/ ?>


<?php
$sortable = in_array('position', $vars['fields'][$this->section]);
?>

<!-- Primary table start -->
<div class="col-12 mt-5">
    <div class="card">
        <div class="card-body">
            <h4 class="header-title"><?=$this->section;?></h4>
            <div class="data-tables datatable-primary">
            	<form method="post">
            		<input type="hidden" name="section" value="<?=$this->section;?>">
            		<input type="hidden" name="action" class="action">
            	
	                <table id="dataTable-<?=underscored($this->section);?>" class="text-center">
	                    <thead class="text-capitalize">
	                        <tr>
	        					<th><i class="fas fa-arrows-alt-v"></i></th>
	        					<th>&nbsp;</th>
	        					<th>&nbsp;</th>
								<?php
								foreach( $vars['labels'][$this->section] as $k ){
									if ($k=='id') {
										continue;
									}
									
									$order = underscored('T_'.underscored($this->section).'.'.$k);
									
									if( $vars['fields'][$this->section][$k]=='select' or $vars['fields'][$this->section][$k]=='combo' or $vars['fields'][$this->section][$k]=='radio' ){
										if( !is_array($vars['options'][$k]) ){
											$option = key($vars['fields'][$vars['options'][$k]]);
							
											$order='T_'.underscored($k).'.'.underscored($option);
										}
									}
									
									$label = $vars['label'][$this->section][$k];
									if(!$label) {
										$label = ucfirst(str_replace('_', ' ', $k));
									}
								?>
									<th><?=ucfirst($label);?></th>
								<?php
								}
								?>
	                        </tr>
	                    </thead>
	                </table>
	                
					<div class="row">
						<div class="col-sm-12">
	                		<a href="?option=<?=$this->section;?>&edit=1&<?=$qs;?>" class="btn btn-primary mb-3">Add</a>
	                		<button type="button" class="btn btn-danger mb-3" data-value="delete" data-confirm="true">Delete</button>
	                		
							<?php
							foreach( $cms_buttons as $k=>$button ){
								if( $this->section==$button['section'] and $button['page']=='list' ){
					                require('includes/button.php');
								}
							}
							?>
	                	</div>
	                	
						
	                </div>
                
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$params = $conditions;
$params['section'] = $this->section;

$first_field_type = $vars['fields'][$this->section][$vars['labels'][$this->section][0]];
$asc = ($first_field_type=='date' or $first_field_type=='timestamp') ? 'desc' : 'asc';

//debug($first_field_type,1);

$order = 3;
?>

<script>
$(document).ready(function() {
    var table = $('#dataTable-<?=underscored($this->section);?>').DataTable( {
		ajax: '/_lib/api/?<?=http_build_query($params);?>',
		<?php if ($sortable) { ?>
		"rowReorder": {
            dataSrc: 0
        },
        "paging": false,
        <?php } ?>
        
		"pageLength": 10,
		"stateSave": true,
		
		"serverSide": true,
		
		'columnDefs': [{
			"targets": 0,
			"render": function ( data, type, row, meta ) {
				return '<i class="fas fa-square"></i>';
			},
			"visible": <?=$sortable ? 'true' : 'false';?>,
			"width": 20,
			"orderable": false
		}, {
		    'targets': 1,
		    'checkboxes': {
		       'selectRow': true
		    },
			"width": 20,
			"orderable": false
		 }, {
			"targets": 2,
			"render": function ( data, type, row, meta ) {
				return '<div style="white-space: nowrap;"><a href="?option=<?=$params['section'];?>&view=true&id='+data+'" title="ID: '+data+'"><i class="fas fa-search"></i></a> &nbsp; <a href="?option=<?=$params['section'];?>&edit=true&id='+data+'"><i class="fas fa-pencil-alt"></i></a></div>';
			},
			"width": 50,
			"orderable": false
		}],
		'select': {
		 'style': 'multi'
		},
		
		"order": [[<?=$order;?>, '<?=$asc;?>']],
		"ordering": <?=$sortable ? 'false' : 'true';?>,
		
		"autoWidth": false,
		"responsive": true
    } );
 
    table.on( 'row-reorder', function ( e, diff, edit ) {
    	var table = $(this).DataTable();
    	
    	/*
    	console.log(arguments);
    	console.log(edit.triggerRow.data());
    	
        */
        //var result = 'Reorder started on row: '+edit.triggerRow.data()[1]+'<br>';
 
		var items = [];
        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
            var rowData = table.row( diff[i].node ).data();
            //result += rowData[1]+' updated to be in position '+ diff[i].newData+' (was '+diff[i].oldData+')<br>';
            
            if (diff[i].newData) {
            	items.push({
            		'id': rowData[1],
            		'position': diff[i].newPosition
            	});
            }
        }
		
		//console.log(diff)
		//console.log(items)
		//console.log(result)
		//return
		
        jQuery.ajax('/_lib/api/?cmd=reorder&<?=http_build_query($params);?>', {
            dataType: 'json',
			type: 'post',
			data: {
                items: items
			},
			success: function() {
				table.ajax.reload();
			}
        });
    } );
} );
</script>

<!-- Primary table end -->