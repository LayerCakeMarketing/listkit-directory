<template>
    <div class="rich-text-editor">
        <div v-if="editor" class="editor-toolbar">
            <button
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'is-active': editor.isActive('bold') }"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                    <path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'is-active': editor.isActive('italic') }"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="4" x2="10" y2="4"></line>
                    <line x1="14" y1="20" x2="5" y2="20"></line>
                    <line x1="15" y1="4" x2="9" y2="20"></line>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <polyline points="6 22 6 15 18 15 18 22"></polyline>
                    <line x1="12" y1="2" x2="12" y2="15"></line>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'is-active': editor.isActive('bulletList') }"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'is-active': editor.isActive('orderedList') }"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="10" y1="6" x2="21" y2="6"></line>
                    <line x1="10" y1="12" x2="21" y2="12"></line>
                    <line x1="10" y1="18" x2="21" y2="18"></line>
                    <path d="M4 6h1v4"></path>
                    <path d="M4 10h2"></path>
                    <path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().setHorizontalRule().run()"
                class="toolbar-button"
                type="button"
                :disabled="showHtmlEditor"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo() || showHtmlEditor"
                class="toolbar-button"
                type="button"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </button>
            <button
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo() || showHtmlEditor"
                class="toolbar-button"
                type="button"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
            </button>
            
            <div class="toolbar-separator"></div>
            
            <!-- Code/HTML View Toggle -->
            <button
                @click="toggleHtmlView"
                :class="{ 'is-active': showHtmlEditor }"
                class="toolbar-button"
                type="button"
                title="Toggle HTML/Code View"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 18 22 12 16 6"></polyline>
                    <polyline points="8 6 2 12 8 18"></polyline>
                </svg>
            </button>
        </div>
        
        <!-- Rich Text Editor -->
        <EditorContent v-if="!showHtmlEditor" :editor="editor" />
        
        <!-- HTML/Code Editor -->
        <div v-if="showHtmlEditor" class="html-editor-container">
            <textarea
                v-model="htmlContent"
                @input="updateFromHtml"
                class="html-editor"
                placeholder="Enter HTML content..."
                spellcheck="false"
            ></textarea>
        </div>
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount, ref } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Write your description here...'
    }
})

const emit = defineEmits(['update:modelValue'])

// State for HTML view
const showHtmlEditor = ref(false)
const htmlContent = ref(props.modelValue || '')

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    onUpdate: ({ editor }) => {
        const content = editor.getHTML()
        emit('update:modelValue', content)
        htmlContent.value = content
    },
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[150px] p-4',
        },
    },
})

// Toggle HTML view
const toggleHtmlView = () => {
    showHtmlEditor.value = !showHtmlEditor.value
    if (!showHtmlEditor.value) {
        // Switching back to rich text editor
        editor.value.commands.setContent(htmlContent.value, false)
    }
}

// Update from HTML editor
const updateFromHtml = () => {
    emit('update:modelValue', htmlContent.value)
}

// Watch for external changes to modelValue
watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value || '', false)
        htmlContent.value = value || ''
    }
})

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy()
    }
})
</script>

<style scoped>
.rich-text-editor {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    overflow: hidden;
}

.editor-toolbar {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.5rem;
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.toolbar-button {
    padding: 0.375rem;
    border-radius: 0.25rem;
    border: 1px solid transparent;
    background-color: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}

.toolbar-button:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.toolbar-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toolbar-button.is-active {
    background-color: #3b82f6;
    color: white;
}

.toolbar-separator {
    width: 1px;
    height: 24px;
    background-color: #e5e7eb;
    margin: 0 0.5rem;
    align-self: center;
}

/* HTML Editor Styles */
.html-editor-container {
    position: relative;
}

.html-editor {
    width: 100%;
    min-height: 200px;
    padding: 1rem;
    font-family: 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #1f2937;
    background-color: #f9fafb;
    border: none;
    resize: vertical;
    outline: none;
}

.html-editor:focus {
    background-color: #ffffff;
}

/* Override Tiptap's default styles */
:deep(.ProseMirror) {
    min-height: 150px;
    padding: 1rem;
    font-size: 1.0625rem;
    line-height: 1.75;
    color: #374151;
}

/* Paragraphs */
:deep(.ProseMirror p) {
    margin-bottom: 1rem;
    line-height: 1.75;
}

:deep(.ProseMirror p:last-child) {
    margin-bottom: 0;
}

/* Headings */
:deep(.ProseMirror h2) {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    margin-top: 1.5rem;
    line-height: 1.3;
    letter-spacing: -0.02em;
    color: #111827;
}

:deep(.ProseMirror h3) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    margin-top: 1.25rem;
    line-height: 1.4;
    letter-spacing: -0.01em;
    color: #111827;
}

/* Bullet Lists with custom styled bullets */
:deep(.ProseMirror ul) {
    list-style: none;
    padding-left: 0;
    margin-bottom: 1rem;
    margin-top: 0.5rem;
}

:deep(.ProseMirror ul li) {
    position: relative;
    padding-left: 1.25rem;
    margin-bottom: 0.5rem;
    line-height: 1.75;
}

:deep(.ProseMirror ul li::before) {
    content: "•";
    position: absolute;
    left: 0;
    color: #9CA3AF;
    font-size: 1rem;
    line-height: 1.75;
    top: 0;
}

/* Numbered Lists with custom styled numbers */
:deep(.ProseMirror ol) {
    list-style: none;
    padding-left: 0;
    margin-bottom: 1rem;
    margin-top: 0.5rem;
    counter-reset: list-counter;
}

:deep(.ProseMirror ol li) {
    position: relative;
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
    line-height: 1.75;
    counter-increment: list-counter;
}

:deep(.ProseMirror ol li::before) {
    content: counter(list-counter) ".";
    position: absolute;
    left: 0;
    font-weight: 500;
    color: #6B7280;
    line-height: 1.75;
    top: 0;
}

/* Nested lists */
:deep(.ProseMirror ul ul),
:deep(.ProseMirror ul ol),
:deep(.ProseMirror ol ul),
:deep(.ProseMirror ol ol) {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
    margin-left: 1.5rem;
}

/* Strong text */
:deep(.ProseMirror strong) {
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.01em;
}

/* Horizontal rule */
:deep(.ProseMirror hr) {
    margin: 1.5rem 0;
    border: none;
    height: 1px;
    background: linear-gradient(to right, transparent, #D1D5DB, transparent);
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}

:deep(.ProseMirror:focus) {
    outline: none;
}
</style>