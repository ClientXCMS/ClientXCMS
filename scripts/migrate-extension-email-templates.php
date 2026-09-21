#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Services\Mail\LegacySyntaxScanner;
use App\Services\Mail\StoredTemplateMigrator;
use App\Services\Mail\TemplateConverter;

require dirname(__DIR__).'/vendor/autoload.php';

const DEFAULT_COMMIT_MESSAGE = 'chore: migrate email templates to closed grammar';

/** @return never */
function fail(string $message, int $code = 1): void
{
    fwrite(STDERR, "Error: {$message}\n");
    exit($code);
}

/** @return array{output: string, code: int} */
function command(array $command, string $directory, bool $allowFailure = false): array
{
    $pipes = [];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $directory);
    if (! is_resource($process)) {
        fail('Unable to start: '.implode(' ', $command));
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $code = proc_close($process);
    $output = trim($stdout);

    if ($code !== 0 && ! $allowFailure) {
        fail(implode(' ', $command)." failed in {$directory}".($output === '' ? '' : ":\n{$output}"));
    }

    return ['output' => $output, 'code' => $code];
}

/** @return list<string> */
function repositories(string $root, array $selected): array
{
    $repositories = [];
    foreach (new DirectoryIterator($root) as $entry) {
        if ($entry->isDot() || ! $entry->isDir() || ! is_dir($entry->getPathname().'/.git')) {
            continue;
        }
        if ($selected !== [] && ! in_array($entry->getFilename(), $selected, true)) {
            continue;
        }
        if ($selected === [] && ! preg_match('/^(?:addon|addons|module)-/', $entry->getFilename())) {
            continue;
        }

        $hasEmailFiles = array_merge(
            glob($entry->getPathname().'/addons/*/emails.json') ?: [],
            glob($entry->getPathname().'/modules/*/emails.json') ?: [],
        );
        if ($hasEmailFiles !== []) {
            $repositories[] = $entry->getPathname();
        }
    }
    sort($repositories);

    return $repositories;
}

/** @return list<string> */
function emailFiles(string $repository): array
{
    $result = command(['git', 'ls-files', '--', '*emails.json'], $repository);
    if ($result['output'] === '') {
        return [];
    }

    return array_values(array_filter(
        explode("\n", $result['output']),
        static fn (string $path): bool => (bool) preg_match('~(?:^|/)(?:addons|modules)/[^/]+/emails\.json$~', $path),
    ));
}

/**
 * @param  list<array{path: string, construct: string}>  $pending
 */
function rewriteValue(
    mixed $value,
    string $path,
    StoredTemplateMigrator $migrator,
    LegacySyntaxScanner $scanner,
    array &$pending,
): mixed {
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            $value[$key] = rewriteValue($child, $path.'.'.$key, $migrator, $scanner, $pending);
        }

        return $value;
    }

    if (! is_string($value)) {
        return $value;
    }

    $rewritten = $migrator->rewrite($value);
    foreach ($scanner->scan($rewritten)->manual as $construct) {
        $pending[] = ['path' => ltrim($path, '.'), 'construct' => $construct];
    }

    return $rewritten;
}

