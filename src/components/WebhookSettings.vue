<template>
    <div class="ng-webhooks">
        <h4>{{ t('git', 'Webhooks') }}</h4>

        <div v-if="hooks.length === 0" class="ng-empty">{{ t('git', 'No webhooks configured.') }}</div>
        <table v-else class="ng-hook-table">
            <thead>
                <tr>
                    <th>{{ t('git', 'URL') }}</th>
                    <th>{{ t('git', 'Events') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="hook in hooks" :key="hook.id">
                    <td><code class="ng-hook-url">{{ hook.url }}</code></td>
                    <td>{{ hook.events.join(', ') }}</td>
                    <td>
                        <NcButton type="error" @click="del(hook.id)">{{ t('git', 'Delete') }}</NcButton>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="ng-add-hook">
            <h5>{{ t('git', 'Add webhook') }}</h5>
            <div class="ng-form-group">
                <label>{{ t('git', 'Payload URL') }}</label>
                <input v-model="form.url" type="url" class="ng-input" placeholder="https://example.com/webhook" />
            </div>
            <div class="ng-form-group">
                <label>{{ t('git', 'Secret') }}</label>
                <input v-model="form.secret" type="text" class="ng-input" placeholder="Optional secret" />
            </div>
            <div class="ng-form-group">
                <label>{{ t('git', 'Events') }}</label>
                <div class="ng-checks">
                    <label v-for="ev in availableEvents" :key="ev">
                        <input v-model="form.events" type="checkbox" :value="ev" /> {{ ev }}
                    </label>
                </div>
            </div>
            <p v-if="error" class="ng-error">{{ error }}</p>
            <NcButton type="primary" :disabled="saving" @click="save">
                {{ saving ? t('git', 'Adding…') : t('git', 'Add webhook') }}
            </NcButton>
        </div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
    name: 'WebhookSettings',
    components: { NcButton },
    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },
    data() {
        return {
            hooks: [], loading: false, saving: false, error: '',
            availableEvents: ['push', 'create', 'delete', 'issues', 'pull_request'],
            form: { url: '', secret: '', events: ['push'] },
        }
    },
    mounted() { this.load() },
    methods: {
        async load() {
            try {
                const { data } = await axios.get(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/webhooks`))
                this.hooks = data
            } catch { this.hooks = [] }
        },
        async save() {
            this.error = ''
            if (!this.form.url) { this.error = this.t('git', 'URL is required'); return }
            this.saving = true
            try {
                const { data } = await axios.post(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/webhooks`), {
                    url: this.form.url, secret: this.form.secret, events: this.form.events,
                })
                this.hooks.push(data)
                this.form = { url: '', secret: '', events: ['push'] }
            } catch (e) {
                this.error = e.response?.data?.error ?? this.t('git', 'Failed to add webhook')
            } finally { this.saving = false }
        },
        async del(id) {
            try {
                await axios.delete(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/webhooks/${id}`),
                    { headers: { requesttoken: OC.requestToken } })
                this.hooks = this.hooks.filter(h => h.id !== id)
            } catch (e) { console.error(e) }
        },
    },
}
</script>

<style scoped>
.ng-webhooks { margin-top: 24px; }
.ng-webhooks h4 { font-size: 15px; font-weight: 700; margin: 0 0 12px; }
.ng-hook-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.ng-hook-table th { padding: 6px 12px; text-align: left; font-size: 12px; font-weight: 600; color: var(--color-text-maxcontrast); border-bottom: 1px solid var(--color-border); }
.ng-hook-table td { padding: 8px 12px; border-bottom: 1px solid var(--color-border); font-size: 13px; }
.ng-hook-url { font-size: 12px; word-break: break-all; }
.ng-add-hook { border: 1px solid var(--color-border); border-radius: 8px; padding: 16px; }
.ng-add-hook h5 { font-size: 14px; font-weight: 700; margin: 0 0 14px; }
.ng-form-group { margin-bottom: 14px; }
.ng-form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
.ng-input { width: 100%; padding: 7px 10px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 13px; box-sizing: border-box; }
.ng-checks { display: flex; flex-wrap: wrap; gap: 12px; }
.ng-checks label { display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; }
.ng-error { color: var(--color-error); font-size: 13px; margin-bottom: 8px; }
.ng-empty { color: var(--color-text-maxcontrast); font-size: 13px; margin-bottom: 16px; }
</style>
