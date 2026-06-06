<template>
    <div class="ng-issues">
        <div class="ng-section-header">
            <div class="ng-filter-tabs">
                <button v-for="tab in stateTabs" :key="tab.value"
                    class="ng-filter-tab" :class="{ active: stateFilter === tab.value }"
                    @click="setFilter(tab.value)">
                    {{ tab.label }} <span v-if="tab.count !== null" class="ng-tab-count">{{ tab.count }}</span>
                </button>
            </div>
            <NcButton type="primary" @click="$router.push(`/${owner}/${repoName}/issues/new`)">
                <template #icon><Plus :size="16" /></template>
                {{ t('git', 'New issue') }}
            </NcButton>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <div v-else-if="issues.length === 0" class="ng-empty">{{ t('git', 'No issues found.') }}</div>
        <div v-else>
            <div v-for="issue in issues" :key="issue.id" class="ng-issue-row"
                @click="$router.push(`/${owner}/${repoName}/issues/${issue.number}`)">
                <span :class="['ng-state-dot', issue.state === 'open' ? 'ng-state-open' : 'ng-state-closed']">●</span>
                <div class="ng-issue-main">
                    <span class="ng-issue-title">{{ issue.title }}</span>
                    <span class="ng-issue-meta">#{{ issue.number }} · {{ issue.state }} · {{ formatDate(issue.created_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { Plus } from 'lucide-vue-next'

export default {
    name: 'IssueListView',
    components: { NcButton, Plus },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() {
        return { issues: [], allIssues: [], loading: false, stateFilter: 'open' }
    },
    computed: {
        openCount()   { return this.allIssues.filter(i => i.state === 'open').length },
        closedCount() { return this.allIssues.filter(i => i.state === 'closed').length },
        stateTabs() {
            return [
                { value: 'open',   label: this.t('git', 'Open'),   count: this.openCount },
                { value: 'closed', label: this.t('git', 'Closed'), count: this.closedCount },
                { value: 'all',    label: this.t('git', 'All'),    count: null },
            ]
        },
    },
    mounted() { this.loadAll() },
    methods: {
        async loadAll() {
            this.loading = true
            try {
                const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/issues?state=all`))
                this.allIssues = data
                this.applyFilter()
            } catch { this.issues = [] }
            finally { this.loading = false }
        },
        setFilter(val) { this.stateFilter = val; this.applyFilter() },
        applyFilter() {
            this.issues = this.stateFilter === 'all'
                ? this.allIssues
                : this.allIssues.filter(i => i.state === this.stateFilter)
        },
        formatDate(ts) { return new Date(ts * 1000).toLocaleDateString() },
    },
}
</script>

<style scoped>
.ng-issues { padding: 20px 28px 20px 52px; }
.ng-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; }
.ng-filter-tabs { display: flex; gap: 4px; }
.ng-filter-tab {
    padding: 6px 14px; border: 1px solid var(--color-border); border-radius: 6px;
    background: none; cursor: pointer; font-size: 13px; color: var(--color-text-maxcontrast);
}
.ng-filter-tab.active { background: var(--color-primary-element); color: #fff; border-color: var(--color-primary-element); font-weight: 600; }
.ng-tab-count { font-weight: 700; margin-left: 4px; }
.ng-issue-row {
    display: flex; align-items: flex-start; gap: 10px; padding: 12px 0;
    border-bottom: 1px solid var(--color-border); cursor: pointer;
}
.ng-issue-row:hover { background: var(--color-background-hover); margin: 0 -8px; padding: 12px 8px; }
.ng-state-dot { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.ng-state-open { color: #2da44e; }
.ng-state-closed { color: #8250df; }
.ng-issue-main { display: flex; flex-direction: column; gap: 3px; }
.ng-issue-title { font-size: 14px; font-weight: 500; }
.ng-issue-meta { font-size: 12px; color: var(--color-text-maxcontrast); }
.ng-loading, .ng-empty { color: var(--color-text-maxcontrast); padding: 16px 0; }
</style>
