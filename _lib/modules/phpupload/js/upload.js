Ext.Loader.setConfig({enabled: true});

Ext.Loader.setConfig({
    enabled: true,
    paths: {
        'Ext.ux.DataView': '../ux/DataView/',
        'Ext.ux': 'https://extjs.cachefly.net/extjs-4.1.1-gpl/examples/ux',
        'Ext.ux.upload': 'js/ux/upload',
        'Ext.ux.grid': 'js/ux/grid',
        'Ext.ux.container': 'js/ux/container'
    }
});

Ext.require([
    'Ext.data.*',
    'Ext.util.*',
    'Ext.ux.DataView.LabelEditor',
    'Ext.state.*',
    'Ext.ux.upload.Button',
    'Ext.ux.upload.plugin.Window',

    'Ext.grid.*',
    'Ext.ux.grid.feature.Tileview',
	'Ext.ux.grid.plugin.DragSelector',
	'Ext.ux.container.SwitchButtonSegment'
]);

var path = location.hash.substr(1);
var callback = function(files){
    //console.log(files);

    var URL = config.root+files[0];
    //var win = tinyMCEPopup.getWindowArg("window");

    // insert information now
    //win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;

    top.tinymce.activeEditor.windowManager.getParams().oninsert(URL);
    top.tinymce.activeEditor.windowManager.close();

    // are we an image browser
    /*
    if (typeof(win.ImageDialog) != "undefined") {
        // we are, so update image dimensions...
        if (win.ImageDialog.getImageData)
            win.ImageDialog.getImageData();

        // ... and preview if necessary
        if (win.ImageDialog.showPreviewImage)
            win.ImageDialog.showPreviewImage(URL);
    }*/

    // close popup window
    //tinyMCEPopup.close();
    return;
};

