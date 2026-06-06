<template>
    <div
        class="ng-tree-view"
        :class="{ 'ng-drop-zone--active': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.self="isDragging = false"
        @drop.prevent="onDrop">

        <!-- Drop overlay -->
        <div v-if="isDragging" class="ng-drop-overlay">
            <div class="ng-drop-hint">
                <Upload :size="40" />
                <span>{{ t('git', 'Drop files to upload') }}</span>
            </div>
        </div>

        <!-- Empty repo -->
        <div v-if="isEmpty" class="ng-empty-repo">
            <h3>{{ t('git', 'Repository is empty') }}</h3>
            <p>{{ t('git', 'Push your first commit via CLI, or upload files using the button in the toolbar:') }}</p>
            <pre class="ng-code-block">git clone {{ cloneUrl }}
cd {{ repoName }}
echo "# {{ repoName }}" >> README.md
git add README.md
git commit -m "Initial commit"
git push origin main</pre>
            <div class="ng-clone-row">
                <span class="ng-clone-label">{{ t('git', 'Clone URL:') }}</span>
                <code class="ng-clone-url">{{ cloneUrl }}</code>
                <button class="ng-copy-btn" @click="copy(cloneUrl)">📋</button>
            </div>
        </div>

        <template v-else>
            <!-- ── Toolbar ─────────────────────────────────── -->
            <div class="ng-tree-toolbar">
                <!-- Left: branch selector + breadcrumb -->
                <div class="ng-toolbar-nav">
                    <select v-model="activeBranch" class="ng-branch-select" @change="onBranchChange">
                        <option v-for="b in branches" :key="b" :value="b">{{ b }}</option>
                    </select>
                    <Breadcrumb :owner="owner" :repo="repoName" :branch="activeBranch" :path="currentPath" />
                </div>

                <!-- Right: action buttons -->
                <div class="ng-toolbar-actions">
                    <button class="ng-icon-btn" :title="cloneUrl" @click="copy(cloneUrl)">
                        <Link :size="15" />
                    </button>
                    <NcButton @click="showNewFolderModal = true">
                        <template #icon><FolderPlus :size="16" /></template>
                        {{ t('git', 'New folder') }}
                    </NcButton>
                    <NcButton @click="openFilePicker">
                        <template #icon><Upload :size="16" /></template>
                        {{ t('git', 'Upload') }}
                    </NcButton>
                    <input ref="fileInput" type="file" multiple style="display:none" @change="onFilesPicked" />
                </div>
            </div>

            <!-- File list -->
            <div v-if="loading" class="ng-loading">{{ t('git', 'Loading…') }}</div>
            <table v-else class="ng-file-list">
                <tbody>
                    <tr v-if="currentPath" class="ng-file-row ng-file-row--up" @click="goUp">
                        <td class="ng-file-icon">📁</td>
                        <td class="ng-file-name">..</td>
                        <td class="ng-file-size"></td>
                    </tr>
                    <tr
                        v-for="entry in entries"
                        :key="entry.name"
                        class="ng-file-row"
                        @click="navigate(entry)">
                        <td class="ng-file-icon">{{ entry.type === 'tree' ? '📁' : '📄' }}</td>
                        <td class="ng-file-name">{{ entry.name }}</td>
                        <td class="ng-file-size">{{ entry.type === 'blob' && entry.size !== null ? formatSize(entry.size) : '' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- README below file list (root level only) -->
            <ReadmeView v-if="!currentPath && readmeContent" :content="readmeContent" />
        </template>

        <!-- ── Upload commit modal ─────────────────────────── -->
        <NcModal
            v-if="pendingFiles.length > 0"
            :name="t('git', 'Upload files')"
            @close="cancelUpload">
            <div class="ng-upload-modal">
                <h3>{{ t('git', 'Upload {n} file(s)', { n: pendingFiles.length }) }}</h3>

                <div class="ng-upload-file-list">
                    <div v-for="f in pendingFiles" :key="f.name" class="ng-upload-file-row">
                        <FileText :size="16" class="ng-upload-file-icon" />
                        <span class="ng-upload-file-name">{{ f.name }}</span>
                        <span class="ng-upload-file-size">{{ formatSize(f.size) }}</span>
                        <button class="ng-upload-remove" @click="removePending(f.name)">✕</button>
                    </div>
                </div>

                <div v-if="currentPath" class="ng-upload-dest">
                    {{ t('git', 'Destination:') }} <code>{{ currentPath }}/</code>
                </div>

                <div class="ng-form-group">
                    <label>{{ t('git', 'Commit message') }}</label>
                    <input v-model="commitMessage" type="text" class="ng-input" />
                </div>

                <p v-if="uploadError" class="ng-error">{{ uploadError }}</p>

                <div class="ng-upload-actions">
                    <NcButton @click="cancelUpload">{{ t('git', 'Cancel') }}</NcButton>
                    <NcButton type="primary" :disabled="uploading" @click="commitUpload">
                        <template #icon><Upload :size="16" /></template>
                        {{ uploading ? t('git', 'Uploading…') : t('git', 'Commit & upload') }}
                    </NcButton>
                </div>
            </div>
        </NcModal>

        <!-- ── New folder modal ────────────────────────────── -->
        <NcModal
            v-if="showNewFolderModal"
            :name="t('git', 'New folder')"
            @close="showNewFolderModal = false">
            <div class="ng-upload-modal">
                <h3>{{ t('git', 'Create a new folder') }}</h3>
                <p class="ng-modal-hint">
                    {{ t('git', 'Git does not support empty folders — a hidden .gitkeep file will be created inside.') }}
                </p>

                <div v-if="currentPath" class="ng-upload-dest">
                    {{ t('git', 'Location:') }} <code>{{ currentPath }}/</code>
                </div>

                <div class="ng-form-group">
                    <label>{{ t('git', 'Folder name') }}</label>
                    <input
                        v-model="newFolderName"
                        type="text"
                        class="ng-input"
                        :placeholder="t('git', 'my-folder')"
                        autofocus
                        @keydown.enter="createFolder" />
                    <span v-if="newFolderError" class="ng-error">{{ newFolderError }}</span>
                </div>

                <div class="ng-upload-actions">
                    <NcButton @click="showNewFolderModal = false">{{ t('git', 'Cancel') }}</NcButton>
                    <NcButton type="primary" :disabled="creatingFolder" @click="createFolder">
                        <template #icon><FolderPlus :size="16" /></template>
                        {{ creatingFolder ? t('git', 'Creating…') : t('git', 'Create folder') }}
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
import { Upload, FileText, Link, FolderPlus } from 'lucide-vue-next'
import Breadcrumb from '../components/Breadcrumb.vue'
import ReadmeView from '../components/ReadmeView.vue'

export default {
    name: 'TreeView',
    components: { NcButton, NcModal, Upload, FileText, Link, FolderPlus, Breadcrumb, ReadmeView },
    props: {
        owner:         { type: String, required: true },
        repoName:      { type: String, required: true },
        branches:      { type: Array,  default: () => [] },
        defaultBranch: { type: String, default: 'main' },
    },
    data() {
        return {
            entries: [], readmeContent: '',
            loading: false, isEmpty: false, activeBranch: '',

            // Upload
            isDragging:    false,
            pendingFiles:  [],
            commitMessage: 'Upload files via NextGit',
            uploading:     false,
            uploadError:   '',

            // New folder
            showNewFolderModal: false,
            newFolderName:      '',
            newFolderError:     '',
            creatingFolder:     false,
        }
    },
    computed: {
        currentPath() { return this.$route.query.path ?? '' },
        cloneUrl() {
            return `${window.location.origin}/apps/git/git/${this.owner}/${this.repoName}.git`
        },
    },
    watch: {
        '$route': { immediate: true, handler() { this.load() } },
        defaultBranch(val) { if (!this.activeBranch) this.activeBranch = val },
    },
    mounted() {
        this.activeBranch = this.$route.params.branch || this.defaultBranch
    },
    methods: {
        // ── Tree loading ──────────────────────────────
        async load() {
            this.isEmpty = this.branches.length === 0
            if (this.isEmpty) return
            const branch = this.$route.params.branch || this.defaultBranch
            this.activeBranch = branch
            const path = this.currentPath
            this.loading = true
            try {
                const url = generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/tree/${branch}`) +
                    (path ? `?path=${encodeURIComponent(path)}` : '')
                const { data } = await axios.get(url)
                this.entries = data
                if (!path) this.loadReadme(branch)
            } catch (e) {
                console.error('Tree load failed', e)
            } finally {
                this.loading = false
            }
        },
        async loadReadme(branch) {
            try {
                const { data } = await axios.get(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/readme`)
                )
                this.readmeContent = data.content ?? ''
            } catch { this.readmeContent = '' }
        },
        navigate(entry) {
            const base   = `/${this.owner}/${this.repoName}`
            const prefix = this.currentPath ? this.currentPath + '/' : ''
            if (entry.type === 'tree') {
                this.$router.push(`${base}/tree/${this.activeBranch}?path=${encodeURIComponent(prefix + entry.name)}`)
            } else {
                this.$router.push(`${base}/blob/${this.activeBranch}?path=${encodeURIComponent(prefix + entry.name)}`)
            }
        },
        goUp() {
            const parts = this.currentPath.split('/').filter(Boolean)
            parts.pop()
            const newPath = parts.join('/')
            const q = newPath ? `?path=${encodeURIComponent(newPath)}` : ''
            this.$router.push(`/${this.owner}/${this.repoName}/tree/${this.activeBranch}${q}`)
        },
        onBranchChange() {
            this.$router.push(`/${this.owner}/${this.repoName}/tree/${this.activeBranch}`)
        },

        // ── Upload: file picker ───────────────────────
        openFilePicker() {
            this.$refs.fileInput.value = ''
            this.$refs.fileInput.click()
        },
        onFilesPicked(event) {
            this.readFilesIntoState(event.target.files)
        },
        onDrop(event) {
            this.isDragging = false
            this.readFilesIntoState(event.dataTransfer.files)
        },
        async readFilesIntoState(fileList) {
            if (!fileList || fileList.length === 0) return
            this.uploadError = ''
            this.commitMessage = `Upload ${fileList.length} file(s) via NextGit`
            const results = await Promise.all(Array.from(fileList).map(f => this.readFileAsBase64(f)))
            const existing = new Set(this.pendingFiles.map(f => f.name))
            for (const r of results) {
                if (existing.has(r.name)) {
                    const idx = this.pendingFiles.findIndex(f => f.name === r.name)
                    this.pendingFiles.splice(idx, 1, r)
                } else {
                    this.pendingFiles.push(r)
                }
            }
        },
        readFileAsBase64(file) {
            return new Promise((resolve) => {
                const reader = new FileReader()
                reader.onload = (e) => {
                    const base64 = e.target.result.split(',')[1] ?? ''
                    resolve({ name: file.name, contentBase64: base64, size: file.size })
                }
                reader.readAsDataURL(file)
            })
        },
        removePending(name) {
            this.pendingFiles = this.pendingFiles.filter(f => f.name !== name)
        },
        cancelUpload() {
            this.pendingFiles = []; this.uploadError = ''; this.uploading = false
        },
        async commitUpload() {
            if (this.pendingFiles.length === 0) return
            this.uploading = true; this.uploadError = ''
            try {
                await axios.post(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/upload`),
                    {
                        branch:    this.activeBranch,
                        directory: this.currentPath,
                        message:   this.commitMessage,
                        files:     this.pendingFiles.map(f => ({ name: f.name, content: f.contentBase64 })),
                    }
                )
                this.cancelUpload()
                await this.load()
            } catch (e) {
                this.uploadError = e.response?.data?.error ?? this.t('git', 'Upload failed')
            } finally {
                this.uploading = false
            }
        },

        // ── New folder ────────────────────────────────
        async createFolder() {
            this.newFolderError = ''
            const name = this.newFolderName.trim().replace(/[/\\<>:"|?*]/g, '')
            if (!name) { this.newFolderError = this.t('git', 'Folder name is required'); return }
            this.creatingFolder = true
            const dir = this.currentPath ? `${this.currentPath}/${name}` : name
            try {
                await axios.post(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/upload`),
                    {
                        branch:    this.activeBranch,
                        directory: dir,
                        message:   `chore: create ${dir}/`,
                        files:     [{ name: '.gitkeep', content: btoa('') }],
                    }
                )
                this.showNewFolderModal = false
                this.newFolderName = ''
                await this.load()
            } catch (e) {
                this.newFolderError = e.response?.data?.error ?? this.t('git', 'Failed to create folder')
            } finally {
                this.creatingFolder = false
            }
        },

        // ── Helpers ───────────────────────────────────
        formatSize(bytes) {
            if (bytes < 1024)    return bytes + ' B'
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
            return (bytes / 1048576).toFixed(1) + ' MB'
        },
        copy(text) {
            navigator.clipboard?.writeText(text).catch(() => {})
        },
    },
}
</script>

