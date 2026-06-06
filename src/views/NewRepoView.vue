<template>
    <div class="ng-new-repo">
        <div class="ng-page-header">
            <button class="ng-back" @click="$router.push('/')">
                <ArrowLeft :size="18" /> {{ t('git', 'Back') }}
            </button>
            <h2>{{ t('git', 'Create a new repository') }}</h2>
        </div>

        <div class="ng-form-card">
            <div class="ng-form-group">
                <label for="nr-name">{{ t('git', 'Repository name') }} *</label>
                <input
                    id="nr-name"
                    v-model="name"
                    type="text"
                    class="ng-input"
                    :placeholder="t('git', 'my-project')"
                    autofocus
                    @keydown.enter="submit" />
                <span v-if="nameError" class="ng-error">{{ nameError }}</span>
                <span class="ng-hint">{{ t('git', 'Letters, numbers, hyphens, dots, underscores.') }}</span>
            </div>

            <div class="ng-form-group">
                <label for="nr-desc">{{ t('git', 'Description') }}</label>
                <input
                    id="nr-desc"
                    v-model="description"
                    type="text"
                    class="ng-input"
                    :placeholder="t('git', 'Optional short description')" />
            </div>

            <div class="ng-form-group ng-form-group--inline">
                <input id="nr-private" v-model="isPrivate" type="checkbox" />
                <label for="nr-private">{{ t('git', 'Private repository') }}</label>
            </div>

            <p v-if="error" class="ng-error">{{ error }}</p>

            <div class="ng-form-actions">
                <NcButton @click="$router.push('/')">{{ t('git', 'Cancel') }}</NcButton>
                <NcButton type="primary" :disabled="creating" @click="submit">
                    {{ creating ? t('git', 'Creating…') : t('git', 'Create repository') }}
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
    name: 'NewRepoView',
    components: { NcButton, ArrowLeft },
    emits: ['repo-created'],
    data() {
        return {
            name: '', description: '', isPrivate: false,
            creating: false, nameError: '', error: '',
        }
    },
    methods: {
        async submit() {
            this.nameError = ''
            this.error = ''
            if (!this.name.trim()) { this.nameError = this.t('git', 'Name is required'); return }
            this.creating = true
            try {
                const { data } = await axios.post(generateUrl('/apps/git/api/repos'), {
                    name: this.name.trim(),
                    description: this.description,
                    is_private: this.isPrivate,
                })
                this.$emit('repo-created', data)
                this.$router.push(`/${data.owner_uid}/${data.name}`)
            } catch (e) {
                this.error = e.response?.data?.error ?? this.t('git', 'Failed to create repository')
            } finally {
                this.creating = false
            }
        },
    },
}
</script>

<style scoped>
.ng-new-repo { padding: 28px 32px 28px 52px; max-width: 560px; }
.ng-page-header { margin-bottom: 24px; }
.ng-page-header h2 { font-size: 22px; font-weight: 700; margin: 12px 0 0; }
.ng-back {
    display: inline-flex; align-items: center; gap: 6px;
    background: none; border: none; cursor: pointer;
    color: var(--color-text-maxcontrast); font-size: 13px; padding: 0;
}
.ng-back:hover { color: var(--color-main-text); }
.ng-form-card {
    background: var(--color-main-background);
    border: 1px solid var(--color-border);
    border-radius: 8px; padding: 24px;
}
.ng-form-group { margin-bottom: 18px; }
.ng-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
.ng-form-group--inline { display: flex; align-items: center; gap: 8px; }
.ng-form-group--inline label { margin: 0; }
.ng-input {
    width: 100%; padding: 8px 12px;
    border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text);
    font-size: 14px; box-sizing: border-box;
}
.ng-input:focus { outline: 2px solid var(--color-primary-element); border-color: var(--color-primary-element); }
.ng-hint { font-size: 12px; color: var(--color-text-maxcontrast); margin-top: 4px; display: block; }
.ng-error { color: var(--color-error); font-size: 13px; margin-top: 4px; display: block; }
.ng-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
</style>
