<template>
    <NcContent app-name="git">
        <!-- ───────── Left sidebar ───────── -->
        <NcAppNavigation>
            <template #list>
                <NcAppNavigationNew
                    :text="t('git', 'New repository')"
                    @click="$router.push('/new')">
                    <template #icon>
                        <Plus :size="20" />
                    </template>
                </NcAppNavigationNew>

                <NcAppNavigationItem
                    v-for="repo in repos"
                    :key="repo.id"
                    :name="repo.name"
                    :class="{ active: isActiveRepo(repo) }"
                    @click="openRepo(repo)">
                    <template #icon>
                        <GitBranch :size="20" />
                    </template>
                    <template #actions>
                        <NcActionButton @click.stop="confirmDelete(repo)">
                            <template #icon>
                                <Delete :size="20" />
                            </template>
                            {{ t('git', 'Delete') }}
                        </NcActionButton>
                    </template>
                </NcAppNavigationItem>

                <div v-if="!loading && repos.length === 0" class="ng-nav-empty">
                    {{ t('git', 'No repositories yet') }}
                </div>
            </template>

            <!-- ── Footer: Info + Settings ── -->
            <template #footer>
                <div class="ng-sidebar-footer">
                    <NcButton type="tertiary" @click="showAbout = true">
                        <template #icon><Info :size="16" /></template>
                        {{ t('git', 'Info') }}
                    </NcButton>
                    <NcButton type="tertiary" @click="showSettings = true">
                        <template #icon><Settings :size="16" /></template>
                        {{ t('git', 'Settings') }}
                    </NcButton>
                </div>
            </template>
        </NcAppNavigation>

        <!-- ───────── Main content (router-view) ───────── -->
        <NcAppContent>
            <router-view :repos="repos" @repo-created="onRepoCreated" @repo-deleted="onRepoDeleted" />
        </NcAppContent>

        <!-- Delete confirmation modal -->
        <NcModal v-if="deleteTarget" :name="t('git', 'Delete repository')" @close="deleteTarget = null">
            <div class="ng-modal-body">
                <h2>{{ t('git', 'Delete repository') }}</h2>
                <p>{{ t('git', 'Are you sure you want to delete "{name}"? This cannot be undone.', { name: deleteTarget.name }) }}</p>
                <div class="ng-modal-actions">
                    <NcButton @click="deleteTarget = null">{{ t('git', 'Cancel') }}</NcButton>
                    <NcButton type="error" :disabled="deleting" @click="deleteRepo">
                        {{ deleting ? t('git', 'Deleting…') : t('git', 'Delete') }}
                    </NcButton>
                </div>
            </div>
        </NcModal>

        <!-- About dialog -->
        <AboutDialog v-if="showAbout" @close="showAbout = false" />

        <!-- Settings dialog (context-aware) -->
        <SettingsDialog v-if="showSettings" :current-repo="currentRepo" @close="showSettings = false" />
    </NcContent>
</template>

<script>
import {
    NcContent,
    NcAppNavigation,
    NcAppNavigationItem,
    NcAppNavigationNew,
    NcAppContent,
    NcButton,
    NcModal,
    NcActionButton,
} from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { GitBranch, Plus, Delete, Info, Settings } from 'lucide-vue-next'
import AboutDialog from './components/AboutDialog.vue'
import SettingsDialog from './components/SettingsDialog.vue'

export default {
    name: 'NextGitApp',

    components: {
        NcContent, NcAppNavigation, NcAppNavigationItem, NcAppNavigationNew,
        NcAppContent, NcButton, NcModal, NcActionButton,
        GitBranch, Plus, Delete, Info, Settings,
        AboutDialog, SettingsDialog,
    },

    data() {
        return {
            repos:        [],
            loading:      false,
            deleteTarget: null,
            deleting:     false,
            showAbout:    false,
            showSettings: false,
        }
    },

    computed: {
        currentRepo() {
            const { owner, repo } = this.$route.params
            if (!owner || !repo) return null
            return this.repos.find(r => r.owner_uid === owner && r.name === repo) ?? null
        },
    },

    async mounted() {
        await this.loadRepos()
    },

    methods: {
        async loadRepos() {
            this.loading = true
            try {
                const { data } = await axios.get(generateUrl('/apps/git/api/repos'))
                this.repos = data
            } catch (e) {
                console.error('NextGit: failed to load repos', e)
            } finally {
                this.loading = false
            }
        },

        isActiveRepo(repo) {
            const { owner, repo: name } = this.$route.params
            return owner === repo.owner_uid && name === repo.name
        },

        openRepo(repo) {
            this.$router.push(`/${repo.owner_uid}/${repo.name}`)
        },

        onRepoCreated(repo) {
            this.repos.push(repo)
        },

        onRepoDeleted(repoId) {
            this.repos = this.repos.filter(r => r.id !== repoId)
        },

        confirmDelete(repo) {
            this.deleteTarget = repo
        },

        async deleteRepo() {
            if (!this.deleteTarget) return
            this.deleting = true
            try {
                await axios.delete(
                    generateUrl(`/apps/git/api/repos/${this.deleteTarget.owner_uid}/${this.deleteTarget.name}`),
                    { headers: { requesttoken: OC.requestToken } }
                )
                this.repos = this.repos.filter(r => r.id !== this.deleteTarget.id)
                if (this.$route.params.owner === this.deleteTarget.owner_uid &&
                    this.$route.params.repo  === this.deleteTarget.name) {
                    this.$router.push('/')
                }
                this.deleteTarget = null
            } catch (e) {
                console.error('NextGit: delete failed', e)
            } finally {
                this.deleting = false
            }
        },
    },
}
</script>

<style scoped>
.ng-nav-empty {
    padding: 12px 16px;
    color: var(--color-text-maxcontrast);
    font-size: 13px;
}

/* Sidebar footer — mirrors web-rdp pattern */
.ng-sidebar-footer {
    display: flex;
    gap: 4px;
    padding: 4px 8px;
    border-top: 1px solid var(--color-border);
}

.ng-sidebar-footer :deep(.button-vue) {
    flex: 1;
    justify-content: center;
}

.ng-modal-body {
    padding: 24px;
}
.ng-modal-body h2 {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 700;
}
.ng-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 20px;
}
</style>
