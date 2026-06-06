<template>
    <nav class="ng-breadcrumb">
        <router-link :to="`/${owner}/${repo}/tree/${branch}`" class="ng-bc-link">
            {{ repo }}
        </router-link>
        <template v-for="(seg, idx) in segments" :key="idx">
            <span class="ng-bc-sep">/</span>
            <router-link
                v-if="idx < segments.length - 1"
                :to="`/${owner}/${repo}/tree/${branch}?path=${buildPath(idx)}`"
                class="ng-bc-link">
                {{ seg }}
            </router-link>
            <span v-else class="ng-bc-current">{{ seg }}</span>
        </template>
    </nav>
</template>

<script>
export default {
    name: 'Breadcrumb',
    props: {
        owner:  { type: String, required: true },
        repo:   { type: String, required: true },
        branch: { type: String, required: true },
        path:   { type: String, default: '' },
    },
    computed: {
        segments() {
            return this.path ? this.path.split('/').filter(Boolean) : []
        },
    },
    methods: {
        buildPath(upTo) {
            return this.segments.slice(0, upTo + 1).join('/')
        },
    },
}
</script>

<style scoped>
.ng-breadcrumb {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px; flex-wrap: wrap;
}
.ng-bc-link {
    color: var(--color-primary-element);
    text-decoration: none; font-weight: 500;
}
.ng-bc-link:hover { text-decoration: underline; }
.ng-bc-sep { color: var(--color-text-maxcontrast); }
.ng-bc-current { color: var(--color-main-text); font-weight: 600; }
</style>
