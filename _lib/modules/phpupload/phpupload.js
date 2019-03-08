/*
TODO
file size
last modified
file sort
list view
toolbar layout
*/

var callback = function(files){
	var URL = config.root+files[0];
	top.tinymce.activeEditor.windowManager.getParams().oninsert(URL);
	top.tinymce.activeEditor.windowManager.close();
	return;
};

$(function() {
	var current = 0;
	var total = 0;
	var loading = false;
	var dialog;
	
	$('<div id="toolbar">\
		<button type="button" id="pickfiles" class="upload">Upload files</button>\
		<button type="button" class="delete" disabled>Delete</button>\
		<button type="button" class="rename" disabled>Rename</button>\
		<button type="button" class="new_folder">New folder</button>\
		<button type="button" class="download" disabled>Download</button>\
		<button type="button" class="rotate_left" disabled><i class="fa fa-undo" aria-hidden="true"></i></button>\
		<button type="button" class="rotate_right" disabled><i class="fas fa-redo" aria-hidden="true"></i></button>\
		<button type="button" class="refresh"><i class="fas fa-sync" aria-hidden="true"></i></button>\
		<button type="button" class="level_up" disabled><i class="fas fa-level-up-alt" aria-hidden="true"></i></button>\
		<button type="button" class="enter" disabled>OK</button>\
		<input type="text" name="search" class="search">\
	</div>\
	<div id="uploads">\
	<ul></ul>\
	</div>\
	').appendTo('body');
	
    // plupload
	var uploader = new plupload.Uploader({
		runtimes : 'html5,html4',
		browse_button : 'pickfiles', // you can pass in id...
		
		url : "",
		
		filters : {
			max_file_size : '10mb',
			mime_types: [
				{title : "Image files", extensions : "jpg,gif,png"},
				{title : "Zip files", extensions : "zip"}
			]
		},
	 
	    init: {
			beforeupload: function(uploader, file)
			{
				/*
				uploader.uploader.settings.multipart_params = {
				filename: file.files[0].name
				};
				*/
				uploader.settings.url = '?path='+path;

				//console.log('beforeupload');
			},
			
			FilesAdded: function(up, files) {
				uploader.start();
			},
			
			FileUploaded: function(up, file, obj) {
				var result = JSON.parse(obj.response);
				
				if (result.error) {
					
					$(function () {
						$('body').append('<div id="warning"><h2>'+file.name+'</h2><p>'+result.error+'</p></div>');
					    $("#warning").dialog({
					        autoOpen: false,
					        draggable: false,
					        resizable: false,
					        show: {
					            effect: 'fade',
					            duration: 2000
					        },
					        hide: {
					            effect: 'fade',
					            duration: 2000
					        },
					        open: function(){
					            $(this).dialog('close');
					        },
					        close: function(){
					            $(this).dialog('destroy');
					            $('#warning').remove();
					        }
					    });
					    
					    $(".ui-dialog-titlebar").remove();
					    
					    $("#warning").dialog("open");
					});
				}
			},

			UploadProgress: function(up, file) {
				if (!$('#dialog').length) {
					$('<div id="dialog" title="File Upload">\
						<div class="progress-label">Uploading...</div>\
						<div id="progressbar"></div>\
					</div>').appendTo('body');
					
					dialog = $( "#dialog" ).dialog({
						dialogClass: "no-close",
						autoOpen: false,
						closeOnEscape: false,
						resizable: false,
						modal: true/*,
						buttons: dialogButtons,
						open: function() {
							progressTimer = setTimeout( progress, 2000 );
						},
						beforeClose: function() {
							downloadButton.button( "option", {
								disabled: false,
								label: "Start Download"
							});
						}*/
					});
					
					dialog.dialog( "open" );
				}
				
				$( function() {
					$( "#progressbar" ).progressbar({
						value: file.percent,
						change: function() {
							$( ".progress-label" ).text( file.name + ' ' + $( "#progressbar" ).progressbar( "value" ) + "%" );
						},
						complete: function() {
							$( ".progress-label" ).text( "Complete!" );
							dialog.dialog( "close" );
							$( "#dialog" ).remove();
						}
					});
				} );
			},

			Error: function(up, err) {
				document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
				$('#dialog').remove();
			},
			
			UploadComplete: function(uploader, files) {
				$('#dialog').remove();
				refresh(function() {
					$('#uploads .ui-state-active').removeClass('ui-state-active');
					files.forEach(function(item) {
						var el = $('#uploads a[data-name="' + item.name + '"]');
						if($(el).parent().get(0)) {
							$(el).parent().addClass('ui-state-active').get(0).scrollIntoView();
						}
					});
					check_buttons();
					
					//clear upload queue
					uploader.splice();
				});
			}
	    }
	});
	 
	uploader.init();
	
	// list files
	function refresh(callback) {
		clear();
		load(callback);
	}
	function clear() {
		current = 0;
		total = 0;
		$('#uploads ul').html('');
	}
	function load(callback) {
		if (loading) {
			console.log('already loading');
			return;
		}
		
		loading = true;
		
		$.ajax({
			dataType: "json",
			url: '?cmd=get&path='+path+'&current='+current,
			success: function(data) {
				// get selected
				var name = String($('#uploads .ui-state-active a').data('name'));
				
				// sort folders first then alphabetically
				data.files.sort(function(a, b) {
					if (!a.leaf && b.leaf) {
						return -1;
					}
					
					if (!b.leaf && a.leaf) {
						return 1;
					}
					
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				});
				
				//$('#uploads ul').html('');
				var thumb;
				data.files.forEach(function(item) {
					thumb = item.leaf ? item.thumb : 'images/folder_thumb.png';
					$('<li>\
						<a href="javascript:void(0);" data-name="' + item.name + '" data-leaf="' + item.leaf + '">\
							<img class="lazy" data-original="' + thumb + '">\
							<span>' + item.name + '</span>\
						</a>\
					</li>'
					).appendTo('#uploads ul');
				});
				
				// set selected
				if (name) {
					$('#uploads a[data-name="' + name + '"]').trigger('click');
				}
				
				current = data.current;
				total = data.total;
				loading = false;
				
				if (current < total) {
					load(callback);
				} else {
					$("img.lazy").lazyload();
					if (callback) {
						callback();
					}
				}
			}
		});
	}
	
	function check_buttons() {
		if(path) {
			$('.level_up').removeAttr('disabled');
		} else {
			$('.level_up').attr('disabled', 'disabled');
		}
		
		$('.delete, .rename, .download, .rotate_left, .rotate_right, .enter').attr('disabled', 'disabled');

		if($('#uploads .ui-state-active a').length===1) {
			var leaf = $('#uploads .ui-state-active a').data('leaf');
			
			if (leaf) {
				$('.delete, .rename, .download, .rotate_left, .rotate_right, .enter').removeAttr('disabled');
			} else {
				$('.delete, .rename').removeAttr('disabled');
			}
		} else if($('#uploads .ui-state-active a').length>1) {
			$('.delete').removeAttr('disabled');
			$('.enter').removeAttr('disabled');
		}
	}
	
	$('.refresh').click(function() {
		refresh();
	});
	
	$('.new_folder').click(function() {
		var name = prompt("New folder name", "Untitled");
		
		if (name !== null) {
			$.ajax({
				dataType: "json",
				url: '?cmd=create&name='+name+'&path='+path,
				success: function(data) {
					refresh();
				}
			});
		}
	});
	
	var confirmDelete = false;
	$('.delete').click(function() {
		if (!confirmDelete) {
			confirmDelete = confirm("Are you sure?");
		}
		
		if (confirmDelete) {
			var el = $('#uploads .ui-state-active a').first();
			var name = String(el.data('name'));
			$.ajax({
				method: "GET",
				dataType: "json",
				url: '',
				data: {
					cmd: 'delete',
					name: name,
					path: path
				},
				success: function(data) {
					el.parent().remove();
					if($('#uploads .ui-state-active a').length===0) {
						refresh();
						confirmDelete = false;
					} else {
						setTimeout(function() {$('.delete').click();}, 200);
					}
				}
			});
		}
	});
	
	$('.rotate_left').click(function() {
		var name = String($('#uploads .ui-state-active a').data('name'));
		$.ajax({
			dataType: "json",
			url: '?cmd=rotateLeft&name='+name+'&path='+path,
			success: function(data) {
				var el = $('#uploads .ui-state-active a img');
				el.attr('src', el.data('original')+'?t='+Date.now());
			}
		});
	});
	
	$('.rotate_right').click(function() {
		var name = String($('#uploads .ui-state-active a').data('name'));
		$.ajax({
			dataType: "json",
			url: '?cmd=rotateRight&name='+name+'&path='+path,
			success: function(data) {
				var el = $('#uploads .ui-state-active a img');
				el.attr('src', el.data('original')+'?t='+Date.now());
			}
		});
	});
	
	$('.rename').click(function() {
		var name = String($('#uploads .ui-state-active a').data('name'));
		var newname = prompt("New name", name);
		
		if (newname !== null) {
			$.ajax({
				method: "GET",
				dataType: "json",
				url: '',
				data: {
					cmd: 'rename',
					name: name,
					newname: newname,
					path: path
				},
				success: function(data) {
					$('#uploads a[data-name="' + name + '"]').attr('data-name', newname).data(name, newname).children('span').text(newname);
				}
			});
		}
	});
	
	$('.download').click(function() {
		var name = String($('#uploads .ui-state-active a').data('name'));
		
		if (path) {
			name = path + '/' + name;
		}
		window.open('index.php?download=' + name);
	});
	
	$('.level_up').click(function() {
		var pos = location.hash.lastIndexOf('/');
		
		if( pos == -1 ){
			location.hash = '';
		}else{
			location.hash = location.hash.substr(0, pos);
		}
	});
	
	$('.enter').click(function() {
		var name = String($('#uploads .ui-state-active a').data('name'));
		var leaf = $('#uploads .ui-state-active a').data('leaf');
		
		if (path) {
			name = path + '/' + name;
		}
		
		if (!leaf) {
			location.hash = encodeURIComponent(name);
		} else {
			var files = [];
			
			$('#uploads .ui-state-active a').each(function(item) {
				var name = String($(this).data('name'));
				var leaf = $(this).data('leaf');
				
				if (path) {
					name = path + '/' + name;
				}
				
				files.push(name);
			});
			
			callback(files);
		}
	});
	
	$("#uploads ul").basicMenu({
		select: function (event, ui) {
			check_buttons();
		}
	});
	
	$('.search').keyup(function() {
		var val = $(this).val().toLowerCase();
		
		$('#uploads li a').each(function(item) {
			var name = String($(this).data('name')).toLowerCase();
			
			if (name.indexOf(val)===-1) {
				$(this).parent().hide();
			} else {
				$(this).parent().show();
			}
		});
	});
	
	$('body').keydown(function(e) {
		if (e.ctrlKey && e.key==='a') {
			$('#uploads li').addClass('ui-state-active');
			e.preventDefault();
		}
	});
	
	var name = $('body').on('dblclick', '#uploads a', function() { $('.enter').click(); });

	window.onhashchange = function(){
		path = decodeURIComponent(location.hash.substr(1));
		refresh();
		check_buttons();
	};
	
	/*
	$(window).scroll(function() {
		if($(window).scrollTop() + $(window).height() > $(document).height()-1000) {
			// load some more
			if (current < total) {
				load();
			}
		}
	});
	*/
	
	var path = decodeURIComponent(location.hash.substr(1));
	
	refresh();
	check_buttons();
});