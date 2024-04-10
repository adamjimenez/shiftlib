/*jshint esversion: 11 */

window.addEventListener('DOMContentLoaded', function() {
    const validateForms = document.querySelectorAll('form.validate, form[sl-validate]');

    let submitHandler = async function (form, validate) {
        // Disable the buttons
        const submitButtons = form.querySelectorAll('*[type="submit"], *[type="image"]');
        submitButtons.forEach(button => button.disabled = true);

        // Remove error messages
        const errorDivs = form.querySelectorAll('div.sl-error');
        errorDivs.forEach(div => div.remove());

        let url = form.action ? form.action: location.href;
        let formData;
        let headers = {};
        
        if (validate) {
            formData = new URLSearchParams();
            for (const pair of new FormData(form)) {
                let value = pair[1] instanceof File ? pair[1].name: pair[1];
                formData.append(pair[0], value);
            }
    
            if (validate) {
                formData.append('validate', 1);
            }
            headers.contentType = 'application/x-www-form-urlencoded'
        } else {
            formData = new FormData(form);
        }
        
        formData.append('nospam', 1);

        // validate
        let response = await fetch(url, {
            method: form.method,
            body: formData,
            headers: headers
        });
        
        let data = await response.json();
    
        if (!data.success) {
            // display errors
            let errors = data.errors ? data.errors: data;

            errors.forEach(function(item) {
                let pos = item.indexOf(' ');
                let fieldName = item;

                if (pos === -1) {
                    error = 'required';
                } else {
                    fieldName = item.substring(0, pos);
                    error = item.substring(pos + 1);
                }

                let parent = '';
                let fieldInput = form[fieldName + '[]'] ? form[fieldName + '[]']: form[fieldName];

                if (fieldInput) {
                    if (fieldInput.style) {
                        parent = fieldInput.parentNode;
                    } else if (fieldInput[0]) {
                        parent = fieldInput[0].parentNode.parentNode;
                    }
                } else {
                    alert(item);
                }

                if (parent) {
                    div = document.createElement("div");
                    div.innerHTML = error;
                    div.style.color = 'red';
                    div.className = 'sl-error';
                    parent.appendChild(div);
                }
            })

        } else {
            const recaptchaSiteKey = document.querySelector('#recaptcha')?.dataset?.key;

            if (recaptchaSiteKey) {
                await grecaptcha.ready();
                const token = await grecaptcha.execute(recaptchaSiteKey, {
                    action: 'submit'
                });

                const recaptchaTextarea = form.querySelector('.recaptcha') || document.createElement('textarea');

                if (!recaptchaTextarea.classList.contains('recaptcha')) {
                    recaptchaTextarea.style.display = 'none';
                    recaptchaTextarea.classList.add('recaptcha');
                    recaptchaTextarea.name = 'g-recaptcha-response';
                    form.appendChild(recaptchaTextarea);
                }

                recaptchaTextarea.value = token;
            }
            
            // submit again for reals
            if (validate) {
                // backcompat
                if (form.classList.contains('validate')) {
                    form.submit();
                } else {
                    submitHandler(form);
                }
            } else {
                // handle redirect
                if (data.redirect) {
                    location.href = data.redirect;
                }
                
                // handle hide
                if (form.hasAttribute('sl-hide')) {
                    form.style.display = 'none';
                }
                
                // handle target template
                if (form.hasAttribute('sl-target')) {
                    let el = document.querySelector(form.getAttribute('sl-target'));
                    if (!el.dataset.template) {
                        el.dataset.template = el.innerHTML;
                    }
                    
                    const html = Handlebars.compile(el.dataset.template)(data.data);
                    el.innerHTML = html;
                    el.style.display = '';
                }
            }
        }
        
        // re-enable submit
        submitButtons.forEach(button => button.disabled = false);
    }

    // enable validation
    validateForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            // Your form validation logic goes here
            event.preventDefault(); // Prevent default form submission
            submitHandler(this, true);
        });
    });
});