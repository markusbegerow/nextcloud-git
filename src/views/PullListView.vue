<template>
    <div class="ng-pulls">
        <div class="ng-section-header">
            <div class="ng-filter-tabs">
                <button v-for="tab in stateTabs" :key="tab.value"
                    class="ng-filter-tab" :class="{ active: stateFilter === tab.value }"
                    @click="setFilter(tab.value)">
                    {{ tab.label }} <span v-if="tab.count !== null" class="ng-tab-count">{{ tab.count }}</span>
                </button>
            </div>
            <NcButton type="primary" @click="$router.push(`/${owner}/${repoName}/pulls/new`)">
                <template #icon><Plus :size="16" /></template>
                {{ t('git', 'New pull request') }}
            </NcButton>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <div v-else-if="pulls.length === 0" class="ng-empty">{{ t('git', 'No pull requests found.') }}</div>
        <div v-else>
            <div v-for="pr in pulls" :key="pr.id" class="ng-pr-row"
                @click="$router.push(`/${owner}/${repoName}/pulls/${pr.number}`)">
                <span :class="['ng-pr-icon', `ng-pr-${pr.state}`]">⤵</span>
                <div class="ng-pr-main">
                    <span class="ng-pr-title">{{ pr.title }}</span>
                    <span class="ng-pr-meta">
                        #{{ pr.number }} ·
                        <span :class="['ng-state-badge-sm', `ng-${pr.state}`]">{{ pr.state }}</span>
                        · {{ pr.head_branch }} → {{ pr.base_branch }}
                        · {{ formatDate(pr.created_at) }}
                    </span>
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
    name: 'PullListView',
    components: { NcButton, Plus },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() { return { allPulls: [], pulls: [], loading: false, stateFilter: 'open' } },
    computed: {
        stateTabs() {
            const cnt = (s) => this.allPulls.filter(p => p.state === s).length
            return [
                { value: 'open',   label: this.t('git', 'Open'),   count: cnt('open') },
                { value: 'merged', label: this.t('git', 'Merged'), count: cnt('merged') },
                { value: 'closed', label: this.t('git', 'Closed'), count: cnt('closed') },
                { value: 'all',    label: this.t('git', 'All'),    count: null },
            ]
        },
    },
    mounted() { this.loadAll() },
    methods: {
        async loadAll() {
            this.loading = true
            try {
                const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/pulls?state=all`))
                this.allPulls = data
                this.applyFilter()
            } catch { this.pulls = [] }
            finally { this.loading = false }
        },
        setFilter(val) { this.stateFilter = val; this.applyFilter() },
        applyFilter() {
            this.pulls = this.stateFilter === 'all' ? this.allPulls : this.allPulls.filter(p => p.state === this.stateFilter)
        },
        formatDate(ts) { return new Date(ts * 1000).toLocaleDateString() },
    },
}
</script>

<style scoped>
.ng-pulls { padding: 20px 28px 20px 52px; }
.ng-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px; }
.ng-filter-tabs { display: flex; gap: 4px; }
.ng-filter-tab { padding: 6px 14px; border: 1px solid var(--color-border); border-radius: 6px; background: none; cursor: pointer; font-size: 13px; color: var(--color-text-maxcontrast); }
.ng-filter-tab.active { background: var(--color-primary-element); color: #fff; border-color: var(--color-primary-element); font-weight: 600; }
.ng-tab-count { font-weight: 700; margin-left: 4px; }
.ng-pr-row { display: flex; align-items: flex-start; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--color-border); cursor: pointer; }
.ng-pr-row:hover { background: var(--color-background-hover); margin: 0 -8px; padding: 12px 8px; }
.ng-pr-icon { font-size: 18px; flex-shrink: 0; }
.ng-pr-open { color: #2da44e; }
.ng-pr-merged { color: #8250df; }
.ng-pr-closed { color: var(--color-text-maxcontrast); }
.ng-pr-main { display: flex; flex-direction: column; gap: 3px; }
.ng-pr-title { font-size: 14px; font-weight: 500; }
.ng-pr-meta { font-size: 12px; color: var(--color-text-maxcontrast); }
.ng-state-badge-sm { padding: 1px 6px; border-radius: 4px; font-weight: 600; font-size: 11px; }
.ng-open { background: #2da44e22; color: #2da44e; }
.ng-merged { background: #8250df22; color: #8250df; }
.ng-closed { background: var(--color-border); color: var(--color-text-maxcontrast); }
.ng-loading, .ng-empty { color: var(--color-text-maxcontrast); padding: 16px 0; }
</style>
