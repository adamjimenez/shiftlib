var global = window; // fix bug with tinymce

function timeSince(timeStamp) {
    if (typeof timeStamp === "string") {
        timeStamp = new Date(timeStamp.replace(/-/g, "/"));
    }
    
    var now = new Date();
    var secondsPast = parseInt((now.getTime() - timeStamp.getTime() ) / 1000);
        
    if(secondsPast <= 86400){
        var hour = timeStamp.getHours();
        hour = ("0" + hour).slice(-2);
        var min = timeStamp.getMinutes();
        min = ("0" + min).slice(-2);
        return hour + ':' + min;
    }
    if(secondsPast > 86400){
        day = timeStamp.getDate();
        month = timeStamp.toDateString().match(/ [a-zA-Z]*/)[0].replace(" ","");
        year = timeStamp.getFullYear() == now.getFullYear() ? "" :  " "+timeStamp.getFullYear();
        return day + " " + month + year;
    }
}

//needed to include input file
function serializeAll (form) {
    var rselectTextarea = /^(?:select|textarea)/i;
    var rinput = /^(?:color|date|datetime|datetime-local|email|file|hidden|month|number|password|range|search|tel|text|time|url|week)$/i;
    var rCRLF = /\r?\n/g;

    var arr = $(form).map(function(){
        return form.elements ? $.makeArray( form.elements ) : form;
    })
    .filter(function(){
        return this.name && !this.disabled &&
            ( this.checked || rselectTextarea.test( this.nodeName ) ||
                rinput.test( this.type ) );
    })
    .map(function( i, elem ){
        if ($(elem).attr('type')=='file') {
            var val = [];
            for (var i = 0; i < $(elem).get(0).files.length; ++i) {
                val.push($(elem).get(0).files[i].name);
            }
        } else {
            var val = $( this ).val();
        }

        return val === null ?
            null :
            $.isArray( val ) ?
                $.map( val, function( val, i ){
                    return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
                }) :
                { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
    }).get();

    return $.param(arr);
};

function showProgress(on){
    if( !$.dialog ){
        return;
    }

    if(on){
        var dialog = $("#progressDialog");
        if ($("#progressDialog").length === 0){
            dialog = $('<div id="progressDialog" title="Loading">Please wait..</div>').appendTo('body');
        }

        $( "#progressDialog" ).dialog({
            closeOnEscape: false,
            open: function(event, ui) { $(this).parent().children().children('.ui-dialog-titlebar-close').hide(); },
            height: 140,
            modal: true
        });
    }else{
        $("#progressDialog").dialog("close");
    }
}

function initForms()
{
    //validation
    $.each($('form.validate'), function() {
        if ($('*[type=file], *[type=files]',this).length) {
            $(this).attr('enctype', 'multipart/form-data');
        }
    });
    
    $('form.validate').on('submit', function(evt) {
        evt.preventDefault();
        evt.stopPropagation();

        showProgress(true);

        //disable buttons
        $('*[type=submit], *[type=image]',this).attr('disabled', '');

        //remove error messages
        $('div.error').remove();
        $('.errors').hide();

        var url = location.href;
        if( this.action ){
            url = this.action;
        }

        //validate
        $.ajax( url, {
            dataType: 'json',
            type: $(this).attr('method'),
            data: serializeAll(this)+'&validate=1&nospam=1',
            error: function(returned){
                alert('Submit error, check console for details');
                console.log(returned.responseText)
            },
            success: $.proxy(function(returned) {
                var errorMethod = 'inline';
                var firstError;

                if( $(this).attr('data-errorMethod') ){
                    errorMethod = $(this).attr('data-errorMethod');
                //legacy support
                }else if( $(this).attr('errorMethod') ){
                    errorMethod = $(this).attr('errorMethod');
                }

                console.log(returned);

                showProgress(false);

                if( parseInt(returned, 10)!==returned-0 ) {
                    if( returned.length>0 ){

                        //display errors
                        var errors='';

                        for( i=0;i<returned.length;i++ ){
                            var pos = returned[i].indexOf(' ');

                            var field;
                            var error;

                            if( pos===-1 ){
                                field = returned[i];
                                error = 'required';
                            }else{
                                field = returned[i].substring(0, pos);
                                error = returned[i].substring(pos + 1);
                            }

                            var parent='';

                            if( this[field] ){
                                if( this[field].style ){
                                    if( !firstError ){
                                        firstError=field;
                                    }

                                    parent=this[field].parentNode;
                                } else if( this[field][0] ){
                                    parent=this[field][0].parentNode.parentNode;
                                }
                                
                                errors += field + ': ' + error + '\n';
                            } else if ( this[field+'[]'] ){
                                if( this[field+'[]'].style ){
                                    if( !firstError ){
                                        firstError=field;
                                    }

                                    parent=this[field+'[]'].parentNode;
                                } else if( this[field+'[]'][0] ){
                                    parent=this[field+'[]'][0].parentNode.parentNode;
                                }

                                errors += field + ': ' + error + '\n';
                            } else {
                                errors += error + '\n';

                                console.log('field not found: '+field);
                            }

                            if( parent && errorMethod=='inline' ){
                                div = document.createElement("div");
                                div.innerHTML = error;
                                div.style.color = 'red';
                                div.className = 'error';

                                parent.appendChild(div);
                            }
                        }

                        if( errorMethod=='alert' ){
                            alert('Please check the required fields\n'+errors);
                        }
                        
                        $('.errors').html('Please check the following:<br>' + errors.replace(/(?:\r\n|\r|\n)/g, '<br>')).show();

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

                        //focus field
                    //    $(this).find('[name="'+returned[0]+'"]:first').focus();
                    }
                } else if ($(this).data('target')) {
                    
                    $(this).hide();
                    $(this).data('target').show();
                    
                } else {
                    //submit form
                    window.onbeforeunload = null;

                    //nospam
                    $(this).append('<input type="hidden" name="nospam" value="1">');

                    this.submit();
                }
            }, this),
            complete: $.proxy(function(returned) {
                // re-enable submit
                $('*[type=submit], *[type=image]', this).removeAttr('disabled');
            }, this)
        });
    });

    //textarea
    if($('textarea.autosize').length) {
        $.getScript("https://cdn.jsdelivr.net/npm/jquery-autosize@1.18.18/jquery.autosize.min.js").done(function(){
            $('textarea.autosize').autosize();
        });
    }
    
    //combobox
    if($('select.combobox').length ) {
        $("head").append("<link>");
        var css = $("head").children(":last");
        css.attr({
            rel:  "stylesheet",
            type: "text/css",
            href: "/_lib/js/jquery.ui.combobox/jquery.ui.combobox.css"
        });

        $.getScript("/_lib/js/jquery.ui.combobox/jquery.ui.combobox.js").done(function(){
            $('select.combobox').combobox();
        });
    }

    //datefields
    if( $().datepicker ){
        $("input[data-type='date']").datepicker({
            dateFormat: 'yy-mm-dd',
            altFormat: 'yy-mm-dd',
            constrainInput: false
        });

    	//dob
    	$("input[data-type='dob']").datepicker({
    		dateFormat: 'yy-mm-dd',
    		altFormat: 'yy-mm-dd',
    		changeMonth : true,
    		changeYear : true,
    		yearRange: '-100y:c+nn',
    		maxDate: '-1d'
    	});
    }

    //month
    if( $('input.month').length ){
        $("head").append("<link>");
        var css = $("head").children(":last");
        css.attr({
            rel:  "stylesheet",
            type: "text/css",
            href: "https://cdn.jsdelivr.net/npm/jquery-ui-month-picker@3.0.4/src/MonthPicker.css"
        });

        $.getScript("https://cdn.jsdelivr.net/npm/jquery-ui-month-picker@3.0.4/src/MonthPicker.js").done(function(){
            $('input.month').MonthPicker({ 
                Button: false,
                MonthFormat: 'yy-mm'
            });
        });
    }
    
    //combobox
    if( $("input[data-type='time']").length ){
        $("head").append("<link>");
        var css = $("head").children(":last");
        css.attr({
            rel:  "stylesheet",
            type: "text/css",
            href: "//cdn.jsdelivr.net/npm/timepicker@1.11.12/jquery.timepicker.css"
        });
        
        $.getScript("//cdn.jsdelivr.net/npm/timepicker@1.11.12/jquery.timepicker.js").done(function(){
            $("input[data-type='time']").timepicker({ 
                'scrollDefault': 'now',
                'timeFormat': 'H:i:s'
                });
        });
    }

    //upload
    if( $('.upload').length ){
        $.getScript("/_lib/phpupload/js/jquery.upload.js").done(function(){
            $('.upload').upload();
        });
    }

    //ratings
    if( $('select.rating').length ){
        $("head").append("<link>");
        var ratingCss = $("head").children(":last");
        ratingCss.attr({
            rel:  "stylesheet",
            type: "text/css",
            href: "https://cdnjs.cloudflare.com/ajax/libs/jquery.rateit/1.1.3/rateit.css"
        });

        $.getScript("https://cdnjs.cloudflare.com/ajax/libs/jquery.rateit/1.1.3/jquery.rateit.js").done(function(){
            $("select.rating").each(function(index) {
                var field = $(this);

                var starwidth = field.attr('data-rateit-starwidth') ? field.attr('data-rateit-starwidth') : 16;
                var starheight = field.attr('data-rateit-starheight') ? field.attr('data-rateit-starheight') : 16;

                var cls = field.attr('class');

                var readonly = $(this).attr('disabled');

                field.after('<div class="'+cls+'"></div>').next().rateit({
                    backingfld: field,
                    resetable: false,
                    ispreset: true,
                    step: 1,
                    value: field.val(),
                    starwidth: starwidth,
                    starheight: starheight,
                    readonly: readonly
                }).on('rated', function (event, value) {
                    var field = $(this).prev();
                    field.val(value);

                    if( field.attr('data-section') ){
                        $.ajax('/_lib/api/?cmd=rating', {
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

    // tinymce4
    var tinymce_url = '//cloud.tinymce.com/dev/'; // changed to dev from stable for bugfix in 4.7
    if( $("[data-type='tinymce']").length ){
        $.getScript(tinymce_url+"jquery.tinymce.min.js").done(function(){
            $("[data-type='tinymce']").tinymce({
                script_url: tinymce_url+'tinymce.min.js',
                plugins: [
                    "importcss advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste textcolor colorpicker hr",
                    //"autoresize"
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
                        url: "/_lib/phpupload/index.php?field=field_name&file=url",
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
    
    if ($.ui && $.ui.sortable) {
        //files
        $('ul.files').sortable();
        
        $('.checkboxes').sortable({
            axis: 'y',
        });
    }
    
    // file preview and add another
    if( $('ul.files').length ){
        function readURL(input) {
            if (input.files && input.files[0]) {
                var filesAmount = input.files.length;
                
                for (i = 0; i < filesAmount; i++) {
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('<img class="file-preview" title="click to remove" style="max-width: 100px; max-height: 100px; cursor: pointer;">').appendTo($(input).parent()).attr('src', e.target.result    );
                        $(input).hide();
                    }
                    
                    reader.readAsDataURL(input.files[i]);
                }
            }
        }
            
        $("body").on('change', 'ul.files input', function() {
            $(this).parent().clone().appendTo( $(this).closest('ul') ).find('input').val('');
            readURL(this);
        });
            
        $("body").on('click', 'img.file-preview', function() {
            $(this).parent().remove();
        });
    }

    //combo
    if( $("input[data-type='combo']").length ){
        $("input[data-type='combo']").each(function() {
            $(this).autocomplete({
                source: '/_lib/api/?cmd=autocomplete&field=' + $(this).data('field'),
                select: function (event, ui) {
                    // Set autocomplete element to display the label
                    this.value = ui.item.label;
                    
                    // Store value in hidden field
                    $('input[name=' + $(this).data('field') + ']').val(ui.item.value);
                    
                    // Prevent default behaviour
                    return false;
                }
            });
        });
    }

    //chained
    if( $('div.chained').length ){
        $('div.chained').each(function() {
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
                if (!chooseValue) {
                    chooseValue = '';
                }
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
                    url: '/_lib/api/?cmd=chained',
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
    if( $('input[type="file"]').length ){
        $('input[type="file"]').change(function(evt) {
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
}

function addItem(aList,aField) {
    phpUploadCallback=function(images){
        for( var i in images ){
            if( images.hasOwnProperty(i) ){
                var ul = document.getElementById(aList);
                li = document.createElement("li");

                var itemHTML='<input type="hidden" name="'+aField+'[]" value="'+images[i]+'" size="5"> ';
                itemHTML+='<img src="_lib/phpupload/?func=preview&file='+images[i]+'" width="100" height="100" /><br /> ';
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
    thumb.src='_lib/phpupload/?func=preview&file=';
    label.innerHTML='';
}

function selectAll(field){
    $('input').each(function() {
        if( this.name==field+'[]' ){
            this.checked=true;
        }
    });
}

function selectNone(field){
    $('input').each(function() {
        if( this.name==field+'[]' ){
            this.checked=false;
        }
    });
}

function clearFile(field)
{
    inputFile = document.createElement("input");
    inputFile.setAttribute('name', field.getAttribute('name'));
    inputFile.setAttribute('type', 'file');

    var cell = field.parentNode;

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


$(function() {
    initForms();
    
    $('.mob-nav-icon').click(function () {
        $('.content-wrapper').toggleClass('fullwidth');
        $('.leftcol').toggleClass('nav-hdn');
    });
});