<?php

declare(strict_types=1);

namespace OCA\Git\Service;

use OCP\IConfig;
use RuntimeException;

class GitService {
    private string $baseDir;

    public function __construct(IConfig $config) {
        $dataDir = rtrim($config->getSystemValue('datadirectory', '/var/www/html/data'), '/');
        $this->baseDir = $dataDir . '/nextgit/repos';
    }

    public function repoPath(string $owner, string $name): string {
        return $this->baseDir . '/' . $owner . '/' . $name . '.git';
    }

    public function initRepo(string $owner, string $name): void {
        $path = $this->repoPath($owner, $name);
        if (!is_dir($path)) {
            mkdir($path, 0750, true);
        }
        $this->run($path, 'git init --bare -q .');
    }

    public function deleteRepo(string $owner, string $name): void {
        $path = $this->repoPath($owner, $name);
        if (is_dir($path)) {
            $this->rmdirRecursive($path);
        }
    }

    public function isEmpty(string $owner, string $name): bool {
        $path = $this->repoPath($owner, $name);
        $result = $this->run($path, 'git branch -l');
        return trim(implode('', $result['output'])) === '';
    }

    /** @return array<array{type:string,name:string,size:int|null}> */
    public function getTree(string $owner, string $name, string $branch, string $subPath = ''): array {
        $path = $this->repoPath($owner, $name);
        $ref = $subPath !== '' ? escapeshellarg($branch . ':' . $subPath) : escapeshellarg($branch . ':');
        $result = $this->run($path, 'git ls-tree --long ' . $ref);
        if ($result['code'] !== 0) {
            return [];
        }
        $entries = [];
        foreach ($result['output'] as $line) {
            $line = rtrim($line);
            if ($line === '') continue;
            // format: <mode> <type> <object>  <size|->  <name>
            if (preg_match('/^\d+\s+(blob|tree)\s+\S+\s+(\d+|-)\s+(.+)$/', $line, $m)) {
                $entries[] = [
                    'type' => $m[1],
                    'name' => $m[3],
                    'size' => $m[2] === '-' ? null : (int) $m[2],
                ];
            }
        }
        usort($entries, fn($a, $b) => ($a['type'] === $b['type'] ? strcmp($a['name'], $b['name']) : ($a['type'] === 'tree' ? -1 : 1)));
        return $entries;
    }

    public function getBlob(string $owner, string $name, string $branch, string $filePath): string {
        $path = $this->repoPath($owner, $name);
        $ref  = escapeshellarg($branch . ':' . $filePath);
        $result = $this->run($path, 'git show ' . $ref);
        if ($result['code'] !== 0) {
            throw new RuntimeException('File not found: ' . $filePath);
        }
        return implode("\n", $result['output']);
    }

    /** @return array<array{hash:string,author:string,date:string,message:string}> */
    public function getCommits(string $owner, string $name, string $branch, int $limit = 30): array {
        $path   = $this->repoPath($owner, $name);
        $format = escapeshellarg('%H|%an|%ai|%s');
        $result = $this->run($path, 'git log --format=' . $format . ' -n ' . $limit . ' ' . escapeshellarg($branch));
        if ($result['code'] !== 0) {
            return [];
        }
        $commits = [];
        foreach ($result['output'] as $line) {
            $line = rtrim($line);
            if ($line === '') continue;
            [$hash, $author, $date, $message] = explode('|', $line, 4);
            $commits[] = compact('hash', 'author', 'date', 'message');
        }
        return $commits;
    }

    /** @return string[] */
    public function getBranches(string $owner, string $name): array {
        $path   = $this->repoPath($owner, $name);
        $result = $this->run($path, 'git branch -l');
        if ($result['code'] !== 0) {
            return [];
        }
        return array_values(array_filter(array_map(
            fn($b) => ltrim(trim($b), '* '),
            $result['output']
        ), fn($b) => $b !== ''));
    }

    public function getReadme(string $owner, string $name, string $branch): ?string {
        $path = $this->repoPath($owner, $name);
        foreach (['README.md', 'README.txt', 'README', 'readme.md'] as $candidate) {
            $result = $this->run($path, 'git show ' . escapeshellarg($branch . ':' . $candidate));
            if ($result['code'] === 0) {
                return implode("\n", $result['output']);
            }
        }
        return null;
    }

    public function getInfoRefs(string $owner, string $name, string $service): string {
        $path   = $this->repoPath($owner, $name);
        $result = $this->run($path, 'git ' . escapeshellarg($service) . ' --stateless-rpc --advertise-refs .');
        if ($result['code'] !== 0) {
            throw new RuntimeException('git info-refs failed');
        }
        $body = implode("\n", $result['output']);
        // prefix PKT-LINE header
        $svcLine = '# service=git-' . $service . "\n";
        $pkt     = sprintf('%04x', strlen($svcLine) + 4) . $svcLine . "0000";
        return $pkt . $body;
    }

