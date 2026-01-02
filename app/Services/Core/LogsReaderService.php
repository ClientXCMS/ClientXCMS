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

namespace App\Services\Core;

use App\Core\Logs\Level;
use App\Core\Logs\Pattern;

/**
 * @see https://github.com/rap2hpoutre/laravel-log-viewer/blob/master/src/Rap2hpoutre/LaravelLogViewer/LaravelLogViewer.php
 */
class LogsReaderService
{
    const MAX_FILE_SIZE = 52428800;

    private string $file = '';

    private string $folder = '';

    private string $storage_path;

    private Level $level;

    private Pattern $pattern;

    /**
     * LaravelLogViewer constructor.
     */
    public function __construct()
    {
        $this->level = new Level;
        $this->pattern = new Pattern;
        $this->storage_path = storage_path('logs');

    }

    public function setFolder(string $folder): void
    {
        if (! str_contains($folder, 'storage/logs') || $this->isValidFolder($folder)) {
            throw new \Exception('No such log file: '.$folder);
        }
        if (app('files')->exists($folder)) {

            $this->folder = $folder;
        } elseif (is_array($this->storage_path)) {

            foreach ($this->storage_path as $value) {

                $logsPath = $value.'/'.$folder;

                if (app('files')->exists($logsPath)) {
                    $this->folder = $folder;
                    break;
                }
            }
        } else {

            $logsPath = $this->storage_path.'/'.$folder;
            if (app('files')->exists($logsPath)) {
                $this->folder = $folder;
            }

        }
    }

    /**
     * @throws \Exception
     */
    public function setFile(string $file): void
    {
        $file = $this->pathToLogFile($file);

        if (! str_contains($file, '.log') || $this->isValidFolder($file)) {
            throw new \Exception('No such log file: '.$file);
        }
        if (app('files')->exists($file)) {
            $this->file = $file;
        }
    }

    /**
     * @throws \Exception
     */
    public function pathToLogFile(string $file): string
    {

        if (app('files')->exists($file)) { // try the absolute path

            return $file;
        }
        if (is_array($this->storage_path)) {

            foreach ($this->storage_path as $folder) {
                if (app('files')->exists($folder.'/'.$file)) { // try the absolute path
                    $file = $folder.'/'.$file;
                    break;
                }
            }

            return $file;
        }

        $logsPath = $this->storage_path;
        $logsPath .= ($this->folder) ? '/'.$this->folder : '';
        $file = $logsPath.'/'.$file;
        // check if requested file is really in the logs directory
        if (dirname($file) !== $logsPath) {
            throw new \Exception('No such log file: '.$file);
        }

        return $file;
    }

    public function all(): ?array
    {
        $log = [];

        if (! $this->file) {
            $log_file = (! $this->folder) ? $this->getFiles() : $this->getFolderFiles();
            if (! count($log_file)) {
                return [];
            }
            $this->file = $log_file[0];
        }

        $max_file_size = function_exists('config') ? config('logviewer.max_file_size', self::MAX_FILE_SIZE) : self::MAX_FILE_SIZE;
        if (app('files')->size($this->file) > $max_file_size) {
            return null;
        }

        if (! is_readable($this->file)) {
            return [[
                'context' => '',
                'level' => '',
                'date' => null,
                'text' => 'Log file "'.$this->file.'" not readable',
                'stack' => '',
            ]];
        }

        $file = app('files')->get($this->file);

        preg_match_all($this->pattern->getPattern('logs'), $file, $headings);

        if (! is_array($headings)) {
            return $log;
        }

        $log_data = preg_split($this->pattern->getPattern('logs'), $file);

        if ($log_data[0] < 1) {
            array_shift($log_data);
        }

        foreach ($headings as $h) {
            for ($i = 0, $j = count($h); $i < $j; $i++) {
                foreach ($this->level->all() as $level) {
                    if (strpos(strtolower($h[$i]), '.'.$level) || strpos(strtolower($h[$i]), $level.':')) {

                        preg_match($this->pattern->getPattern('current_log', 0).$level.$this->pattern->getPattern('current_log', 1), $h[$i], $current);
                        if (! isset($current[4])) {
                            continue;
                        }

                        $log[] = [
                            'context' => $current[3],
                            'level' => $level,
                            'folder' => $this->folder,
                            'level_class' => $this->level->cssClass($level),
                            'level_img' => $this->level->img($level),
                            'date' => $current[1],
                            'text' => $current[4],
                            'in_file' => isset($current[5]) ? $current[5] : null,
                            'stack' => preg_replace("/^\n*/", '', $log_data[$i]),
                        ];
                    }
                }
            }
        }

        if (empty($log)) {

            $lines = explode(PHP_EOL, $file);
            $log = [];

            foreach ($lines as $key => $line) {
                $log[] = [
                    'context' => '',
                    'level' => '',
                    'folder' => '',
                    'level_class' => '',
                    'level_img' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'in_file' => null,
                    'stack' => '',
                ];
            }
        }

        return array_reverse($log);
    }

