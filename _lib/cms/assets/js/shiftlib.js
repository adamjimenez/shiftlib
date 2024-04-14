/*jshint esversion: 11 */

// form validation
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

// inline cms
class PageEditor {
    constructor() {
        this.pageData = JSON.parse(document.getElementById('pageData').textContent);

        const nav = document.createElement("div");
        nav.classList.add('sl-nav');
        nav.innerHTML = `
        <button type="button" sl-addpage>
        &plus;
        </button>

        <button type="button" sl-editPage>
        Edit
        </button>

        <button type="button" sl-renamePage>
        Rename
        </button>

        <button type="button" sl-deletePage>
        &#128465;
        </button>

        <button type="button" sl-savePage disabled>
        &#128190;
        </button>

        <button type="button" sl-publish>
        Publish
        </button>

        <div sl-uploads style="display: none;"></div>
        `;
        document.body.appendChild(nav);

        const style = document.createElement("style");
        style.textContent = `
        .sl-nav {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: #000;
        z-index: 10000;
        }

        .mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before {
        color: #ccc !important;
        }

        *[contentEditable="true"]:focus,
        *[contentEditable="true"]:hover {
        outline: 2px solid #1976D2;
        }

        *[sl-block], *[sl-type] {
        position: relative;
        }

        *[sl-block]:focus,
        *[sl-block]:hover,
        body[sl-editing] div[sl-type="upload"]:hover {
            border: 2px solid #1976D2;
        }
        
        body[sl-editing] div[sl-type="upload"] {
            min-height: 100px;
            min-width: 100px;
        }

        *[sl-block] .sl-menu {
        position: absolute;
        top: 0;
        }
        
        .sl-menu, .sl-choose {
            display: none;
        }
        
        *[sl-block]:hover .sl-menu,
        div[sl-type]:hover .sl-choose {
            display: block;
        }

        .sl-choose {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%)
        }

        *[sl-uploads] {
        position: fixed; /* Stay in place even when content scrolls */
        top: 20px; /* Margin from the top */
        left: 20px; /* Margin from the left */
        right: 20px; /* Margin from the right */
        bottom: 20px; /* Margin from the bottom */
        background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black background */
        width: calc(100% - 40px); /* Account for left and right margins */
        height: calc(100vh - 40px); /* Account for top and bottom margins and viewport height (vh) */
        z-index: 1000;
        }

        *[sl-uploads] img {
        margin: 10px;
        }
        
        *[sl-block]:first-child button[sl-move-up] {
          display: none;
        }
        
        button[sl-move-down] {
            transform: rotate(90deg);
            padding-bottom: 3px;
        }
        
        button[sl-move-up] {
            transform: rotate(-90deg);
            padding-bottom: 3px;
        }
        
        *[sl-block]:last-child button[sl-move-down] {
          display: none;
        }
        `;
        document.head.appendChild(style);

        this.addPageButton = document.querySelector('[sl-addpage]');
        this.editPageButton = document.querySelector('[sl-editpage]');
        this.renamePageButton = document.querySelector('[sl-renamepage]');
        this.deletePageButton = document.querySelector('[sl-deletepage]');
        this.savePageButton = document.querySelector('[sl-savepage]');
        this.publishPageButton = document.querySelector('[sl-publish]');

        // button handlers
        this.addPageButton.addEventListener('click',
            async () => {
                let name = prompt('Enter a page name');

                if (name) {
                    let formData = new FormData();
                    formData.append('name', name);

                    let pageName = this.pageData.base + '/' + this.strToPageName(name);

                    formData.append('page_name', pageName);

                    let url = '/_lib/api/v2/?cmd=save&section=cms%20pages';
                    let response = await fetch(url, {
                        method: 'post',
                        body: formData
                    });

                    let data = await response.json();

                    if (data.error) {
                        alert(data.error)
                    } else {
                        // redirect to page
                        location.href = '/' + pageName;
                    }
                }
            })

        this.editPageButton.addEventListener('click',
            async () => {
                document.querySelector('body').setAttribute('sl-editing', true);
                
                this.editPageButton.disabled = true;

                let headingConfig = {
                    menubar: false,
                    inline: true,
                    plugins: [
                        'lists',
                        'autolink'
                    ],
                    toolbar: 'undo redo | bold italic underline',
                    valid_elements: 'strong,em,span[style],a[href]',
                    valid_styles: {
                        '*': 'font-size,font-family,color,text-decoration,text-align'
                    },
                    license_key: 'gpl',
                    setup: (editor) => {
                        editor.on("change", (e) => {
                            let key = e.target.bodyElement.getAttribute('sl-name');
                            this.setValue(key, editor.getContent());
                        });
                    },
                };

                const copyConfig = {
                    menubar: false,
                    inline: true,
                    plugins: [
                        'link',
                        'lists',
                        'autolink',
                        'image',
                    ],
                    toolbar: [
                        'undo redo | bold italic underline | fontfamily fontsize',
                        'forecolor backcolor | alignleft aligncenter alignright alignfull | numlist bullist outdent indent | image'
                    ],
                    valid_elements: 'p[style],strong,em,span[style],a[href],ul,ol,li,img[longdesc|usemap|src|border|alt=|title|hspace|vspace|width|height|align]',
                    valid_styles: {
                        '*': 'font-size,font-family,color,text-decoration,text-align'
                    },
                    file_picker_callback: (cb) => {
                        this.chooseImage(cb);
                    },
                    license_key: 'gpl',
                    setup: (editor) => {
                        editor.on("change", (e) => {
                            let key = e.target.bodyElement.getAttribute('sl-name');
                            this.setValue(key, editor.getContent());
                        });
                    }
                };

                document.querySelectorAll('h1[sl-name], [sl-type="text"]').forEach((node) => {
                    headingConfig.target = node;
                    headingConfig.placeholder = node.getAttribute('sl-name');
                    tinymce.init({
                        ...headingConfig
                    });
                });

                document.querySelectorAll('div[sl-type="editor"]').forEach((node) => {
                    copyConfig.target = node;
                    copyConfig.placeholder = node.getAttribute('sl-name');
                    tinymce.init({
                        ...copyConfig
                    });
                });

                // image uploads
                this.uploadsEl = document.querySelector('[sl-uploads]');
                
                // close when clicking outside
                document.body.addEventListener("click", (event) => {
                    if (!this.uploadsEl.contains(event.target)) { // Check if clicked element is not a descendant of the overlay
                        this.uploadsEl.style.display = "none"; // Hide the overlay
                    }
                });

                this.uploadCallback = null;

                document.querySelectorAll('div[sl-type="upload"]').forEach((node) => {
                    node.style.position = 'relative';
                    
                    const buttonContainer = document.createElement("div");
                    buttonContainer.classList.add('sl-choose');
                    node.appendChild(buttonContainer);

                    const chooseButton = document.createElement("button");
                    chooseButton.innerText = 'Choose';

                    chooseButton.addEventListener('click', (event) => {
                        this.chooseImage(node);
                        event.stopPropagation();
                    })

                    buttonContainer.appendChild(chooseButton);

                    const clearButton = document.createElement("button");
                    clearButton.innerHTML = '&#10005;';

                    clearButton.addEventListener('click', (event) => {
                        let imgEl = node.querySelector('img');
                        if (!imgEl) {
                            return;
                        }
                        
                        // change child image
                        imgEl.src = '';
        
                        // change meta value
                        let name = node.getAttribute('sl-name');
                        this.setValue(name, '');
                    })

                    buttonContainer.appendChild(clearButton);
                });

                // blocks
                document.querySelectorAll('[sl-name]').forEach((node) => {
                    let name = node.getAttribute('sl-name');
                    if (name.endsWith(']')) {
                        node.setAttribute('sl-block', true);

                        const menu = document.createElement("div");
                        menu.classList.add('sl-menu');
                        menu.innerHTML = `
                        <button type="button" sl-move-up>&#10140;</button>
                        <button type="button" sl-move-down>&#10140;</button>
                        <button type="button" sl-duplicate-element style="font-weight: bold;">&plus;</button>
                        <button type="button" sl-delete-element>&#128465;</button>
                        `;

                        node.appendChild(menu);
                    }
                });
                
                document.body.addEventListener('click', (e) => {
                    // todo add handlers for duplicate and sort here
                    if (e.target.hasAttribute('sl-delete-element')) {
                        let confirmed = confirm('Delete this element?');

                        if (!confirmed) {
                            return;
                        }
                        
                        let node = e.target.closest('[sl-name]');
                        
                        this.updateProperty(node, (obj, arrayName, key) => {
                            let arr = obj[arrayName];
                            arr.splice(key, 1);
                        });
                        
                        node.remove();
                        
                        this.setDirty(true);

                    } else if (e.target.hasAttribute('sl-duplicate-element')) {
                        let node = e.target.closest('[sl-name]');
                        
                        this.updateProperty(node, (obj, arrayName, key) => {
                            let arr = obj[arrayName];
                            const firstPart = arr.slice(0, key + 1);
                            const duplicatedElement = arr[key];
                            const secondPart = arr.slice(key + 1);

                            obj[arrayName] = firstPart.concat(duplicatedElement, secondPart);
                        });

                        node.parentNode.insertBefore(node.cloneNode(true), node.nextSibling);
                        
                        this.setDirty(true);
                    
                    } else if (e.target.hasAttribute('sl-move-down')) {
                        let node = e.target.closest('[sl-name]');
                        
                        this.updateProperty(node, (obj, arrayName, key) => {
                            let arr = obj[arrayName];
                            const item = arr.splice(key, 1)[0];
                            arr.splice(key + 1, 0, item);
                        });

                        node.parentNode.insertBefore(node, node.nextElementSibling.nextSibling);
                        
                        this.setDirty(true);
                    } else if (e.target.hasAttribute('sl-move-up')) {
                        let node = e.target.closest('[sl-name]');
                        
                        this.updateProperty(node, (obj, arrayName, key) => {
                            let arr = obj[arrayName];
                            const item = arr.splice(key, 1)[0];
                            arr.splice(key - 1, 0, item);
                        });
                        
                        node.parentNode.insertBefore(node, node.previousElementSibling);
                        
                        this.setDirty(true);
                    }
                });

            })

        this.renamePageButton.addEventListener('click',
            async () => {
                let name = prompt('Enter a page name', this.pageData.page.name);

                if (name) {
                    let formData = new FormData();
                    formData.append('name', name);

                    let pageName = this.pageData.base + '/' + this.strToPageName(name);

                    formData.append('page_name', pageName);

                    let params = new URLSearchParams();
                    params.append('cmd', 'save');
                    params.append('section', 'cms pages');
                    params.append('id', this.pageData.page.id);
                    params.append('fields[]', 'page_name');
                    params.append('fields[]', 'name');

                    let url = '/_lib/api/v2/?' + params.toString();
                    let response = await fetch(url, {
                        method: 'post',
                        body: formData
                    });

                    let data = await response.json();

                    console.log(data)

                    if (data.error) {
                        alert(data.error)
                    } else {
                        // redirect to page
                        location.href = '/' + pageName;
                    }
                }
            })

        this.publishPageButton.addEventListener('click',
            async () => {
                let confirmed = confirm(this.publishPageButton.innerText + '?');

                if (!confirmed) {
                    return;
                }

                let formData = new FormData();
                formData.append('published', parseInt(this.pageData.page.published) ? 0: 1);

                let params = new URLSearchParams();
                params.append('cmd', 'save');
                params.append('section', 'cms pages');
                params.append('id', this.pageData.page.id);
                params.append('fields[]', 'published');

                let url = '/_lib/api/v2/?' + params.toString();
                let response = await fetch(url, {
                    method: 'post',
                    body: formData
                });

                let data = await response.json();

                console.log(data)

                if (data.error) {
                    alert(data.error)
                } else {
                    // redirect to page
                    location.reload();
                }
            })

        this.deletePageButton.addEventListener('click',
            async () => {
                let result = confirm('Delete the page?');

                if (result) {
                    let url = '/_lib/api/v2/?cmd=delete&section=cms%20pages';

                    let formData = new FormData();
                    formData.append('ids[]', this.pageData.page.id);

                    let response = await fetch(url, {
                        method: 'post',
                        body: formData
                    });

                    let data = await response.json();

                    if (data.error) {
                        alert(data.error)
                    } else {
                        // redirect to page
                        location.href = '/' + this.pageData.base;
                    }
                }
            })

        this.savePageButton.addEventListener('click',
            async () => {
                let formData = new FormData();

                for (const [name, value] of Object.entries(this.pageData.page)) {
                    formData.append(name, value);
                }

                formData.append('meta', JSON.stringify(this.meta));

                let url = '/_lib/api/v2/?cmd=save&section=cms%20pages&id=' + this.pageData.page.id;
                let response = await fetch(url, {
                    method: 'post',
                    body: formData
                });

                let data = await response.json();

                if (data.error) {
                    alert(data.error)
                    return;
                }

                this.setDirty(false);
            })

        this.meta = this.pageData.content;

        if (!this.pageData.catcher) {
            this.addPageButton.style.display = "none";
            this.renamePageButton.style.display = "none";
            this.deletePageButton.style.display = "none";
        }

        if (parseInt(this.pageData.page.published)) {
            this.publishPageButton.innerText = 'Unpublish';
        }
    }
    
