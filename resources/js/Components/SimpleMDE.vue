<template>
    <div class="simplemde-container">
        <textarea :id="id" ref="textarea"></textarea>
    </div>
</template>

<script>
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

export default {
    props: {
        modelValue: String,
        id: String,
        options: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update:modelValue'],
    data() {
        return {
            editor: null
        };
    },
    watch: {
        modelValue(newValue) {
            if (this.editor && newValue !== this.editor.value()) {
                this.editor.value(newValue);
            }
        }
    },
    mounted() {
        this.initializeEditor();
    },
    beforeUnmount() {
        this.destroyEditor();
    },
    methods: {
        initializeEditor() {
            const defaultOptions = {
                element: this.$refs.textarea,
                initialValue: this.modelValue,
                spellChecker: false,
                toolbar: [
                    'bold', 'italic', 'heading', '|',
                    'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ],
                status: false,
                autofocus: false,
                placeholder: 'Write your email here...',
                shortcuts: {
                    toggleSideBySide: null, // Disable side-by-side shortcut
                    toggleFullScreen: null  // Disable fullscreen shortcut
                }
            };

            const mergedOptions = { ...defaultOptions, ...this.options };
            this.editor = new EasyMDE(mergedOptions);

            this.editor.codemirror.on('change', () => {
                this.$emit('update:modelValue', this.editor.value());
            });
        },
        destroyEditor() {
            if (this.editor) {
                this.editor.toTextArea();
                this.editor = null;
            }
        },
        insertAtCursor(text) {
            if (!this.editor) return;

            const cm = this.editor.codemirror;
            const doc = cm.getDoc();
            const cursor = doc.getCursor();
            doc.replaceRange(text, cursor);
            cm.focus();
        }
    }
};
</script>

<style>
.simplemde-container .EasyMDEContainer {
    @apply rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
}

.simplemde-container .EasyMDEContainer .CodeMirror {
    @apply border-0;
}

.simplemde-container .EasyMDEContainer .editor-toolbar {
    @apply border-gray-300 bg-gray-50 rounded-t-md;
}

.simplemde-container .EasyMDEContainer .editor-toolbar button {
    @apply text-gray-600 hover:bg-gray-200 hover:border-gray-300;
}

.simplemde-container .EasyMDEContainer .editor-toolbar button.active {
    @apply bg-gray-200 border-gray-300;
}

.simplemde-container .EasyMDEContainer .CodeMirror-fullscreen,
.simplemde-container .EasyMDEContainer .editor-preview-side {
    @apply z-50;
}

.simplemde-container .EasyMDEContainer .CodeMirror {
    @apply min-h-[150px];
}
</style>
