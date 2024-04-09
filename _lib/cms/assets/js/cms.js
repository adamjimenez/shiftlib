/*jshint esversion: 11 */

function timeSince(timeStamp) {
    if (typeof timeStamp === "string") {
        timeStamp = new Date(timeStamp.replace(/-/g, "/"));
    }

    let now = new Date();
    let secondsPast = parseInt((now.getTime() - timeStamp.getTime()) / 1000);

    if (secondsPast <= 86400) {
        let hour = timeStamp.getHours();
        hour = ("0" + hour).slice(-2);
        let min = timeStamp.getMinutes();
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

function initForms() {
    // validation
    document.querySelectorAll('form.validate').forEach(function(form) {
        const fileInputs = form.querySelectorAll('*[type="file"], *[type="files"]');
        if (fileInputs.length) {
            form.setAttribute('enctype', 'multipart/form-data');
        }
    });

    const validateForms = document.querySelectorAll('form.validate');

    validateForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Your form validation logic goes here
            event.preventDefault(); // Prevent default form submission

            let form = this;

            // Disable the buttons
            const submitButtons = form.querySelectorAll('*[type="submit"], *[type="image"]');
            submitButtons.forEach(button => button.disabled = true);

            // Remove error messages
            const errorDivs = form.querySelectorAll('div.error');
            errorDivs.forEach(div => div.remove());

            let url = form.action ? form.action : location.href;
            
            const data = new URLSearchParams();
            for (const pair of new FormData(form)) {
                let value = pair[1] instanceof File ? pair[1].name : pair[1];
                data.append(pair[0], value);
            }
            
            data.append('validate', 1);
            data.append('nospam', 1);

            //validate
            fetch(url, {
                method: form.method,
                body: data,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded' // For POST requests
                }
            })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                // Handle successful response (replace with your logic)
                console.log('Success:', data);

                if (!data.success) {

                    // display errors
                    let errors = data.errors ? data.errors: data;

                    errors.forEach(function(item) {
                        let pos = item.indexOf(' ');
                        let fieldName;

                        if (pos === -1) {
                            fieldName = item;
                            error = 'required';
                        } else {
                            fieldName = item.substring(0, pos);
                            error = item.substring(pos + 1);
                        }

                        let parent = '';
                        let fieldInput = form[fieldName + '[]'] ? form[fieldName + '[]'] : form[fieldName];
                        
                        if (fieldInput) {
                            if (fieldInput.style) {
                                parent = fieldInput.parentNode;
                            } else if (fieldInput[0]) {
                                parent = fieldInput[0].parentNode.parentNode;
                            }
                        } else {
                            console.log('field not found: ' + fieldName);
                        }

                        if (parent) {
                            div = document.createElement("div");
                            div.innerHTML = error;
                            div.style.color = 'red';
                            div.className = 'error';
                            parent.appendChild(div);
                        }
                    })

                } else {
                    // Create the hidden input element
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'nospam';
                    hiddenInput.value = '1';

                    // Append the hidden input to the form
                    form.appendChild(hiddenInput);

                    const recaptchaSiteKey = document.querySelector('#recaptcha')?.dataset?.key;

                    if (recaptchaSiteKey) {
                        grecaptcha.ready(() => {
                            grecaptcha.execute(recaptchaSiteKey, {
                                action: 'submit'
                            }).then(token => {
                                const recaptchaTextarea = form.querySelector('.recaptcha') || document.createElement('textarea');

                                if (!recaptchaTextarea.classList.contains('recaptcha')) {
                                    recaptchaTextarea.style.display = 'none';
                                    recaptchaTextarea.classList.add('recaptcha');
                                    recaptchaTextarea.name = 'g-recaptcha-response';
                                    form.appendChild(recaptchaTextarea);
                                }

                                recaptchaTextarea.value = token;
                                form.submit();
                            });
                        });
                    } else {
                        form.submit();
                    }
                }
                // re-enable submit
                const submitButtons = form.querySelectorAll('*[type="submit"], *[type="image"]');
                submitButtons.forEach(button => button.disabled = false);
            })
            .catch(error => {
                // Handle errors
                console.error('Error:', error);
                alert(error.message); // Example error message display
            });

        });
    });
}

function delItem(field) {
    let obj = field.parentNode;
    obj.parentNode.removeChild(obj);
}

window.addEventListener('DOMContentLoaded', function() {
    initForms();
});