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
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class UpdaterManager
{
    private const METADATA_FILES = ['README.md', 'LICENSE.txt', 'CHANGELOG.md', '.gitignore', 'LICENSE'];

    public function update(string $uuid, ExtensionType $type)
    {
        ExtensionType::assertValidUuid($uuid);
        $this->extractExtension($this->download($uuid, $type->value), storage_path("app/extracts/{$uuid}"), $type, $uuid);
    }

    public function updateCore()
    {
        $this->extract($this->download('core', 'core'), storage_path('app/extracts/core'));
    }

    /**
     * Only ever writes the files the extension owns. The destination comes from
     * the type and uuid the product asked for, never from the archive itself.
     */
    public function extractExtension(string $file, string $to, ExtensionType $type, string $uuid)
    {
        ExtensionType::assertValidUuid($uuid);

        $this->extractArchive($file, $to, function (string $root) use ($type, $uuid): Finder {
            $owned = (new Finder)->in($root)->files()->ignoreDotFiles(false)->ignoreVCS(false)
                ->filter(static fn (\SplFileInfo $file): bool => $type->owns(
                    substr($file->getPathname(), strlen($root) + 1),
                    $uuid
                ));

            if (! $owned->hasResults()) {
                throw new \RuntimeException("Archive does not contain {$type->path($uuid)}");
            }

            return $owned;
        });
    }

    public function extract(string $file, string $to)
    {
        $this->extractArchive($file, $to, null);
    }

    private function extractArchive(string $file, string $to, ?\Closure $confine)
    {
        self::rejectZipSlip($file);
        $fileSystem = new Filesystem;
        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::CHECKCONS) !== true) {
            $fileSystem->remove($file);

            throw new \RuntimeException("Unable to open zip file: {$file}");
        }

        try {
            if (! $zip->extractTo($to)) {
                throw new \RuntimeException("Failed to extract zip file: {$file}");
            }
            $root = $to.DIRECTORY_SEPARATOR.self::archiveRootDirectory($to);
            $fileSystem->remove(array_map(
                static fn (string $metadata): string => $root.DIRECTORY_SEPARATOR.$metadata,
                self::METADATA_FILES
            ));
            $fileSystem->mirror($root, base_path(), $confine === null ? null : $confine($root), ['override' => true]);
        } finally {
            $zip->close();
            $fileSystem->remove([$file, $to]);
        }
    }

    private function download(string $uuid, string $type): string
    {
        $filename = storage_path("app/updates/{$uuid}.zip");
        if (! is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }
        $resource = Utils::tryFopen($filename, 'w+b');
        if (! $resource) {
            throw new \RuntimeException("Unable to open file for writing: {$filename}");
        }
        $response = app('license')->download($uuid, $resource);
        if (! file_exists($filename)) {
            throw new \RuntimeException("File not found after download: {$filename}");
        }
        self::checkIfValidZip($filename);
        (new ArchiveVerifier)->verify(
            $filename,
            ArchiveProof::fromResponse($response instanceof ResponseInterface ? $response : null),
            $uuid,
            $type
        );

        return $filename;
    }

    /**
     * Vendor archives wrap everything in a single root directory, whose name is
     * the repository name and not the extension identifier.
     */
    private static function archiveRootDirectory(string $to): string
    {
        $root = collect((new Finder)->in($to)->directories()->depth('== 0'))->first();
        if ($root === null) {
            throw new \RuntimeException("Archive has no root directory: {$to}");
        }

        return basename($root->getPathname());
    }

    /**
     * Walk the ZIP entries before extracting and refuse any entry whose name
     * escapes the destination: .. segment, absolute path or Windows drive.
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