    chooseImage(target) {
        this.uploadCallback = target;
        this.uploadsEl.style.display = '';
        this.fetchImages();
    }
    
    updateProperty(node, cb) {
        let name = node.getAttribute('sl-name');
        
        let parts = name.split('.');
        let obj = this.meta;
        parts.forEach((part, index) => {
            const regex = /^(.+?)\[(\d+)\]$/;
            const match = part.match(regex);

            if (match) {
                const arrayName = match[1]; // "slides"
                const key = parseInt(match[2]); // 0 (as a number)

                let arr = obj[arrayName];

                if (index + 1 == parts.length) {
                    cb(obj, arrayName, key);
                }

                obj = arr;
            } else {
                obj = obj[part];
            }
        })
    }

    /*
		async fetchData() {
			// get data
			let url = '/_lib/api/v2/?section=cms_pages&fields[page_name]=' + this.pageData.request;
			let response = await fetch(url);

			let data = await response.json();
			console.log(data)

			if (!data.data[0].id) {
				this.editPageButton.disabled = true;
				this.renamePageButton.disabled = true;
				this.deletePageButton.disabled = true;
				this.setDirty(false);
			} else {
				this.data = data.data[0];
				this.meta = this.data.meta ? JSON.parse(this.data.meta): {};
				console.log(this.meta)
			}
			this.meta = this.pageData.content;
		}
		*/

