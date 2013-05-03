// upload
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

        $('iframe#uploadFrame').attr('src', '_lib/modules/phpupload/?file='+file+'&date='+(new Date()).getTime());

        $('iframe#uploadFrame').load(function(){
            //set callback in iframe
            document.getElementById('uploadFrame').contentWindow.callback = function(files){
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

    var addFiles = function(files, list){
        for( var i in files ){
            if( files[i] ){
                list.append('\
                    <li>\
                        <img src="/_lib/modules/phpupload/?func=preview&file='+files[i]+'&w=320&h=240" class="thumb" /><br />\
                        <label class="label">'+files[i]+'</label>\
                		<a href="javascript:;" class="clear">Delete</a>\
                    </li>\
                ');
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
                    $(this).after('\
                        <div class="upload">\
                            <ul></ul>\
                    		<a href="javascript:;" class="choose">Add</a>\
                        </div>\
                    ');

                    addFiles(files, $(this).next().find('ul'));

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