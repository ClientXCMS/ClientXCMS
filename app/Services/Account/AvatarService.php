<?php

/*
 * This file is part of the CLIENTXCMS project.
 * Year: 2026 — v2.16 release.
 */

namespace App\Services\Account;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * v2.16 — Owner-agnostic avatar lifecycle.
 *
 * Handles upload + resize-to-square + delete for any model that exposes
 * an `avatar_path` attribute (Customer and Admin so far). Stored on the
 * `public` disk under `avatars/{owner-type}/{id}/{random}.{ext}`.
 *
 * The class deliberately does NOT extend Intervention/Image to avoid an
 * extra dependency — GD is already required by composer.json. The
 * resizer is a no-op when the source image is already small enough, so
 * users don't pay GD cost on a 100×100 photo.
 */
class AvatarService
{
    public const MAX_SIDE = 256;

    public const MAX_BYTES = 2 * 1024 * 1024; // 2 MiB

    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public static function upload(Model $owner, UploadedFile $file): string
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Avatar file is larger than the 2 MiB limit.');
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Unsupported avatar mime type: ' . $file->getMimeType());
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $ownerType = self::ownerSlug($owner);
        $directory = "avatars/{$ownerType}/{$owner->getKey()}";
        $filename = Str::random(32) . '.' . $extension;
        $path = "{$directory}/{$filename}";

        // Resize before storing if either dimension is bigger than MAX_SIDE.
        $resizedContents = self::resizeToSquare(
            $file->getPathname(),
            $file->getMimeType()
        );

        $disk = Storage::disk('public');
        if ($resizedContents !== null) {
            $disk->put($path, $resizedContents);
        } else {
            // Source already fits — store as-is, no GD round-trip.
            $disk->putFileAs($directory, $file, $filename);
        }

        // Drop any prior avatar to keep the storage clean.
        self::removeStored($owner);
        $owner->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public static function delete(Model $owner): void
    {
        self::removeStored($owner);
        if ($owner->getAttribute('avatar_path') !== null) {
            $owner->forceFill(['avatar_path' => null])->save();
        }
    }

    public static function url(?Model $owner): ?string
    {
        if ($owner === null) {
            return null;
        }
        $path = $owner->getAttribute('avatar_path');
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function initials(?Authenticatable $owner): string
    {
        if (! $owner) {
            return '·';
        }
        $first = (string) ($owner->firstname ?? '');
        $last = (string) ($owner->lastname ?? '');
        $initials = trim(($first !== '' ? Str::upper(Str::substr($first, 0, 1)) : '')
            . ($last !== '' ? Str::upper(Str::substr($last, 0, 1)) : ''));

        if ($initials === '') {
            $email = (string) ($owner->email ?? '·');

            return Str::upper(Str::substr($email, 0, 1));
        }

        return $initials;
    }

    /**
     * Deterministic background colour from the owner's name/email so
     * the initials fallback looks distinct per user without persisting
     * any extra column.
     */
    public static function backgroundColour(?Authenticatable $owner): string
    {
        $palette = [
            '#0ea5e9', '#22c55e', '#a855f7', '#ec4899', '#f97316',
            '#14b8a6', '#6366f1', '#84cc16', '#eab308', '#06b6d4',
        ];
        if ($owner === null) {
            return $palette[0];
        }
        $seed = (string) ($owner->email ?? $owner->getKey() ?? 'anon');
        $idx = crc32($seed) % count($palette);

        return $palette[$idx];
    }

    private static function removeStored(Model $owner): void
    {
        $path = $owner->getAttribute('avatar_path');
        if (! $path) {
            return;
        }
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            logger()->warning('[v2.16] Could not delete stale avatar at ' . $path . ': ' . $e->getMessage());
        }
    }

    /**
     * Returns binary contents of a square-cropped image at MAX_SIDE, or
     * NULL if the source already fits and no GD work was needed.
     */
    private static function resizeToSquare(string $sourcePath, ?string $mimeType): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null; // GD missing — fall back to storing raw upload
        }

        [$width, $height] = @getimagesize($sourcePath) ?: [0, 0];
        if ($width === 0 || $height === 0) {
            return null;
        }
        if ($width <= self::MAX_SIDE && $height <= self::MAX_SIDE && $width === $height) {
            return null;
        }

        $source = self::loadImage($sourcePath, $mimeType);
        if ($source === null) {
            return null;
        }

        // Centre-crop to a square before downscaling so portrait/landscape
        // photos don't get squashed.
        $side = min($width, $height);
        $srcX = (int) (($width - $side) / 2);
        $srcY = (int) (($height - $side) / 2);

        $target = imagecreatetruecolor(self::MAX_SIDE, self::MAX_SIDE);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled(
            $target, $source,
            0, 0,
            $srcX, $srcY,
            self::MAX_SIDE, self::MAX_SIDE,
            $side, $side
        );

        ob_start();
        // Always emit PNG so transparency survives — the file extension
        // remains the original one for nicety, but the bytes are PNG.
        imagepng($target, null, 6);
        $contents = ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $contents ?: null;
    }

    private static function loadImage(string $path, ?string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path) ?: null,
            'image/png' => imagecreatefrompng($path) ?: null,
            'image/gif' => imagecreatefromgif($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp')
                ? (imagecreatefromwebp($path) ?: null)
                : null,
            default => null,
        };
    }

    private static function ownerSlug(Model $owner): string
    {
        return Str::slug(class_basename($owner));
    }
}
