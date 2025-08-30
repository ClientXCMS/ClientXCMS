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


namespace App\Models\Traits;

use App\Models\Metadata;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMetadata
{
    /**
     * Relation pour obtenir les métadonnées associées au modèle.
     */
    public function metadata(): MorphMany
    {
        return $this->morphMany(Metadata::class, 'model');
    }

    /**
     * Cache pour stocker les métadonnées du modèle.
     *
     * @var array|null
     */
    protected $metadataCache = null;

    /**
     * Fonction pour obtenir les métadonnées du modèle.
     */
    public function getCachedMetadata(): array
    {
        if ($this->metadataCache !== null) {
            return $this->metadataCache;
        }
        if ($this->relationLoaded('metadata')) {
            return $this->metadataCache = $this->metadata->pluck('value', 'key')->all();
        }
        return $this->metadataCache = $this->metadata()->get()->pluck('value', 'key')->toArray();
    }

    public static function getItemsByMetadata($key, $value = null)
    {
        return self::whereHas('metadata', function ($query) use ($key, $value) {
            $query->where('key', $key)->where('model_type', self::class);
            if ($value !== null) {
                if (is_array($value)) {
                    $query->whereIn('value', $value);
                } else {
                    $query->where('value', $value);
                }
            }
        })->get();
    }

    /**
     * Fonction pour attacher une métadonnée au modèle.
     *
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function attachMetadata(string $key, $value)
    {
        if ($this->metadata()->where('key', $key)->exists()) {
            $this->updateMetadata($key, $value);
            $metadata = $this->metadata()->where('key', $key)->first();
        } else {
            $metadata = $this->metadata()->create([
                'key' => $key,
                'value' => $value,
            ]);
        }
        $this->updateMetadataCache();

        return $metadata;
    }

    /**
     * Fonction pour obtenir une métadonnée spécifique du modèle.
     *
     * @return mixed
     */
    public function getMetadata(string $key)
    {
        $cachedMetadata = $this->getCachedMetadata();

        return $cachedMetadata[$key] ?? null;
    }

    /**
     * Fonction pour synchroniser les métadonnées avec le cache.
     *
     * @return void
     */
    public function syncMetadata(array $metadata)
    {
        $this->metadata()->delete();
        foreach ($metadata as $key => $value) {
            $this->attachMetadata($key, $value);
        }
        $this->updateMetadataCache();
    }

    /**
     * Fonction pour mettre à jour le cache des métadonnées.
     *
     * @return void
     */
    protected function updateMetadataCache()
    {
        $this->metadataCache = $this->metadata()->get()->pluck('value', 'key')->toArray();
    }

    public function detachMetadata(string $key)
    {
        $this->metadata()->where('key', $key)->delete();
        $this->updateMetadataCache();
    }

    public function detachMetadatas(array $keys)
    {
        $this->metadata()->whereIn('key', $keys)->delete();
        $this->updateMetadataCache();
    }

    public function updateMetadata(string $key, $value)
    {
        $this->metadata()->where('key', $key)->update(['value' => $value]);
        $this->updateMetadataCache();
    }

    public function updateMetadataOrCreate(string $key, $value)
    {
        $metadata = $this->metadata()->where('key', $key)->first();
        if ($metadata === null) {
            $this->attachMetadata($key, $value);
        } else {
            $metadata->update(['value' => $value]);
        }
    }

    public function hasMetadata($key): bool
    {
        if (is_array($key)) {
            return collect($key)->every(function ($k) {
                return $this->hasMetadata($k);
            });
        }
        return $this->getCachedMetadata()[$key] ?? false;
    }

    public static function bootHasMetadata()
    {
        static::deleting(function ($model) {
            $model->metadata()->delete();
        });
    }
}
