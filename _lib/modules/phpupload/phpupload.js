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
		<button type="button" class="rotate_right" disabled><i class="fa fa-repeat" aria-hidden="true"></i></button>\
		<button type="button" class="refresh"><i class="fa fa-refresh" aria-hidden="true"></i></button>\
		<button type="button" class="level_up" disabled><i class="fa fa-level-up" aria-hidden="true"></i></button>\
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
							$( ".progress-label" ).text( "Current Progress: " + $( "#progressbar" ).progressbar( "value" ) + "%" );
						},
						complete: function() {
							$( ".progress-label" ).text( "Complete!" );
							dialog.dialog( "close" );
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
						console.log(item.name);
						$(el).parent().addClass('ui-state-active').get(0).scrollIntoView();
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
				var name = $('#uploads .ui-state-active a').data('name');
				
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
					thumb = item.leaf ? 'images/file_thumb.png' : 'images/folder_thumb.png';
					$('<li>\
						<a href="javascript:void(0);" data-name="' + item.name + '" data-leaf="' + item.leaf + '">\
							<img src="' + thumb + '">\
							' + item.name + '\
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
				
				if (callback) {
					callback();
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
		
		$('.delete, .rename, .download, .rotate_left, .rotate_right .enter').attr('disabled', 'disabled');

		if($('#uploads .ui-state-active a').length===1) {
			var leaf = $('#uploads .ui-state-active a').data('leaf');
			
			if (leaf) {
				$('.delete, .rename, .download, .rotate_left, .rotate_right .enter').removeAttr('disabled');
			} else {
				$('.delete, .rename').removeAttr('disabled');
			}
		} else if($('#uploads .ui-state-active a').length>1) {
			$('.delete').removeAttr('disabled');
		} else {
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
			var name = el.data('name');
			$.ajax({
				dataType: "json",
				url: '?cmd=delete&name='+name+'&path='+path,
				success: function(data) {
					el.parent().remove();
					if($('#uploads .ui-state-active a').length===0) {
						refresh();
						confirmDelete = false;
					} else {
						$('.delete').click();
					}
				}
			});
		}
	});
	
	$('.rotate_left').click(function() {
		var name = $('#uploads .ui-state-active a').data('name');
		$.ajax({
			dataType: "json",
			url: '?cmd=rotateLeft&name='+name+'&path='+path,
			success: function(data) {
				refresh();
			}
		});
	});
	
	$('.rotate_right').click(function() {
		var name = $('#uploads .ui-state-active a').data('name');
		$.ajax({
			dataType: "json",
			url: '?cmd=rotateRight&name='+name+'&path='+path,
			success: function(data) {
				refresh();
			}
		});
	});
	
	$('.rename').click(function() {
		var name = $('#uploads .ui-state-active a').data('name');
		var newname = prompt("New name", name);
		
		if (newname !== null) {
			$.ajax({
				dataType: "json",
				url: '?cmd=rename&name='+name+'&newname='+newname+'&path='+path,
				success: function(data) {
					refresh();
				}
			});
		}
	});
	
	$('.download').click(function() {
		var name = $('#uploads .ui-state-active a').data('name');
		
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
		var name = $('#uploads .ui-state-active a').data('name');
		var leaf = $('#uploads .ui-state-active a').data('leaf');
		
		if (path) {
			name = path + '/' + name;
		}
		
		if (!leaf) {
			location.hash = name;
		} else {
			var files = [];
			
			$('#uploads .ui-state-active a').each(function(item) {
				var name = $(this).data('name');
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
			var name = $(this).data('name').toLowerCase();
			
			if (name.indexOf(val)===-1) {
				$(this).parent().hide();
			} else {
				$(this).parent().show();
			}
		});
	});
	
	var name = $('body').on('dblclick', '#uploads a', function() { $('.enter').click(); });

	window.onhashchange = function(){
		path = location.hash.substr(1);
		refresh();
		check_buttons();
	};
	
	$(window).scroll(function() {
		if($(window).scrollTop() + $(window).height() > $(document).height()-1000) {
			// load some more
			if (current < total) {
				load();
			}
		}
	});
	
	var path = location.hash.substr(1);
	
	refresh();
	check_buttons();
});