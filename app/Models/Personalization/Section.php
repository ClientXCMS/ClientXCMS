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

namespace App\Models\Personalization;

use App\DTO\Core\Extensions\ExtensionSectionTrait;
use App\Models\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $uuid
 * @property string $theme_uuid
 * @property string $path
 * @property int $order
 * @property int $is_active
 * @property string $url
 * @property array|null $config
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereThemeUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Section withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Section extends Model
{
    use ExtensionSectionTrait;
    use HasFactory;
    use SoftDeletes;
    use Translatable;

    protected $table = 'theme_sections';

    const TAGS_DISABLED = [
        '<?php', '?>', '@php', '@endphp', '@shell', '<?=',
        'env(', '$_ENV', '$_SERVER', '$_GET', '.env', '.__DIR__',
        '$_POST', '$_REQUEST', '$_SESSION', '$_COOKIE', 'exec(',
        'shell_exec(', 'system(', 'passthru(', 'proc_open(', 'popen(',
        'pcntl_exec(', 'eval(', 'assert(', 'preg_replace(', 'create_function(',
        'require(', 'unlink(', 'fopen(', 'file_get_contents(', 'file_put_contents(',
        'file(', 'readfile(', 'base64_decode(', 'gzinflate(', 'gzuncompress(',
        'gzdecode(', 'gzcompress(', 'gzdeflate(', 'gzencode(', 'gzuncompress(',
        'ini_set(', 'set_time_limit(', 'error_reporting(', 'ini_get(', 'ini_restore(',
        'ini_alter(', 'ini_set(', 'unserialize(', 'serialize(', 'var_dump(',
        'print_r(', 'debug_backtrace(', 'debug_print_backtrace(', 'dump(', 'die(',
        'exit(', 'phpinfo(', 'php_uname(', 'getenv(', 'get_current_user(',
        'getmyuid(', 'getmygid(', 'getmypid(', 'getmyinode(', 'getlastmod(',
        'getprotobyname(', 'getprotobynumber(', 'getservbyname(', 'getservbyport(',

    ];

    protected $fillable = [
        'uuid',
        'theme_uuid',
        'path',
        'is_active',
        'url',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public static function scanSections()
    {
        /** @var \App\DTO\Core\Extensions\ThemeSectionDTO[] $sections */
        $sections = app('theme')->getThemeSections();
        $theme = app('theme')->getTheme();
        foreach ($sections as $section) {
            if (! $section->isDefault()) {
                continue;
            }
            if (Section::where('uuid', $section->uuid)->exists()) {
                continue;
            }
            Section::insert([
                'uuid' => $section->uuid,
                'theme_uuid' => $theme->uuid,
                'path' => $section->json['path'],
                'is_active' => true,
                'url' => $section->json['default_url'] ?? '/',
            ]);
        }
    }

    public function getUrlAttribute($value)
    {
        return $value ?? '/';
    }

    public function toDTO(): \App\DTO\Core\Extensions\ThemeSectionDTO
    {
        return \App\DTO\Core\Extensions\ThemeSectionDTO::fromModel($this);
    }

    public function saveContent(string $content)
    {
        if ($this->toDTO()->isProtected()) {
            return;
        }
        $theme = app('theme')->getTheme();
        $path = $theme->path.'/views/sections_copy/'.$this->id.'-'.$this->uuid.'.blade.php';
        $this->path = 'sections_copy/'.$this->id.'-'.$this->uuid;
        if (! file_exists($theme->path.'/views/sections_copy')) {
            mkdir($theme->path.'/views/sections_copy', 0777, true);
        }
        $content = sanitize_content($content);
        file_put_contents($path, $content);
        $this->save();
    }

    public function restore()
    {
        $theme = app('theme')->getTheme();
        $path = 'sections/'.$this->uuid;
        $newPath = $theme->path.'views/'.$this->path.'.blade.php';
        unset($newPath);
        $this->path = $path;
        $this->save();
    }

    public function getSetting(string $key, mixed $default = null, ?string $locale = null): mixed
    {
        $fieldDef = $this->getFieldDefinition($key);
        $isTranslatable = $fieldDef && ($fieldDef['translatable'] ?? false);

        if ($isTranslatable) {
            $value = $this->getTranslation('config_' . $key, null, $locale);
            if ($value === '' || $value === null) {
                return $default;
            }
            return $this->castConfigValue($value, $fieldDef['type'] ?? 'text');
        }

        $config = $this->config ?? [];
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        return $this->castConfigValue($config[$key], $fieldDef['type'] ?? 'text');
    }

    public function setSetting(string $key, mixed $value, ?string $locale = null): void
    {
        $fieldDef = $this->getFieldDefinition($key);
        $isTranslatable = $fieldDef && ($fieldDef['translatable'] ?? false);

        $value = $this->prepareConfigValue($value);

        if ($isTranslatable) {
            $locale = $locale ?? app()->getLocale();
            $this->saveTranslation('config_' . $key, $locale, (string) $value);
            return;
        }

        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
        $this->save();

        $this->clearConfigCache();
    }

    public function deleteSetting(string $key, ?string $locale = null): void
    {
        $fieldDef = $this->getFieldDefinition($key);
        $isTranslatable = $fieldDef && ($fieldDef['translatable'] ?? false);

        if ($isTranslatable) {
            $locale = $locale ?? app()->getLocale();
            $this->translations()
                ->where('key', 'config_' . $key)
                ->where('locale', $locale)
                ->delete();
            Cache::forget('translations_' . self::class . '_' . $this->id);
            return;
        }

        $config = $this->config ?? [];
        unset($config[$key]);
        $this->config = $config;
        $this->save();

        $this->clearConfigCache();
    }

    public function getConfigurableFields(): array
    {
        $dto = $this->toDTO();
        return $dto->json['fields'] ?? [];
    }

    public function isConfigurable(): bool
    {
        $dto = $this->toDTO();
        return ($dto->json['configurable'] ?? false) && !empty($this->getConfigurableFields());
    }

    public function getFieldDefinition(string $key): ?array
    {
        $fields = $this->getConfigurableFields();
        foreach ($fields as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }
        return null;
    }

    private function castConfigValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (int) $value : $value,
            'json', 'repeater' => is_string($value) ? (json_decode($value, true) ?? []) : (array) $value,
            default => $value,
        };
    }

    private function prepareConfigValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return $value;
    }

    private function clearConfigCache(): void
    {
        Cache::forget('theme_configuration');
    }

    public function cloneSection()
    {
        $clone = $this->replicate();
        $clone->config = $this->config;
        $clone->save();

        // Clone translations for config fields
        foreach ($this->translations as $translation) {
            if (str_starts_with($translation->key, 'config_')) {
                Translation::create([
                    'model' => self::class,
                    'model_id' => $clone->id,
                    'key' => $translation->key,
                    'locale' => $translation->locale,
                    'content' => $translation->content,
                ]);
            }
        }

        $theme = app('theme')->getTheme();
        if (! file_exists($theme->path.'/views/sections_copy')) {
            mkdir($theme->path.'/views/sections_copy', 0777, true);
        }
        $path = $theme->path.'/views/sections_copy/'.$clone->id.'-'.$clone->uuid.'.blade.php';
        $clone->path = 'sections_copy/'.$clone->id.'-'.$clone->uuid;
        $clone->save();
        if (file_exists($theme->path.'/views/'.$this->path.'.blade.php')) {
            $content = file_get_contents($theme->path.'/views/'.$this->path.'.blade.php');
        } else {
            $content = file_get_contents(app('view')->getFinder()->find($this->path));
        }
        $content = sanitize_content($content);
        file_put_contents($path, $content);

        return $clone;
    }

    public function delete()
    {
        $theme = app('theme')->getTheme();
        $path = $theme->path.'/views/'.$this->path.'.blade.php';
        if (file_exists($path) && str_contains($path, 'sections_copy')) {
            unlink($path);
        }
        parent::delete();
    }
}
