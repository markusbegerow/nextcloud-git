<template>
    <NcModal :name="t('git', 'Settings')" @close="$emit('close')">
        <div class="ng-settings-dlg">

            <!-- Clone preference -->
            <div class="ng-sd-section">
                <h4>{{ t('git', 'Clone preference') }}</h4>
                <div class="ng-sd-row">
                    <label for="ng-clone-pref">{{ t('git', 'Default clone protocol') }}</label>
                    <select id="ng-clone-pref" v-model="clonePref" class="ng-select" @change="savePref">
                        <option value="https">HTTPS</option>
                        <option value="ssh">SSH</option>
                    </select>
                </div>
                <code class="ng-clone-preview">{{ previewUrl }}</code>
            </div>

            <!-- Context: current repository (only shown when a repo is open) -->
            <div v-if="currentRepo" class="ng-sd-section ng-sd-repo">
                <h4>{{ t('git', 'Current repository') }}</h4>
                <div class="ng-sd-repo-name">
                    <GitBranch :size="16" />
                    <strong>{{ currentRepo.name }}</strong>
                    <span v-if="currentRepo.is_private" class="ng-badge-sm">{{ t('git', 'Private') }}</span>
                </div>
                <p v-if="currentRepo.description" class="ng-sd-repo-desc">{{ currentRepo.description }}</p>

                <div class="ng-sd-quicklinks">
                    <NcButton @click="go('tree')">
                        <template #icon><Code :size="16" /></template>
                        {{ t('git', 'Code') }}
                    </NcButton>
                    <NcButton @click="go('issues')">
                        <template #icon><CircleDot :size="16" /></template>
                        {{ t('git', 'Issues') }}
                    </NcButton>
                    <NcButton @click="go('pulls')">
                        <template #icon><GitMerge :size="16" /></template>
                        {{ t('git', 'Pull Requests') }}
                    </NcButton>
                    <NcButton @click="go('settings')">
                        <template #icon><Settings :size="16" /></template>
                        {{ t('git', 'Repo settings') }}
                    </NcButton>
                </div>
            </div>

            <div class="ng-sd-actions">
                <NcButton type="primary" @click="$emit('close')">{{ t('git', 'Close') }}</NcButton>
            </div>
        </div>
    </NcModal>
</template>

<script>
import { NcModal, NcButton } from '@nextcloud/vue'
import { GitBranch, Code, CircleDot, GitMerge, Settings } from 'lucide-vue-next'

const PREF_KEY = 'nextgit-clone-pref'

export default {
    name: 'SettingsDialog',
    components: { NcModal, NcButton, GitBranch, Code, CircleDot, GitMerge, Settings },
    emits: ['close'],
    props: {
        currentRepo: { type: Object, default: null },
    },
    data() {
        return {
            clonePref: localStorage.getItem(PREF_KEY) || 'https',
        }
    },
    computed: {
        previewUrl() {
            const base = window.location.origin
            const user = this.currentRepo?.owner_uid ?? '{user}'
            const repo = this.currentRepo?.name ?? '{repo}'
            if (this.clonePref === 'ssh') {
                return `git@${window.location.hostname}:${user}/${repo}.git`
            }
            return `${base}/apps/git/git/${user}/${repo}.git`
        },
    },
    methods: {
        savePref() {
            localStorage.setItem(PREF_KEY, this.clonePref)
        },
        go(section) {
            if (!this.currentRepo) return
            const owner = this.currentRepo.owner_uid
            const name  = this.currentRepo.name
            let path = `/${owner}/${name}/${section}`
            if (section === 'tree') {
                path = `/${owner}/${name}/tree/${this.currentRepo.default_branch}`
            }
            this.$router.push(path)
            this.$emit('close')
        },
    },
}
</script>

<style scoped>
.ng-settings-dlg { padding: 24px; max-width: 420px; }

.ng-sd-section { margin-bottom: 22px; }
.ng-sd-section h4 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: var(--color-text-maxcontrast);
    margin: 0 0 10px; padding-bottom: 6px;
    border-bottom: 1px solid var(--color-border);
}

.ng-sd-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 8px;
}
.ng-sd-row label { font-size: 13px; font-weight: 500; }
.ng-select {
    padding: 6px 10px; border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text);
    font-size: 13px; cursor: pointer;
}

.ng-clone-preview {
    display: block;
    background: var(--color-background-dark); border: 1px solid var(--color-border);
    border-radius: 6px; padding: 8px 12px; font-family: monospace;
    font-size: 12px; word-break: break-all;
}

.ng-sd-repo-name {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; margin-bottom: 6px;
    color: var(--color-main-text);
}
.ng-badge-sm {
    font-size: 11px; font-weight: 600; background: var(--color-border);
    border-radius: 4px; padding: 1px 6px; color: var(--color-text-maxcontrast);
}
.ng-sd-repo-desc { font-size: 13px; color: var(--color-text-maxcontrast); margin: 0 0 12px; }

.ng-sd-quicklinks { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

.ng-sd-actions { display: flex; justify-content: flex-end; margin-top: 8px; }
</style>
