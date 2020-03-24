<?php
$sortable = in_array('position', $vars['fields'][$this->section]);
?>

<!-- table start -->

<div class="data-tables datatable-primary">
    <form method="post">
        <input type="hidden" name="section" value="<?=$this->section;?>">
        <input type="hidden" name="action" class="action">
        <input type="hidden" name="custom_button" class="custom_button">
    
        <table id="dataTable-<?=underscored($this->section);?>" class="text-center">
            <thead>
                <tr>
                    <th class="noVis"><i class="fas fa-arrows-alt-v"></i>&nbsp;</th>
                    <th class="noVis">&nbsp;</th>
                    <?php
                    foreach ($vars['fields'][$this->section] as $name => $type) {
                        if (in_array($type, $this->hidden_columns)) {
                            continue;
                        } ?>
                        <th data-name="<?=$name; ?>"><?=ucfirst(spaced($name)); ?></th>
                    <?php
                    }
                    ?>
                </tr>
            </thead>
        </table>
    </form>
</div>

<?php
$params = [];
$params['fields'] = $conditions;
$params['section'] = $this->section;
$order = 2;
?>

<style>
    .hideText {
        font-size: 0;
    }
    
    .selectAllPages {
        text-align: center;
        background: #ccc;
        padding: 10px;
    }
    
    .selectAllPages span {
        color: blue;
        font-weight: bold;
        cursor: pointer;
    }
</style>

    
<script>
    // used for import
    fields['<?=$this->section;?>'] = <?=json_encode(array_keys($vars['fields'][$this->section]));?>;
</script>


<script>

$(function() {
    var table;
    
    var buttons = [{
        text: '<i class="fas fa-plus"></i> Add',
        className: 'btn-primary',
        action: function ( e, dt, node, config ) {
            location.href = '?option=<?=$this->section;?>&edit=1<?=$qs ? '&' . $qs : '';?>';
        }
    }, {
        extend: 'colvis',
        columns: ':not(.noVis)',
        text: '<i class="fas fa-columns"></i>'
    }, {
        titleAttr: 'Import',
        text: '<i class="fas fa-file-import"></i>',
        action: function ( e, dt, node, config ) {
            $('#importSection').val('<?=$this->section;?>');
            $('#importModal').modal('show');
        }
    }, {
        titleAttr: 'Export',
        text: '<i class="fas fa-file-export"></i>',
        action: function ( e, dt, node, config ) {
            button_handler('export', false, dt);
        }
    }, {
        titleAttr: 'Delete',
        text: '<i class="fas fa-trash"></i>',
        className: 'btn-danger',
        action: function ( e, dt, node, config ) {
            button_handler('delete', true, dt);
        }
    }];
    
    <?php
    /*
    foreach ($cms_buttons as $k => $button) {
        if (($this->section == $button['section'] || in_array($this->section, $button['section'])) && 'list' == $button['page']) {
        ?>
            buttons.push({
                text: '<?=is_string($button['label']) ? $button['label'] : $button['label']();?>',
                action: function ( e, dt, node, config ) {
                    var form = $(node).closest('form');
                    form.find('.custom_button').val(<?=$k;?>);
                    form.submit();
                }
            });
        <?php
        }
    }
    */
    ?>
    
    table = $('#dataTable-<?=underscored($this->section);?>').DataTable( {
        dom: 'Bfrtip',
        buttons: buttons,
        ajax: {
            url: '/_lib/api/?<?=http_build_query($params);?>',
            type: 'POST'
        },
        <?php if ($sortable) { ?>
        "rowReorder": {
            dataSrc: 0
        },
        "paging": false,
        <?php } ?>
        
        "pageLength": 10,
        "stateSave": true,
        "stateDuration": 60 * 60 * 24 * 365,
        
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
         }],
        'select': {
            "style": "multi",
            "selector": "td.dt-checkboxes-cell"
        },
        
        "order": [[<?=$order;?>, '<?=$asc;?>']],
        "ordering": <?=$sortable ? 'false' : 'true';?>,
        
        "autoWidth": false,
        "responsive": true
    } );
    
    // move to toolbar
    table.buttons().container()
        .appendTo( $('.toolbar .holder' ) ).attr('data-section', '<?=$this->section;?>');
    
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
    
    // select all pages
    table.on( 'select', function ( e, dt, type, indexes ) {
        var selectAllEl = $('.dt-checkboxes-select-all input').get(0);
        var info = dt.table().page.info();
        var selectAllPagesEl = $(dt.table().container()).find('.selectAllPages');
        
        if(info.pages > 1 && !selectAllPagesEl.length && selectAllEl.checked && !selectAllEl.indeterminate) {
            $(dt.table().node()).before('<div class="selectAllPages"><span>Select all pages</span></div>');
        }
    } );
    
    table.on( 'deselect', function ( e, dt, type, indexes ) {
        $(dt.table().container()).find('.selectAllPages').remove();
    } );
    
    $('body').on('click', '.selectAllPages', function() {
        if (!$(this).data('selected')) {
            $(this).find('span').text('Clear selection');
            $(this).data('selected', true);
        } else {
            // clear selection
            var form = $(this).closest('form');
            var table = form.find('table').DataTable();
            table.rows().deselect();
            
            $(this).remove();
        }
    })
    
    $(table.table().container()).on('click', '.dt-checkboxes-cell', function (e) {
        e.stopPropagation();
    });
    
    $(table.table().container()).on('mousedown', 'tr[role]', function (e) {
        if ($(e.target).hasClass('dt-checkboxes-cell') || $(e.target).hasClass('dt-checkboxes')) {
            return;
        }
        
        var data = table.row( this ).data();
        
        if (!data) {
            return;
        }
        
        var url = '?option=<?=$params['section'];?>&view=true&id=' + data[1] + '&<?=$qs;?>';
        
        if (e.ctrlKey || e.metaKey || e.which === 2) {
            window.open(url);
        } else if (e.which == 1) {
            location.href = url;
        }
        
        e.stopPropagation();
    });
    
    $(table.table().container()).on('click', 'tr.child', function (e) {
        $(this).prev().trigger('click');
    });
} );
</script>

<!-- table end -->