    public function runService(string $owner, string $name, string $service, string $input): string {
        $path = $this->repoPath($owner, $name);
        $cmd  = 'git ' . escapeshellarg($service) . ' --stateless-rpc .';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env = ['GIT_HTTP_EXPORT_ALL' => '1', 'HOME' => '/root'];
        $proc = proc_open($cmd, $descriptors, $pipes, $path, $env);
        if (!is_resource($proc)) {
            throw new RuntimeException('proc_open failed for git ' . $service);
        }
        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($proc);
        return $output;
    }

    /**
     * Returns structured commit graph data for all branches.
     * @return array<array{hash:string,parents:string[],refs:string[],author:string,date:string,message:string}>
     */
    public function getGraph(string $owner, string $name, int $limit = 150): array {
        $path   = $this->repoPath($owner, $name);
        $format = escapeshellarg('%H|%P|%D|%an|%ai|%s');
        $result = $this->run($path, 'git log --all --topo-order --format=' . $format . ' -n ' . $limit);
        if ($result['code'] !== 0) {
            return [];
        }
        $commits = [];
        foreach ($result['output'] as $line) {
            $line = rtrim($line);
            if ($line === '') continue;
            [$hash, $parentsRaw, $refsRaw, $author, $date, $message] = explode('|', $line, 6);
            $parents = array_values(array_filter(explode(' ', $parentsRaw)));
            $refs    = $refsRaw !== ''
                ? array_map('trim', explode(',', $refsRaw))
                : [];
            $commits[] = compact('hash', 'parents', 'refs', 'author', 'date', 'message');
        }
        return $commits;
    }

    /**
     * Commits one or more files directly into a bare repo using git-fast-import.
     *
     * @param array<array{name:string,content:string}> $files  file name + raw binary content
     */
    public function commitFiles(
        string $owner,
        string $name,
        string $branch,
        string $directory,
        array  $files,
        string $message,
        string $authorName  = 'NextGit',
        string $authorEmail = 'nextgit@localhost'
    ): void {
        $path      = $this->repoPath($owner, $name);
        $ts      = time();
        $isEmpty = $this->isEmpty($owner, $name);

        // Normalise directory: strip leading/trailing slashes
        $dir = trim($directory, '/');

        // Resolve the branch to its current commit hash (needed for the `from` directive).
        // Using the symbolic ref directly causes "Can't create a branch from itself".
        $parentHash = '';
        if (!$isEmpty) {
            $rev = $this->run($path, 'git rev-parse refs/heads/' . escapeshellarg($branch));
            $parentHash = trim($rev['output'][0] ?? '');
        }

        // Build the fast-import stream
        $stream  = "commit refs/heads/{$branch}\n";
        $stream .= "author {$authorName} <{$authorEmail}> {$ts} +0000\n";
        $stream .= "committer {$authorName} <{$authorEmail}> {$ts} +0000\n";
        $stream .= 'data ' . strlen($message) . "\n" . $message . "\n";

        if ($parentHash !== '') {
            $stream .= "from {$parentHash}\n";
        }
        // NOTE: no blank line here — a blank line terminates the commit in fast-import.
        // File modifications come directly after the header/from section.

        foreach ($files as $file) {
            $fileName = basename($file['name']);
            $filePath = $dir !== '' ? "{$dir}/{$fileName}" : $fileName;
            $content  = $file['content'];
            $stream  .= "M 100644 inline {$filePath}\n";
            $stream  .= 'data ' . strlen($content) . "\n" . $content . "\n";
        }

        $stream .= "\n";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env  = ['HOME' => '/root', 'PATH' => '/usr/bin:/bin'];
        $proc = proc_open('git fast-import --quiet', $descriptors, $pipes, $path, $env);
        if (!is_resource($proc)) {
            throw new \RuntimeException('git fast-import failed to start');
        }

        fwrite($pipes[0], $stream);
        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0) {
            throw new \RuntimeException('git fast-import error: ' . $stderr);
        }
    }

    private function run(string $repoPath, string $cmd): array {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env = ['GIT_HTTP_EXPORT_ALL' => '1', 'HOME' => '/root', 'PATH' => '/usr/bin:/bin'];
        $proc = proc_open($cmd, $descriptors, $pipes, $repoPath, $env);
        if (!is_resource($proc)) {
            return ['code' => -1, 'output' => []];
        }
        $raw  = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        return ['code' => $code, 'output' => explode("\n", $raw)];
    }

    private function rmdirRecursive(string $dir): void {
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $p = $dir . '/' . $item;
            is_dir($p) ? $this->rmdirRecursive($p) : unlink($p);
        }
        rmdir($dir);
    }
}
