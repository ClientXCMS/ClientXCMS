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


namespace App\Core\License;

use Illuminate\Filesystem\Filesystem;

class LicenseCache
{
    private Filesystem $file;

    private ?License $license = null;

    const EXPIRE = 24 * 3600;

    public function __construct()
    {
        $this->file = new Filesystem;
    }

    public static function expireLicense()
    {
        (new LicenseCache)->file->delete(app('license')->getLicenseFile());
    }

    public static function isValid()
    {
        if ((new self)->getLicense() == null) {
            return false;
        }

        return (new self)->getLicense()->isValid();

    }

    public function isHit()
    {
        if ($this->getLicense() == null) {
            return false;
        }

        return $this->file->exists(app('license')->getLicenseFile()) === false || $this->getLicense() != null && $this->getLicense()->isHit() && ! empty(file_get_contents(app('license')->getLicenseFile()));
    }

    public static function get(): ?License
    {
        return (new self)->getLicense();
    }

    public static function set(string $key, $value)
    {
        if (self::get() != null) {
            $license = self::get()->set($key, $value);
            (new self)->persist($license);
        }
    }

    public function getLicense(): ?License
    {
        if ($this->license === null) {
            $this->ensureFileExist();
            $content = require app('license')->getLicenseFile();

            if (is_array($content)) {
                $license = new License(
                    $content['expire'],
                    $content['support_expiration'] ?? null,
                    $content['lastchecked'],
                    $content['nextCheck'],
                    $content['server'] ?? null,
                    $content['extensions'] ?? [],
                    $content['type'] ?? 'community',
                    $content['version_type'] ?? 'self_hosted',
                    $content['data'] ?? [],
                );
                $this->license = $license;
            } else {
                $this->license = null;
            }
        }

        return $this->license;
    }

    private function ensureFileExist()
    {
        try {

            if ($this->file->exists(app('license')->getLicenseFile()) === false) {
                $this->file->put(app('license')->getLicenseFile(), '');
            }
        } catch (\Exception $e) {
            exit($e);
        }
    }

    public function persist(License $license)
    {
        $string = '<?php return '.var_export($license->__serialize(), true).';';

        return $this->file->put(app('license')->getLicenseFile(), $string);
    }

    public function getNextCheck()
    {
        return time() + self::EXPIRE;
    }
}
