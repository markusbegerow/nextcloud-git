<template>
    <div class="ng-new-issue">
        <div class="ng-page-header">
            <button class="ng-back" @click="$router.back()">
                <ArrowLeft :size="16" /> {{ t('git', 'Back to issues') }}
            </button>
            <h3>{{ t('git', 'New issue') }}</h3>
        </div>

        <div class="ng-form-card">
            <div class="ng-form-group">
                <label>{{ t('git', 'Title') }} *</label>
                <input v-model="title" type="text" class="ng-input" :placeholder="t('git', 'Issue title')" autofocus />
                <span v-if="titleError" class="ng-error">{{ titleError }}</span>
            </div>

            <div class="ng-form-group">
                <label>{{ t('git', 'Description') }}</label>
                <div class="ng-editor-tabs">
                    <button :class="['ng-etab', { active: editorTab === 'write' }]" @click="editorTab = 'write'">{{ t('git', 'Write') }}</button>
                    <button :class="['ng-etab', { active: editorTab === 'preview' }]" @click="editorTab = 'preview'">{{ t('git', 'Preview') }}</button>
                </div>
                <textarea v-if="editorTab === 'write'" v-model="body" class="ng-textarea" rows="8"
                    :placeholder="t('git', 'Describe the issue (Markdown supported)')" />
                <div v-else class="ng-preview ng-readme-body" v-html="previewHtml" />
            </div>

            <p v-if="error" class="ng-error">{{ error }}</p>

            <div class="ng-form-actions">
                <NcButton @click="$router.back()">{{ t('git', 'Cancel') }}</NcButton>
                <NcButton type="primary" :disabled="submitting" @click="submit">
                    {{ submitting ? t('git', 'Submitting…') : t('git', 'Submit issue') }}
                </NcButton>
            </div>
        </div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { marked } from 'marked'
import { ArrowLeft } from 'lucide-vue-next'

export default {
    name: 'NewIssueView',
    components: { NcButton, ArrowLeft },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() { return { title: '', body: '', editorTab: 'write', submitting: false, titleError: '', error: '' } },
    computed: {
        previewHtml() { return marked(this.body || '_Nothing to preview_') },
    },
    methods: {
        async submit() {
            this.titleError = ''
            if (!this.title.trim()) { this.titleError = this.t('git', 'Title is required'); return }
            this.submitting = true
            try {
                await axios.post(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/issues`), {
                    title: this.title.trim(), body: this.body,
                })
                this.$router.push(`/${this.owner}/${this.repoName}/issues`)
            } catch (e) {
                this.error = e.response?.data?.error ?? this.t('git', 'Failed to submit issue')
            } finally { this.submitting = false }
        },
    },
}
</script>

<style scoped>
.ng-new-issue { padding: 20px 28px 20px 52px; max-width: 700px; }
.ng-page-header { margin-bottom: 20px; }
.ng-page-header h3 { font-size: 18px; font-weight: 700; margin: 10px 0 0; }
.ng-back { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; color: var(--color-text-maxcontrast); font-size: 13px; padding: 0; }
.ng-back:hover { color: var(--color-main-text); }
.ng-form-card { border: 1px solid var(--color-border); border-radius: 8px; padding: 24px; }
.ng-form-group { margin-bottom: 18px; }
.ng-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.ng-input { width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 14px; box-sizing: border-box; }
.ng-textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 14px; box-sizing: border-box; resize: vertical; font-family: monospace; }
.ng-editor-tabs { display: flex; gap: 4px; margin-bottom: 8px; }
.ng-etab { padding: 5px 12px; border: 1px solid var(--color-border); border-radius: 6px 6px 0 0; background: none; cursor: pointer; font-size: 13px; }
.ng-etab.active { background: var(--color-background-hover); font-weight: 600; border-bottom-color: var(--color-background-hover); }
.ng-preview { min-height: 150px; border: 1px solid var(--color-border); border-radius: 6px; padding: 12px 16px; background: var(--color-main-background); }
.ng-error { color: var(--color-error); font-size: 13px; display: block; margin-top: 4px; }
.ng-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
</style>
