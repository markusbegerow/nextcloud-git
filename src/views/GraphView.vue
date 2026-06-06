<template>
    <div class="ng-graph-view">
        <div v-if="loading" class="ng-loading">{{ t('git', 'Loading graph…') }}</div>

        <div v-else-if="commits.length === 0" class="ng-empty-state">
            <Network :size="48" class="ng-empty-icon" />
            <p>{{ t('git', 'No commits yet.') }}</p>
        </div>

        <template v-else>
            <!-- Toolbar: zoom + info -->
            <div class="ng-graph-toolbar">
                <div class="ng-graph-zoom-btns">
                    <NcButton :disabled="zoom >= 3" @click="zoomIn">
                        <template #icon><ZoomIn :size="16" /></template>
                    </NcButton>
                    <span class="ng-zoom-label">{{ Math.round(zoom * 100) }}%</span>
                    <NcButton :disabled="zoom <= 0.2" @click="zoomOut">
                        <template #icon><ZoomOut :size="16" /></template>
                    </NcButton>
                    <NcButton @click="zoomFit">
                        <template #icon><Maximize2 :size="16" /></template>
                        {{ t('git', 'Fit') }}
                    </NcButton>
                    <NcButton @click="scrollToHead">
                        <template #icon><ArrowUpToLine :size="16" /></template>
                        {{ t('git', 'HEAD') }}
                    </NcButton>
                </div>
                <span class="ng-graph-info">
                    {{ t('git', '{n} commits · {b} branch(es)', { n: commits.length, b: branchCount }) }}
                </span>
            </div>

            <!-- Canvas scroll area -->
            <div class="ng-graph-scroll" ref="scrollEl">
                <canvas ref="canvas" class="ng-graph-canvas" />

                <!-- Row hover tooltip (absolutely positioned overlay) -->
                <div
                    v-if="tooltip"
                    class="ng-graph-tooltip"
                    :style="{ top: Math.round(tooltip.row * ROW_H * zoom) + 'px',
                              height: Math.round(ROW_H * zoom) + 'px' }">
                    <span class="ng-tt-hash">{{ tooltip.hash.slice(0, 7) }}</span>
                    <span class="ng-tt-message">{{ tooltip.message }}</span>
                    <span class="ng-tt-meta">{{ tooltip.author }} · {{ formatDate(tooltip.date) }}</span>
                    <span v-for="r in tooltip.cleanRefs" :key="r" class="ng-tt-ref">{{ r }}</span>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { Network, ZoomIn, ZoomOut, Maximize2, ArrowUpToLine } from 'lucide-vue-next'

// ── Layout constants (unscaled) ───────────────────────────
const ROW_H   = 34   // px per commit row
const LANE_W  = 22   // px per lane column
const DOT_R   = 6    // commit dot radius
const PAD_L   = 14   // left padding before lanes
const BADGE_H = 15   // branch badge height

const COLORS = [
    '#0082c9', '#e9322d', '#2eb52e', '#e68523',
    '#9b59b6', '#1abc9c', '#e91e8c', '#f39c12',
]

// ── Cross-browser rounded rectangle helper ────────────────
function roundRect(ctx, x, y, w, h, r) {
    r = Math.min(r, w / 2, h / 2)
    ctx.beginPath()
    ctx.moveTo(x + r, y)
    ctx.lineTo(x + w - r, y)
    ctx.arcTo(x + w, y,     x + w, y + r,     r)
    ctx.lineTo(x + w, y + h - r)
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r)
    ctx.lineTo(x + r, y + h)
    ctx.arcTo(x,     y + h, x,     y + h - r,  r)
    ctx.lineTo(x, y + r)
    ctx.arcTo(x,     y,     x + r, y,           r)
    ctx.closePath()
}

