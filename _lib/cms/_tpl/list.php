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
                                foreach ($vars['labels'][$this->section] as $k) {
                                    if ('id' == $k) {
                                        continue;
                                    }
                                    
                                    $order = underscored('T_' . underscored($this->section) . '.' . $k);
                                    
                                    if ('select' == $vars['fields'][$this->section][$k] or 'combo' == $vars['fields'][$this->section][$k] or 'radio' == $vars['fields'][$this->section][$k]) {
                                        if (!is_array($vars['options'][$k])) {
                                            $option = key($vars['fields'][$vars['options'][$k]]);
                            
                                            $order = 'T_' . underscored($k) . '.' . underscored($option);
                                        }
                                    }
                                    
                                    $label = $vars['label'][$this->section][$k];
                                    if (!$label) {
                                        $label = ucfirst(str_replace('_', ' ', $k));
                                    } ?>
									<th><?=ucfirst($label); ?></th>
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
                            foreach ($cms_buttons as $k => $button) {
                                if ($this->section == $button['section'] and 'list' == $button['page']) {
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
$asc = ('date' == $first_field_type or 'timestamp' == $first_field_type) ? 'desc' : 'asc';

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
        "searching": false,
		
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
				return '<div style="white-space: nowrap;"><a href="?option=<?=$params['section'];?>&view=true&id='+data+'&<?=$qs;?>" title="ID: '+data+'"><i class="fas fa-search"></i></a> &nbsp; <a href="?option=<?=$params['section'];?>&edit=true&id='+data+'"><i class="fas fa-pencil-alt"></i></a></div>';
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