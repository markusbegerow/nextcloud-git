<template>
    <div class="ng-settings">
        <h3>{{ t('git', 'Repository settings') }}</h3>

        <!-- General settings -->
        <section class="ng-settings-section">
            <h4>{{ t('git', 'General') }}</h4>
            <div class="ng-form-group">
                <label>{{ t('git', 'Repository name') }}</label>
                <input v-model="form.name" type="text" class="ng-input" />
            </div>
            <div class="ng-form-group">
                <label>{{ t('git', 'Description') }}</label>
                <input v-model="form.description" type="text" class="ng-input" />
            </div>
            <div class="ng-form-group">
                <label>{{ t('git', 'Default branch') }}</label>
                <select v-model="form.defaultBranch" class="ng-select">
                    <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                </select>
            </div>
            <p v-if="saveError" class="ng-error">{{ saveError }}</p>
            <p v-if="saveSuccess" class="ng-success">{{ t('git', 'Settings saved.') }}</p>
            <NcButton type="primary" :disabled="saving" @click="save">
                {{ saving ? t('git', 'Saving…') : t('git', 'Save settings') }}
            </NcButton>
        </section>

        <!-- Webhooks -->
        <section class="ng-settings-section">
            <WebhookSettings :owner="owner" :repo-name="repoName" />
        </section>

        <!-- Danger zone -->
        <section class="ng-settings-section ng-danger-zone">
            <div class="ng-danger-header">
                <span>⚠</span>
                <span>{{ t('git', 'Danger zone') }}</span>
            </div>

            <div class="ng-danger-row">
                <div>
                    <strong>{{ t('git', 'Transfer ownership') }}</strong>
                    <p>{{ t('git', 'Transfer this repository to another user.') }}</p>
                    <div class="ng-inline-form">
                        <input v-model="transferTarget" type="text" class="ng-input-sm" :placeholder="t('git', 'Username')" />
                        <NcButton type="warning" @click="transfer">{{ t('git', 'Transfer') }}</NcButton>
                    </div>
                    <p v-if="transferError" class="ng-error">{{ transferError }}</p>
                </div>
            </div>

            <div class="ng-danger-row">
                <div>
                    <strong>{{ t('git', 'Delete this repository') }}</strong>
                    <p>{{ t('git', 'This action cannot be undone.') }}</p>
                    <NcButton type="error" @click="showDeleteConfirm = true">{{ t('git', 'Delete repository') }}</NcButton>
                </div>
            </div>
        </section>

        <!-- Delete confirm modal -->
        <NcModal v-if="showDeleteConfirm" :name="t('git', 'Delete repository')" @close="showDeleteConfirm = false">
            <div class="ng-modal-body">
                <h3>{{ t('git', 'Delete repository') }}</h3>
                <p>{{ t('git', 'Type the repository name to confirm:') }}</p>
                <input v-model="deleteConfirmName" type="text" class="ng-input" :placeholder="repoName" />
                <div class="ng-modal-actions">
                    <NcButton @click="showDeleteConfirm = false">{{ t('git', 'Cancel') }}</NcButton>
                    <NcButton type="error" :disabled="deleteConfirmName !== repoName || deleting" @click="deleteRepo">
                        {{ deleting ? t('git', 'Deleting…') : t('git', 'Permanently delete') }}
                    </NcButton>
                </div>
            </div>
        </NcModal>
    </div>
</template>

<script>
import { NcButton, NcModal } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import WebhookSettings from '../components/WebhookSettings.vue'

export default {
    name: 'RepoSettingsView',
    components: { NcButton, NcModal, WebhookSettings },
    emits: ['repo-updated', 'repo-deleted'],
    props: {
        owner:         { type: String, required: true },
        repoName:      { type: String, required: true },
        repo:          { type: Object, default: null },
        branches:      { type: Array,  default: () => [] },
        defaultBranch: { type: String, default: 'main' },
    },
    data() {
        return {
            form: { name: '', description: '', defaultBranch: '' },
            saving: false, saveError: '', saveSuccess: false,
            transferTarget: '', transferError: '',
            showDeleteConfirm: false, deleteConfirmName: '', deleting: false,
        }
    },
    watch: {
        repo: { immediate: true, handler(r) {
            if (r) {
                this.form.name = r.name
                this.form.description = r.description ?? ''
                this.form.defaultBranch = r.default_branch
            }
        } },
    },
    methods: {
        async save() {
            this.saveError = ''; this.saveSuccess = false; this.saving = true
            try {
                const { data } = await axios.patch(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}`),
                    { name: this.form.name, description: this.form.description, default_branch: this.form.defaultBranch }
                )
                this.$emit('repo-updated', data)
                this.saveSuccess = true
                if (data.name !== this.repoName) {
                    this.$router.replace(`/${this.owner}/${data.name}/settings`)
                }
            } catch (e) {
                this.saveError = e.response?.data?.error ?? this.t('git', 'Failed to save settings')
            } finally { this.saving = false }
        },
        async transfer() {
            this.transferError = ''
            if (!this.transferTarget.trim()) { this.transferError = this.t('git', 'Username required'); return }
            try {
                const { data } = await axios.post(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/transfer`),
                    { new_owner: this.transferTarget.trim() }
                )
                this.$router.push(`/${data.owner_uid}/${data.name}`)
            } catch (e) {
                this.transferError = e.response?.data?.error ?? this.t('git', 'Transfer failed')
            }
        },
        async deleteRepo() {
            this.deleting = true
            try {
                await axios.delete(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}`),
                    { headers: { requesttoken: OC.requestToken } }
                )
                this.$emit('repo-deleted', this.repo?.id)
                this.$router.push('/')
            } catch (e) { console.error(e) }
            finally { this.deleting = false }
        },
    },
}
</script>

<style scoped>
.ng-settings { padding: 20px 28px 20px 52px; max-width: 700px; }
.ng-settings h3 { font-size: 20px; font-weight: 700; margin: 0 0 24px; }
.ng-settings-section { border: 1px solid var(--color-border); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.ng-settings-section h4 { font-size: 15px; font-weight: 700; margin: 0 0 16px; }
.ng-form-group { margin-bottom: 14px; }
.ng-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
.ng-input, .ng-select { width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 14px; box-sizing: border-box; }
.ng-input-sm { padding: 6px 10px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 13px; }
.ng-danger-zone {
    border: 2px solid #c0392b;
    padding: 0;
    overflow: hidden;
}
.ng-danger-header {
    display: flex; align-items: center; gap: 10px;
    background: #c0392b;
    color: #ffffff;
    padding: 12px 20px;
    font-size: 15px; font-weight: 700;
    letter-spacing: 0.02em;
}
.ng-danger-header span:first-child { font-size: 18px; }
/* Re-add inner padding for the rows */
.ng-danger-zone .ng-danger-row { padding: 12px 20px; }
.ng-danger-zone .ng-danger-row:last-child { border-bottom: none; }
.ng-danger-row { border-bottom: 1px solid var(--color-border); padding: 12px 0; }
.ng-danger-row:last-child { border-bottom: none; }
.ng-danger-row p { font-size: 13px; color: var(--color-text-maxcontrast); margin: 4px 0 10px; }
.ng-inline-form { display: flex; gap: 8px; align-items: center; }
.ng-error { color: var(--color-error); font-size: 13px; margin-top: 6px; }
.ng-success { color: var(--color-success, #2da44e); font-size: 13px; margin-top: 6px; }
.ng-modal-body { padding: 24px; }
.ng-modal-body h3 { margin: 0 0 12px; }
.ng-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
</style>
