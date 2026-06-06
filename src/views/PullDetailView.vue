<template>
    <div class="ng-pr-detail">
        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <template v-else-if="pr">
            <div class="ng-pr-header">
                <button class="ng-back" @click="$router.push(`/${owner}/${repoName}/pulls`)">
                    <ArrowLeft :size="16" /> {{ t('git', 'Pull Requests') }}
                </button>
                <div class="ng-pr-title-row">
                    <h2>{{ pr.title }} <span class="ng-pr-num">#{{ pr.number }}</span></h2>
                    <span :class="['ng-state-badge', `ng-${pr.state}`]">{{ pr.state }}</span>
                </div>
                <div class="ng-pr-meta">
                    {{ pr.head_branch }} → {{ pr.base_branch }}
                    · {{ t('git', 'by {user}', { user: pr.creator_uid }) }}
                    · {{ formatDate(pr.created_at) }}
                </div>
            </div>

            <div v-if="pr.body" class="ng-pr-body ng-readme-body" v-html="bodyHtml" />

            <!-- Actions -->
            <div v-if="pr.state === 'open'" class="ng-pr-actions">
                <div v-if="diff && !diff.canMerge" class="ng-conflict-warning">
                    ⚠ {{ t('git', 'This branch has conflicts that must be resolved before merging.') }}
                </div>
                <NcButton type="primary" :disabled="merging || (diff && !diff.canMerge)" @click="merge">
                    {{ merging ? t('git', 'Merging…') : t('git', 'Merge pull request') }}
                </NcButton>
                <NcButton @click="close">{{ t('git', 'Close') }}</NcButton>
            </div>
            <div v-else class="ng-pr-closed-notice">
                {{ pr.state === 'merged' ? t('git', 'This pull request was merged.') : t('git', 'This pull request was closed.') }}
            </div>

            <p v-if="actionError" class="ng-error">{{ actionError }}</p>

            <!-- Diff -->
            <h3 class="ng-diff-heading">{{ t('git', 'Changes') }}</h3>
            <DiffViewer v-if="diff" :files="diff.files" />
            <div v-else class="ng-loading">{{ t('git', 'Loading diff…') }}</div>
        </template>
        <div v-else class="ng-error-msg">{{ t('git', 'Pull request not found.') }}</div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { marked } from 'marked'
import { ArrowLeft } from 'lucide-vue-next'
import DiffViewer from '../components/DiffViewer.vue'

export default {
    name: 'PullDetailView',
    components: { NcButton, DiffViewer, ArrowLeft },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() { return { pr: null, diff: null, loading: false, merging: false, actionError: '' } },
    computed: {
        num() { return parseInt(this.$route.params.num) },
        bodyHtml() { return this.pr?.body ? marked(this.pr.body) : '' },
    },
    watch: { '$route.params.num': { immediate: true, handler() { this.load() } } },
    methods: {
        async load() {
            this.loading = true
            try {
                const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/pulls/${this.num}`))
                this.pr   = data
                this.diff = data.diff ?? null
            } catch { this.pr = null }
            finally { this.loading = false }
        },
        async merge() {
            this.merging = true; this.actionError = ''
            try {
                const { data } = await axios.post(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/pulls/${this.num}/merge`))
                this.pr = data
            } catch (e) {
                this.actionError = e.response?.data?.error ?? this.t('git', 'Merge failed')
            } finally { this.merging = false }
        },
        async close() {
            try {
                const { data } = await axios.post(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/pulls/${this.num}/close`))
                this.pr = data
            } catch (e) { this.actionError = e.response?.data?.error ?? this.t('git', 'Failed') }
        },
        formatDate(ts) { return new Date(ts * 1000).toLocaleDateString() },
    },
}
</script>

<style scoped>
.ng-pr-detail { padding: 20px 28px 20px 52px; max-width: 900px; }
.ng-back { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; color: var(--color-text-maxcontrast); font-size: 13px; padding: 0; margin-bottom: 12px; }
.ng-back:hover { color: var(--color-main-text); }
.ng-pr-title-row { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.ng-pr-title-row h2 { font-size: 20px; font-weight: 700; margin: 0; flex: 1; }
.ng-pr-num { color: var(--color-text-maxcontrast); font-weight: 400; }
.ng-state-badge { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; white-space: nowrap; }
.ng-open { background: #2da44e; color: #fff; }
.ng-merged { background: #8250df; color: #fff; }
.ng-closed { background: var(--color-text-maxcontrast); color: #fff; }
.ng-pr-meta { font-size: 12px; color: var(--color-text-maxcontrast); margin-top: 8px; }
.ng-pr-body { border: 1px solid var(--color-border); border-radius: 8px; padding: 20px 24px; margin: 20px 0; }
.ng-pr-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 20px 0; }
.ng-conflict-warning { color: #e36209; font-size: 13px; }
.ng-pr-closed-notice { color: var(--color-text-maxcontrast); font-style: italic; margin: 16px 0; }
.ng-diff-heading { font-size: 16px; font-weight: 700; margin: 24px 0 12px; border-bottom: 1px solid var(--color-border); padding-bottom: 8px; }
.ng-error { color: var(--color-error); font-size: 13px; }
.ng-loading, .ng-error-msg { color: var(--color-text-maxcontrast); padding: 16px 0; }
</style>
