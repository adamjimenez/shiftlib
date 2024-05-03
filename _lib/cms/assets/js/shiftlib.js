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
        
        if (!data.success && data !== 1) {
            // display errors
            let errors = data.errors ? data.errors: data;

            errors?.forEach(function(item) {
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
            
            // scroll to first error
            const firstError = document.querySelector('.sl-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

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
        let pageDataEl = document.getElementById('pageData');
        this.pageData = pageDataEl ? JSON.parse(pageDataEl.textContent) : {};
        
        let editable = document.querySelectorAll('[sl-id], [sl-name], [sl-block]');
        
        if (!editable.length && !this.pageData?.catcher) {
            return;
        }

        const nav = document.createElement("div");
        nav.classList.add('sl-nav');
        nav.innerHTML = `
        <button type="button" class="icon" sl-addpage style="display: none;">
        &plus;
        </button>

        <button type="button" class="icon" sl-editPage>
        &#128295;
        </button>

        <button type="button" class="icon" sl-cancelPage style="display: none;">
        <div style="transform: rotate(180deg);">&#10140;</div>
        </button>

        <button type="button" class="icon" sl-renamePage style="display: none;">
        &#9998;
        </button>

        <button type="button" class="icon" sl-deletePage style="display: none;">
        &#128465;
        </button>

        <button type="button" class="icon" sl-savePage disabled style="display: none;">
        &#128190;
        </button>

        <button type="button" class="icon" sl-publish style="display: none;">
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
        z-index: 10000;
        padding: 5px;
        }
        
        [sl-editing] {
            margin-top: 60px;
        }
        
        [sl-editing] .sl-nav {
            background: #000;
        }
        
        .sl-nav button {
          border: none;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 16px;
          padding: 16px;
          border-radius: 50px;
          opacity: 0.9;
        }
        
        .sl-nav button:not([disabled]):hover {
            opacity: 1;
            background: #666 !important;
        }
        
        .sl-nav button[disabled] {
            opacity: 0.5;
        }
        
        .sl-nav button.icon {
            background: transparent; color: #fff;
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
        [sl-editing] div[sl-type="upload"]:hover {
            border: 2px solid #1976D2;
        }
        
        [sl-editing] div[sl-type="upload"] {
            min-height: 100px;
            min-width: 100px;
        }
        
        [sl-editing] [sl-cancelpage],
        [sl-editing] [sl-savePage]
        {
            display: inline-block !important;
        }
        
        .sl-nav button.icon[sl-editpage] {
            background-color: #000;
            position: fixed;
            bottom: 10px;
            left: 10px;
        }
        
        [sl-editing] [sl-editpage] {
            display: none !important;
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
        overflow: auto;
        }

        *[sl-uploads] img {
        margin: 10px;
        }

        *[sl-uploads] .folder {
        margin: 10px;
        cursor: pointer;
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
        
        /* carousel fix */
        .cdxcarousel-item {
            min-height: 235px;
            height: auto;
        }
        `;
        document.head.appendChild(style);

        this.addPageButton = document.querySelector('[sl-addpage]');
        this.editPageButton = document.querySelector('[sl-editpage]');
        this.cancelButton = document.querySelector('[sl-cancelpage]');
        this.renamePageButton = document.querySelector('[sl-renamepage]');
        this.deletePageButton = document.querySelector('[sl-deletepage]');
        this.savePageButton = document.querySelector('[sl-savepage]');
        this.publishPageButton = document.querySelector('[sl-publish]');
        
        this.editors = [];
        this.blockEditors = [];
        this.dirty = false;

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
        
        this.cancelButton.addEventListener('click', () => {
            document.querySelector('body').removeAttribute('sl-editing');
            this.editPageButton.disabled = false;

            this.addPageButton.style.display = "none";
            this.renamePageButton.style.display = "none";
            this.deletePageButton.style.display = "none";
            this.publishPageButton.style.display = "none";
            
            // destroy tinymces
            this.editors.forEach(editor => {
                editor.remove();
            });
            this.editors = [];
            
            // destroy editorjs
            this.blockEditors.forEach(editor => {
                console.log(editor)
                editor.destroy();
            });
            this.blockEditors = [];
            
            // remove elements
            document.querySelectorAll('.sl-choose, .sl-menu').forEach(node => {
                node.remove();
            });
            
            // unregister event listeners
            document.body.removeEventListener('click', e => this.clickHandler(e));
            window.removeEventListener('beforeunload', this.checkChanges);
            this.cancelButton.disabled = true;
            
            this.editData = {};
            
            this.loadSavePoint();
            
            // needed for editorjs
            location.reload();
        });
        this.cancelButton.disabled = true;

        this.editPageButton.addEventListener('click',
            async () => {
                document.querySelector('body').setAttribute('sl-editing', true);
                
                this.editPageButton.disabled = true;

                if (this.pageData.catcher) {
                    this.addPageButton.style.display = "";
                    
                    if (this.pageData?.page?.name) {
                        this.renamePageButton.style.display = "";
                        this.deletePageButton.style.display = "";
                        this.publishPageButton.style.display = "";
                    }
                }

                let textConfig = {
                    menubar: false,
                    inline: true,
                    toolbar: 'undo redo',
                    valid_elements: '',
                    valid_styles: {},
                    license_key: 'gpl',
                    setup: (editor) => {
                        editor.on("change", e => { this.editorChangeHandler(e, editor); });
                    },
                };

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
                        editor.on("change", e => { this.editorChangeHandler(e, editor); });
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
                        'forecolor backcolor | alignleft aligncenter alignright alignfull | numlist bullist outdent indent | hr link image forecolor backcolor'
                    ],
                    valid_elements: 'p[style],strong,em,span[style],a[href],ul,ol,li,img[longdesc|usemap|src|border|alt=|title|hspace|vspace|width|height|align]',
                    valid_styles: {
                        '*': 'font-size,font-family,color,text-decoration,text-align'
                    },
                    file_picker_callback: cb => {
                        this.chooseImage(cb);
                    },
                    license_key: 'gpl',
                    setup: (editor) => {
                        editor.on("change", e => { this.editorChangeHandler(e, editor); });
                    },
                    relative_urls: false,
                    remove_script_host: true
                };

                document.querySelectorAll('[sl-type="text"]').forEach(async node => {
                    textConfig.target = node;
                    textConfig.placeholder = this.getFieldName(node);
                    let editor = await tinymce.init({
                        ...textConfig
                    });
                    this.editors.push(editor[0]);
                });

                document.querySelectorAll('[sl-type="heading"]').forEach(async node => {
                    headingConfig.target = node;
                    headingConfig.placeholder = this.getFieldName(node);
                    let editor = await tinymce.init({
                        ...headingConfig
                    });
                    this.editors.push(editor[0]);
                });

                document.querySelectorAll('[sl-type="editor"]').forEach(async node => {
                    copyConfig.target = node;
                    copyConfig.placeholder = this.getFieldName(node);
                    let editor = await tinymce.init({
                        ...copyConfig
                    });
                    this.editors.push(editor[0]);
                });

                document.querySelectorAll('[sl-type="block"]').forEach(async node => {
            		let fieldName = this.getFieldName(node);
            		
            		node.innerHTML = '';
            		
            		let tools = {
        				header: window.Header,
        				list: window.List,
        				image: {
                            class: window.ImageTool,
                            config: {
                                endpoints: {
                                    byFile: '/_lib/api/v2/?cmd=uploads',
                                }
                            }
                        },
        				carousel: {
                            class: window.Carousel,
                            config: {
                                endpoints: {
                                    byFile: '/_lib/api/v2/?cmd=uploads',
                                }
                            }
                        },
        			};
        			
        			this.pageData.block_tools.forEach(item => {
        			    tools[item.name.toLowerCase()] = {
        			        class: window[item.name],
        			        config: item.config
        			    }
        			});
            		
            		let blockEditor = new EditorJS({
            			holder: node,
            			tools: tools,
            			data: this.meta[fieldName] ? this.meta[fieldName] : {},
                        onChange: (editor) => {
                    		editor.saver.save().then(async (outputData) => {
                                this.setValue(fieldName, outputData, this.meta);
                    		}).catch((error) => {
                    			console.log('Saving failed: ', error)
                    		});
                        }
            		});
            		
            		this.blockEditors.push(blockEditor);
                });

                // image uploads
                this.uploadsEl = document.querySelector('[sl-uploads]');
                
                this.uploadCallback = null;

                document.querySelectorAll('[sl-type="upload"]').forEach(node => {
                    node.style.position = 'relative';
                    
                    const buttonContainer = document.createElement("div");
                    buttonContainer.classList.add('sl-choose');
                    node.appendChild(buttonContainer);

                    const chooseButton = document.createElement("button");
                    chooseButton.innerText = 'Choose';

                    chooseButton.addEventListener('click', event => {
                        this.chooseImage(node);
                        event.stopPropagation();
                    })

                    buttonContainer.appendChild(chooseButton);

                    const clearButton = document.createElement("button");
                    clearButton.innerHTML = '&#10005;';

                    clearButton.addEventListener('click', event => {
                        let imgEl = node.querySelector('img');
                        if (!imgEl) {
                            return;
                        }
                        
                        // change child image
                        imgEl.src = '';
        
                        // change meta value
                        let name = node.getAttribute('sl-name');
                        this.setValue(name, '', this.meta);
                    })

                    buttonContainer.appendChild(clearButton);
                });

                // blocks
                document.querySelectorAll('[sl-name]').forEach(node => {
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
                
                document.body.addEventListener('click', e => this.clickHandler(e));
                
                window.addEventListener('beforeunload', e => this.checkChanges(e));
                
                this.cancelButton.disabled = false;
                
                this.setSavePoint();
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
                console.log(this.editData);
                
                let result;
                
                for (const [section, value] of Object.entries(this.editData)) {
                    if (Array.isArray(value)) {
                        value.forEach(async (item, id) => {
                            result = await this.saveData(section, id, item);    
                        });
                    } else {
                        result = await this.saveData(section, 1, value);
                    }
                }
                
                if (this.meta) {
                    let formData = new FormData();
    
                    for (const [name, value] of Object.entries(this.pageData.page)) {
                        formData.append(name, value);
                    }
    
                    formData.append('meta', JSON.stringify(this.meta));
    
                    let url = '/_lib/api/v2/?cmd=save&section=cms%20pages&id=' + this.pageData.page?.id;
                    let response = await fetch(url, {
                        method: 'post',
                        body: formData
                    });
    
                    let data = await response.json();
    
                    if (data.error) {
                        alert(data.error)
                        return;
                    }
                }

                if (result !== false)
                    this.setDirty(false);
                    
                this.setSavePoint();
            })

        this.meta = this.pageData.meta ? this.pageData.meta : {};
        this.editData = {};
        this.savePointData = {};

        if (!this.pageData.page) {
            this.publishPageButton.style.display = "none";
        } else if (parseInt(this.pageData.page.published)) {
            this.publishPageButton.innerText = 'Unpublish';
        }
    }
    
    setSavePoint() {
        document.querySelectorAll('[sl-type="heading"],[sl-type="editor"],[sl-type="text"]').forEach(node => {
            this.savePointData[this.getFieldName(node)] = node.innerHTML;
        });
        
        document.querySelectorAll('[sl-type="upload"]').forEach(node => {
            let imageEl = node.querySelector('img');
            
            if (imageEl) {
                this.savePointData[this.getFieldName(node)] = imageEl.src;
            }
        });
    }
    
    loadSavePoint() {
        document.querySelectorAll('[sl-type="heading"],[sl-type="editor"],[sl-type="text"]').forEach(node => {
                node.innerHTML = this.savePointData[this.getFieldName(node)];
        });
        
        document.querySelectorAll('[sl-type="upload"]').forEach(node => {
            let imageEl = node.querySelector('img');
            
            if (imageEl) {
                 imageEl.src = this.savePointData[this.getFieldName(node)];
            }
        });
        
        this.dirty = false;
    }
    
    async saveData(section, id, data) {
        let formData = new FormData();

        let params = new URLSearchParams();
        params.append('cmd', 'save');
        params.append('section', section);
        params.append('id', id);

        for (const [name, value] of Object.entries(data)) {
            formData.append(name, value);
            params.append('fields[]', name);
        }

        let url = '/_lib/api/v2/?' + params.toString();

        let response = await fetch(url, {
            method: 'post',
            body: formData
        });

        let result = await response.json();

        if (result.error) {
            alert(result.error)
            return false;
        }
    }
    
    getFieldName(node) {
        let name = node.getAttribute('sl-name');
        
        if (!name) {
            name = node.getAttribute('sl-id');
        }
        
        return name;
    }
    
    editorChangeHandler(e, editor) {
        let node = e.target.bodyElement;
        let name = node.getAttribute('sl-name');
        let obj = this.meta;
        
        if (!name) {
            name = node.getAttribute('sl-id');
            obj = this.editData;
        }
        
        this.setValue(name, editor.getContent(), obj);
    }
    
    checkChanges(e) {
        if (this.dirty) {
            e.preventDefault();
            return "Are you sure you want to leave? You have unsaved changes.";
        }
    }
    
    clickHandler(e) {
        // close when clicking outside
        if (!this.uploadsEl.contains(e.target)) { // Check if clicked element is not a descendant of the overlay
            this.uploadsEl.style.display = "none"; // Hide the overlay
        }
        
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

    async fetchImages(path) {
        // get data
        let url = '/_lib/api/v2/?cmd=uploads&path=' + (path ? path : '');
        let response = await fetch(url);

        this.uploadsEl.innerHTML = '<h1>Loading..</h1>';
        
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
            let node;
            
            if (!item.leaf) {
                node = document.createElement('div');
                node.innerText = item.name;
                node.classList.add("folder");
                
                node.addEventListener('click', () => {
                    this.openFolder(item.id);
                });
            } else if (item.thumb) {
                node = document.createElement('img');
                node.src = item.thumb;
                node.addEventListener('click', () => {
                    this.selectFile(item.id);
                });
            } else {
                return;
            }

            this.uploadsEl.appendChild(node);
        });
    }
    
    openFolder(path) {
        this.fetchImages(path);
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
            this.setValue(name, id, this.meta);
        }

        this.uploadsEl.style.display = 'none';
    }

    setValue(keyStr, value, obj) {
        // find nested object
        let parts = keyStr.split('.');

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
        this.dirty = dirty;
    }

    strToPageName(pageName) {
        // Remove odd characters (except whitespace, A-Z, a-z, 0-9, ., -, /, and ())
        pageName = pageName.toLowerCase().replace(/[^a-zA-Z0-9\s\.\-\/\(\)]/g, "");

        // Replace ">" with "/"
        pageName = pageName.replace(/>/g, "/");

        // Trim leading and trailing whitespace
        pageName = pageName.trim();

        // Replace spaces with dashes
        pageName = pageName.replace(/\s+/g, "-");

        // Remove trailing dot (.)
        pageName = pageName.replace(/\.$/, "");

        return pageName;
    }
}