    async fetchImages() {
        // get data
        let url = '/_lib/api/v2/?cmd=uploads';
        let response = await fetch(url);

        let data = await response.json();

        this.uploadsEl.innerHTML = '';
        
        let labelEl = document.createElement('label');
        labelEl.innerText = 'Choose file';
        this.uploadsEl.appendChild(labelEl);
        
        let fileEl = document.createElement('input');
        fileEl.setAttribute('type', 'file');
        fileEl.style.display = 'none';
        labelEl.appendChild(fileEl);
        
        fileEl.addEventListener('change', async () => {
            const formData = new FormData();

            console.log('File details:', fileEl);
            formData.append('file', fileEl.files[0]);

            const result = await fetch('/_lib/api/v2/?cmd=uploads&path=', {
                method: 'post',
                body: formData
            });
            
            let data = await result.json();
            
            this.selectFile(data.file);
        })
        
        data.items.forEach((item) => {
            if (!item.thumb) {
                return;
            }

            let node = document.createElement('img');
            node.src = item.thumb;
            node.addEventListener('click', () => {
                this.selectFile(item.id);
            });

            this.uploadsEl.appendChild(node);
        });
    }
    
    selectFile(id) {
        const filepath = id ? '/uploads/' + id : '';
        
        if (typeof this.uploadCallback === 'function') {
            this.uploadCallback(filepath, { title: filepath });
        } else {
            // change child image
            let imageEl = this.uploadCallback.querySelector('img');
            
            if (!imageEl) {
                imageEl = document.createElement('img');
                this.uploadCallback.appendChild(imageEl);
            }
            
            imageEl.src = filepath;

            // change meta value
            let name = this.uploadCallback.getAttribute('sl-name');
            this.setValue(name, id);
        }

        this.uploadsEl.style.display = 'none';
    }