Ext.onReady(function(){
    window.onhashchange = function(){
        path = location.hash.substr(1);

        if( path ){
            Ext.getCmp('upButton').enable();
        }else{
            Ext.getCmp('upButton').disable();
        }

        store.load({
            params: {
                path: path
            }
        });
    };

    fileModel = Ext.define('fileModel', {
        extend: 'Ext.data.Model',
        fields: [
           {name: 'leaf', type: 'boolean'},
           {name: 'name'},
           {name: 'thumb'},
           {name: 'thumb_medium'},
           {name: 'size', type: 'float'},
           {name:'modified', type:'date', dateFormat:'timestamp'}
        ]
    });

    var store = Ext.create('Ext.data.Store', {
        autoSync:true,
        autoLoad: true,
        model: 'fileModel',
        proxy: {
            type: 'ajax',
            api: {
                create: 'index.php?cmd=create',
                read: 'index.php?cmd=get',
                update: 'index.php?cmd=update',
                destroy: 'index.php?cmd=delete'
            },
            //url: 'index.php',
            reader: {
                type: 'json',
                root: 'files'
            },
            writer: {
                type: 'json' //,
                //allowSingle :false      //always wrap in an array
            },
            extraParams: {
                path: path
            }
        },
        listeners: {
            write: function(proxy, operation){
                //refresh
                //store.load();
            }
        },
        // sort folders first
        onBeforeSort: function() {
            this.sort({
                property: 'leaf',
                direction: 'ASC'
            }, 'prepend', false);
        }
    });

    var open = function(nodes){
        var files = [];
        var isDir = false;

        Ext.each(nodes, function(node) {
            if( !node.raw.leaf ){
                location.hash = node.raw.id;
                isDir = true;
                return;
            }else{
                files.push(node.raw.id);
            }
        });

        if( isDir === false && callback ){
            callback(files);
        }
    };

    //plupload
    var uploadOptions = {
        url: location.href,
        headers: {
            path: path
        },
        autoStart: false,
        //chunk_size: '8mb',
        max_file_size : '100mb',
        drop_element: 'dragload',
        statusQueuedText: 'Ready to upload',
        statusUploadingText: 'Uploading ({0}%)',
        statusFailedText: '<span style="color: red">Error</span>',
        statusDoneText: '<span style="color: green">Complete</span>',
        statusInvalidSizeText: 'File too large',
        statusInvalidExtensionText: 'Invalid file type'
    };

    if( config && config.resize_images ){
        uploadOptions.resize = {width : config.resize_dimensions[0], height : config.resize_dimensions[1], quality : 90};
    }

    var uploadButton = Ext.create('Ext.ux.upload.Button', {
		text: 'Upload files',
		//singleFile: true,
		plugins: [{
            ptype: 'ux.upload.window',
            title: 'Upload',
            width: 520,
            height: 350
        }],
		uploader: uploadOptions,
		listeners:
		{
			filesadded: function(uploader, files)
			{
                setTimeout(function () { uploader.start(); }, 500);
				return true;
			},

			beforeupload: function(uploader, file)
			{
                uploader.uploader.settings.multipart_params = {
                    filename: file.files[0].name
                };

                uploader.uploader.settings.headers.path = location.hash.substr(1);

				//console.log('beforeupload');
			},

			fileuploaded: function(uploader, file)
			{
				//console.log('fileuploaded');
			},

			uploadcomplete: function(uploader, success, failed)
			{
                var files = Ext.clone(success);

				//reload
                store.load({
                    params: {
                        path: path
                    },
                    scope: this,
                    callback: function() {
                        //select files
                        var records = [];
                        Ext.each(files, function(node) {
                            records.push(store.findRecord('name', node.name));
                        });

                        view.getSelectionModel().select(records);
                    }
                });
			},
			scope: this
		}
	});

    var grid = Ext.create('Ext.grid.Panel', {
        store: store,
        region: 'center',
        autoScroll: true,
        autoHeight: true,
        height: '100%',
        multiSelect: true,

        viewConfig: {
            stripeRows: true,
            chunker: Ext.view.TableChunker,
            plugins: [
                Ext.create('Ext.ux.DataView.LabelEditor', {
                    dataIndex: 'name',
                    pluginId: 'LabelEditor',
                    field: Ext.create('Ext.form.field.Text', {
                        xtype:'textfield',
                        allowBlank:false,
                        listeners: {
                            focus: function(){
                                var value = this.getValue();

                                if (value.indexOf('.') !== -1) {
                                    this.inputEl.dom.setSelectionRange(0, value.lastIndexOf('.'));
                                }else{
                                    this.inputEl.dom.setSelectionRange(0, value.length);
                                }
                            }
                        }
                    })
                })
            ]
        },

        plugins: [
            //Ext.create('Ext.ux.grid.plugin.DragSelector', {}), //breaks keynav in ext 4.1
            Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function(){
                        if( grid.features[0].viewMode!=='default' ){
                            return false;
                        }
                    }
                }
            })
        ],

        features: [Ext.create('Ext.ux.grid.feature.Tileview', {
            viewMode: 'tileIcons',
			getAdditionalData: function(data, index, record, orig)
			{
                switch(this.viewMode){
                    case 'tileIcons':
                        return {
                            thumbnail: data.thumb
                        };
                    case 'mediumIcons':
                        return {
                            thumbnail: data.thumb_medium
                        };
                    default:
                        return {};
                }
			},
			viewTpls:
			{
				mediumIcons: [
					'<td class="{cls} ux-explorerview-medium-icon-row">',
					'<table class="x-grid-row-table">',
						'<tbody>',
							'<tr>',
								'<td class="x-grid-col x-grid-cell ux-explorerview-icon" style="background: url(&quot;{thumbnail}&quot;) no-repeat scroll 50% 100% transparent;">',
								'</td>',
							'</tr>',
							'<tr>',
								'<td class="x-grid-col x-grid-cell">',
									'<div class="x-grid-cell-inner x-editable" unselectable="on">{name}</div>',
								'</td>',
							'</tr>',
						'</tbody>',
					'</table>',
					'</td>'].join(''),

                tileIcons: [
					'<td class="{cls} ux-explorerview-detailed-icon-row">',
					'<table class="x-grid-row-table">',
						'<tbody>',
							'<tr>',
								'<td class="x-grid-col x-grid-cell ux-explorerview-icon" style="background: url(&quot;{thumbnail}&quot;) no-repeat scroll 50% 50% transparent;">',
								'</td>',

								'<td class="x-grid-col x-grid-cell">',
									'<div class="x-grid-cell-inner x-editable" unselectable="on">{name}</div>',
								'</td>',
							'</tr>',
						'</tbody>',
					'</table>',
					'</td>'].join('')
            }
        })],
        columns: [{
            text: 'Name',
            id: 'name',
            flex: 1,
            dataIndex: 'name',
            editor: Ext.create('Ext.form.field.Text', {
                xtype:'textfield',
                allowBlank:false,
                listeners: {
                    focus: function(){
                        var value = this.getValue();

                        if (value.indexOf('.') !== -1) {
                            this.inputEl.dom.setSelectionRange(0, value.lastIndexOf('.'));
                        }else{
                            this.inputEl.dom.setSelectionRange(0, value.length);
                        }
                    }
                }
            })
        }, {
            text: 'Modified',
            xtype: 'datecolumn',
            id: 'modified',
            format: 'd-m-Y h:m',
            flex: 1,
            dataIndex: 'modified'
        }, {
            text: 'Size',
            xtype: 'templatecolumn',
            id: 'size',
            tpl: '{size:fileSize}',
            align: 'right',
            flex: 1,
            dataIndex: 'size'
        }],
        tbar: [
            uploadButton,{
                xtype: 'button',
                id: 'deleteButton',
                text: 'Delete',
                disabled: true,
                handler: function() {
                    Ext.Msg.show({
                        title: 'Delete',
                        msg: 'Do you really want to delete the selected file(s)?',
                        icon: Ext.Msg.WARNING,
                        buttons: Ext.Msg.YESNO,
                        scope: this,
                        fn: function (response) {
                            // do nothing if answer is not yes
                            if ('yes' !== response) {
                                return;
                            }
                            store.remove(view.getSelectionModel().getSelection());
                        }
                    });
                }
            },{
                xtype: 'button',
                id: 'renameButton',
                text: 'Rename',
                disabled: true,
                handler: function() {
                    var labelEditor = view.getPlugin('LabelEditor');
                    var record = view.getSelectionModel().getSelection()[0];
                    var target = view.getNode(record);

                    target = target.querySelector(".x-editable");

                    labelEditor.activeRecord = record;

                    Ext.Function.bind( function(){
                        labelEditor.startEdit(target, record.data.name);
                    }, labelEditor)();
                }
            },{
                xtype: 'button',
                id: 'newFolderButton',
                text: 'New folder',
                handler: function() {
                    // add folder
                    var folder = store.add({
                        name: 'untitled',
                        leaf: false,
                        thumb: 'images/folder_thumb.png',
                        thumb_medium: 'images/folder_thumb_medium.png'
                    })[0];

                    //scroll
                    view.getNode(folder).scrollIntoView();

                    //select
                    view.getSelectionModel().select(folder);

                    //rename
                    Ext.getCmp('renameButton').handler.call();
                }
            },{
                xtype: 'button',
                id: 'downloadButton',
                text: 'Download',
                disabled: true,
                handler: function() {
                    var file = view.getSelectionModel().getSelection()[0].get('id');
                    window.open('index.php?download='+file);
                }
            }, '->', {
                xtype: 'textfield',
                emptyText: 'Filter',
                listeners: {
                    change: function(field, newValue, oldValue, options){
                        grid.store.clearFilter();

                        if (newValue) {
                            var matcher = new RegExp(Ext.String.escapeRegex(newValue), 'i');
                            grid.store.filter("id", matcher);
                        }
                    }
                }
            }, {
                xtype: 'button',
                id: 'upButton',
                iconCls: 'icon-up',
                handler: function() {
                    var pos = location.hash.lastIndexOf('/');

                    if( pos == -1 ){
                        location.hash = '';
                    }else{
                        location.hash = location.hash.substr(0, pos);
                    }
                },
                disabled: true
            },{
                xtype: 'button',
                id: 'refreshButton',
                icon: 'https://extjs.cachefly.net/ext-4.1.1-gpl/resources/themes/images/gray/grid/refresh.gif',
                handler: function() {
                    store.load();
                }
            }, {
            xtype: 'switchbuttonsegment',
            activeItem: 1,
            scope: this,
            items: [{
                tooltip: 'Details',
                viewMode: 'default',
                iconCls: 'icon-default'
            }, {
                tooltip: 'Tiles',
                viewMode: 'tileIcons',
                iconCls: 'icon-tile'
            }, {
                tooltip: 'Icons',
                viewMode: 'mediumIcons',
                iconCls: 'icon-medium'
            }],
            listeners: {
                change: function(btn, item)
                {
					grid.features[0].setView(btn.viewMode);
                },
                scope: this
            }
        }],
        bbar: ['->',{
            xtype: 'button',
            id: 'chooseButton',
            text: 'Choose',
            disabled: true,
            handler: function() {
                open(view.getSelectionModel().getSelection());
            }
        }],
        listeners: {
            selectionchange: function(dv, nodes ){
                var l = nodes.length;

                //enable buttons
                if( l==1 ){
                    Ext.getCmp('deleteButton').enable();
                    Ext.getCmp('renameButton').enable();
                    Ext.getCmp('downloadButton').enable();
                    Ext.getCmp('chooseButton').enable();
                }else if( l>1 ){
                    Ext.getCmp('deleteButton').enable();
                    Ext.getCmp('renameButton').disable();
                    Ext.getCmp('downloadButton').disable();
                    Ext.getCmp('chooseButton').enable();
                }else{
                    Ext.getCmp('deleteButton').disable();
                    Ext.getCmp('renameButton').disable();
                    Ext.getCmp('downloadButton').disable();
                    Ext.getCmp('chooseButton').disable();
                }
            },
            itemdblclick: function(dv, nodes ){
                open(nodes);
            }
        }
    });

    var view = grid.getView();

    //open on enter
    view.on('itemkeydown', function(dv, nodes, item, index, e ){
        if( e.getKey() == e.ENTER ){
            open(view.getSelectionModel().getSelection());
        }
        if( e.getKey() == e.DELETE ){
            Ext.getCmp('deleteButton').handler.call();
        }
    });

    var viewport = Ext.create('Ext.container.Viewport', {
        layout: 'border',
        items: grid
    });

    //keys
    var map = new Ext.util.KeyMap({
		target: viewport.getEl(),
		binding: [{
			key: 'a',
			shift: false,
			ctrl: true,
			handler: function (obj, e) {
                view.getSelectionModel().selectAll();
			},
            defaultEventAction: 'preventDefault'
		},{
            key: Ext.EventObject.F2,
            handler: function (obj, e) {
                Ext.getCmp('renameButton').handler.call();
            },
            defaultEventAction: 'preventDefault'
        }]
    });
});