<template>
    <NcModal
        :name="t('git', 'Info')"
        @close="$emit('close')">
        <div class="ng-about">
            <!-- App identity -->
            <div class="ng-about-header">
                <div class="ng-about-icon">
                    <GitBranch :size="40" />
                </div>
                <div class="ng-about-title">
                    <h2>NextGit</h2>
                    <p class="ng-about-subtitle">{{ t('git', 'Git repository hosting for Nextcloud') }}</p>
                </div>
                <span class="ng-about-version">v1.0</span>
            </div>

            <!-- Application Information -->
            <div class="ng-about-section">
                <p class="ng-section-heading">{{ t('git', 'Application Information') }}</p>
                <dl class="ng-about-meta">
                    <div>
                        <dt>{{ t('git', 'Author') }}</dt>
                        <dd>Markus Begerow</dd>
                    </div>
                    <div>
                        <dt>{{ t('git', 'Version') }}</dt>
                        <dd>1.0</dd>
                    </div>
                    <div>
                        <dt>{{ t('git', 'License') }}</dt>
                        <dd>AGPL v3</dd>
                    </div>
                </dl>
            </div>

            <!-- Features -->
            <div class="ng-about-section">
                <p class="ng-section-heading">{{ t('git', 'Features') }}</p>
                <ul class="ng-about-features">
                    <li>{{ t('git', 'Create and manage bare git repositories inside Nextcloud') }}</li>
                    <li>{{ t('git', 'Browse files, folders and commit history in the browser') }}</li>
                    <li>{{ t('git', 'Clone over HTTPS using your Nextcloud credentials') }}</li>
                    <li>{{ t('git', 'Built-in issue tracker with open/close workflow') }}</li>
                    <li>{{ t('git', 'Pull requests with unified diff viewer') }}</li>
                    <li>{{ t('git', 'Webhook notifications and optional SSH access') }}</li>
                </ul>
            </div>

            <!-- Links -->
            <div class="ng-about-section">
                <p class="ng-section-heading">{{ t('git', 'Links') }}</p>
                <div class="ng-about-links">
                    <a href="https://markus-begerow.de/linktree" target="_blank" rel="noopener" class="ng-about-link">
                        <Globe :size="16" class="ng-about-link-icon" />
                        <div class="ng-about-link-body">
                            <span class="ng-about-link-label">{{ t('git', 'Website') }}</span>
                            <span class="ng-about-link-sub">markus-begerow.de/linktree</span>
                        </div>
                        <span class="ng-about-link-arrow">↗</span>
                    </a>
                    <a href="https://github.com/markusbegerow/nextcloud-git" target="_blank" rel="noopener" class="ng-about-link">
                        <Github :size="16" class="ng-about-link-icon" />
                        <div class="ng-about-link-body">
                            <span class="ng-about-link-label">GitHub</span>
                            <span class="ng-about-link-sub">markusbegerow/nextcloud-git</span>
                        </div>
                        <span class="ng-about-link-arrow">↗</span>
                    </a>
                    <a href="https://www.paypal.com/paypalme/MarkusBegerow" target="_blank" rel="noopener" class="ng-about-link">
                        <Coffee :size="16" class="ng-about-link-icon" />
                        <div class="ng-about-link-body">
                            <span class="ng-about-link-label">{{ t('git', 'Donate a coffee') }}</span>
                            <span class="ng-about-link-sub">paypal.me/MarkusBegerow</span>
                        </div>
                        <span class="ng-about-link-arrow">↗</span>
                    </a>
                </div>
            </div>
        </div>
    </NcModal>
</template>

<script>
import { NcModal } from '@nextcloud/vue'
import { GitBranch, Globe, Github, Coffee } from 'lucide-vue-next'

export default {
    name: 'AboutDialog',
    components: { NcModal, GitBranch, Globe, Github, Coffee },
    emits: ['close'],
}
</script>

<style scoped>
.ng-about {
    padding: 20px;
    max-width: 480px;
}

/* App identity row */
.ng-about-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.ng-about-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-primary-element);
    border-radius: 12px;
    color: #fff;
    flex-shrink: 0;
}

.ng-about-title {
    flex: 1;
    min-width: 0;
}

.ng-about-title h2 {
    margin: 0 0 2px;
    font-size: 1.2em;
    font-weight: 700;
}

.ng-about-subtitle {
    margin: 0;
    font-size: 0.8em;
    color: var(--color-text-maxcontrast);
}

.ng-about-version {
    font-size: 0.8em;
    color: var(--color-text-maxcontrast);
    border: 1px solid var(--color-border);
    border-radius: 999px;
    padding: 2px 8px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Section wrapper */
.ng-about-section {
    border-top: 1px solid var(--color-border);
    padding-top: 16px;
    margin-bottom: 16px;
}

/* Section heading label */
.ng-section-heading {
    margin: 0 0 10px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: var(--color-text-maxcontrast);
}

/* Meta info dl */
.ng-about-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 0;
}

.ng-about-meta > div {
    display: flex;
    gap: 8px;
    font-size: 0.875em;
}

.ng-about-meta dt {
    color: var(--color-text-maxcontrast);
    width: 64px;
    flex-shrink: 0;
}

.ng-about-meta dd {
    margin: 0;
    font-weight: 600;
    color: var(--color-main-text);
}

/* Features list */
.ng-about-features {
    margin: 0;
    padding-left: 18px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.ng-about-features li {
    font-size: 0.8em;
    color: var(--color-text-maxcontrast);
    line-height: 1.5;
}

/* Links */
.ng-about-links {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.ng-about-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius);
    color: var(--color-main-text);
    text-decoration: none;
    transition: border-color 0.15s, background 0.15s;
}

.ng-about-link:hover {
    border-color: var(--color-primary-element);
    background: var(--color-background-hover);
}

.ng-about-link-icon {
    color: var(--color-text-maxcontrast);
    flex-shrink: 0;
}

.ng-about-link:hover .ng-about-link-icon {
    color: var(--color-primary-element);
}

.ng-about-link-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.ng-about-link-label {
    font-size: 0.875em;
    font-weight: 600;
}

.ng-about-link-sub {
    font-size: 0.75em;
    color: var(--color-text-maxcontrast);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ng-about-link-arrow {
    color: var(--color-text-maxcontrast);
    font-size: 0.875em;
    flex-shrink: 0;
}
</style>
