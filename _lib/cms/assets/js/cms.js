function timeSince(timeStamp) {
    if (typeof timeStamp === "string") {
        timeStamp = new Date(timeStamp.replace(/-/g, "/"));
    }

    var now = new Date();
    var secondsPast = parseInt((now.getTime() - timeStamp.getTime()) / 1000);

    if (secondsPast <= 86400) {
        var hour = timeStamp.getHours();
        hour = ("0" + hour).slice(-2);
        var min = timeStamp.getMinutes();
        min = ("0" + min).slice(-2);
        return hour + ':' + min;
    }
    if (secondsPast > 86400) {
        day = timeStamp.getDate();
        month = timeStamp.toDateString().match(/ [a-zA-Z]*/)[0].replace(" ", "");
        year = timeStamp.getFullYear() == now.getFullYear() ? "": " "+timeStamp.getFullYear();
        return day + " " + month + year;
    }
}

//needed to include input file
function serializeAll (form) {
    var rselectTextarea = /^(?:select|textarea)/i;
    var rinput = /^(?:color|date|datetime|datetime-local|email|file|hidden|month|number|password|range|search|tel|text|time|url|week)$/i;
    var rCRLF = /\r?\n/g;

    var arr = $(form).map(function() {
        return form.elements ? $.makeArray(form.elements): form;
    })
    .filter(function() {
        return this.name && !this.disabled &&
        (this.checked || rselectTextarea.test(this.nodeName) ||
            rinput.test(this.type));
    })
    .map(function(i, elem) {
        if ($(elem).attr('type') == 'file') {
            var val = [];
            for (var i = 0; i < $(elem).get(0).files.length; ++i) {
                val.push($(elem).get(0).files[i].name);
            }
        } else {
            var val = $(this).val();
        }

        return val === null ?
        null:
        $.isArray(val) ?
        $.map(val, function(val, i) {
            return {
                name: elem.name, value: val.replace(rCRLF, "\r\n")
            };
        }):
        {
            name: elem.name,
            value: val.replace(rCRLF, "\r\n")
        };
    }).get();

    return $.param(arr);
}

function initForms() {
    //validation
    $.each($('form.validate'),
        function() {
            if ($('*[type=file], *[type=files]', this).length) {
                $(this).attr('enctype', 'multipart/form-data');
            }
        });

    var callback = function(form) {
        form.submit();
    }

    $('form.validate').on('submit',
        function(evt) {
            evt.preventDefault();
            evt.stopPropagation();

            var form = this;

            //disable buttons
            $('*[type=submit], *[type=image]', form).attr('disabled', '');

            //remove error messages
            $('div.error').remove();
            $('.errors').hide();

            var url = location.href;
            if (form.action) {
                url = form.action;
            }

            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }

            //validate
            $.ajax(url, {
                dataType: 'json',
                type: $(form).attr('method'),
                data: serializeAll(form)+'&validate=1&nospam=1',
                error: function(returned) {
                    //alert('Submit error, check console for details');
                    alert(returned.responseText)
                },
                success: function(data) {
                    var errorMethod = 'inline';
                    var firstError;
                    var errorText = '';

                    if ($(form).attr('data-errorMethod')) {
                        errorMethod = $(form).attr('data-errorMethod');
                        //legacy support
                    } else if ($(form).attr('errorMethod')) {
                        errorMethod = $(form).attr('errorMethod');
                    }

                    console.log(data);

                    if (parseInt(data) !== 1) {

                        //display errors
                        var errors = data.errors ? data.errors: data;

                        errors.forEach(function(item) {

                            var pos = item.indexOf(' ');
                            var field;

                            if (pos === -1) {
                                field = item;
                                error = 'required';
                            } else {
                                field = item.substring(0, pos);
                                error = item.substring(pos + 1);
                            }

                            var parent = '';
                            if (form[field]) {
                                if (form[field].style) {
                                    if (!firstError) {
                                        firstError = field;
                                    }

                                    parent = form[field].parentNode;
                                } else if (form[field][0]) {
                                    parent = form[field][0].parentNode.parentNode;
                                }

                                errorText += field + ': ' + error + '\n';
                            } else if (form[field + '[]']) {
                                if (form[field + '[]'].style) {
                                    if (!firstError) {
                                        firstError = field;
                                    }

                                    parent = form[field + '[]'].parentNode;
                                } else if (form[field + '[]'][0]) {
                                    parent = form[field + '[]'][0].parentNode.parentNode;
                                }

                                errorText += field + ': ' + error + '\n';
                            } else {
                                errorText += error + '\n';
                                console.log('field not found: ' + field);
                            }

                            if (parent && errorMethod === 'inline') {
                                div = document.createElement("div");
                                div.innerHTML = error;
                                div.style.color = 'red';
                                div.className = 'error';
                                parent.appendChild(div);
                            }
                        })

                        if (errorMethod === 'alert') {
                            alert('Please check the required fields\n' + errorText);
                        }

                        $('.errors').html('Please check the following:<br>' + errorText.replace(/(?:\r\n|\r|\n)/g, '<br>')).show();
                        $(form).trigger('validationError');

                    } else {
                        //submit form
                        window.onbeforeunload = null;

                        //nospam
                        $(form).append('<input type="hidden" name="nospam" value="1">');

                        var recaptchaSiteKey = $('#recaptcha').data('key');

                        if (recaptchaSiteKey) {
                            grecaptcha.ready(function() {
                                grecaptcha.execute(recaptchaSiteKey, {
                                    action: 'submit'
                                }).then(function(token) {

                                    var el = $(form).find('.recaptcha');
                                    if (!el.length) {
                                        el = $('<textarea style="display: none;" class="recaptcha" name="g-recaptcha-response"></textarea>').appendTo(form);
                                    }

                                    el.val(token);
                                    callback(form);
                                });
                            });
                        } else {
                            callback(form);
                        }
                    }
                },
                complete: function(returned) {
                    // re-enable submit
                    $('*[type=submit], *[type=image]', form).removeAttr('disabled');
                }
            });
        });
}

function delItem(field) {
    var obj=field.parentNode;
    obj.parentNode.removeChild(obj);
}

$(function() {
    initForms();
});