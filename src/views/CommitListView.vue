<template>
    <div class="ng-commits">
        <div class="ng-commits-toolbar">
            <select v-model="activeBranch" class="ng-branch-select" @change="load">
                <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
            </select>
            <span class="ng-commit-count">{{ t('git', '{n} commits', { n: totalCommits }) }}</span>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>

        <div v-else-if="page.length === 0" class="ng-empty">{{ t('git', 'No commits yet.') }}</div>

        <div v-else>
            <div v-for="commit in page" :key="commit.hash" class="ng-commit-row">
                <div class="ng-commit-main">
                    <div class="ng-commit-message">{{ commit.message }}</div>
                    <div class="ng-commit-meta">
                        <strong>{{ commit.author }}</strong>
                        &nbsp;·&nbsp;
                        <span :title="commit.date">{{ relativeTime(commit.date) }}</span>
                    </div>
                </div>
                <code class="ng-commit-hash">{{ commit.hash.slice(0, 7) }}</code>
            </div>

            <!-- Pagination -->
            <div class="ng-pagination">
                <button :disabled="pageNum === 0" class="ng-page-btn" @click="pageNum--">‹ {{ t('git', 'Prev') }}</button>
                <span>{{ pageNum + 1 }} / {{ totalPages }}</span>
                <button :disabled="pageNum >= totalPages - 1" class="ng-page-btn" @click="pageNum++">{{ t('git', 'Next') }} ›</button>
            </div>
        </div>
    </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

const PAGE_SIZE = 20

export default {
    name: 'CommitListView',
    props: {
        owner:         { type: String, required: true },
        repoName:      { type: String, required: true },
        branches:      { type: Array,  default: () => [] },
        defaultBranch: { type: String, default: 'main' },
    },
    data() {
        return { commits: [], loading: false, activeBranch: '', pageNum: 0 }
    },
    computed: {
        totalCommits() { return this.commits.length },
        totalPages() { return Math.max(1, Math.ceil(this.commits.length / PAGE_SIZE)) },
        page() {
            const s = this.pageNum * PAGE_SIZE
            return this.commits.slice(s, s + PAGE_SIZE)
        },
    },
    watch: {
        '$route.params.branch': { immediate: true, handler(b) { this.activeBranch = b || this.defaultBranch; this.load() } },
        defaultBranch(val) { if (!this.activeBranch) { this.activeBranch = val; this.load() } },
    },
    methods: {
        async load() {
            if (!this.activeBranch) return
            this.loading = true; this.pageNum = 0
            try {
                const { data } = await axios.get(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/commits/${this.activeBranch}`)
                )
                this.commits = data
            } catch { this.commits = [] }
            finally { this.loading = false }
        },
        relativeTime(iso) {
            const d = new Date(iso)
            const now = Date.now()
            const diff = now - d.getTime()
            const m = Math.floor(diff / 60000)
            if (m < 1)  return this.t('git', 'just now')
            if (m < 60) return this.t('git', '{n}m ago', { n: m })
            const h = Math.floor(m / 60)
            if (h < 24) return this.t('git', '{n}h ago', { n: h })
            const days = Math.floor(h / 24)
            if (days < 30) return this.t('git', '{n}d ago', { n: days })
            return d.toLocaleDateString()
        },
    },
}
</script>

<style scoped>
.ng-commits { padding: 20px 28px 20px 52px; }
.ng-commits-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.ng-branch-select {
    padding: 5px 10px; border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text); font-size: 13px;
}
.ng-commit-count { font-size: 13px; color: var(--color-text-maxcontrast); }
.ng-commit-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0; border-bottom: 1px solid var(--color-border); gap: 16px;
}
.ng-commit-main { flex: 1; min-width: 0; }
.ng-commit-message { font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ng-commit-meta { font-size: 12px; color: var(--color-text-maxcontrast); margin-top: 3px; }
.ng-commit-hash {
    font-family: monospace; font-size: 12px;
    background: var(--color-background-dark); border: 1px solid var(--color-border);
    border-radius: 4px; padding: 3px 8px; white-space: nowrap;
}
.ng-pagination { display: flex; align-items: center; gap: 12px; margin-top: 16px; justify-content: center; }
.ng-page-btn {
    padding: 5px 14px; border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text); cursor: pointer;
}
.ng-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.ng-loading, .ng-empty { color: var(--color-text-maxcontrast); padding: 16px 0; }
</style>
