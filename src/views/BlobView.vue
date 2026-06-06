<template>
    <div class="ng-blob-view">
        <!-- Toolbar -->
        <div class="ng-blob-toolbar">
            <Breadcrumb :owner="owner" :repo="repoName" :branch="branch" :path="filePath" />
            <div class="ng-blob-actions">
                <span class="ng-file-size">{{ sizeLabel }}</span>
                <a :href="rawUrl" target="_blank" class="ng-raw-link">{{ t('git', 'Raw') }}</a>
            </div>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <div v-else-if="isBinary" class="ng-binary-hint">{{ t('git', 'Binary file not shown.') }}</div>
        <div v-else class="ng-code-wrapper">
            <table class="ng-code-table">
                <tbody>
                    <tr v-for="(line, idx) in lines" :key="idx">
                        <td class="ng-ln">{{ idx + 1 }}</td>
                        <td class="ng-lc"><span v-html="line" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import hljs from 'highlight.js'
import Breadcrumb from '../components/Breadcrumb.vue'

const EXT_MAP = {
    js: 'javascript', ts: 'typescript', vue: 'xml', php: 'php',
    py: 'python', rb: 'ruby', go: 'go', rs: 'rust', java: 'java',
    c: 'c', cpp: 'cpp', cs: 'csharp', sh: 'bash', bash: 'bash',
    json: 'json', yaml: 'yaml', yml: 'yaml', md: 'markdown',
    html: 'html', css: 'css', scss: 'scss', sql: 'sql', xml: 'xml',
}

export default {
    name: 'BlobView',
    components: { Breadcrumb },
    props: {
        owner:         { type: String, required: true },
        repoName:      { type: String, required: true },
        defaultBranch: { type: String, default: 'main' },
    },
    data() {
        return { content: '', loading: false, isBinary: false }
    },
    computed: {
        branch()   { return this.$route.params.branch || this.defaultBranch },
        filePath() { return this.$route.query.path ?? '' },
        rawUrl()   {
            return generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/blob/${this.branch}`) +
                (this.filePath ? `?path=${encodeURIComponent(this.filePath)}` : '')
        },
        ext() { return (this.filePath.split('.').pop() ?? '').toLowerCase() },
        lang() { return EXT_MAP[this.ext] ?? null },
        lines() {
            const raw = this.content
            const highlighted = this.lang
                ? hljs.highlight(raw, { language: this.lang, ignoreIllegals: true }).value
                : hljs.highlightAuto(raw).value
            return highlighted.split('\n')
        },
        sizeLabel() {
            const b = new TextEncoder().encode(this.content).length
            if (b < 1024) return b + ' B'
            if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'
            return (b / 1048576).toFixed(1) + ' MB'
        },
    },
    watch: {
        '$route': { immediate: true, handler() { this.load() } },
    },
    methods: {
        async load() {
            if (!this.filePath) return
            this.loading = true
            this.isBinary = false
            try {
                const url = generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/blob/${this.branch}`) +
                    `?path=${encodeURIComponent(this.filePath)}`
                const { data } = await axios.get(url)
                this.content = data.content ?? ''
                // Detect binary: contains null bytes
                this.isBinary = this.content.includes('\0')
            } catch (e) {
                this.content = ''
            } finally {
                this.loading = false
            }
        },
    },
}
</script>

<style scoped>
.ng-blob-view { padding: 20px 28px 20px 52px; }
.ng-blob-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
}
.ng-blob-actions { display: flex; align-items: center; gap: 12px; }
.ng-file-size { font-size: 12px; color: var(--color-text-maxcontrast); }
.ng-raw-link {
    font-size: 12px; color: var(--color-primary-element); text-decoration: none;
    border: 1px solid var(--color-border); border-radius: 4px; padding: 3px 10px;
}
.ng-raw-link:hover { background: var(--color-background-hover); }
.ng-loading, .ng-binary-hint { color: var(--color-text-maxcontrast); padding: 16px 0; }
.ng-code-wrapper {
    border: 1px solid var(--color-border); border-radius: 6px; overflow: auto;
    font-family: monospace; font-size: 13px; line-height: 1.6;
    background: var(--color-background-dark);
    max-height: calc(100vh - 260px);
}
.ng-code-table { border-collapse: collapse; width: 100%; }
.ng-ln {
    min-width: 44px; text-align: right; padding: 0 14px 0 8px;
    color: var(--color-text-maxcontrast); user-select: none;
    border-right: 1px solid var(--color-border); background: var(--color-background-hover);
    vertical-align: top;
}
.ng-lc { padding: 0 16px; white-space: pre; }
</style>

<style>
/* highlight.js token colors — unscoped */
.hljs-keyword, .hljs-selector-tag, .hljs-built_in { color: var(--color-primary-element); }
.hljs-string, .hljs-attr { color: #22863a; }
.hljs-comment, .hljs-quote { color: var(--color-text-maxcontrast); font-style: italic; }
.hljs-number, .hljs-literal { color: #005cc5; }
.hljs-title, .hljs-section { font-weight: bold; }
</style>
