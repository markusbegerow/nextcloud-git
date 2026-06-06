#!/bin/bash
# NextGit SSH shell wrapper
# Usage: Add to /root/.ssh/authorized_keys as:
#   command="/path/to/nextgit-shell.sh USERNAME PASSWORD",no-port-forwarding,no-X11-forwarding,no-agent-forwarding ssh-rsa AAAA...
#
# USERNAME and PASSWORD are a Nextcloud username + app password (not the main password).
# They are read from the environment variables NEXTGIT_USER and NEXTGIT_PASS,
# which should be set in the authorized_keys command line as shell variables.

set -e

NEXTCLOUD_URL="${NEXTGIT_NC_URL:-http://localhost}"
DATA_DIR="${NEXTGIT_DATA_DIR:-/var/www/html/data}"
REPOS_DIR="$DATA_DIR/nextgit/repos"

# Validate credentials via Nextcloud API
AUTH_RESPONSE=$(curl -sf -X POST \
    -d "user=${NEXTGIT_USER}&pass=${NEXTGIT_PASS}" \
    "${NEXTCLOUD_URL}/apps/git/api/ssh/auth" 2>/dev/null) || true

OWNER_UID=$(echo "$AUTH_RESPONSE" | grep -o '"owner_uid":"[^"]*"' | cut -d'"' -f4)

if [ -z "$OWNER_UID" ]; then
    echo "NextGit: authentication failed." >&2
    exit 1
fi

# Parse SSH_ORIGINAL_COMMAND: git-upload-pack 'owner/repo.git'
CMD=$(echo "$SSH_ORIGINAL_COMMAND" | awk '{print $1}')
REPO_ARG=$(echo "$SSH_ORIGINAL_COMMAND" | sed "s/^$CMD '//;s/'$//")
REPO_NAME=$(basename "$REPO_ARG" .git)
REPO_OWNER=$(dirname "$REPO_ARG")

if [ "$REPO_OWNER" != "$OWNER_UID" ]; then
    echo "NextGit: access denied." >&2
    exit 1
fi

REPO_PATH="$REPOS_DIR/$OWNER_UID/$REPO_NAME.git"

if [ ! -d "$REPO_PATH" ]; then
    echo "NextGit: repository not found: $REPO_NAME" >&2
    exit 1
fi

case "$CMD" in
    git-upload-pack)  exec git-upload-pack  "$REPO_PATH" ;;
    git-receive-pack) exec git-receive-pack "$REPO_PATH" ;;
    *)
        echo "NextGit: unsupported command: $CMD" >&2
        exit 1
        ;;
esac
