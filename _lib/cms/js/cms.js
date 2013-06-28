function debug(log_txt) {
    if (window.console != undefined) {
        console.log(log_txt);
    }
}

(function($) {
    //needed to include input file
    $.fn.serializeAll = function() {
		var rselectTextarea = /^(?:select|textarea)/i;
		var rinput = /^(?:color|date|datetime|datetime-local|email|file|hidden|month|number|password|range|search|tel|text|time|url|week)$/i;
		var rCRLF = /\r?\n/g;

		var arr = this.map(function(){
			return this.elements ? jQuery.makeArray( this.elements ) : this;
		})
		.filter(function(){
			return this.name && !this.disabled &&
				( this.checked || rselectTextarea.test( this.nodeName ) ||
					rinput.test( this.type ) );
		})
		.map(function( i, elem ){
			var val = jQuery( this ).val();

			return val == null ?
				null :
				jQuery.isArray( val ) ?
					jQuery.map( val, function( val, i ){
						return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
					}) :
					{ name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
		}).get();

		return $.param(arr);
	}
})(jQuery);

function remove_options(select)
{
	jQuery('option:not(:first-child)', select).remove();
	select.disabled=true;
	return true;
}

function add_options(result,target_option, value)
{
	models = eval('('+result+')');

	var mod=target_option;
	var selectedIndex=0;

	if( !models ){
		optval="";
		opttext="Not Applicable"
		mod.options[0]=new Option(opttext,optval, true,true);
	}else{
		for( i=0;i<models.length;i++ ){
			mod.options[i+1]=new Option(models[i],i, true,true);

			if( models[i].replace(/-/,'').toLowerCase()==value.replace(/-/,'').toLowerCase() ){
				selectedIndex=i+1;
			}
		}

		mod.options[0].value=""
		mod.options[0].text=""
		mod.disabled=false;
	}
	mod.options.selectedIndex=selectedIndex;
}

function showProgress(on){
	if( !jQuery.dialog ){
		return;
	}

	if(on){
		var dialog = jQuery("#progressDialog");
		if (jQuery("#progressDialog").length == 0){
			dialog = jQuery('<div id="progressDialog" title="Loading">Please wait..</div>').appendTo('body');
		}

		jQuery( "#progressDialog" ).dialog({
			closeOnEscape: false,
			open: function(event, ui) { jQuery(this).parent().children().children('.ui-dialog-titlebar-close').hide(); },
			height: 140,
			modal: true
		});
	}else{
		jQuery("#progressDialog").dialog("close");
	}
}

function initForms()
{
	//validation
	jQuery('form.validate').bind('submit', function(evt) {
		evt.preventDefault();
		evt.stopPropagation();

		showProgress(true);

		//disable buttons
		jQuery('*[type=submit], *[type=image]',this).attr('disabled', '');;

		//remove error messages
		jQuery('div.error').remove();

		var url = location.href;
		if( this.action ){
			url = this.action;
		}

		//validate
		jQuery.ajax( url, {
			dataType: 'json',
			type: 'post',
			data: jQuery(this).serializeAll()+'&validate=1',
			success: jQuery.proxy(function(returned){
				var errorMethod=jQuery(this).attr('errorMethod')=='alert' ? 'alert' : 'inline';

				debug(returned);

				showProgress(false);

				if( parseInt(returned)!=returned-0 ){
					if( returned.length>0 ){

						//display errors
						var errors='';

						for( i=0;i<returned.length;i++ ){
							var pos=returned[i].indexOf(' ');

							if( pos==-1 ){
								var field=returned[i];
								var error='Required';
							}else{
								var field=returned[i].substring(0,pos);
								var error=returned[i].substring(pos+1);
							}

							var parent='';

							if( this[field] ){
								if( this[field].style ){
									if( !firstError ){
										var firstError=field;
									}

									parent=this[field].parentNode;
								}else if( this[field][0] ){
									parent=this[field][0].parentNode.parentNode;
								}
								errors+=field+'\n';
							}else if( this[field+'[]'] ){
								if( this[field+'[]'].style ){
									if( !firstError ){
										var firstError=field;
									}

									parent=this[field+'[]'].parentNode;
								}else if( this[field+'[]'][0] ){
									parent=this[field+'[]'][0].parentNode.parentNode;
								}

								errors+=field+'\n';
							}else{
								errors+=error+'\n';
							}

							if( parent && errorMethod=='inline' ){
								div = document.createElement("div");
								div.innerHTML=error;
								div.style.color='red';
								div.className='error';

								parent.appendChild(div);
							}
						}

						if( errorMethod=='alert' ){
							alert('Please check the required fields\n'+errors);
						}

						//show first error
						var tab, node;

						node=this[firstError];

						if( node ){
							while( node=node.parentNode ){
								if( node.style.display=='none' ){
									tab=node.id;
									break;
								}

								if( node.nodeName=='BODY' ){
									break;
								}
							}
						}

						if( tab && set_tab ){
							debug('switch tab: '+tab);
							set_tab(tab);
						}

						//focus field
						if( this[firstError] ){
							this[firstError].focus();
						}
					}

					//remove error messages
					jQuery('*[type=submit], *[type=image]',this).removeAttr('disabled');
				}else{
					//submit form
					window.onbeforeunload = null;
					this.submit();
				}
			},this)
		});
	});

	//textarea
	jQuery('textarea.autogrow').bind('keyup', function() {
		this.style.overflow = "hidden";
		this.style.fontSize = 12 + "px";
		//var textarea = _gel("stickyText12");
		var cols = this.cols;
		var str = this.value;
		str = str.replace(/\r\n?/, "\n");
		var lines = 2;
		var chars = 0;
		for (i = 0; i < str.length; i++) {
			var c = str.charAt(i);
			chars++;
			if (c == "\n" || chars == cols) {
				lines ++;
				chars = 0;
			}
		}

		if( this.rows>lines ){
			lines=this.rows;
		}

		this.style.height = lines*14 + "px";
	});

	jQuery('textarea.autogrow').trigger('keyup');

	//combobox
	if( jQuery('select.combobox').length ){
		jQuery("head").append("<link>");
		var css = jQuery("head").children(":last");
		css.attr({
			rel:  "stylesheet",
			type: "text/css",
			href: "/_lib/js/jquery.ui.combobox/jquery.ui.combobox.css"
		});

		jQuery.getScript("/_lib/js/jquery.ui.combobox/jquery.ui.combobox.js").done(function(){
			jQuery('select.combobox').combobox();
		});
	}

	//datefields
    if( jQuery().datepicker ){
    	jQuery('input.date').datepicker({
    		dateFormat: 'dd/mm/yy',
    		altFormat: 'dd/mm/yy'
    	});

    	//dob
    	jQuery('input.dob').datepicker({
    		dateFormat: 'dd/mm/yy',
    		altFormat: 'dd/mm/yy',
    		changeMonth : true,
    		changeYear : true,
    		yearRange: '-100y:c+nn',
    		maxDate: '-1d'
    	});
    }

	//maps
	if( jQuery('input.map').length ){
		google.load("maps", "3", {other_params: "sensor=false", "callback" : function(){
			jQuery.each(jQuery('input.map'), function() {
				div = document.createElement("div");
				div.style.width='600px';
				div.style.height='400px';

				this.parentNode.appendChild(div);

				var coords=null;

				if( this.value ){
					coords = this.value.split(' ');
				}else{
					coords=[51.8100844,-0.02911359999995966];
				}

				var lat=coords[0];
				var lng=coords[1];

				var latlng = new google.maps.LatLng(lat, lng);
				var myOptions = {
					zoom: 10,
					center: latlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				}
				maps[this.name] = new google.maps.Map(div, myOptions);

				maps[this.name].markers=[];
				maps[this.name].markers.push(new google.maps.Marker({
					map: maps[this.name],
					position: latlng,
					draggable:true
				}));

				var field=this;

				google.maps.event.addListener(maps[this.name].markers[0], "dragend", function() {
					var latlng=this.getPosition();
					field.value=latlng.lat()+' '+latlng.lng();
				});
			});
		}});
	}

    //upload
    if( jQuery('.upload').length ){
		jQuery.getScript("/_lib/modules/phpupload/js/jquery.upload.js").done(function(){
			jQuery('.upload').upload();
		});
	}

	//ratings
	if( jQuery('select.rating').length ){
		jQuery("head").append("<link>");
		var css = jQuery("head").children(":last");
		css.attr({
			rel:  "stylesheet",
			type: "text/css",
			href: "/_lib/js/jquery.ui.stars/jquery.ui.stars.css"
		});

		jQuery.getScript("/_lib/js/jquery.ui.stars/jquery.ui.stars.js").done(function(){
			jQuery('select.rating').parent().stars({
				inputType: "select"
			});
		});
	}

	//tinymce
	if( jQuery('textarea.tinymce').length ){
		jQuery.getScript("/_lib/js/tinymce/jquery.tinymce.js").done(function(){
        	jQuery('textarea.tinymce').tinymce({
    			// Location of TinyMCE script
    			script_url : '/_lib/js/tinymce/tiny_mce.js',

                relative_urls : false,
                remove_script_host : false,
                //convert_urls : false,

    			// General options
    			theme : "advanced",
    			plugins : "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,preview,media,searchreplace,contextmenu,paste,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

    			// Theme options
    			//theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
        		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
    			theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,preview,|,forecolor,backcolor",
    			theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,iespell,media,advhr,|,fullscreen,attribs,|,visualchars,nonbreaking,template,pagebreak",
    			theme_advanced_toolbar_location : "top",
    			theme_advanced_toolbar_align : "left",
    			theme_advanced_statusbar_location : "bottom",
    			theme_advanced_resizing : true,

    			// Example content CSS (should be your site CSS)
    			//content_css : "css/style.css",

    			// Drop lists for link/image/media/template dialogs
    			template_external_list_url : "lists/template_list.js",
    			external_link_list_url : "/_lib/js/tinymce.lists/links.php",
    			external_image_list_url : "/_lib/js/tinymce.lists/images.php",
    			media_external_list_url : "lists/media_list.js",

                file_browser_callback : myFileBrowser,
        		accessibility_warnings : false
    		});
		});
	}

	//files
	if( jQuery('ul.files').length ){
		jQuery('ul.files').sortable();
	}

	//combo
	if( jQuery('input.combo').length ){
		jQuery('input.combo').each(function() {
			jQuery(this).autocomplete({
				source: '_lib/cms/_ajax/autocomplete.php?field='+this.name
			});
		});
	}
}

function myFileBrowser (field_name, url, type, win) {
    var cmsURL = '/_lib/modules/phpupload/?field=field_name&file=url';    // script URL - use an absolute path!
    if (cmsURL.indexOf("?") < 0) {
        //add the type as the only query parameter
        cmsURL = cmsURL + "?type=" + type;
    } else {
        //add the type as an additional query parameter
        // (PHP session ID is now included if there is one at all)
        cmsURL = cmsURL + "&type=" + type;
    }

    tinyMCE.activeEditor.windowManager.open({
        file : cmsURL,
        title : 'File Browser',
        width : 420,  // Your dimensions may differ - toy around with them!
        height : 400,
        resizable : "yes",
        inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
        close_previous : "no"
    }, {
        window : win,
        input : field_name
    });
    return false;
}

function numbersonly(e){
	var unicode=e.charCode? e.charCode : e.keyCode

	if (unicode!=8 && unicode!=46 && unicode!=9){ //if the key isn't the backspace key or dot or tab (which we should allow)
		if (unicode<48||unicode>57){ //if not a number
			return false //disable key press
		}
	}
}

function addItem(aList,aField) {
	phpUploadCallback=function(images){
		for( i in images ){
			if( images.hasOwnProperty(i) ){
				var ul = document.getElementById(aList);
				li = document.createElement("li");

				var itemHTML='<input type="hidden" name="'+aField+'[]" value="'+images[i]+'" size="5"> ';
				itemHTML+='<img src="_inc/modules/phpupload/?func=preview&file='+images[i]+'" width="100" height="100" /><br /> ';
				itemHTML+='<label>'+images[i]+'</label><br />';
				//itemHTML+='<span class="link" onClick="phpUpload(\'image'+rows+'\')">Choose</span> ';
				itemHTML+='<a href="javascript:void(0)" class="link" onClick="delItem(this)">Delete</a>';

				li.innerHTML=itemHTML;

				ul.appendChild(li);
			}
		}
	};

	phpUpload();
}

function delItem(field) {
	var obj=field.parentNode;
    obj.parentNode.removeChild(obj);
}

function clearItem(aField) {
    var field = document.getElementById(aField);
	var thumb = document.getElementById(aField+'_thumb');
	var label = document.getElementById(aField+'_label');

	field.value='';
	thumb.src='_inc/modules/phpupload/?func=preview&file=';
	label.innerHTML='';
}

function selectAll(field){
	jQuery('input').each(function() {
		if( this.name==field+'[]' ){
			this.checked=true;
		}
	});
}

function selectNone(field){
	jQuery('input').each(function() {
		if( this.name==field+'[]' ){
			this.checked=false;
		}
	});
}

/*
function addRowrelated(aTable) {
	rows++;

	aRow = aTable.insertRow(aTable.rows.length);

	aCell = aRow.insertCell(0);
	aCell.innerHTML= '<select name="related[]" id="related'+rows+'"></select>';

	aCell = aRow.insertCell(1);
	aCell.innerHTML= '<a href="javascript:;" onClick="delRow(this)">Delete</a>';
}
*/

function delRow(row) {
	rows--;
	row.parentNode.parentNode.parentNode.deleteRow(row.parentNode.parentNode.rowIndex);
}

function init_tabs()
{
	jQuery('.tab').each(function(index) {
		if( index==0 ){
			set_tab(this.target);
		}

		jQuery(this).on('mousedown', function(e){
            set_tab(e.target.target) }
        );
	});
}

function set_tab(target)
{
    jQuery('.tab').each(function(index) {
		if( target==this.target ){
			jQuery('#'+this.target).show();
			jQuery(this).addClass('current')
		}else{
			jQuery('#'+this.target).hide();
			jQuery(this).removeClass('current')
		}
	});
}

jQuery(document).ready(function() {
	initForms();
});