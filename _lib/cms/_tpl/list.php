<?php
$sortable = in_array('position', $vars['fields'][$this->section]);
?>

<!-- Primary table start -->
<div class="col-12">
    <div class="card">
        <div class="card-body">
            <h1 class="header-title"><?=ucwords($this->section);?></h1>
            <div class="data-tables datatable-primary">
            	<form method="post">
            		<input type="hidden" name="section" value="<?=$this->section;?>">
            		<input type="hidden" name="action" class="action">
            	
	                <table id="dataTable-<?=underscored($this->section);?>" class="text-center">
	                    <thead>
	                        <tr>
	        					<th><i class="fas fa-arrows-alt-v"></i><span class="hideText">Reorder</span></th>
	        					<th><span class="hideText">Checkboxes</span></th>
	        					<th><span class="hideText">Actions</span></th>
								<?php
                                foreach ($vars['fields'][$this->section] as $name=>$type) {
                                    if ('id' == $type) {
                                        continue;
                                    }
                                    ?>
									<th><?=ucfirst(spaced($name)); ?></th>
								<?php
                                }
                                ?>
	                        </tr>
	                    </thead>
	                </table>
	                
					<div class="row buttons">
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
$params = [];
$params['fields'] = $conditions;
$params['section'] = $this->section;
$order = 3;
?>

<style>
	.hideText {
		font-size: 0;
	}
</style>

<script>
$(document).ready(function() {
    var table = $('#dataTable-<?=underscored($this->section);?>').DataTable( {
    	dom: 'Bfrtip',
	    buttons: [
	        'copy',
            {
                extend: 'colvis',
                columns: ':not(.noVis)'
            }
	    ],
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
    
	// reordering
    table.on( 'row-reorder', function ( e, diff, edit ) {
    	var table = $(this).DataTable();
		var items = [];
        for ( var i=0, ien=diff.length ; i<ien ; i++ ) {
            var rowData = table.row( diff[i].node ).data();
            
            if (diff[i].newData) {
            	items.push({
            		'id': rowData[1],
            		'position': diff[i].newPosition
            	});
            }
        }
		
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