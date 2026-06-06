<template>
    <div class="ng-repo-view">
        <!-- Repo header -->
        <div v-if="repo" class="ng-repo-header">
            <h2>
                <GitBranch :size="20" class="ng-hdr-icon" />
                {{ repo.name }}
                <span v-if="repo.is_private" class="ng-badge">{{ t('git', 'Private') }}</span>
            </h2>
            <p v-if="repo.description" class="ng-repo-desc">{{ repo.description }}</p>

            <!-- Tab bar -->
            <nav class="ng-tabs">
                <router-link :to="treePath" class="ng-tab" :class="{ 'ng-tab--active': isTab('tree') || isTab('blob') }">
                    <Code :size="15" /> {{ t('git', 'Code') }}
                </router-link>
                <router-link :to="`/${owner}/${name}/commits/${defaultBranch}`" class="ng-tab" :class="{ 'ng-tab--active': isTab('commits') }">
                    <Clock :size="15" /> {{ t('git', 'Commits') }}
                </router-link>
                <router-link :to="`/${owner}/${name}/graph`" class="ng-tab" :class="{ 'ng-tab--active': isTab('graph') }">
                    <Network :size="15" /> {{ t('git', 'Graph') }}
                </router-link>
                <router-link :to="`/${owner}/${name}/issues`" class="ng-tab" :class="{ 'ng-tab--active': isTab('issues') }">
                    <CircleDot :size="15" /> {{ t('git', 'Issues') }}
                    <span v-if="openIssueCount > 0" class="ng-tab-count">{{ openIssueCount }}</span>
                </router-link>
                <router-link :to="`/${owner}/${name}/pulls`" class="ng-tab" :class="{ 'ng-tab--active': isTab('pulls') }">
                    <GitMerge :size="15" /> {{ t('git', 'Pull Requests') }}
                </router-link>
                <router-link :to="`/${owner}/${name}/settings`" class="ng-tab" :class="{ 'ng-tab--active': isTab('settings') }">
                    <Settings :size="15" /> {{ t('git', 'Settings') }}
                </router-link>
            </nav>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
        <div v-else-if="error" class="ng-error-msg">{{ error }}</div>
        <router-view v-else-if="repo" v-bind="childProps" @repo-updated="onRepoUpdated" />
    </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { provide } from 'vue'
import { GitBranch, Code, Clock, CircleDot, GitMerge, Network, Settings } from 'lucide-vue-next'

export default {
    name: 'RepoView',
    components: { GitBranch, Code, Clock, CircleDot, GitMerge, Network, Settings },

    data() {
        return {
            repo: null, branches: [], defaultBranch: 'main',
            openIssueCount: 0, loading: false, error: '',
        }
    },

    computed: {
        owner() { return this.$route.params.owner },
        name()  { return this.$route.params.repo },
        treePath() {
            const b = this.defaultBranch || 'main'
            return `/${this.owner}/${this.name}/tree/${b}`
        },
        childProps() {
            return {
                repo: this.repo,
                branches: this.branches,
                defaultBranch: this.defaultBranch,
                owner: this.owner,
                repoName: this.name,
            }
        },
    },

    watch: {
        '$route.params': {
            immediate: true,
            handler({ owner, repo }) {
                if (owner && repo) this.loadRepo(owner, repo)
            },
        },
    },

    methods: {
        async loadRepo(owner, name) {
            this.loading = true
            this.error = ''
            try {
                const [repoRes, branchRes] = await Promise.all([
                    axios.get(generateUrl(`/apps/git/api/repos/${owner}/${name}`)),
                    axios.get(generateUrl(`/apps/git/api/repos/${owner}/${name}/branches`)),
                ])
                this.repo = repoRes.data
                this.branches = branchRes.data
                this.defaultBranch = this.repo.default_branch || (this.branches[0] ?? 'main')

                // If landing on bare /:owner/:repo (no child route), navigate to tree
                const activePath = this.$route.path
                const basePath   = `/${owner}/${name}`
                if (activePath === basePath || activePath === basePath + '/') {
                    this.$router.replace(`${basePath}/tree/${this.defaultBranch}`)
                }

                // Quietly load open issue count for tab badge
                try {
                    const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${owner}/${name}/issues`))
                    this.openIssueCount = data.length
                } catch { /* issues not critical */ }
            } catch (e) {
                this.error = e.response?.status === 404
                    ? this.t('git', 'Repository not found.')
                    : this.t('git', 'Failed to load repository.')
            } finally {
                this.loading = false
            }
        },

        isTab(tab) {
            const path = this.$route.path
            if (tab === 'tree') return path.includes('/tree/') || path === `/${this.owner}/${this.name}` || path === `/${this.owner}/${this.name}/`
            if (tab === 'blob') return path.includes('/blob/')
            return path.includes(`/${tab}`)
        },

        onRepoUpdated(updatedRepo) {
            this.repo = updatedRepo
            this.defaultBranch = updatedRepo.default_branch
        },
    },
}
</script>

<style scoped>
.ng-repo-view { display: flex; flex-direction: column; height: 100%; }
.ng-repo-header { padding: 20px 28px 0 52px; border-bottom: 1px solid var(--color-border); }
.ng-repo-header h2 {
    display: flex; align-items: center; gap: 8px;
    font-size: 20px; font-weight: 700; margin: 0 0 6px;
}
.ng-hdr-icon { color: var(--color-primary-element); flex-shrink: 0; }
.ng-repo-desc { color: var(--color-text-maxcontrast); margin: 0 0 14px; font-size: 13px; }
.ng-badge {
    font-size: 11px; font-weight: 600; background: var(--color-border);
    border-radius: 4px; padding: 2px 7px; color: var(--color-text-maxcontrast);
}
.ng-tabs { display: flex; gap: 0; margin-top: 8px; }
.ng-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 16px; border-bottom: 2px solid transparent;
    text-decoration: none; font-size: 13px; color: var(--color-text-maxcontrast);
    margin-bottom: -1px; transition: color 0.15s, border-color 0.15s;
}
.ng-tab:hover { color: var(--color-main-text); }
.ng-tab--active { color: var(--color-primary-element); border-bottom-color: var(--color-primary-element); font-weight: 600; }
.ng-tab-count {
    background: var(--color-primary-element); color: #fff;
    border-radius: 10px; padding: 1px 6px; font-size: 11px; font-weight: 700;
}
.ng-loading, .ng-error-msg { padding: 28px 32px; color: var(--color-text-maxcontrast); }
.ng-error-msg { color: var(--color-error); }
</style>