export default {
    name: 'GraphView',
    components: { NcButton, Network, ZoomIn, ZoomOut, Maximize2, ArrowUpToLine },

    props: {
        owner:    { type: String, required: true },
        repoName: { type: String, required: true },
    },

    data() {
        return {
            commits:  [],
            layout:   [],
            maxLane:  1,
            loading:  false,
            zoom:     1.0,
            tooltip:  null,
            ROW_H,    // expose constant to template
        }
    },

    computed: {
        branchCount() {
            const names = new Set()
            for (const c of this.commits) {
                for (const r of c.refs) {
                    const clean = r.replace(/^HEAD -> /, '').replace(/^tag: /, '')
                    if (clean) names.add(clean)
                }
            }
            return names.size || 1
        },
    },

    watch: {
        '$route.params': { immediate: true, handler() { this.load() } },
        // Draw only after layout is built AND DOM is updated
        layout(val) {
            if (val.length) this.$nextTick(() => this.draw())
        },
    },

    methods: {
        // ── Data loading ──────────────────────────────────
        async load() {
            this.loading = true
            this.tooltip = null
            this.layout  = []
            try {
                const { data } = await axios.get(
                    generateUrl(`/apps/git/api/repos/${this.owner}/${this.repoName}/graph`)
                )
                this.commits = data
                this.buildLayout()
            } catch {
                this.commits = []
            } finally {
                this.loading = false
            }
        },

        // ── Lane assignment algorithm ─────────────────────
        buildLayout() {
            if (!this.commits.length) { this.layout = []; return }

            const layout      = []
            const activeLanes = []   // activeLanes[i] = hash expected on lane i, or null

            const claimLane = (hash) => {
                let i = activeLanes.indexOf(hash)
                if (i !== -1) return i
                // Reuse a free slot, or open a new lane
                i = activeLanes.indexOf(null)
                if (i === -1) { i = activeLanes.length; activeLanes.push(hash) }
                else activeLanes[i] = hash
                return i
            }

            for (const commit of this.commits) {
                // Find this commit's own lane (reserved by a prior child commit)
                let lane = activeLanes.indexOf(commit.hash)
                if (lane === -1) {
                    lane = activeLanes.indexOf(null)
                    if (lane === -1) { lane = activeLanes.length; activeLanes.push(commit.hash) }
                    else activeLanes[lane] = commit.hash
                }

                const laneColor = COLORS[lane % COLORS.length]
                const edges     = []

                // Free this slot
                activeLanes[lane] = null

                const [first, ...rest] = commit.parents

                if (first) {
                    // First parent continues on the same lane
                    activeLanes[lane] = first
                    edges.push({ fromLane: lane, toLane: lane, color: laneColor })
                }
                for (const p of rest) {
                    // Merge parents get their own lane
                    const pLane = claimLane(p)
                    edges.push({ fromLane: lane, toLane: pLane, color: COLORS[pLane % COLORS.length] })
                }

                layout.push({ commit, lane, laneColor, edges })
            }

            this.maxLane = Math.max(...activeLanes.map((_, i) => i), ...layout.map(r => r.lane)) + 1
            this.layout  = layout
        },

        // ── Canvas drawing ────────────────────────────────
        draw() {
            const canvas = this.$refs.canvas
            const scrollEl = this.$refs.scrollEl
            if (!canvas || !scrollEl) return

            const z = this.zoom
            // Unscaled dimensions
            const laneAreaW = PAD_L + this.maxLane * LANE_W + 16
            const uW = Math.max(700, scrollEl.clientWidth || 700)
            const uH = this.layout.length * ROW_H + 12

            // Physical pixel dimensions
            canvas.width  = Math.ceil(uW * z)
            canvas.height = Math.ceil(uH * z)
            canvas.style.width  = canvas.width  + 'px'
            canvas.style.height = canvas.height + 'px'

            const ctx = canvas.getContext('2d')
            ctx.clearRect(0, 0, canvas.width, canvas.height)
            ctx.save()
            ctx.scale(z, z)

            const cx = (lane) => PAD_L + lane * LANE_W + LANE_W / 2
            const cy = (row)  => row * ROW_H + ROW_H / 2

            // ── Draw edges ──
            for (let i = 0; i < this.layout.length; i++) {
                const { edges } = this.layout[i]
                for (const edge of edges) {
                    const x1 = cx(edge.fromLane), y1 = cy(i)
                    const x2 = cx(edge.toLane),   y2 = cy(i + 1)
                    ctx.beginPath()
                    ctx.strokeStyle = edge.color
                    ctx.lineWidth   = 2
                    if (edge.fromLane === edge.toLane) {
                        ctx.moveTo(x1, y1); ctx.lineTo(x2, y2)
                    } else {
                        ctx.moveTo(x1, y1)
                        ctx.bezierCurveTo(x1, y1 + ROW_H * 0.55, x2, y2 - ROW_H * 0.55, x2, y2)
                    }
                    ctx.stroke()
                }
            }

            // ── Draw circles + text ──
            const mainTextColor = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-main-text').trim() || '#222'
            const metaColor = getComputedStyle(document.documentElement)
                .getPropertyValue('--color-text-maxcontrast').trim() || '#888'

            for (let i = 0; i < this.layout.length; i++) {
                const { commit, lane, laneColor } = this.layout[i]
                const x = cx(lane), y = cy(i)

                // Dot
                ctx.beginPath()
                ctx.arc(x, y, DOT_R, 0, Math.PI * 2)
                ctx.fillStyle   = laneColor
                ctx.fill()
                ctx.strokeStyle = '#ffffff'
                ctx.lineWidth   = 2
                ctx.stroke()

                // Branch / tag badges
                let badgeX = laneAreaW
                ctx.font = 'bold 10px system-ui, sans-serif'
                for (const ref of commit.refs) {
                    const isTag  = ref.startsWith('tag:')
                    const isHead = ref.startsWith('HEAD')
                    const label  = ref.replace(/^HEAD -> /, '').replace(/^tag: /, '🏷 ')
                    const tw     = ctx.measureText(label).width
                    const bw     = tw + 10, bh = BADGE_H
                    const by     = y - bh / 2

                    roundRect(ctx, badgeX, by, bw, bh, 3)
                    ctx.fillStyle = isHead ? '#0a58ca' : laneColor
                    ctx.fill()

                    ctx.fillStyle = '#ffffff'
                    ctx.fillText(label, badgeX + 5, by + bh - 3)
                    badgeX += bw + 4
                }

                // Commit message
                ctx.font      = '13px system-ui, sans-serif'
                ctx.fillStyle = mainTextColor
                const msgX = badgeX + (commit.refs.length ? 6 : 0)
                const maxW = uW - msgX - 80
                let msg = commit.message
                while (msg.length > 1 && ctx.measureText(msg + '…').width > maxW) msg = msg.slice(0, -1)
                if (msg !== commit.message) msg += '…'
                ctx.fillText(msg, msgX, y + 4)

                // Short hash (right-aligned)
                ctx.font      = '11px monospace'
                ctx.fillStyle = metaColor
                ctx.fillText(commit.hash.slice(0, 7), uW - 68, y + 4)
            }

            ctx.restore()

            // Wire events
            canvas.onmousemove  = (e) => this.onCanvasHover(e, scrollEl)
            canvas.onmouseleave = ()  => { this.tooltip = null }
        },

        onCanvasHover(e, scrollEl) {
            const rect   = this.$refs.canvas.getBoundingClientRect()
            const mouseY = (e.clientY - rect.top) / this.zoom
            const row    = Math.floor(mouseY / ROW_H)
            if (row >= 0 && row < this.layout.length) {
                const c = this.layout[row].commit
                this.tooltip = {
                    row,
                    hash:      c.hash,
                    message:   c.message,
                    author:    c.author,
                    date:      c.date,
                    cleanRefs: c.refs.map(r => r.replace(/^HEAD -> /, '').replace(/^tag: /, '🏷 ')),
                }
            } else {
                this.tooltip = null
            }
        },

        // ── Zoom controls ─────────────────────────────────
        zoomIn()  { this.setZoom(Math.min(3,   this.zoom * 1.3)) },
        zoomOut() { this.setZoom(Math.max(0.2,  this.zoom / 1.3)) },
        zoomFit() {
            const el = this.$refs.scrollEl
            if (!el || !this.layout.length) return
            const fit = Math.min(1, el.clientHeight / (this.layout.length * ROW_H + 12))
            this.setZoom(Math.max(0.2, fit))
        },
        setZoom(z) {
            this.zoom = Math.round(z * 100) / 100  // 2 decimal places
            this.$nextTick(() => this.draw())
        },
        scrollToHead() {
            const el = this.$refs.scrollEl
            if (el) el.scrollTop = 0
        },

        formatDate(iso) {
            return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
        },
    },
}
</script>

