var callback = function(files){
	var URL = config.root+files[0];
	top.tinymce.activeEditor.windowManager.getParams().oninsert(URL);
	top.tinymce.activeEditor.windowManager.close();
	
	return;
};

$(function() {
	$('<div id="toolbar">\
		<button type="button" id="pickfiles" class="upload">Upload files</button>\
		<button type="button" class="delete" disabled>Delete</button>\
		<button type="button" class="rename" disabled>Rename</button>\
		<button type="button" class="new_folder">New folder</button>\
		<button type="button" class="download" disabled>Download</button>\
		<button type="button" class="rotate_left" disabled><i class="fa fa-undo" aria-hidden="true"></i></button>\
		<button type="button" class="rotate_right" disabled><i class="fa fa-repeat" aria-hidden="true"></i></button>\
		<button type="button" class="refresh"><i class="fa fa-refresh" aria-hidden="true"></i></button>\
		<button type="button" class="up" disabled><i class="fa fa-level-up" aria-hidden="true"></i></button>\
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
	 
			Error: function(up, err) {
				document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
			},
			
			UploadComplete: function() {
				refresh();
			}
	    }
	});
	 
	uploader.init();
	
	// list files
	function refresh() {
		$.ajax({
			dataType: "json",
			url: '?cmd=get&path='+path,
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
				
				$('#uploads ul').html('');
				var thumb;
				data.files.forEach(function(item) {
					thumb = item.leaf ? item.thumb : 'images/folder_thumb.png';
					$('<li>\
						<a href="javascript:void(0);" data-name="' + item.name + '" data-leaf="' + item.leaf + '">\
							<img src="' + thumb + '?mtime=' + Date.now() + '">\
							' + item.name + '\
						</a>\
					</li>'
					).appendTo('#uploads ul');
				});
				
				// set selected
				if (name) {
					$('#uploads a[data-name="' + name + '"]').trigger('click');
				}
			}
		});
	}
	
	function check_buttons() {
		if(path) {
			$('.up').removeAttr('disabled');
		} else {
			$('.up').attr('disabled');
		}
		
		$('.delete, .rename, .download, .rotate_left, .rotate_right').attr('disabled', 'disabled');

		if($('#uploads .ui-state-active a').length) {
			var leaf = $('#uploads .ui-state-active a').data('leaf');
			
			if (leaf) {
				$('.delete, .rename, .download, .rotate_left, .rotate_right').removeAttr('disabled');
			} else {
				$('.delete, .rename').removeAttr('disabled');
			}
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
	
	$('.delete').click(function() {
		var r = confirm("Are you sure?");
		if (r) {
			var name = $('#uploads .ui-state-active a').data('name');
			$.ajax({
				dataType: "json",
				url: '?cmd=delete&name='+name+'&path='+path,
				success: function(data) {
					refresh();
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
	
	$('.up').click(function() {
		var pos = location.hash.lastIndexOf('/');
		
		if( pos == -1 ){
			location.hash = '';
		}else{
			location.hash = location.hash.substr(0, pos);
		}
	});
	
	$("#uploads ul").basicMenu({
		select: function (event, ui) {
			check_buttons();
		}
	});
	
	var name = $('body').on('dblclick', '#uploads a', function() {
		var name = $('#uploads .ui-state-active a').data('name');
		var leaf = $('#uploads .ui-state-active a').data('leaf');
		
		if (path) {
			name = path + '/' + name;
		}
		
		if (!leaf) {
			location.hash = name;
		} else {
			var files = [];
			files.push(name);
			callback(files);
		}
	});

	window.onhashchange = function(){
		path = location.hash.substr(1);
		refresh();
		check_buttons();
	};
	
	var path = location.hash.substr(1);
	
	refresh();
	check_buttons();
});