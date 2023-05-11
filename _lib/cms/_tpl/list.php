<?php
$fields = $this->get_fields($this->section);

$sortable = isset($fields['position']) && $fields['position']['type'] === 'position';
?>

<!-- table start -->

<div class="data-tables datatable-primary">
    <form method="post">
        <input type="hidden" name="section" value="<?=$this->section; ?>">
        <input type="hidden" name="action" class="action">
        <input type="hidden" name="custom_button" class="custom_button">

        <table id="dataTable-<?=underscored($this->section); ?>" class="text-center">
            <thead>
                <tr>
                    <th class="noVis"><i class="fas fa-arrows-alt-v"></i>&nbsp;</th>
                    <th class="noVis">&nbsp;</th>
                    <th class="noVis">&nbsp;</th>
                    <?php
                    foreach ($fields as $name => $field) {
                        $type = $field['type'];

                        if (in_array($type, $this->hidden_columns)) {
                            continue;
                        } ?>
                        <th data-name="<?=$name; ?>" <?php if ($type === 'editor') { ?>data-visible="false"<?php } ?>><?=ucfirst(spaced($name)); ?></th>
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
    
    .bold {
        font-weight: bold;
    }
</style>

<script>
    // used for import
    table_config['<?=$this->section; ?>'] = {
        fields: <?=json_encode(array_keys($fields)); ?>,
        add_link: '?option=<?=$this->section; ?>&edit=1<?=$qs ? '&' . $qs : ''; ?>',
        read_field: '<?=array_search('read', array_keys($fields));?>'
    }
</script>

<script>

    $(function() {
        var table;

        var buttons = [{
            text: '<i class="fas fa-plus"></i> Add',
            className: 'btn-primary',
            action: function (e, dt, node, config) {
                var section = $(this.table().container()).closest('form').children('[name="section"]').val()
                location.href = table_config[section].add_link;
            }
        }, {
            extend: 'colvis',
            columns: ':not(.noVis)',
            text: '<i class="fas fa-columns"></i>',
            prefixButtons: [{
                extend: 'colvisGroup',
                text: 'Show all',
                show: ':not(.noVis):hidden'
            }, {
                extend: 'colvisGroup',
                text: 'Hide all',
                hide: ':not(.noVis)'
            }, {
                extend: 'colvisRestore',
                text: 'Restore'
            }]
        }, {
            titleAttr: 'Import',
            text: '<i class="fas fa-file-import"></i>',
            action: function (e, dt, node, config) {
                var section = $(this.table().container()).closest('form').children('[name="section"]').val()
                $('#importSection').val(section);
                $('#importModal').modal('show');
            }
        }, {
            extend: 'collection',
            text: '<i class="fas fa-file-export"></i>',
            buttons: [{
                titleAttr: 'Export',
                className: 'rowsRequired',
                text: 'Export selected',
                action: function (e, dt, node, config) {
                    button_handler('export', false, dt);
                }
            }, {
                titleAttr: 'Export',
                text: 'Export all',
                action: function (e, dt, node, config) {
                    dt.rows().select();
                    $(dt.table().container()).find('.selectAllPages').click();
                    button_handler('export', false, dt);
                    $(dt.table().container()).find('.selectAllPages').click();
                }
            }]
        }, {
            titleAttr: 'Delete',
            text: '<i class="fas fa-trash"></i>',
            className: 'btn-danger rowsRequired',
            action: function (e, dt, node, config) {
                button_handler('delete', true, dt);
            }
        }];

        var columnDefs = [{
            "targets": 0,
            "render": function (data, type, row, meta) {
                return '<i class="fas fa-square"></i>';
            },
            "visible": <?=$sortable ? 'true' : 'false'; ?>,
            "width": 20,
            "orderable": false
        }, {
            'targets': 1,
            'checkboxes': {
                'selectRow': true,
                'stateSave': false
            },
            "width": 20,
            "orderable": false
        }, {
            'targets': 2,
            className: 'control',
            data: null,
            defaultContent: ""
        }];

        // hide copy columns
        $('[data-visible=false]').each(function() {
            columnDefs.push({
                'targets': $(this).index(),
                'visible': false
            });
        });

        table = $('#dataTable-<?=underscored($this->section); ?>').DataTable( {
            dom: 'Bfrtlip',
            buttons: buttons,
            ajax: {
                url: '/_lib/api/?<?=http_build_query($params); ?>',
                type: 'POST'
            },
            <?php if ($sortable) {
                ?>
                "rowReorder": {
                    dataSrc: 0
                },
                "paging": false,
                <?php
            } ?>

            "pageLength": 10,
            "lengthMenu": [[10,
                25,
                50,
                100,
                200],
                [10,
                    25,
                    50,
                    100,
                    200]],

            "stateSave": true,
            "stateDuration": 60 * 60 * 24 * 365,
            "stateLoaded": function (settings, data) {
                var table = $(this).DataTable();
                table.page(1).draw(false);
                table.search('').draw();
            },

            "serverSide": true,
            //"searching": false,

            'columnDefs': columnDefs,
            'select': {
                "style": "multi",
                "selector": "td.dt-checkboxes-cell"
            },

            "order": [[<?=$order; ?>,
                '<?=$asc; ?>']],
            "ordering": <?=$sortable ? 'false' : 'true'; ?>,

            "autoWidth": false,
            "responsive": {
                details: {
                    type: 'column',
                    target: 2
                }
            },
            fnRowCallback: function(nRow, aData, iDisplayIndex) {
                var section = $(this[0]).closest('form').children('[name="section"]').val();

                if (table_config[section].read_field && aData[parseInt(table_config[section].read_field) + 3] == '0') {
                    $('td', nRow).each(function(){
                        $(this).addClass('bold');
                    });
                }
                return nRow;
            }
        });

        // move to toolbar
        table.buttons().container().appendTo($('.toolbar .holder')).attr('data-section','<?=$this->section; ?>');
        
        $('.dropdown[data-section="<?=$this->section; ?>"]').appendTo($('.toolbar .holder').parent()).attr('data-section','<?=$this->section; ?>').hide();

        // disable buttons that need require a row to be selected
        table.buttons('.rowsRequired').disable()

        // reordering
        table.on('row-reorder',
            function (e, diff, edit) {
                var table = $(this).DataTable();
                var items = [];
                for (var i = 0, ien = diff.length; i < ien; i++) {
                    var rowData = table.row(diff[i].node).data();

                    if (diff[i].newData) {
                        items.push({
                            'id': rowData[1],
                            'position': diff[i].newPosition
                        });
                    }
                }

                jQuery.ajax('/_lib/api/?cmd=reorder&<?=http_build_query($params); ?>', {
                    dataType: 'json',
                    type: 'post',
                    data: {
                        items: items
                    },
                    success: function() {
                        table.ajax.reload();
                    }
                });
            });

        // select all pages
        table.on('select',
            function (e, dt, type, indexes) {
                var selectAllEl = $('.dt-checkboxes-select-all input').get(0);
                var info = dt.table().page.info();
                var selectAllPagesEl = $(dt.table().container()).find('.selectAllPages');

                if (info.pages > 1 && !selectAllPagesEl.length && selectAllEl.checked && !selectAllEl.indeterminate) {
                    $(dt.table().node()).before('<div class="selectAllPages"><span>Select all pages</span></div>');
                }

                // toggle buttons
                table.buttons('.rowsRequired').enable();
            });

        table.on('deselect',
            function (e, dt, type, indexes) {
                $(dt.table().container()).find('.selectAllPages').remove();

                // toggle buttons
                var selectAllEl = $('.dt-checkboxes-select-all input').get(0);
                if (selectAllEl.checked === false && selectAllEl.indeterminate === false) {
                    table.buttons('.rowsRequired').disable();
                }
            });

        // toggle selecting all pages
        $('body').on('click',
            '.selectAllPages',
            function() {
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

        $(table.table().container()).on('mousedown',
            '.dataTable tbody tr',
            function (e) {
                if ($(e.target).hasClass('dt-checkboxes-cell') || $(e.target).hasClass('dt-checkboxes') || $(e.target).hasClass('control')) {
                    return;
                }

                var data = table.row(this).data();

                if (!data) {
                    return;
                }

                var url = '?option=<?=$params['section']; ?>&view=true&id=' + data[1] + '<?=$qs ? '&' . $qs : ''; ?>';

                if (e.ctrlKey || e.metaKey || e.which === 2) {
                    var win = window.open(url);
                    window.focus();
                } else if (e.which == 1) {
                    location.href = url;
                }

                e.stopPropagation();
            });

        $(table.table().container()).on('click',
            'tr.child',
            function (e) {
                $(this).prev().trigger('click');
            });
    });
</script>

<!-- table end -->