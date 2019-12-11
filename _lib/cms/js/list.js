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

	for( var j in fields ){
		if( typeof(fields[j])=='string' ){
			remove_options(document.getElementById('field_'+fields[j]));
		}
	}

	$('#csv_loaded').hide();

	document.getElementById('file_field').innerHTML = document.getElementById('file_field').innerHTML;

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
            url: '/_lib/cms/_ajax/csv_upload.php',  //server script to process data
            type: 'POST',
            //Ajax events
            success: completeHandler = function(data) {
                //open preview
                result=jQuery.parseJSON(data);

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

function loadFile(file)
{
	jQuery.ajax(
		'/_lib/cms/_ajax/csv_fields.php',
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

				jQuery.ajax('/_lib/cms/_ajax/csv_preview.php',{
					type: 'post',
					parameters: 'csv='+file,
                    success: function(data) {
                        $("#csv_preview").html(data);
                    }
				});

				for( var j in fields ){
					if( typeof(fields[j])=='string' ){
						remove_options(document.getElementById('field_'+fields[j]));
						add_options(result,document.getElementById('field_'+fields[j]),fields[j]);
					}
				}
				$('#csv_loaded').show();

				document.getElementById('file_field').innerHTML='<strong>'+file+'</strong> <a href="#" onclick="changeFile();">change</a><input type="hidden" name="csv" value="'+file+'">';

			}
		}
	);
}

function set_language()
{
	var option = document.getElementById('language');

	for( var j=0; j<option.options.length; j++ ){
		if( document.getElementById('language_'+option.options[j].value).style.display!=='none' ){
			document.getElementById('language_'+option.options[j].value).style.display='none';
		}
	}

	document.getElementById('language_'+$('#language').val()).style.display='block';
}

//import csv
function checkForm()
{
    var pars = $('#importForm').serialize();

    console.log(pars);

    var source = new EventSource('/_lib/cms/_ajax/import.php?'+pars);

    $( "#progress" ).dialog({modal:true});

    var results = {
        0: 0,
        1: 0,
        2: 0
    };
	source.addEventListener('message', function(event) {
		var data = JSON.parse(event.data);
        console.log(data);
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