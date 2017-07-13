function debug(log_txt) {
    if (window.console !== undefined) {
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

			return val === null ?
				null :
				jQuery.isArray( val ) ?
					jQuery.map( val, function( val, i ){
						return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
					}) :
					{ name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
		}).get();

		return $.param(arr);
	};
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

function showProgress(on){
	if( !jQuery.dialog ){
		return;
	}

	if(on){
		var dialog = jQuery("#progressDialog");
		if (jQuery("#progressDialog").length === 0){
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
		jQuery('*[type=submit], *[type=image]',this).attr('disabled', '');

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
			data: jQuery(this).serializeAll()+'&validate=1&nospam=1',
			success: jQuery.proxy(function(returned){
			    var errorMethod = 'inline';
				var firstError;

				if( jQuery(this).attr('data-errorMethod') ){
				    errorMethod = jQuery(this).attr('data-errorMethod');
				//legacy support
				}else if( jQuery(this).attr('errorMethod') ){
				    errorMethod = jQuery(this).attr('errorMethod');
				}

				debug(returned);

				showProgress(false);

				if( parseInt(returned, 10)!==returned-0 ){
					if( returned.length>0 ){

						//display errors
						var errors='';

						for( i=0;i<returned.length;i++ ){
							var pos = returned[i].indexOf(' ');

							var field;
							var error;

							if( pos===-1 ){
								field=returned[i];
								error='Required';
							}else{
								field=returned[i].substring(0,pos);
								error=returned[i].substring(pos+1);
							}

							var parent='';

							if( this[field] ){
								if( this[field].style ){
									if( !firstError ){
										firstError=field;
									}

									parent=this[field].parentNode;
								}else if( this[field][0] ){
									parent=this[field][0].parentNode.parentNode;
								}
								errors+=field+'\n';
							}else if( this[field+'[]'] ){
								if( this[field+'[]'].style ){
									if( !firstError ){
										firstError=field;
									}

									parent=this[field+'[]'].parentNode;
								}else if( this[field+'[]'][0] ){
									parent=this[field+'[]'][0].parentNode.parentNode;
								}

								errors+=field+'\n';
							}else{
								errors+=error+'\n';

								debug('field not found: '+field);
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

						node = this[firstError];

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
						$(this).find('[name='+returned[0]+']:first').focus();
					}

					//remove error messages
					jQuery('*[type=submit], *[type=image]',this).removeAttr('disabled');
				}else{
					//submit form
					window.onbeforeunload = null;

                    //nospam
                    $(this).append('<input type="hidden" name="nospam" value="1">');

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
    	jQuery("input[data-type='date']").datepicker({
    		dateFormat: 'dd/mm/yy',
    		altFormat: 'dd/mm/yy'
    	});

    	//dob
    	jQuery("input[data-type='dob']").datepicker({
    		dateFormat: 'dd/mm/yy',
    		altFormat: 'dd/mm/yy',
    		changeMonth : true,
    		changeYear : true,
    		yearRange: '-100y:c+nn',
    		maxDate: '-1d'
    	});

    	//month
    	jQuery('input.month').datepicker({
	        dateFormat: "mm/yy",
	        changeMonth: true,
	        changeYear: true,
	        showButtonPanel: true,
            onClose: function(dateText, inst) {
				function isDonePressed(){
					return ($('#ui-datepicker-div').html().indexOf('ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover') > -1);
				}

				if (isDonePressed()){
					var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
					var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
					$(this).datepicker('setDate', new Date(year, month, 1)).trigger('change');
                    
					$('.date-picker').focusout(); //Added to remove focus from datepicker input box on selecting date
                }
            },
			beforeShow: function(input, inst) {
				if ((datestr = $(this).val()).length > 0) {
					year = datestr.substring(datestr.length-4, datestr.length);
					month = datestr.substring(0, 2);
					$(this).datepicker('option', 'defaultDate', new Date(year, month-1, 1));
					$(this).datepicker('setDate', new Date(year, month-1, 1));
				}

				setTimeout(function() {
					$(".ui-datepicker-calendar").hide();
                }, 1);
            },
            onChangeMonthYear: function() {
				setTimeout(function() {
					$(".ui-datepicker-calendar").hide();
                }, 1);
            }
        });
    }

	//maps
	if( jQuery('input.map').length ){
	    var maps = [];
		google.load("maps", "3", {other_params: "sensor=false&key="+$('input.map').data('key'), "callback" : function(){
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

				var lat = coords[0];
				var lng = coords[1];

				var latlng = new google.maps.LatLng(lat, lng);
				var myOptions = {
					zoom: 7,
					center: latlng,
					mapTypeId: google.maps.MapTypeId.ROADMAP
				};
				maps[this.name] = new google.maps.Map(div, myOptions);

				maps[this.name].markers = [];
				if( this.value || !$(this).prop('readonly') ){
					maps[this.name].markers.push(new google.maps.Marker({
						map: maps[this.name],
						position: latlng,
						draggable: true
					}));

					var field = this;

					google.maps.event.addListener(maps[this.name].markers[0], "dragend", function() {
						var latlng=this.getPosition();
						field.value=latlng.lat()+' '+latlng.lng();
					});
				}

				if ($(this).data('points')) {
					var infowindow = new google.maps.InfoWindow();
					var points = $(this).data('points');
					var name = this.name;

					for (var i in points) {
						var point = points[i];

						lat = point.coords[0];
						lng = point.coords[1];
						latlng = new google.maps.LatLng(lat, lng);

						var length = maps[this.name].markers.push(new google.maps.Marker({
							map: maps[this.name],
							position: latlng,
							title: point.title,
							description: point.description,
							icon: point.icon,
							link: point.link
						}));

						var marker = maps[this.name].markers[length-1];

						google.maps.event.addListener(marker, 'click', (function(marker, i) {
							return function() {
								infowindow.setContent('<strong>'+marker.title+'</strong><br>'+marker.description+'<br><a href="'+marker.link+'">read more</a>');
								infowindow.open(maps[name], marker);
							};
						})(marker, i));
					}

					if ($(this).data('results')) {
						var map = maps[this.name];
						var markers = maps[this.name].markers;
						var resultsEl = $('#'+$(this).data('results'));
						var current_marker;
						google.maps.event.addListener( map, 'bounds_changed', function() {
							resultsEl.children().remove();

							// Read the bounds of the map being displayed.
							bounds = map.getBounds();

							// Iterate through all of the markers that are displayed on the *entire* map.
							for ( i = 0, l = markers.length; i < l; i++ ) {
								current_marker = markers[ i ];

								/* If the current marker is visible within the bounds of the current map,
								* let's add it as a list item to #nearby-results that's located above
								* this script.
								*/
								if ( bounds.contains( current_marker.getPosition() ) ) {

									/* Only add a list item if it doesn't already exist. This is so that
									* if the browser is resized or the tablet or phone is rotated, we don't
									* have multiple results.
									*/
									if ( 0 === $( '#map-marker-' + i ).length ) {
										resultsEl.append(
											$( '<li />' )
											.attr( 'id', 'map-marker-' + i )
											.attr( 'class', 'depot-result' )
											.html( '<a href="#">'+current_marker.title+'</a>' )
											.click($.proxy(function() {
												new google.maps.event.trigger( this, 'click' );
											}, current_marker))
										);

									}

								}

							}

						});

					}
				}
			});
		}});
	}

	function showAddress(address, mapName) {
		geocoder.geocode( { 'address': address}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				maps[mapName].setCenter(results[0].geometry.location);
				maps[mapName].markers[0].setPosition(results[0].geometry.location);
			} else {
				console.log("Geocode was not successful for the following reason: " + status);
			}
		});
	}

	jQuery('.mapSearch').click(function(){
		showAddress($(this).prev().val(), $(this).next().next().attr('name'));
	});

    //upload
    if( jQuery('.upload').length ){
		jQuery.getScript("/_lib/modules/phpupload/js/jquery.upload.js").done(function(){
			jQuery('.upload').upload();
		});
	}

    //ratings
	if( jQuery('select.rating').length ){
		jQuery("head").append("<link>");
		var ratingCss = jQuery("head").children(":last");
		ratingCss.attr({
			rel:  "stylesheet",
			type: "text/css",
			href: "/_lib/js/rateit/rateit.css"
		});

		jQuery.getScript("/_lib/js/rateit/jquery.rateit.js").done(function(){
            $("select.rating").each(function(index) {
                var field = $(this);
                field.hide();

				var starwidth = field.attr('data-rateit-starwidth') ? field.attr('data-rateit-starwidth') : 16;
				var starheight = field.attr('data-rateit-starheight') ? field.attr('data-rateit-starheight') : 16;

				var cls = field.attr('class');

				var readonly = $(this).attr('disabled');

                field.after('<div class="'+cls+'"></div>').next().rateit({
                    backingfld: field.attr('id'),
                    resetable: false,
                    ispreset: true,
                    step: 1,
                    value: field.val(),
                    starwidth: starwidth,
                    starheight: starheight,
                    readonly: readonly
                }).bind('rated', function (event, value) {
                    var field = $(this).prev();
                    field.val(value);

                    if( field.attr('data-section') ){
                        jQuery.ajax('/_lib/cms/_ajax/rating.php', {
                            dataType: 'json',
                			type: 'post',
                			data: {
                                section: field.attr('data-section'),
                                field: field.attr('name'),
                                item: field.attr('data-item'),
                                value: value
                			}
                        });
                    }
                });
            });
		});
	}

	//tinymce4
    var tinymce_url = '//cloud.tinymce.com/stable/';
	if( jQuery('textarea.tinymce').length ){
        jQuery.getScript(tinymce_url+"jquery.tinymce.min.js").done(function(){
            $('textarea.tinymce').tinymce({
                script_url: tinymce_url+'tinymce.min.js',
                plugins: [
                    "importcss advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste textcolor colorpicker hr"
                ],
                toolbar: "insertfile undo redo | styleselect | formatselect  | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | hr link image forecolor backcolor | components",

                //content_css: "css/style.css",

                /*
                style_formats: [
                    {title: 'Bold text', inline: 'b'},
                    {title: 'Red text', inline: 'span', styles: {color: '#ff0000'}},
                    {title: 'Red header', block: 'h1', styles: {color: '#ff0000'}},
                    {title: 'Example 1', inline: 'span', classes: 'example1'},
                    {title: 'Example 2', inline: 'span', classes: 'example2'},
                    {title: 'Table styles'},
                    {title: 'Table row 1', selector: 'tr', classes: 'tablerow1'}
                ],
                */

                relative_urls : false,
                remove_script_host : false, //needed for shiftmail

                content_css : "/css/style.css?" + new Date().getTime(),

    			// Drop lists for link/image/media/template dialogs
    			template_external_list_url : "lists/template_list.js",
    			external_link_list_url : "/_lib/js/tinymce.lists/links.php",
    			external_image_list_url : "/_lib/js/tinymce.lists/images.php",
    			media_external_list_url : "lists/media_list.js",

                file_picker_callback  :  function(callback, value, meta) {
                    tinymce.activeEditor.windowManager.open({
                        title: "File browser",
                        url: "/_lib/modules/phpupload/?field=field_name&file=url",
                        width: 800,
                        height: 600
                    }, {
                        oninsert: function(url) {
                            callback(url, {text: url});
                        }
                    });

                },
        		accessibility_warnings: false,
                popup_css: false,

                theme_advanced_styles: 'Link to Image=lightbox',

                extended_valid_elements: '[span[itemprop|itemtype|itemscope|class|style]',

                //valid_elements : "a[href|target=_blank],strong/b,div,br,table,tr,th,td,img,span[itemprop|itemtype|itemscope|class|style],i[class],hr,iframe[width|height|src|frameborder|allowfullscreen],ul[id],li",

                valid_elements: "*[*]",

                invalid_elements: 'script',

                setup : function(editor) {
                    editor.addShortcut('Ctrl+219', '', 'outdent');
                    editor.addShortcut('Ctrl+221', '', 'indent');

                    var componentMenu = [];

                    if (typeof components != 'undefined') {
	                   	for(var i in components){
                    		if (components.hasOwnProperty(i)) {
	                    		componentMenu.push({
	                    			text: components[i],
	                    			value: '{$'+components[i]+'}',
	                    			onclick: function(val, val2) {
	                    				editor.insertContent(this._value);
	                    			}
	                    		});
	                    	}
                    	}

						if (components.length) {
					        var result = editor.addButton('components', {
					            type: 'menubutton',
					            text: 'Components',
					            icon: false,
					            menu: componentMenu
					        });
						}
                    }

			        //console.log(result);
                },

                paste_data_images: true
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

	//chained
	if( jQuery('div.chained').length ){
		jQuery('div.chained').each(function() {
			//create select element
			var section = $(this).data('section');
			var name = $(this).data('name');
			var value = $(this).data('value');
			var el = $(this);
			
			function create_select(options) {
				// only create menu if there ar eoptions
            	if (!Object.keys(options).length) {
            		return;
            	}
            	
            	// we will need the name attribute for the new select
               	el.children('select').removeAttr('name');
               	
               	// create new select
				var select = $('<select name="'+name+'" class="chained"></select>').appendTo(el);
				
				// the choose option will use the value from the previous select
				var chooseValue = select.prev('select').val();
				select.append('<option value="'+chooseValue+'">Choose</option>');
				
				// add options
                $.each(options, function(key, val) {
                	var selected = (key==value || val.children) ? 'selected' : '';
                	select.append('<option value="'+key+'" '+selected+'>'+val.value+'</option>');
                	
                	// create next select
                	if (val.children) {
                		create_select(val.children);
                	}
                });
			}
			
			function get_values(value, parent) {
				//get values
	            $.ajax({
	                type: "GET",
	                url: '/_lib/cms/_ajax/chained.php',
	                data: {
	                    section: section,
	                    value: value,
	                    parent: parent
	                },
	                success: function(data, textStatus, jqXHR){
	                	create_select(data.options);
	                	
	                	if (parent) {
	                		el.children('select').last().change();
	                	}
	                },
	                dataType: 'json'
	            });
			}
			
			if (!value) {
				get_values(0);
			} else {
				//work backwards
				get_values(value, 1);
			}
            
            $('body').on('change', 'select.chained', function() {
            	// remove following selects
            	$(this).nextAll('select').remove();
            	
            	// add name attribute
            	$(this).attr('name', name);
            	
            	// get the next select menu
            	var value = $(this).val();
            	if (value && $(this).find(":selected").index()!==0) {
            		get_values(value);
            	}
            });
		});
	}

	//video files
	if( jQuery('input[type="file"]').length ){
		jQuery('input[type="file"]').change(function(evt) {
            var input = this;
            var files = evt.target.files; // FileList object

            // Loop through the FileList and render image files as thumbnails.
            for (var i = 0, f; f = files[i]; i++) {
              // Only process image files.
              if (!f.type.match('video.*')) {
                continue;
              }

              var reader = new FileReader();

              // Closure to capture the file information.
              reader.onload = (function(theFile) {
                return function(e) {
                    // Render thumbnail.
                    var video = document.createElement('video');
                    video.style.display = 'none';
                    video.src = e.target.result;

                    input.parentNode.insertBefore(video, input.nextSibling);

                    video.addEventListener('canplay', function() {
                        this.currentTime = this.duration / 2;
                    }, false);

                    video.addEventListener('seeked', function() {
                        var filename = 'thumb';
                        var w = video.videoWidth;//video.videoWidth * scaleFactor;
                        var h = video.videoHeight;//video.videoHeight * scaleFactor;
                        var canvas = document.createElement('canvas');

                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, w, h);

                        //document.body.appendChild(canvas);
                        var data = canvas.toDataURL("image/jpg");

                        var thumbInput = document.createElement('textarea');
                        thumbInput.style.display = 'none';

                        input.parentNode.insertBefore(thumbInput, input.nextSibling);
                        thumbInput.name = 'file_thumb';
                        thumbInput.value = data;

                        video.parentNode.removeChild(video);
                    }, false);
                };
              })(f);

              // Read in the image file as a data URL.
              reader.readAsDataURL(f);
            }
		});
	}

	//cms inline editing
	if( jQuery('span[data-id]').length ){
        jQuery.getScript(tinymce_url+"jquery.tinymce.min.js").done(function(){
            var cms_save = function(){
                $('#saveButton').attr('disabled','disabled');

                //get edited data
                var data = [];
                var item = {};
                $("span[data-edited='true']").each(function( index ) {
                    item = $(this).data();
                    item.value = $(this).html();
                    data.push(item);
                });

                //console.log(data);

                //save it
                $.ajax({
                    type: "POST",
                    url: '/_lib/cms/_ajax/save.php',
                    data: {
                        data: JSON.stringify(data)
                    },
                    success: function(data, textStatus, jqXHR){
                        //console.log(data);
                        $('#saveDiv').remove();
                    },
                    dataType: 'json'
                });
            };

            var cms_cancel = function(){
                $('#saveDiv').remove();
            };

            var tinymce_onchange = function (ed) {
                ed.on('change', function(e) {
                    //set data attribute edited
                    ed.bodyElement.dataset.edited = true;

                    //show save button
                    if( !document.getElementById('saveDiv') ){
                        var div = document.createElement("div");
                        div.id = 'saveDiv';
                        div.style.position = 'fixed';
                        div.style.bottom = 0;
                        div.style.left = 0;
                        div.style.right = 0;
                        div.style.zIndex = 1000;
                        div.style.textAlign = 'center';
                        div.style.background = '#999';
                        div.style.padding = '3px';

                        var saveBtn = document.createElement("BUTTON");
                        saveBtn.id = 'saveButton';
                        saveBtn.type = 'button';
                        saveBtn.innerText = 'Save changes';
                        saveBtn.style.margin = '0px 5px';
                        div.appendChild(saveBtn);
                        saveBtn.onclick = cms_save;

                        var cancelBtn = document.createElement("BUTTON");
                        cancelBtn.id = 'cancelButton';
                        cancelBtn.type = 'button';
                        cancelBtn.innerText = 'Cancel';
                        cancelBtn.style.margin = '0px 5px';
                        div.appendChild(cancelBtn);
                        cancelBtn.onclick = cms_cancel;

                        document.body.appendChild(div);
                    }
                });
            };

            $('span.cms_text').tinymce({
                script_url: tinymce_url+'tinymce.min.js',
                selector: "span.cms_text",
                inline: true,
                toolbar: "undo redo",
                menubar: false,
                setup : tinymce_onchange
            });

            $('div.cms_editor').tinymce({
                script_url: tinymce_url+'tinymce.min.js',
                selector: "div.cms_editor",
                inline: true,
                plugins: [
                    "advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste"
                ],
                toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
                setup : tinymce_onchange
            });
        });
	}
}

/*
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
*/

function numbersonly(e){
	var unicode=e.charCode? e.charCode : e.keyCode;

	if (unicode!==8 && unicode!==46 && unicode!==9){ //if the key isn't the backspace key or dot or tab (which we should allow)
		if (unicode<48||unicode>57){ //if not a number
			return false; //disable key press
		}
	}
}

function addItem(aList,aField) {
	phpUploadCallback=function(images){
		for( var i in images ){
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

function clearFile(field)
{
    var inputHidden = document.getElementById(field);
	inputFile = document.createElement("input");
	inputFile.setAttribute('name', field);
	inputFile.setAttribute('type', 'file');

	var cell=inputHidden.parentNode;

    while ( cell.childNodes.length >= 1 )
    {
        cell.removeChild( cell.firstChild );
    }

	cell.appendChild(inputFile);
}

function delRow(row) {
	rows--;
	row.parentNode.parentNode.parentNode.deleteRow(row.parentNode.parentNode.rowIndex);
}

function init_tabs()
{
	jQuery('.tab').each(function(index) {
		if( index===0 ){
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
			jQuery(this).addClass('current');
		}else{
			jQuery('#'+this.target).hide();
			jQuery(this).removeClass('current');
		}
	});
}

jQuery(document).ready(function() {
	initForms();
});

$(document).ready(function(){
	$('.mob-nav-icon').click(function () {
		$('.content-wrapper').toggleClass('fullwidth');
		$('.leftcol').toggleClass('nav-hdn');
	});
});