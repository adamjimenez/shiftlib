// upload

//ie doesn't support indexof https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/indexOf?redirectlocale=en-US&redirectslug=JavaScript%2FReference%2FGlobal_Objects%2FArray%2FindexOf
if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function (searchElement , fromIndex) {
    var i,
        pivot = (fromIndex) ? fromIndex : 0,
        length;

    if (!this) {
      throw new TypeError();
    }

    length = this.length;

    if (length === 0 || pivot >= length) {
      return -1;
    }

    if (pivot < 0) {
      pivot = length - Math.abs(pivot);
    }

    for (i = pivot; i < length; i++) {
      if (this[i] === searchElement) {
        return i;
      }
    }
    return -1;
  };
}

function file_ext( filename ){
    if( filename.length === 0 ) return "";
    var dot = filename.lastIndexOf(".");
	if( dot == -1 ) return "";
	var extension = filename.substr(dot+1,filename.length);
	return extension;
}

(function( $ ){
    var launch = function(field){
        var file = '';
        if( field ){
            file = field.value;
        }

        if( !$('#uploadContainer').length ){
            $("body").append('<div id="uploadContainer" style="overflow: hidden; padding: 0;"><iframe id="uploadFrame" style="width: 100%; height: 100%;" frameborder="0" /></div>');
        }

        $('#uploadContainer').dialog({
            title:'File upload',
            height: 600,
            width: 800,
            modal: true,
            show: {
                effect: "fade",
                duration: 1000
            },
            hide: {
                effect: "fade",
                duration: 1000
            }
        });

        var uploadContainer = $('#uploadContainer');

        var url = '_lib/modules/phpupload/index.php';

        url += '?date='+(new Date()).getTime();

        if( file ){
            url += '&file='+file;
        }

        if( !file && typeof phpupload_default_dir !== 'undefined' ){
            url += '#'+phpupload_default_dir;
        }

        $('iframe#uploadFrame').attr('src', url);

        $('iframe#uploadFrame').load(function(){
            //set callback in iframe
            document.getElementById('uploadFrame').contentWindow.callback = function(files){
                console.log(files)
                if(field){
                    if( field.prop("tagName")==='INPUT' ){
                        $(field).next().find('ul li').remove();
                    }

                    addFiles(files, $(field).next().find('ul'));

                    //set value
                    updateValue(field);
                }

                //close upload window
                uploadContainer.dialog('close');
            };
        });
    };

    var updateValue = function(field){
        var files = [];
        $(field).next().find('ul li').each(function(){
            files.push($(this).find('label').text());
        });

        $(field).val(files.join("\n"));
    };

    var clear = function(){
        var field = $(this).parent().parent().parent().prev();

        //clear thumb
        $(this).parent().remove();

        updateValue(field);
    };

    var addFiles = function(files, list, readonly){
        for( var i in files ){
            if (files.hasOwnProperty(i)) {
                var html = '<li>';

                    if( ['jpg', 'jpeg', 'gif', 'png'].indexOf(file_ext(files[i].toLowerCase()))!==-1  ){
                        html += '<img src="/_lib/modules/phpupload/?func=preview&file='+files[i]+'&w=320&h=240" class="thumb"><br>';
                    }else if( ['f4v', 'mp4'].indexOf(file_ext(files[i]))!==-1 ){
                        html += '<video width="320" height="240" src="/uploads/'+files[i]+'" controls="controls" preload="none"></video><br>';
                    }

                    html += '<label>'+files[i]+'</label>';

                    if( !readonly && files[i] ){
                        html += '&nbsp;<a href="javascript:;" class="clear">delete</a>';
                    }

                    html += '</li>';

                list.append(html);
            }
        }

        //clear
        $('a.clear').click(clear);
    };

    $.fn.upload = function() {
        return this.each(function() {
            switch( this.tagName ){
                case 'A':
                    $(this).click(function(){
                        launch();
                    });
                break;
                case 'INPUT':
                case 'TEXTAREA':
                    //hide input
                    $(this).hide();

                    var files = $(this).val().split("\n");

                    //add image
                    var html = '<div class="upload"><ul></ul>';

                    if( !$(this).prop('readonly') ){
                        html += '<a href="javascript:;" class="choose">Add</a>';
                    }

                    html += '</div>';

                    $(this).after(html);

                    addFiles(files, $(this).next().find('ul'), $(this).prop('readonly'));

                    //sortable
                    if( $(this).prop("tagName")==='TEXTAREA' ){
                        $( $(this).next().find('ul') ).sortable({
                            axis: "y",
                            placeholder: "ui-state-highlight",
                            update: function( event, ui ) {
                                updateValue($(this).parent().prev());
                            }
                        });
                    }

                    //choose
                    $('a.choose').click(function(){
                        launch($(this).parent().prev());
                    });

                    //clear
                    $('a.clear').click(clear);
                break;
                default:
                    console.log(this.tagName);
                break;
            }
        });
    };
})( jQuery );