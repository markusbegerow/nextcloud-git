<?php

declare(strict_types=1);

return [
    'routes' => [
        // SPA shell — Vue router handles all sub-paths client-side
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        // REST API — repositories
        ['name' => 'api#list_repos',    'url' => '/api/repos',                                     'verb' => 'GET'],
        ['name' => 'api#create_repo',   'url' => '/api/repos',                                     'verb' => 'POST'],
        ['name' => 'api#get_repo',      'url' => '/api/repos/{owner}/{name}',                      'verb' => 'GET'],
        ['name' => 'api#delete_repo',   'url' => '/api/repos/{owner}/{name}',                      'verb' => 'DELETE'],
        ['name' => 'api#update_repo',   'url' => '/api/repos/{owner}/{name}',                      'verb' => 'PATCH'],
        ['name' => 'api#transfer_repo', 'url' => '/api/repos/{owner}/{name}/transfer',             'verb' => 'POST'],
        ['name' => 'api#get_branches',  'url' => '/api/repos/{owner}/{name}/branches',             'verb' => 'GET'],
        ['name' => 'api#get_commits',   'url' => '/api/repos/{owner}/{name}/commits/{branch}',     'verb' => 'GET'],
        ['name' => 'api#get_tree',      'url' => '/api/repos/{owner}/{name}/tree/{branch}',        'verb' => 'GET'],
        ['name' => 'api#get_blob',      'url' => '/api/repos/{owner}/{name}/blob/{branch}',        'verb' => 'GET'],
        ['name' => 'api#get_readme',    'url' => '/api/repos/{owner}/{name}/readme',               'verb' => 'GET'],
        ['name' => 'api#get_graph',     'url' => '/api/repos/{owner}/{name}/graph',                'verb' => 'GET'],
        ['name' => 'api#upload_files',  'url' => '/api/repos/{owner}/{name}/upload',               'verb' => 'POST'],

        // Issues (Phase 3)
        ['name' => 'issue#list_issues',  'url' => '/api/repos/{owner}/{name}/issues',              'verb' => 'GET'],
        ['name' => 'issue#create_issue', 'url' => '/api/repos/{owner}/{name}/issues',              'verb' => 'POST'],
        ['name' => 'issue#get_issue',    'url' => '/api/repos/{owner}/{name}/issues/{number}',     'verb' => 'GET'],
        ['name' => 'issue#update_issue', 'url' => '/api/repos/{owner}/{name}/issues/{number}',     'verb' => 'PATCH'],

        // Pull Requests (Phase 4)
        ['name' => 'pull#list_pulls',  'url' => '/api/repos/{owner}/{name}/pulls',                 'verb' => 'GET'],
        ['name' => 'pull#create_pull', 'url' => '/api/repos/{owner}/{name}/pulls',                 'verb' => 'POST'],
        ['name' => 'pull#get_pull',    'url' => '/api/repos/{owner}/{name}/pulls/{number}',        'verb' => 'GET'],
        ['name' => 'pull#merge_pull',  'url' => '/api/repos/{owner}/{name}/pulls/{number}/merge',  'verb' => 'POST'],
        ['name' => 'pull#close_pull',  'url' => '/api/repos/{owner}/{name}/pulls/{number}/close',  'verb' => 'POST'],

        // Webhooks (Phase 5)
        ['name' => 'webhook#list',   'url' => '/api/repos/{owner}/{name}/webhooks',                'verb' => 'GET'],
        ['name' => 'webhook#create', 'url' => '/api/repos/{owner}/{name}/webhooks',                'verb' => 'POST'],
        ['name' => 'webhook#delete', 'url' => '/api/repos/{owner}/{name}/webhooks/{id}',           'verb' => 'DELETE'],

        // SSH auth (Phase 5)
        ['name' => 'ssh_auth#auth',  'url' => '/api/ssh/auth',                                     'verb' => 'POST'],

        // Git HTTP Smart Protocol
        ['name' => 'git#info_refs',    'url' => '/git/{owner}/{repo}.git/info/refs',               'verb' => 'GET'],
        ['name' => 'git#upload_pack',  'url' => '/git/{owner}/{repo}.git/git-upload-pack',         'verb' => 'POST'],
        ['name' => 'git#receive_pack', 'url' => '/git/{owner}/{repo}.git/git-receive-pack',        'verb' => 'POST'],
    ],
];
