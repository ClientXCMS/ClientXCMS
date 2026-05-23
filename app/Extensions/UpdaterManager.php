<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Extensions;

use GuzzleHttp\Psr7\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class UpdaterManager
{
    /**
     * Sub-directories under base_path() that an extension archive is allowed
     * to write into. Any file outside this list is treated as a supply-chain
     * attempt (overwriting app/Http/Controllers, bootstrap/, vendor/, .env, ...).
     */
    private const ALLOWED_PREFIXES = [
        'modules/',
        'addons/',
        'themes/',
        'email_templates/',
        'invoice_templates/',
    ];

    public function update(string $uuid)
    {

        $filename = storage_path("app/updates/{$uuid}.zip");
        $to = storage_path("app/extracts/{$uuid}");
        if (! is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $resource = Utils::tryFopen($filename, 'w+b');
        if (! $resource) {
            throw new \RuntimeException("Unable to open file for writing: {$filename}");
        }
        app('license')->download($uuid, $resource);
        if (! file_exists($filename)) {
            throw new \RuntimeException("File not found after download: {$filename}");
        }
        self::checkIfValidZip($filename);
        $this->extract($filename, $to);
    }

    public function extract(string $file, string $to)
    {
        self::rejectZipSlip($file);
        $zip = new ZipArchive;
        $finder = new Finder;

        $res = $zip->open($file, ZipArchive::CHECKCONS);
        if ($res) {
            if (! $zip->extractTo($to)) {
                throw new \RuntimeException("Failed to extract zip file: {$file}");
            }
            $path = (basename(collect($finder->in($to)->directories()->depth('== 0'))->first()->getPathname()));
            $fileSystem = new Filesystem;
            $removedFiles = ['README.md', 'LICENSE.txt', 'CHANGELOG.md', '.gitignore', 'LICENSE'];
            foreach ($removedFiles as $file) {
                if (file_exists($to.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file)) {
                    unlink($to.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file);
                }
            }
            self::assertExtractedTreeStaysInExtensionDirs($to.DIRECTORY_SEPARATOR.$path);
            $fileSystem->mirror($to.DIRECTORY_SEPARATOR.$path, base_path(), null, ['override' => true]);
        }
        $zip->close();
        if (file_exists($file)) {
            unlink($file);
        }
        if (is_dir($to)) {
            $fileSystem = new Filesystem;
            $fileSystem->remove($to);
        }
    }

    /**
     * Walk the ZIP entries before extracting and refuse any entry whose name
     * either escapes the destination (.., absolute path, Windows drive) or
     * targets a path under a non-extension prefix at the project root.
     */
    public static function rejectZipSlip(string $file): void
    {
        $zip = new ZipArchive;
        if ($zip->open($file) !== true) {
            return;
        }
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false) {
                    continue;
                }
                $normalized = str_replace('\\', '/', $entry);
                if (str_starts_with($normalized, '/')
                    || preg_match('#(^|/)\.\.(/|$)#', $normalized)
                    || preg_match('/^[a-zA-Z]:/', $normalized)) {
                    throw new \RuntimeException("Zip slip attempt blocked: {$entry}");
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Refuse to mirror anything outside the whitelisted extension directories.
     * The vendor archive layout is `<root>/<prefix>/<extension>/...` where
     * <prefix> is one of ALLOWED_PREFIXES; files at any other location are
     * a supply-chain payload trying to overwrite app/, bootstrap/, etc.
     */
    public static function assertExtractedTreeStaysInExtensionDirs(string $root): void
    {
        $finder = (new Finder)->in($root)->files()->ignoreDotFiles(false);
        foreach ($finder as $file) {
            $rel = ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');
            $allowed = false;
            foreach (self::ALLOWED_PREFIXES as $prefix) {
                if (str_starts_with($rel, $prefix)) {
                    $allowed = true;
                    break;
                }
            }
            if (! $allowed) {
                throw new \RuntimeException("Extension archive contains a file outside the allowed extension directories: {$rel}");
            }
        }
    }

    private static function checkIfValidZip(string $file)
    {
        $zip = new ZipArchive;
        $res = $zip->open($file, ZipArchive::CHECKCONS);
        $zip->close();
        if ($res !== true) {
            switch ($res) {
                case ZipArchive::ER_NOZIP:
                    throw new \Exception('not a zip archive');
                case ZipArchive::ER_INCONS:
                    throw new \Exception('consistency check failed');
                case ZipArchive::ER_CRC:
                    throw new \Exception('checksum failed');
                default:
                    throw new \Exception('error '.$res);
            }
        }
    }
}
