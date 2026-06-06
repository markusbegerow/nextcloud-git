<template>
    <div class="ng-issue-detail">
        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <template v-else-if="issue">
            <div class="ng-issue-header">
                <button class="ng-back" @click="$router.push(`/${owner}/${repoName}/issues`)">
                    <ArrowLeft :size="16" /> {{ t('git', 'Issues') }}
                </button>
                <div class="ng-issue-title-row">
                    <h2>{{ issue.title }} <span class="ng-issue-num">#{{ issue.number }}</span></h2>
                    <span :class="['ng-state-badge', issue.state === 'open' ? 'ng-open' : 'ng-closed']">
                        {{ issue.state === 'open' ? t('git', 'Open') : t('git', 'Closed') }}
                    </span>
                </div>
                <div class="ng-issue-meta">
                    {{ t('git', 'Opened by {user} on {date}', { user: issue.creator_uid, date: formatDate(issue.created_at) }) }}
                </div>
            </div>

            <div class="ng-issue-body ng-readme-body" v-html="bodyHtml" />

            <div class="ng-issue-actions">
                <NcButton v-if="issue.state === 'open'" @click="toggle('closed')">{{ t('git', 'Close issue') }}</NcButton>
                <NcButton v-else @click="toggle('open')">{{ t('git', 'Reopen issue') }}</NcButton>
            </div>
        </template>
        <div v-else class="ng-error-msg">{{ t('git', 'Issue not found.') }}</div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { marked } from 'marked'
import { ArrowLeft } from 'lucide-vue-next'

export default {
    name: 'IssueDetailView',
    components: { NcButton, ArrowLeft },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() { return { issue: null, loading: false } },
    computed: {
        num() { return parseInt(this.$route.params.num) },
        bodyHtml() { return this.issue?.body ? marked(this.issue.body) : '<em>No description provided.</em>' },
    },
    watch: { '$route.params.num': { immediate: true, handler() { this.load() } } },
    methods: {
        async load() {
            this.loading = true
            try {
                const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/issues/${this.num}`))
                this.issue = data
            } catch { this.issue = null }
            finally { this.loading = false }
        },
        async toggle(state) {
            try {
                const { data } = await axios.patch(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/issues/${this.num}`),
                    { state }
                )
                this.issue = data
            } catch (e) { console.error(e) }
        },
        formatDate(ts) { return new Date(ts * 1000).toLocaleDateString() },
    },
}
</script>

<style scoped>
.ng-issue-detail { padding: 20px 28px 20px 52px; max-width: 800px; }
.ng-back { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; color: var(--color-text-maxcontrast); font-size: 13px; padding: 0; margin-bottom: 12px; }
.ng-back:hover { color: var(--color-main-text); }
.ng-issue-title-row { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.ng-issue-title-row h2 { font-size: 20px; font-weight: 700; margin: 0; flex: 1; }
.ng-issue-num { color: var(--color-text-maxcontrast); font-weight: 400; }
.ng-state-badge { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; white-space: nowrap; }
.ng-open { background: #2da44e; color: #fff; }
.ng-closed { background: #8250df; color: #fff; }
.ng-issue-meta { font-size: 12px; color: var(--color-text-maxcontrast); margin-top: 8px; }
.ng-issue-body {
    border: 1px solid var(--color-border); border-radius: 8px;
    padding: 20px 24px; margin: 20px 0;
    background: var(--color-main-background);
}
.ng-issue-actions { display: flex; gap: 8px; }
.ng-loading, .ng-error-msg { color: var(--color-text-maxcontrast); padding: 16px 0; }
</style>
