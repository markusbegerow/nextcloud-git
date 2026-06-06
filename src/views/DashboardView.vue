<template>
    <div class="ng-dashboard">
        <div class="ng-dashboard-header">
            <h2>{{ t('git', 'Repositories') }}</h2>
            <NcButton type="primary" @click="$router.push('/new')">
                <template #icon><Plus :size="20" /></template>
                {{ t('git', 'New repository') }}
            </NcButton>
        </div>

        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>

        <div v-else-if="repos.length === 0" class="ng-empty-state">
            <GitBranch :size="64" class="ng-empty-icon" />
            <h3>{{ t('git', 'No repositories yet') }}</h3>
            <p>{{ t('git', 'Create your first repository to get started.') }}</p>
            <NcButton type="primary" @click="$router.push('/new')">
                <template #icon><Plus :size="20" /></template>
                {{ t('git', 'New repository') }}
            </NcButton>
        </div>

        <div v-else class="ng-repo-grid">
            <div
                v-for="repo in repos"
                :key="repo.id"
                class="ng-repo-card"
                @click="$router.push(`/${repo.owner_uid}/${repo.name}`)">
                <div class="ng-repo-card-header">
                    <GitBranch :size="18" class="ng-repo-icon" />
                    <span class="ng-repo-name">{{ repo.name }}</span>
                    <span v-if="repo.is_private" class="ng-badge">{{ t('git', 'Private') }}</span>
                </div>
                <p v-if="repo.description" class="ng-repo-desc">{{ repo.description }}</p>
                <div class="ng-repo-meta">
                    <span>{{ t('git', 'Default: {branch}', { branch: repo.default_branch }) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { GitBranch, Plus } from 'lucide-vue-next'

export default {
    name: 'DashboardView',
    components: { NcButton, GitBranch, Plus },
    props: {
        repos: { type: Array, default: () => [] },
    },
    data() {
        return { loading: false }
    },
}
</script>

<style scoped>
.ng-dashboard { padding: 28px 32px 28px 52px; }
.ng-dashboard-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.ng-dashboard-header h2 { font-size: 22px; font-weight: 700; margin: 0; }

.ng-empty-state {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; height: 50vh; gap: 16px;
    color: var(--color-text-maxcontrast); text-align: center;
}
.ng-empty-state h3 { font-size: 18px; color: var(--color-main-text); margin: 0; }
.ng-empty-icon { opacity: 0.35; color: var(--color-text-maxcontrast); }

.ng-repo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
}
.ng-repo-card {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 16px;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.ng-repo-card:hover {
    border-color: var(--color-primary-element);
    background: var(--color-background-hover);
}
.ng-repo-card-header {
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
}
.ng-repo-icon { color: var(--color-primary-element); flex-shrink: 0; }
.ng-repo-name { font-weight: 600; font-size: 15px; flex: 1; }
.ng-badge {
    font-size: 11px; font-weight: 600;
    background: var(--color-border); border-radius: 4px;
    padding: 2px 7px; color: var(--color-text-maxcontrast);
}
.ng-repo-desc {
    font-size: 13px; color: var(--color-text-maxcontrast);
    margin: 0 0 8px; overflow: hidden;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.ng-repo-meta { font-size: 12px; color: var(--color-text-maxcontrast); }
.ng-loading { color: var(--color-text-maxcontrast); padding: 24px; }
</style>