<style scoped>
.ng-tree-view {
    padding: 20px 28px 20px 52px;
    position: relative;
}

/* ── Drop overlay ──────────────────────────────────── */
.ng-drop-zone--active { outline: 3px dashed var(--color-primary-element); border-radius: 8px; }
.ng-drop-overlay {
    position: absolute; inset: 0; z-index: 10;
    background: color-mix(in srgb, var(--color-primary-element) 10%, transparent);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}
.ng-drop-hint {
    display: flex; flex-direction: column; align-items: center; gap: 12px;
    color: var(--color-primary-element);
    font-size: 18px; font-weight: 600;
}

/* ── Empty repo ────────────────────────────────────── */
.ng-empty-repo { max-width: 620px; }
.ng-empty-repo h3 { font-size: 17px; margin: 0 0 8px; }
.ng-code-block {
    background: var(--color-background-dark); border: 1px solid var(--color-border);
    border-radius: 6px; padding: 14px 16px; font-family: monospace; font-size: 13px;
    line-height: 1.6; overflow-x: auto; white-space: pre; margin: 12px 0;
}
.ng-clone-row { display: flex; align-items: center; gap: 8px; margin-top: 12px; }
.ng-clone-label { font-size: 13px; font-weight: 600; white-space: nowrap; }
.ng-clone-url {
    background: var(--color-background-dark); border: 1px solid var(--color-border);
    border-radius: 4px; padding: 4px 10px; font-family: monospace; font-size: 13px;
    flex: 1; overflow-x: auto; white-space: nowrap;
}
.ng-copy-btn {
    background: none; border: none; cursor: pointer; font-size: 16px;
    padding: 4px; border-radius: 4px;
}
.ng-copy-btn:hover { background: var(--color-background-hover); }