/** @return array{content: string, changes: int, pending: list<array{path: string, construct: string}>} */
function migrateFile(string $filename, StoredTemplateMigrator $migrator, LegacySyntaxScanner $scanner): array
{
    $before = file_get_contents($filename);
    if ($before === false) {
        fail("Unable to read {$filename}");
    }

    try {
        $decoded = json_decode($before, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail("Invalid JSON in {$filename}: {$exception->getMessage()}");
    }

    if (! is_array($decoded)) {
        fail("The root value in {$filename} must be a JSON object or array");
    }

    $pending = [];
    $rewritten = rewriteValue($decoded, '', $migrator, $scanner, $pending);
    $content = json_encode(
        $rewritten,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    )."\n";

    $changes = $content === $before ? 0 : countStringChanges($decoded, $rewritten);

    return ['content' => $content, 'changes' => $changes, 'pending' => $pending];
}

function countStringChanges(mixed $before, mixed $after): int
{
    if (is_array($before) && is_array($after)) {
        $count = 0;
        foreach ($before as $key => $value) {
            $count += countStringChanges($value, $after[$key] ?? null);
        }

        return $count;
    }

    return is_string($before) && $before !== $after ? 1 : 0;
}

function writeAtomically(string $filename, string $content): void
{
    $temporary = tempnam(dirname($filename), '.emails-json-');
    if ($temporary === false || file_put_contents($temporary, $content) === false || ! rename($temporary, $filename)) {
        if (is_string($temporary) && file_exists($temporary)) {
            unlink($temporary);
        }
        fail("Unable to write {$filename}");
    }
}

function usage(): void
{
    echo <<<'HELP'
Migrate tracked modules/*/emails.json and addons/*/emails.json files in sibling Git repositories.

Usage:
  php scripts/migrate-extension-email-templates.php [options]

Options:
  --apply                 Pull, rewrite and commit (default: dry-run)
  --root=PATH             Directory containing the repositories
  --repo=NAME             Select a repository explicitly (repeatable)
  --message=TEXT          Commit message
  --help                  Display this help

Apply mode requires each selected repository to have a clean tracked worktree.
It uses "git pull --ff-only" and creates one commit per changed repository. It never pushes.
By default, only sibling repositories named addon-*, addons-* or module-* are scanned.
HELP;
    echo "\n";
}

$apply = false;
$root = dirname(__DIR__, 2);
$message = DEFAULT_COMMIT_MESSAGE;
$selected = [];

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--apply') {
        $apply = true;
    } elseif ($argument === '--help') {
        usage();
        exit(0);
    } elseif (str_starts_with($argument, '--root=')) {
        $root = substr($argument, strlen('--root='));
    } elseif (str_starts_with($argument, '--repo=')) {
        $selected[] = substr($argument, strlen('--repo='));
    } elseif (str_starts_with($argument, '--message=')) {
        $message = substr($argument, strlen('--message='));
    } else {
        fail("Unknown option: {$argument}", 2);
    }
}

$root = realpath($root) ?: fail("Root directory does not exist: {$root}");
$repos = repositories($root, $selected);
if ($repos === []) {
    fail('No matching Git repository found');
}

$scanner = new LegacySyntaxScanner;
$migrator = new StoredTemplateMigrator(new TemplateConverter($scanner));
$totalFiles = 0;
$totalStrings = 0;
$totalPending = 0;

echo $apply ? "APPLY mode\n" : "DRY-RUN mode (use --apply to write, pull and commit)\n";

// Check every repository before changing any of them, so a dirty repository does
// not leave an apply run half-completed merely because it sorts later by name.
if ($apply) {
    foreach ($repos as $repo) {
        $name = basename($repo);
        $status = command(['git', 'status', '--porcelain', '--untracked-files=no'], $repo);
        if ($status['output'] !== '') {
            fail("Repository {$name} has tracked changes; commit or stash them first");
        }
        command(['git', 'symbolic-ref', '--quiet', 'HEAD'], $repo);
    }
}

foreach ($repos as $repo) {
    $name = basename($repo);

    if ($apply) {
        echo "[{$name}] pulling with --ff-only\n";
        command(['git', 'pull', '--ff-only'], $repo);
    }

    $changedPaths = [];
    $repoStrings = 0;
    foreach (emailFiles($repo) as $relativePath) {
        $result = migrateFile($repo.'/'.$relativePath, $migrator, $scanner);
        foreach ($result['pending'] as $pending) {
            echo "[{$name}] MANUAL {$relativePath}:{$pending['path']} => {$pending['construct']}\n";
            $totalPending++;
        }
        if ($result['changes'] === 0) {
            continue;
        }

        $changedPaths[] = $relativePath;
        $repoStrings += $result['changes'];
        echo "[{$name}] {$relativePath}: {$result['changes']} string(s) to migrate\n";
        if ($apply) {
            writeAtomically($repo.'/'.$relativePath, $result['content']);
        }
    }

    if ($changedPaths === []) {
        continue;
    }

    $totalFiles += count($changedPaths);
    $totalStrings += $repoStrings;
    if ($apply) {
        command(array_merge(['git', 'add', '--'], $changedPaths), $repo);
        command(['git', 'diff', '--cached', '--check'], $repo);
        command(['git', 'commit', '-m', $message, '--', ...$changedPaths], $repo);
        echo "[{$name}] committed ".count($changedPaths)." file(s)\n";
    }
}

echo "Summary: {$totalFiles} file(s), {$totalStrings} string(s), {$totalPending} manual construct(s)";
echo $apply ? " migrated and committed.\n" : " would be migrated.\n";
