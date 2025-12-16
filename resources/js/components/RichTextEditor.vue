<script setup lang="ts">
import { ref, watch } from 'vue'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = withDefaults(defineProps<{
    modelValue?: string
    placeholder?: string
    height?: string
}>(), {
    modelValue: '',
    placeholder: 'Schrijf hier je tekst...',
    height: '320px'
})

const emit = defineEmits(['update:modelValue'])

const content = ref(props.modelValue)

watch(() => props.modelValue, (newVal) => {
    if (newVal !== content.value) {
        content.value = newVal
    }
})

function onUpdate() {
    emit('update:modelValue', content.value)
}

const toolbarOptions = [
    [{ 'header': [2, 3, 4, 5, 6, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    ['code-block'],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'align': [] }],
    ['link', 'image', 'video'],
    ['clean']
]
</script>

<template>
    <div class="rounded-md border bg-background">
        <QuillEditor
            theme="snow"
            v-model:content="content"
            contentType="html"
            :toolbar="toolbarOptions"
            :placeholder="placeholder"
            :style="{ height: height, borderRadius: '6px' }"
            @update:content="onUpdate"
        />
    </div>
</template>

<style>
/* Dark mode overrides for Quill */
.dark .ql-toolbar {
    border-color: #27272a !important; /* zinc-800 */
    background-color: #18181b; /* zinc-950 */
    color: #e4e4e7; /* zinc-200 */
}
.dark .ql-container {
    border-color: #27272a !important;
    background-color: #09090b; /* zinc-950 */
    color: #e4e4e7;
}
.dark .ql-stroke {
    stroke: #a1a1aa !important; /* zinc-400 */
}
.dark .ql-fill {
    fill: #a1a1aa !important;
}
.dark .ql-picker {
    color: #a1a1aa !important;
}
</style>
