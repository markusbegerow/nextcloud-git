<?php

declare(strict_types=1);

namespace OCA\Git\Service;

class DiffService {
    public function __construct(private GitService $gitService) {}

    /**
     * Returns structured diff data between two branches.
     * @return array{canMerge: bool, files: array}
     */
    public function getDiff(string $owner, string $name, string $baseBranch, string $headBranch): array {
        $path = $this->gitService->repoPath($owner, $name);
        $cmd  = 'git diff ' . escapeshellarg($baseBranch) . '...' . escapeshellarg($headBranch);

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env  = ['HOME' => '/root', 'PATH' => '/usr/bin:/bin'];
        $proc = proc_open($cmd, $descriptors, $pipes, $path, $env);
        if (!is_resource($proc)) {
            return ['canMerge' => false, 'files' => []];
        }
        $raw  = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);

        return [
            'canMerge' => $this->checkMerge($owner, $name, $baseBranch, $headBranch),
            'files'    => $this->parseDiff($raw),
        ];
    }

    /** Parses unified diff output into structured file/hunk/line data. */
    public function parseDiff(string $raw): array {
        $files = [];
        $currentFile = null;
        $currentHunk = null;
        $oldLine = 0;
        $newLine = 0;

        foreach (explode("\n", $raw) as $line) {
            if (str_starts_with($line, 'diff --git')) {
                if ($currentFile !== null) {
                    if ($currentHunk !== null) {
                        $currentFile['hunks'][] = $currentHunk;
                    }
                    $files[] = $currentFile;
                }
                $currentFile = ['file' => '', 'added' => 0, 'removed' => 0, 'hunks' => []];
                $currentHunk = null;
                continue;
            }
            if (str_starts_with($line, '+++ b/')) {
                if ($currentFile !== null) {
                    $currentFile['file'] = substr($line, 6);
                }
                continue;
            }
            if (str_starts_with($line, '--- ') || str_starts_with($line, 'index ') ||
                str_starts_with($line, 'new file') || str_starts_with($line, 'deleted file') ||
                str_starts_with($line, 'Binary files')) {
                continue;
            }
            if (str_starts_with($line, '@@')) {
                if ($currentHunk !== null && $currentFile !== null) {
                    $currentFile['hunks'][] = $currentHunk;
                }
                preg_match('/@@ -(\d+)(?:,\d+)? \+(\d+)(?:,\d+)? @@/', $line, $m);
                $oldLine = isset($m[1]) ? (int) $m[1] : 1;
                $newLine = isset($m[2]) ? (int) $m[2] : 1;
                $currentHunk = ['header' => $line, 'lines' => []];
                continue;
            }
            if ($currentHunk === null || $currentFile === null) continue;

            $type = $line[0] ?? ' ';
            $content = strlen($line) > 0 ? substr($line, 1) : '';
            if ($type === '+') {
                $currentHunk['lines'][] = ['type' => '+', 'content' => $content, 'oldLine' => null, 'newLine' => $newLine++];
                $currentFile['added']++;
            } elseif ($type === '-') {
                $currentHunk['lines'][] = ['type' => '-', 'content' => $content, 'oldLine' => $oldLine++, 'newLine' => null];
                $currentFile['removed']++;
            } else {
                $currentHunk['lines'][] = ['type' => ' ', 'content' => $content, 'oldLine' => $oldLine++, 'newLine' => $newLine++];
            }
        }

        if ($currentFile !== null) {
            if ($currentHunk !== null) $currentFile['hunks'][] = $currentHunk;
            $files[] = $currentFile;
        }

        return $files;
    }

    public function checkMerge(string $owner, string $name, string $base, string $head): bool {
        $path = $this->gitService->repoPath($owner, $name);
        $cmd  = 'git merge-tree ' .
                escapeshellarg($base) . ' ' .
                escapeshellarg($base) . ' ' .
                escapeshellarg($head);
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env  = ['HOME' => '/root', 'PATH' => '/usr/bin:/bin'];
        $proc = proc_open($cmd, $descriptors, $pipes, $path, $env);
        if (!is_resource($proc)) return false;
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]); fclose($pipes[2]);
        $code = proc_close($proc);
        // Conflict markers in output indicate a conflict
        return $code === 0 && !str_contains($out, '<<<<<<<');
    }
}
