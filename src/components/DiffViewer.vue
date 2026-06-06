<template>
    <div class="ng-diff-viewer">
        <div v-if="!files || files.length === 0" class="ng-no-diff">{{ t('git', 'No changes detected.') }}</div>
        <div v-for="(file, fi) in files" :key="fi" class="ng-diff-file">
            <div class="ng-diff-file-header" @click="toggle(fi)">
                <span class="ng-diff-chevron">{{ collapsed[fi] ? '▶' : '▼' }}</span>
                <code class="ng-diff-filename">{{ file.file }}</code>
                <span class="ng-added-badge">+{{ file.added }}</span>
                <span class="ng-removed-badge">-{{ file.removed }}</span>
            </div>
            <div v-if="!collapsed[fi]" class="ng-diff-hunks">
                <div v-for="(hunk, hi) in file.hunks" :key="hi">
                    <div class="ng-hunk-header">{{ hunk.header }}</div>
                    <table class="ng-diff-table">
                        <tbody>
                            <tr v-for="(line, li) in hunk.lines" :key="li"
                                :class="['ng-diff-row', lineClass(line.type)]">
                                <td class="ng-diff-ln ng-diff-ln-old">{{ line.oldLine ?? '' }}</td>
                                <td class="ng-diff-ln ng-diff-ln-new">{{ line.newLine ?? '' }}</td>
                                <td class="ng-diff-gutter">{{ line.type === ' ' ? ' ' : line.type }}</td>
                                <td class="ng-diff-content"><code>{{ line.content }}</code></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DiffViewer',
    props: {
        files: { type: Array, default: () => [] },
    },
    data() { return { collapsed: {} } },
    methods: {
        toggle(idx) {
            this.collapsed = { ...this.collapsed, [idx]: !this.collapsed[idx] }
        },
        lineClass(type) {
            if (type === '+') return 'ng-line-added'
            if (type === '-') return 'ng-line-removed'
            return 'ng-line-context'
        },
    },
}
</script>

<style scoped>
.ng-diff-viewer { font-family: monospace; font-size: 12px; }
.ng-no-diff { color: var(--color-text-maxcontrast); padding: 12px 0; }
.ng-diff-file { border: 1px solid var(--color-border); border-radius: 6px; margin-bottom: 16px; overflow: hidden; }
.ng-diff-file-header {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 14px; background: var(--color-background-hover);
    cursor: pointer; border-bottom: 1px solid var(--color-border);
}
.ng-diff-chevron { font-size: 11px; color: var(--color-text-maxcontrast); }
.ng-diff-filename { flex: 1; font-size: 13px; }
.ng-added-badge { color: #2da44e; font-weight: 700; }
.ng-removed-badge { color: #d1242f; font-weight: 700; }
.ng-diff-hunks { overflow-x: auto; max-height: 500px; overflow-y: auto; }
.ng-hunk-header { background: #b4d5fe22; color: #0969da; padding: 4px 14px; font-size: 12px; }
.ng-diff-table { width: 100%; border-collapse: collapse; }
.ng-diff-row td { padding: 1px 0; white-space: pre; }
.ng-line-added { background: #e6ffec; }
.ng-line-removed { background: #ffebe9; }
.ng-diff-ln { width: 44px; text-align: right; padding: 1px 8px; color: rgba(0,0,0,0.35); user-select: none; border-right: 1px solid var(--color-border); }
.ng-diff-gutter { width: 20px; text-align: center; color: rgba(0,0,0,0.4); padding: 1px 4px; }
.ng-diff-content { padding: 1px 8px; }
</style>
