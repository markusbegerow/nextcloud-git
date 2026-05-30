# NextGit for Nextcloud

<img alt="nextcloud-git" src="https://github.com/user-attachments/assets/af5b07d9-099e-4550-b4f3-8066ff0438c6" />

**NextGit** lets you create and manage bare git repositories directly inside your Nextcloud instance. Clone over HTTPS with your existing Nextcloud credentials, browse code and commits in the browser, track issues, review pull requests with a diff viewer, and visualise your branch history - all without leaving Nextcloud.

---

## Features

- **Repository management** - create, rename, transfer and delete repositories from the sidebar; public or private
- **Code browser** - navigate directories, view file contents with syntax highlighting, and browse commit history per branch
- **Branch graph** - visual lane graph of all branches, merges and forks with zoom controls
- **File upload** - drag files from your desktop onto the tree view, or use the upload button; creates a real git commit
- **New folder** - create directories via the UI (adds a `.gitkeep` so git tracks the folder)
- **Clone over HTTPS** - standard `git clone http://…` using your Nextcloud username and password or an app password
- **Issue tracker** - open and close issues with Markdown body and open/closed filter tabs
- **Pull requests** - create PRs between branches; unified diff viewer with collapsible file hunks; merge or close from the browser
- **Webhook notifications** - fire HTTP POST events on push, create, delete, issues and pull_request; HMAC-SHA256 signed
- **Optional SSH access** - `git@host:owner/repo.git` via a shell wrapper script
- **README rendering** - Markdown README displayed below the file list at the root of every repository
- **Languages** - English 🇬🇧 · German 🇩🇪

---

## Requirements

| Dependency | Version |
|---|---|
| Nextcloud | 27 – 33 |
| PHP | 8.1+ |
| git | any version available on the server |

---

## Installation

### From source (manual)

1. Copy the `git` directory into your Nextcloud `custom_apps/` folder:

   ```bash
   sudo cp -r nextgit /var/www/nextcloud/custom_apps/git
   sudo chown -R www-data:www-data /var/www/nextcloud/custom_apps/git
   ```

2. Enable the app:

   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:enable git
   ```

3. Open **NextGit** in the Nextcloud top navigation bar.

---

## Usage

### Create a repository

1. Click **+ New repository** in the left sidebar.
2. Enter a name (letters, numbers, hyphens, dots) and an optional description.
3. Click **Create repository**.

### Clone a repository

```bash
git clone http://your-nextcloud/apps/git/git/{user}/{repo}.git
```

Use your Nextcloud username and password (or an **app password** from Settings → Security → App passwords).

### Push code

```bash
cd my-project
git remote add origin http://your-nextcloud/apps/git/git/{user}/{repo}.git
git push -u origin main
```

### Browse code

Click any repository in the sidebar to open the code browser. Use the branch selector to switch branches, navigate into folders, and click any file to see its content with syntax highlighting.

### Branch graph

Click the **Graph** tab to see a colour-coded lane graph of all commits across all branches. Use the **+** / **−** buttons to zoom, **Fit** to show all commits at once, and **HEAD** to scroll to the latest commit.

### Issues

Open the **Issues** tab, click **New issue**, and fill in the title and Markdown body. Use the Open / Closed filter tabs to manage issues.

### Pull requests

1. Push a feature branch to the repository.
2. Open the **Pull Requests** tab and click **New pull request**.
3. Select the head branch (your feature branch) and the base branch.
4. Review the diff, then click **Merge pull request** or **Close**.

### Webhooks

Go to **Settings → Webhooks** inside a repository, enter a payload URL and optional secret, tick the events you want, and save. Webhooks fire asynchronously via Nextcloud's background job queue and include an `X-NextGit-Signature: sha256=…` header for verification.

---

## SSH access (optional)

SSH access requires setting up a shell wrapper on the server.

### 1. Install the shell wrapper

```bash
sudo cp custom_apps/git/bin/nextgit-shell.sh /usr/local/bin/nextgit-shell
sudo chmod +x /usr/local/bin/nextgit-shell
```

### 2. Add an authorized key

In `~/.ssh/authorized_keys` for the system user who will accept SSH connections:

```
command="NEXTGIT_USER=ncuser NEXTGIT_PASS=apppassword NEXTGIT_NC_URL=http://localhost NEXTGIT_DATA_DIR=/var/www/html/data /usr/local/bin/nextgit-shell",no-port-forwarding,no-X11-forwarding,no-agent-forwarding ssh-rsa AAAA... user@host
```

Replace:
- `ncuser` / `apppassword` - Nextcloud username and an app password (Settings → Security → App passwords)
- `NEXTGIT_NC_URL` - full URL to your Nextcloud instance
- `NEXTGIT_DATA_DIR` - path to the Nextcloud data directory

### 3. Clone via SSH

```bash
git clone git@your-server:ncuser/my-repo.git
```

---

## Architecture

```
Browser (Vue 3 SPA)
    ↕  REST API + file upload
Nextcloud PHP (Controllers + Services)
    ↕  git CLI via proc_open()
Bare repositories  ·  {datadir}/nextgit/repos/{owner}/{name}.git
    ↕  Git HTTP Smart Protocol (clone / push over HTTP)
git CLI on the client
```

---

## Development

The frontend is a Vue 3 SPA built with webpack.

```bash
cd custom_apps/git

# Install dependencies (first time)
npm install

# Watch mode (rebuilds on save)
npx webpack --config webpack.config.js --watch

# Production build (Windows PowerShell)
$env:NODE_ENV='production'; npx webpack --config webpack.config.js
```

The compiled output goes to `js/git-main.js`, loaded by `PageController` via `Util::addScript`.
After adding a new PHP route, restart the container to flush the APCu route cache:

```bash
docker restart nextcloud-nextcloud-1
```

For full developer reference (database schema, service methods, known gotchas) see [CLAUDE.md](CLAUDE.md).

---

## License

[AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.html) © [Markus Begerow](https://markus-begerow.de/linktree)

---

## 🙋‍♂️ Get Involved

If you encounter any issues or have questions:
- 🐛 [Report bugs](https://github.com/markusbegerow/nextcloud-git/issues)
- 💡 [Request features](https://github.com/markusbegerow/nextcloud-git/issues)
- ⭐ Star the repo if you find it useful!

## ☕ Support the Project

If you like this project, support further development with a repost or coffee:

<a href="https://www.linkedin.com/sharing/share-offsite/?url=https://github.com/MarkusBegerow/nextcloud-git" target="_blank"> <img src="https://img.shields.io/badge/💼-Share%20on%20LinkedIn-blue" /> </a>

[![Buy Me a Coffee](https://img.shields.io/badge/☕-Buy%20me%20a%20coffee-yellow)](https://paypal.me/MarkusBegerow)

## 📬 Contact

- 🧑‍💻 [Markus Begerow](https://linkedin.com/in/markusbegerow)
- 💾 [GitHub](https://github.com/markusbegerow)
- ✉️ [Twitter](https://x.com/markusbegerow)
