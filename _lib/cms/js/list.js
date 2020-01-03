function toggle_advanced(on)
{
	$('#csv').hide();

	var field = $('#search_form');

	if( !on ){
		for (i = 0; i < field.length; i++){
			if( field[i].type!=='hidden' && field[i].type!=='submit' && field[i].type!=='button' ){
				if( field[i].name!=='s' ){
					field[i].disabled = true;
				}else{
					field[i].disabled = false;
				}
			}
		}

		$('#advanced').slideToggle();
	}else{
		for (i = 0; i < field.length; i++){
			if( field[i].type!=='hidden' && field[i].type!=='submit' && field[i].type!=='button' ){
				if( field[i].name=='s' ){
					field[i].disabled = true;
				}else{
					field[i].disabled = false;
				}
			}
		}

		$('#advanced').slideToggle();
	}
}

function toggle_import()
{
	$('#advanced').hide();
	$('#basic').show();
	$('#csv').slideToggle();
}

function changeFile() 
{
	$('#csv_preview').innerHTML='';
	
	$('#csv_loaded option:not(:first-child)').remove();

	$('#csv_loaded').hide();

	document.getElementById('file_field').innerHTML = file_field_html;

	if (!this.files) {
		return;
	}
	
    var file = this.files[0];
    name = file.name;
    size = file.size;
    type = file.type;

    if(file.name.length > 0)  {
        var data = new FormData();
        jQuery.each(this.files, function(i, file) {
            data.append('file-'+i, file);
        });

        jQuery.ajax({
            url: '/_lib/api/?cmd=csv_upload',  //server script to process data
            type: 'POST',
            //Ajax events
            success: completeHandler = function(result) {
            	if( result.error ){
            		alert( result.error );
            	}else{
            		loadFile(result.file);
            	}
            },
            error: errorHandler = function() {
                alert("failed loading csv");
            },
            // Form data
            data: data,
            //Options to tell JQuery not to process data or worry about content-type
            cache: false,
            contentType: false,
            processData: false
        }, 'json');
    }
}

var updater;
var upload;

var file_field_html;
$(function() {
	file_field_html = document.getElementById('file_field').innerHTML;	
})

function loadFile(file)
{
	jQuery.ajax(
		'/_lib/api/?cmd=csv_fields',
		{
			type: 'post',
			data: 'csv='+file,
			success: function(result){
				file_field_html = document.getElementById('file_field').innerHTML;

				if( result.responseText=='null' ){
					alert('Error: can\'t read file. Try converting to CSV.');
					changeFile();
					return false;
				}

				jQuery.ajax('/_lib/api/?cmd=csv_preview',{
					type: 'post',
					parameters: 'csv='+file,
                    success: function(result) {
                    	html = '\
							<h4>Preview</h4>\
							<table class="box">\
						';
						
						result.rows.forEach(function(row, index) {
							html += '<tr>';
							
							row.forEach(function(item) {
								html += '<td>'+item+'</td>';
							});
							
							html += '</tr>';
						});
						
						html += '</table>';
                    	
                        $("#csv_preview").html(html);
                    }
				});
				
				
				var optionsHTML = '';
				result.options.forEach(function(item, index) {
					optionsHTML += '<option value="' + index +  '">' + item +  '</option>';
				});
				
				fields[$('#importSection').val()].forEach(function(item) {
					$('#importForm tbody').append('\
						<tr>\
							<td width="100">' + item + '</td>\
							<td width="100">should receive</td>\
							<td>\
								<select name="fields[' + item + ']" style="width:100px; font-weight:bold;">\
									<option value="">Select Column</option>\
									' + optionsHTML + '\
								</select>\
							</td>\
						</tr>\
					');
				})
				
				$('#csv_loaded').show();

				document.getElementById('file_field').innerHTML = '<strong>'+file+'</strong> <a href="#" onclick="changeFile();">change</a><input type="hidden" name="csv" value="'+file+'">';
			}
		}
	);
}

//import csv
function checkForm()
{
    var pars = $('#importForm').serialize();

    console.log(pars);
    
    $('#importModal').modal('hide');

    var source = new EventSource('/_lib/api/?cmd=csv_import&'+pars);

    $( '<div id="progress" title="Importing"></div>' ).appendTo('body').dialog({modal:true});

    var results = {
        0: 0,
        1: 0,
        2: 0
    };
	source.addEventListener('message', function(event) {
		var data = JSON.parse(event.data);
        //console.log(data);
        results[data.msg]++;

        $('#progress').html('invalid: '+results[0]+'<br>imported: '+results[1]+'<br>updated: '+results[2]+'<br><br>');
	}, false);

	source.addEventListener('error', function(event) {
		if (event.eventPhase == 2) { //EventSource.CLOSED
			source.close();

			$('#progress').append('Finished importing<br><a href="#" onclick="location.reload();">Return to list</a>');
		}
	}, false);

	return false;
}

