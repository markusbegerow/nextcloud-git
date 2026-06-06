import { createRouter, createWebHashHistory } from 'vue-router'

// Static imports — all views bundled into git-main.js (Nextcloud doesn't serve lazy chunks)
import DashboardView    from './views/DashboardView.vue'
import NewRepoView      from './views/NewRepoView.vue'
import RepoView         from './views/RepoView.vue'
import TreeView         from './views/TreeView.vue'
import BlobView         from './views/BlobView.vue'
import CommitListView   from './views/CommitListView.vue'
import IssueListView    from './views/IssueListView.vue'
import NewIssueView     from './views/NewIssueView.vue'
import IssueDetailView  from './views/IssueDetailView.vue'
import PullListView     from './views/PullListView.vue'
import NewPullView      from './views/NewPullView.vue'
import PullDetailView   from './views/PullDetailView.vue'
import RepoSettingsView from './views/RepoSettingsView.vue'
import GraphView        from './views/GraphView.vue'

export default createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: DashboardView },
        { path: '/new', component: NewRepoView },
        {
            path: '/:owner/:repo',
            component: RepoView,
            children: [
                // No redirect here — RepoView navigates to tree/{branch} after loading defaultBranch
                { path: 'tree/:branch(.*)',    component: TreeView },
                { path: 'blob/:branch(.*)',    component: BlobView },
                { path: 'commits/:branch(.*)', component: CommitListView },
                { path: 'graph', component: GraphView },
                { path: 'issues', component: IssueListView },
                { path: 'issues/new', component: NewIssueView },
                { path: 'issues/:num', component: IssueDetailView },
                { path: 'pulls', component: PullListView },
                { path: 'pulls/new', component: NewPullView },
                { path: 'pulls/:num', component: PullDetailView },
                { path: 'settings', component: RepoSettingsView },
            ],
        },
    ],
})
