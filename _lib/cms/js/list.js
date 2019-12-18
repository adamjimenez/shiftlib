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

				for( var j in fields ){
					if( typeof(fields[j])=='string' ){
						remove_options(document.getElementById('field_'+fields[j]));
						add_options(result.options, document.getElementById('field_'+fields[j]),fields[j]);
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

function remove_options(select)
{
	jQuery('option:not(:first-child)', select).remove();
	select.disabled=true;
	return true;
}

function add_options(models,target_option, value)
{
	console.log(models)

	var mod=target_option;
	var selectedIndex=0;

	if( !models ){
		optval="";
		opttext="Not Applicable";
		mod.options[0]=new Option(opttext,optval, true,true);
	}else{
		for( i=0;i<models.length;i++ ){
			mod.options[i+1]=new Option(models[i],i, true,true);

			if( models[i].replace(/-/,'').toLowerCase()===value.replace(/-/,'').toLowerCase() ){
				selectedIndex=i+1;
			}
		}

		mod.options[0].value="";
		mod.options[0].text="";
		mod.disabled=false;
	}
	mod.options.selectedIndex=selectedIndex;
}