<style scoped>
.ng-graph-view { padding: 20px 28px 20px 52px; display: flex; flex-direction: column; height: 100%; }

.ng-loading { color: var(--color-text-maxcontrast); padding: 24px 0; }
.ng-empty-state {
    display: flex; flex-direction: column; align-items: center;
    gap: 12px; padding: 40px 0; color: var(--color-text-maxcontrast);
}
.ng-empty-icon { opacity: 0.3; }

/* ── Toolbar ───────────────────────────────────── */
.ng-graph-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 10px; flex-wrap: wrap;
}
.ng-graph-zoom-btns { display: flex; align-items: center; gap: 6px; }
.ng-zoom-label {
    font-size: 12px; font-weight: 600; min-width: 38px; text-align: center;
    color: var(--color-text-maxcontrast);
}
.ng-graph-info { font-size: 12px; color: var(--color-text-maxcontrast); }

/* ── Canvas scroll area ────────────────────────── */
.ng-graph-scroll {
    position: relative;
    flex: 1;
    min-height: 200px;
    height: calc(100vh - 300px);
    overflow: auto;
    border: 1px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-main-background);
}

.ng-graph-canvas { display: block; cursor: crosshair; }

/* ── Hover tooltip overlay ─────────────────────── */
.ng-graph-tooltip {
    position: absolute; left: 0; right: 0;
    background: color-mix(in srgb, var(--color-primary-element) 8%, var(--color-main-background));
    border-top: 1px solid var(--color-primary-element);
    border-bottom: 1px solid var(--color-primary-element);
    padding: 0 12px;
    pointer-events: none;
    z-index: 5;
    display: flex; align-items: center; gap: 8px; flex-wrap: nowrap;
    overflow: hidden;
    box-sizing: border-box;
}
.ng-tt-hash {
    font-family: monospace; font-size: 12px; font-weight: 700;
    color: var(--color-primary-element); flex-shrink: 0;
}
.ng-tt-message {
    font-size: 12px; flex: 1;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ng-tt-meta {
    font-size: 11px; color: var(--color-text-maxcontrast);
    white-space: nowrap; flex-shrink: 0;
}
.ng-tt-ref {
    font-size: 10px; padding: 1px 5px; border-radius: 3px;
    background: var(--color-primary-element); color: #fff;
    white-space: nowrap; flex-shrink: 0;
}
</style>
