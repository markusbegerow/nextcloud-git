<template>
    <div class="ng-new-pull">
        <div class="ng-page-header">
            <button class="ng-back" @click="$router.back()"><ArrowLeft :size="16" /> {{ t('git', 'Back') }}</button>
            <h3>{{ t('git', 'New pull request') }}</h3>
        </div>

        <div class="ng-form-card">
            <div class="ng-branch-row">
                <div class="ng-form-group">
                    <label>{{ t('git', 'Base branch') }}</label>
                    <select v-model="baseBranch" class="ng-select">
                        <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                    </select>
                </div>
                <span class="ng-arrow">←</span>
                <div class="ng-form-group">
                    <label>{{ t('git', 'Head branch') }}</label>
                    <select v-model="headBranch" class="ng-select">
                        <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                    </select>
                </div>
            </div>

            <div class="ng-form-group">
                <label>{{ t('git', 'Title') }} *</label>
                <input v-model="title" type="text" class="ng-input" :placeholder="t('git', 'Pull request title')" />
            </div>

            <div class="ng-form-group">
                <label>{{ t('git', 'Description') }}</label>
                <textarea v-model="body" class="ng-textarea" rows="5" :placeholder="t('git', 'Optional description')" />
            </div>

            <p v-if="error" class="ng-error">{{ error }}</p>
            <div class="ng-form-actions">
                <NcButton @click="$router.back()">{{ t('git', 'Cancel') }}</NcButton>
                <NcButton type="primary" :disabled="submitting" @click="submit">
                    {{ submitting ? t('git', 'Creating…') : t('git', 'Create pull request') }}
                </NcButton>
            </div>
        </div>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { ArrowLeft } from 'lucide-vue-next'

export default {
    name: 'NewPullView',
    components: { NcButton, ArrowLeft },
    props: {
        owner:         { type: String, required: true },
        repoName:      { type: String, required: true },
        branches:      { type: Array,  default: () => [] },
        defaultBranch: { type: String, default: 'main' },
    },
    data() { return { title: '', body: '', headBranch: '', baseBranch: '', submitting: false, error: '' } },
    mounted() {
        this.baseBranch = this.defaultBranch || (this.branches[0] ?? '')
        this.headBranch = this.branches.find(b => b !== this.baseBranch) ?? ''
    },
    methods: {
        async submit() {
            this.error = ''
            if (!this.title.trim()) { this.error = this.t('git', 'Title is required'); return }
            if (this.headBranch === this.baseBranch) { this.error = this.t('git', 'Head and base branches must differ'); return }
            this.submitting = true
            try {
                const { data } = await axios.post(generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/pulls`), {
                    title: this.title.trim(), body: this.body,
                    head_branch: this.headBranch, base_branch: this.baseBranch,
                })
                this.$router.push(`/${this.owner}/${this.repoName}/pulls/${data.number}`)
            } catch (e) {
                this.error = e.response?.data?.error ?? this.t('git', 'Failed to create pull request')
            } finally { this.submitting = false }
        },
    },
}
</script>

<style scoped>
.ng-new-pull { padding: 20px 28px 20px 52px; max-width: 700px; }
.ng-page-header { margin-bottom: 20px; }
.ng-page-header h3 { font-size: 18px; font-weight: 700; margin: 10px 0 0; }
.ng-back { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; color: var(--color-text-maxcontrast); font-size: 13px; padding: 0; }
.ng-back:hover { color: var(--color-main-text); }
.ng-form-card { border: 1px solid var(--color-border); border-radius: 8px; padding: 24px; }
.ng-branch-row { display: flex; align-items: flex-end; gap: 12px; margin-bottom: 20px; }
.ng-branch-row .ng-form-group { flex: 1; margin: 0; }
.ng-arrow { font-size: 20px; padding-bottom: 4px; color: var(--color-text-maxcontrast); }
.ng-form-group { margin-bottom: 16px; }
.ng-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.ng-select, .ng-input { width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 14px; box-sizing: border-box; }
.ng-textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: 6px; background: var(--color-main-background); color: var(--color-main-text); font-size: 14px; box-sizing: border-box; resize: vertical; }
.ng-error { color: var(--color-error); font-size: 13px; display: block; margin-top: 4px; }
.ng-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
</style>