/* ── Toolbar ───────────────────────────────────────── */
.ng-tree-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 10px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--color-border);
    flex-wrap: wrap;
}
.ng-toolbar-nav {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0; overflow: hidden;
}
.ng-toolbar-actions {
    display: flex; align-items: center; gap: 6px; flex-shrink: 0;
}
.ng-branch-select {
    padding: 5px 10px; border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text);
    font-size: 13px; cursor: pointer; flex-shrink: 0;
}
.ng-icon-btn {
    background: none; border: 1px solid var(--color-border); border-radius: 6px;
    padding: 5px 8px; cursor: pointer; display: flex; align-items: center;
    color: var(--color-text-maxcontrast); height: 36px;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
}
.ng-icon-btn:hover {
    background: var(--color-background-hover);
    border-color: var(--color-primary-element);
    color: var(--color-primary-element);
}

/* ── File table ────────────────────────────────────── */
.ng-file-list { width: 100%; border-collapse: collapse; }
.ng-file-row { cursor: pointer; border-bottom: 1px solid var(--color-border); transition: background 0.1s; }
.ng-file-row:hover { background: var(--color-background-hover); }
.ng-file-row--up { color: var(--color-text-maxcontrast); }
.ng-file-icon { width: 32px; padding: 8px 4px 8px 8px; font-size: 16px; }
.ng-file-name { padding: 8px 4px; font-size: 14px; }
.ng-file-size { padding: 8px 8px 8px 4px; text-align: right; font-size: 12px; color: var(--color-text-maxcontrast); width: 80px; }
.ng-loading { color: var(--color-text-maxcontrast); padding: 16px 0; }

