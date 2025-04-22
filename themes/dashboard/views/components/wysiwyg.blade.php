<link rel="stylesheet" href="{{ url('/assets/plugins/simplemde/simplemde.min.css') }}">
<script src="{{ url('/assets/plugins/simplemde/simplemde.min.js') }}"></script>


<textarea id="template_editor" cols="30" rows="10"></textarea>

<script @nonce>
    var simplemde = new SimpleMDE({
        element: $("#template_editor")[0],
        toolbar: [{
                name: "bold",
                action: SimpleMDE.toggleBold,
                className: "fa fa-bold",
                title: "Bold",
            },
            {
                name: "italic",
                action: SimpleMDE.toggleItalic,
                className: "fa fa-italic",
                title: "Italic",
            },
            {
                name: "strikethrough",
                action: SimpleMDE.toggleStrikethrough,
                className: "fa fa-strikethrough",
                title: "Strikethrough",
            },
            {
                name: "heading",
                action: SimpleMDE.toggleHeadingSmaller,
                className: "fa fa-header",
                title: "Heading",
            },
            {
                name: "code",
                action: SimpleMDE.toggleCodeBlock,
                className: "fa fa-code",
                title: "Code",
            },
            "|",
            {
                name: "unordered-list",
                action: SimpleMDE.toggleBlockquote,
                className: "fa fa-list-ul",
                title: "Generic List",
            },
            {
                name: "uordered-list",
                action: SimpleMDE.toggleOrderedList,
                className: "fa fa-list-ol",
                title: "Numbered List",
            },
            {
                name: "clean-block",
                action: SimpleMDE.cleanBlock,
                className: "fa fa-eraser fa-clean-block",
                title: "Clean block",
            },
            "|",
            {
                name: "link",
                action: SimpleMDE.drawLink,
                className: "fa fa-link",
                title: "Create Link",
            },
            {
                name: "image",
                action: SimpleMDE.drawImage,
                className: "fa fa-picture-o",
                title: "Insert Image",
            },
            /*{
                    name: "table",
                    action: SimpleMDE.drawTable,
                    className: "fa fa-table",
                    title: "Insert Table",
            },*/
            {
                name: "horizontal-rule",
                action: SimpleMDE.drawHorizontalRule,
                className: "fa fa-minus",
                title: "Insert Horizontal Line",
            },
            "|",
            {
                name: "button-component",
                action: setButtonComponent,
                className: "fa fa-hand-pointer-o",
                title: "Button Component",
            },
            {
                name: "table-component",
                action: setTableComponent,
                className: "fa fa-table",
                title: "Table Component",
            },
            {
                name: "promotion-component",
                action: setPromotionComponent,
                className: "fa fa-bullhorn",
                title: "Promotion Component",
            },
            {
                name: "panel-component",
                action: setPanelComponent,
                className: "fa fa-thumb-tack",
                title: "Panel Component",
            },
            "|",
            {
                name: "side-by-side",
                action: SimpleMDE.toggleSideBySide,
                className: "fa fa-columns no-disable no-mobile",
                title: "Toggle Side by Side",
            },
            {
                name: "fullscreen",
                action: SimpleMDE.toggleFullScreen,
                className: "fa fa-arrows-alt no-disable no-mobile",
                title: "Toggle Fullscreen",
            },
            {
                name: "preview",
                action: SimpleMDE.togglePreview,
                className: "fa fa-eye no-disable",
                title: "Toggle Preview",
            },
        ],
        renderingConfig: {
            singleLineBreaks: false,
            codeSyntaxHighlighting: false,
        },
        hideIcons: ["guide"],
        spellChecker: false,
        promptURLs: true,
        placeholder: "Write your Beautiful Email",

    });

    function setButtonComponent(editor) {

        var cm = editor.codemirror;
        var output = '';
        var selectedText = cm.getSelection();
        var text = selectedText || 'Button Text';

        output = `
[component]: # ('mail::button',  ['url' => ''])
` + text + `
[endcomponent]: #
        `;
        cm.replaceSelection(output);

    }

    function setPromotionComponent(editor) {

        var cm = editor.codemirror;
        var output = '';
        var selectedText = cm.getSelection();
        var text = selectedText || 'Promotion Text';

        output = `
[component]: # ('mail::promotion')
` + text + `
[endcomponent]: #
        `;
        cm.replaceSelection(output);

    }

    function setPanelComponent(editor) {

        var cm = editor.codemirror;
        var output = '';
        var selectedText = cm.getSelection();
        var text = selectedText || 'Panel Text';

        output = `
[component]: # ('mail::panel')
` + text + `
[endcomponent]: #
        `;
        cm.replaceSelection(output);

    }

    function setTableComponent(editor) {

        var cm = editor.codemirror;
        var output = '';
        var selectedText = cm.getSelection();

        output = `
[component]: # ('mail::table')
| Laravel       | Table         | Example  |
| ------------- |:-------------:| --------:|
| Col 2 is      | Centered      | $10      |
| Col 3 is      | Right-Aligned | $20      |
[endcomponent]: #
        `;
        cm.replaceSelection(output);

    }
</script>