    setValue(keyStr, value) {
        // find nested object
        let parts = keyStr.split('.');
        let obj = this.meta;

        parts.forEach((part, index) => {
            const regex = /^(.+?)\[(\d+)\]$/;
            const match = part.match(regex);

            if (match) {
                const arrayName = match[1]; // "slides"
                const key = parseInt(match[2]); // 0 (as a number)

                if (typeof obj[arrayName] === 'undefined') {
                    obj[arrayName] = [];
                }

                if (typeof obj[arrayName][key] === 'undefined') {
                    obj[arrayName][key] = {};
                }

                obj = obj[arrayName][key];
            } else {
                if (typeof obj[part] === 'undefined') {
                    obj[part] = {};
                }

                if (index + 1 == parts.length) {
                    // set value
                    obj[part] = value;
                }

                obj = obj[part];
            }
                
        })

        this.setDirty(true);
    }

    setDirty(dirty) {
        this.savePageButton.disabled = !dirty;
    }

    strToPageName(pageName) {
        // Remove odd characters (except whitespace, A-Z, a-z, 0-9, ., -, /, and ())
        pageName = pageName.toLowerCase().replace(/[^a-zA-Z0-9\s\.\-\/\(\)]/g,
            "");

        // Replace ">" with "/"
        pageName = pageName.replace(/>/g,
            "/");

        // Trim leading and trailing whitespace
        pageName = pageName.trim();

        // Replace spaces with dashes
        pageName = pageName.replace(/\s+/g,
            "-");

        // Remove trailing dot (.)
        pageName = pageName.replace(/\.$/,
            "");

        return pageName;
    }
}

window.addEventListener('DOMContentLoaded', function() {
    new PageEditor();
});