    /**Creates a multidimensional array
     * of subdirectories and files
     *
     * @param null $path
     *
     * @return array
     */
    public function foldersAndFiles($path = null): array
    {
        $contents = [];
        $dir = $path ? $path : $this->storage_path;
        foreach (scandir($dir) as $node) {
            if ($node == '.' || $node == '..' || $node == '.gitignore') {
                continue;
            }
            $path = $dir.'\\'.$node;
            if (is_dir($path)) {
                $contents[$path] = $this->foldersAndFiles($path);
            } else {
                $contents[] = $path;
            }
        }

        return $contents;
    }

    /**Returns an array of
     * all subdirectories of specified directory
     *
     * @param string $folder
     *
     * @return array
     */
    public function getFolders(string $folder = ''): array
    {
        $folders = [];
        $listObject = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->storage_path.'/'.$folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($listObject as $fileinfo) {
            if ($fileinfo->isDir()) {
                $folders[] = $fileinfo->getRealPath();
            }
        }

        return $folders;
    }

    public function getFolderFiles(bool $basename = false): array
    {
        return $this->getFiles($basename, $this->folder);
    }

    public function getFiles(bool $basename = false, string $folder = ''): array
    {
        $files = [];
        $pattern = '*.log';
        $fullPath = \Str::startsWith($folder, $this->storage_path) ? $folder : $this->storage_path.'/'.$folder;

        $listObject = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($listObject as $fileinfo) {
            if (! $fileinfo->isDir() && strtolower(pathinfo($fileinfo->getRealPath(), PATHINFO_EXTENSION)) == explode('.', $pattern)[1]) {
                $files[] = $basename ? basename($fileinfo->getRealPath()) : $fileinfo->getRealPath();
            }
        }

        arsort($files);

        return array_values($files);
    }

    public static function directoryTreeStructure(string $storage_path, array $array): void
    {
        foreach ($array as $k => $v) {
            if (is_dir($k)) {

                $exploded = explode('\\', $k);
                $show = last($exploded);

                echo '<div class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white">
				    <a href="?f='.\Illuminate\Support\Facades\Crypt::encrypt($k).'">
					    <i
						    class="bi bi-archive mr-2"></i> '.$show.'
				    </a>
			    </div>';

                if (is_array($v)) {
                    self::directoryTreeStructure($storage_path, $v);
                }

            } else {

                $exploded = explode('\\', $v);
                $show2 = last($exploded);
                $folder = str_replace($storage_path, '', rtrim(str_replace($show2, '', $v), '\\'));
                $file = $v;

                echo '<div class="inline-flex items-center gap-x-3.5 py-3 px-4 text-sm font-medium bg-white border border-gray-200 text-gray-800 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-gray-800 dark:border-gray-700 dark:text-white">
				    <a href="?l='.\Illuminate\Support\Facades\Crypt::encrypt($file).'&f='.\Illuminate\Support\Facades\Crypt::encrypt($folder).'">
					   <i class="bi bi-file-earmark mr-2"></i> '.$show2.'
				    </a>
			    </div>';

            }
        }
    }

    public function getStoragePath(): string
    {
        return $this->storage_path;
    }

    public function setStoragePath(string $path): void
    {
        $this->storage_path = $path;
    }

    public function getFolderName(): string
    {
        return $this->folder;
    }

    public function getFileName(): string
    {
        return basename($this->file);
    }

    public function get()
    {
        try {
            if (app('files')->size($this->file) > self::MAX_FILE_SIZE) {
                $content = 'File too big ('.round(app('files')->size($this->file) / 1048576, 2).'Mo) to be displayed. This content is last logs truncated.'.PHP_EOL;
                $file = fopen($this->file, 'r');
                if ($file === false) {
                    return response()->json(['error' => 'Unable to open file.'], 500);
                }
                $numChars = 1024 * 100;
                fseek($file, -$numChars, SEEK_END);

                $content .= fread($file, $numChars);
                fclose($file);

                return $content;
            }

            return app('files')->get($this->file);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            return '';
        }
    }

    private function isValidFolder(string $folder): bool
    {
        $documentRoot = request()->server('DOCUMENT_ROOT');
        $documentRoot = str_replace('/public', '', $documentRoot);

        return str_contains($documentRoot, $folder);
    }
}
