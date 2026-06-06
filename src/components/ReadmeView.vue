<template>
    <div v-if="content" class="ng-readme">
        <div class="ng-readme-header">
            <FileText :size="16" />
            <span>README</span>
        </div>
        <div class="ng-readme-body" v-html="rendered" />
    </div>
</template>

<script>
import { marked } from 'marked'
import { FileText } from 'lucide-vue-next'

marked.setOptions({ mangle: false, headerIds: false })

export default {
    name: 'ReadmeView',
    components: { FileText },
    props: {
        content: { type: String, default: '' },
    },
    computed: {
        rendered() {
            return marked(this.content || '')
        },
    },
}
</script>

<style scoped>
.ng-readme {
    border: 1px solid var(--color-border);
    border-radius: 8px; margin-top: 24px; overflow: hidden;
}
.ng-readme-header {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px;
    background: var(--color-background-hover);
    border-bottom: 1px solid var(--color-border);
    font-size: 13px; font-weight: 600;
    color: var(--color-text-maxcontrast);
}
.ng-readme-body { padding: 20px 24px; }
</style>

<style>
/* Unscoped — targets v-html content */
.ng-readme-body h1, .ng-readme-body h2, .ng-readme-body h3,
.ng-readme-body h4, .ng-readme-body h5, .ng-readme-body h6 {
    margin: 1em 0 0.5em; font-weight: 700;
    color: var(--color-main-text);
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 0.3em;
}
.ng-readme-body h1 { font-size: 1.8em; }
.ng-readme-body h2 { font-size: 1.4em; }
.ng-readme-body h3 { font-size: 1.2em; }
.ng-readme-body p { line-height: 1.7; margin: 0.8em 0; }
.ng-readme-body a { color: var(--color-primary-element); }
.ng-readme-body code {
    background: var(--color-background-dark);
    border-radius: 4px; padding: 2px 6px;
    font-family: monospace; font-size: 0.9em;
}
.ng-readme-body pre {
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 6px; padding: 14px 16px;
    overflow-x: auto;
}
.ng-readme-body pre code { background: none; padding: 0; }
.ng-readme-body blockquote {
    border-left: 4px solid var(--color-border);
    margin: 0; padding: 4px 16px;
    color: var(--color-text-maxcontrast);
}
.ng-readme-body ul, .ng-readme-body ol { padding-left: 24px; margin: 0.8em 0; }
.ng-readme-body li { line-height: 1.7; }
.ng-readme-body table { border-collapse: collapse; width: 100%; margin: 1em 0; }
.ng-readme-body th, .ng-readme-body td {
    border: 1px solid var(--color-border); padding: 8px 12px; text-align: left;
}
.ng-readme-body th { background: var(--color-background-hover); font-weight: 600; }
</style>
