{{-- Simple MDE - Markdown Editor --}}
@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <textarea
        name="{{ $field['name'] }}"
        data-upload-enabled="{{ isset($field['withFiles']) || isset($field['withMedia']) || isset($field['imageUploadEndpoint']) ? 'true' : 'false'}}"
        data-upload-endpoint="{{ isset($field['imageUploadEndpoint']) ? $field['imageUploadEndpoint'] : 'false'}}"
        data-upload-operation="{{ $crud->get('ajax-upload.formOperation') }}"
        data-init-function="bpFieldInitEasyMdeElement"
        bp-field-main-input
        data-easymdeAttributesRaw="{{ isset($field['easymdeAttributesRaw']) ? "{".$field['easymdeAttributesRaw']."}" : "{}" }}"
        data-easymdeAttributes="{{ isset($field['easymdeAttributes']) ? json_encode($field['easymdeAttributes']) : "{}" }}"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control'])
    	>{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}</textarea>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')


{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
        {{-- Font Awesome CSS loads fonts via @font-face --}}
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
        <link href="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.css" rel="stylesheet" crossorigin="anonymous">
        @bassetBlock('backpack/pro/fields/easymde-field.css')
        <style type="text/css">
            .editor-toolbar {
                border: 1px solid #ddd;
                border-bottom: none;
            }
        </style>
        @endBassetBlock
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        <script src="https://cdn.jsdelivr.net/npm/easymde@2.18.0/dist/easymde.min.js" crossorigin="anonymous"></script>
        @bassetBlock('backpack/pro/fields/easymde-field.js')
        <script>
            function bpFieldInitEasyMdeElement(element) {
                if (element.attr('data-initialized') == 'true') {
                    return;
                }

                if (typeof element.attr('id') == 'undefined') {
                    element.attr('id', 'EasyMDE_'+Math.ceil(Math.random() * 1000000));
                }

                var elementId = element.attr('id');
                var easymdeAttributes = JSON.parse(element.attr('data-easymdeAttributes'));
                var easymdeAttributesRaw = JSON.parse(element.attr('data-easymdeAttributesRaw'));

                if(element.data('upload-enabled') === true){
                    let imageUploadEndpoint =  element.data('upload-endpoint') !== false ? element.data('upload-endpoint') : '{{ url($crud->route. '/ajax-upload') }}';
                    let imageUploadFunction = function (file, onSuccess, onError) {
                        var self = this;

                        onSuccessSup = function onSuccessSup(imageUrl) {
                            element.parent().removeClass('text-danger');
                            element.parent().find('.invalid-feedback').remove();
                            // add a new line before the image only if the editor content is not empty
                            if (self.codemirror.getValue().length > 0) {
                                self.codemirror.replaceSelection('\n');
                            }
                           
                            onSuccess(imageUrl);
                            // add a new line after the image
                            self.codemirror.replaceSelection('\n');
                        };

                        function humanFileSize(bytes, units) {
                            if (Math.abs(bytes) < 1024) {
                                return '' + bytes + units[0];
                            }
                            var u = 0;
                            do {
                                bytes /= 1024;
                                ++u;
                            } while (Math.abs(bytes) >= 1024 && u < units.length);
                            return '' + bytes.toFixed(1) + units[u];
                        }

                        function onErrorSup(errorMessage) {
                            // show error on status bar and reset after 10000ms
                            self.updateStatusBar('upload-image', errorMessage);

                            setTimeout(function () {
                                self.updateStatusBar('upload-image', self.options.imageTexts.sbInit);
                            }, 10000);

                            element.parent().removeClass('text-danger');
                            element.parent().find('.invalid-feedback').remove();

                            // create the error message container
                            let errorContainer = document.createElement("div");
                            errorContainer.classList.add('invalid-feedback', 'd-block');
                            errorContainer.innerHTML = errorMessage;
                            element.parent().append(errorContainer);
                            // add the red text classes
                            element.parent().addClass('text-danger');
                        }

                        function fillErrorMessage(errorMessage) {
                            let units = self.options.imageTexts.sizeUnits.split(',');
                            return errorMessage
                                .replace('#image_name#', file.name)
                                .replace('#image_size#', humanFileSize(file.size, units))
                                .replace('#image_max_size#', humanFileSize(self.options.imageMaxSize, units));
                        }

                        if (file.size > this.options.imageMaxSize) {
                            onErrorSup(fillErrorMessage(this.options.errorMessages.fileTooLarge));
                            return;
                        }

                        let paramName = typeof element.attr('data-repeatable-input-name') !== 'undefined' ? element.closest('[data-repeatable-identifier]').attr('data-repeatable-identifier')+'#'+element.attr('data-repeatable-input-name') : element.attr('name')
                        let formData = new FormData();
                        formData.append(paramName, file);
                        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                        formData.append('fieldName', paramName);
                        formData.append('operation', element.attr('data-upload-operation'));

                        var request = new XMLHttpRequest();
                        
                        request.upload.onprogress = function (event) {
                            if (event.lengthComputable) {
                                let progress = '' + Math.round((event.loaded * 100) / event.total);
                                self.updateStatusBar('upload-image', self.options.imageTexts.sbProgress.replace('#file_name#', file.name).replace('#progress#', progress));
                            }
                        };
                        request.open('POST', this.options.imageUploadEndpoint);
                        request.setRequestHeader('Accept', 'application/json');

                        request.onload = function () {
                            try {
                                var response = JSON.parse(this.responseText);
                            } catch (error) {
                                console.error('EasyMDE: The server did not return a valid json.');
                                onErrorSup(fillErrorMessage(self.options.errorMessages.importError));
                                return;
                            }
                            if (this.status === 200 && response && !response.errors && response.data && response.data.filePath) {
                                onSuccessSup((self.options.imagePathAbsolute ? '' : (window.location.origin + '/')) + response.data.filePath);
                            } else {
                                if (response.errors && response.errors in self.options.errorMessages) {  // preformatted error message
                                    // the the first response.errors key

                                    onErrorSup(fillErrorMessage(self.options.errorMessages[response.errors]));
                                } else if (response.message) {  // server side generated error message
                                    onErrorSup(fillErrorMessage(response.message));
                                } else {  //unknown error
                                    console.error('EasyMDE: Received an unexpected response after uploading the image.'
                                        + this.status + ' (' + this.statusText + ')');
                                    onErrorSup(fillErrorMessage(self.options.errorMessages.importError));
                                }
                            }
                        };

                        request.onerror = function (event) {
                            console.error('EasyMDE: An unexpected error occurred when trying to upload the image.'
                                + event.target.status + ' (' + event.target.statusText + ')');
                            onErrorSup(self.options.errorMessages.importError);
                        };

                        request.send(formData);

                    };

                    let additionalAttributes = {
                        "uploadImage": true, // boolean: enables image upload
                        "imagePathAbsolute": true, // boolean: `false` will prepend window.location.origin to it.
                        "imageUploadFunction":imageUploadFunction,
                        "imageUploadEndpoint": imageUploadEndpoint,// one can pass custom endpoint for image upload
                    }
                    easymdeAttributes = { ...easymdeAttributes, ...additionalAttributes };
                }

                let configurationObject = {
                    element: document.getElementById(elementId),
                    autoDownloadFontAwesome: false
                };

                configurationObject = Object.assign(configurationObject, easymdeAttributes, easymdeAttributesRaw);

                if (!document.getElementById(elementId)) {
                    return;
                }

                let easyMDE = new EasyMDE(configurationObject);

                easyMDE.options.minHeight = easyMDE.options.minHeight || "300px";
                easyMDE.codemirror.getScrollerElement().style.minHeight = easyMDE.options.minHeight;

                // update the original textarea on keypress
                easyMDE.codemirror.on("change", function(){
                    element.val(easyMDE.value()).trigger('change');
                });

                element.on('CrudField:disable', function(e) {
                    element.parent().find('div.editor-toolbar').first().hide()
                    easyMDE.togglePreview(easyMDE);
                });

                element.on('CrudField:enable', function(e) {
                    element.parent().find('div.editor-toolbar').first().show()
                    easyMDE.togglePreview(easyMDE);
                });

                $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                    setTimeout(function() { easyMDE.codemirror.refresh(); }, 10);
                });
            }
        </script>
        @endBassetBlock
    @endpush

{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}