/* ── Upload + New folder modals ────────────────────── */
.ng-upload-modal { padding: 24px; max-width: 480px; }
.ng-upload-modal h3 { font-size: 17px; font-weight: 700; margin: 0 0 12px; }
.ng-modal-hint { font-size: 13px; color: var(--color-text-maxcontrast); margin: 0 0 14px; line-height: 1.5; }
.ng-upload-file-list { margin-bottom: 16px; }
.ng-upload-file-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 0; border-bottom: 1px solid var(--color-border); font-size: 13px;
}
.ng-upload-file-icon { color: var(--color-primary-element); flex-shrink: 0; }
.ng-upload-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ng-upload-file-size { color: var(--color-text-maxcontrast); font-size: 11px; white-space: nowrap; }
.ng-upload-remove {
    background: none; border: none; cursor: pointer; color: var(--color-text-maxcontrast);
    font-size: 14px; padding: 2px 6px; border-radius: 4px; flex-shrink: 0;
}
.ng-upload-remove:hover { background: var(--color-background-hover); color: var(--color-error); }
.ng-upload-dest { font-size: 12px; color: var(--color-text-maxcontrast); margin-bottom: 14px; }
.ng-upload-dest code { background: var(--color-background-dark); padding: 1px 5px; border-radius: 3px; }
.ng-form-group { margin-bottom: 16px; }
.ng-form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
.ng-input {
    width: 100%; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 6px;
    background: var(--color-main-background); color: var(--color-main-text);
    font-size: 14px; box-sizing: border-box;
}
.ng-upload-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }
.ng-error { color: var(--color-error); font-size: 13px; margin-top: 4px; display: block; }
</style>
