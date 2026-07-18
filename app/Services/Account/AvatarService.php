<?php

namespace App\Services\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    public const MAX_SIDE = 256;

    public const MAX_KILOBYTES = 2048;

    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function upload(Model $owner, UploadedFile $file): string
    {
        if (! $owner->exists || ! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException(__('client.profile.avatar.invalid'));
        }

        $directory = 'avatars/'.Str::slug(class_basename($owner)).'/'.$owner->getKey();
        $image = $this->resize($file);
        $extension = $image === null ? $this->extension($file) : 'png';
        $path = $directory.'/'.Str::random(32).'.'.$extension;
        $disk = Storage::disk('public');

        $stored = $image === null
            ? $disk->putFileAs($directory, $file, basename($path))
            : ($disk->put($path, $image) ? $path : false);

        if ($stored === false) {
            throw new \RuntimeException(__('client.profile.avatar.upload_failed'));
        }

        $previous = $owner->getAttribute('avatar_path');
        try {
            $owner->forceFill(['avatar_path' => $path])->saveOrFail();
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        $this->deleteFile($previous);

        return $path;
    }

    public function delete(Model $owner): void
    {
        $path = $owner->getAttribute('avatar_path');
        $owner->forceFill(['avatar_path' => null])->save();
        $this->deleteFile($path);
    }

    public function purge(Model $owner): void
    {
        $this->deleteFile($owner->getAttribute('avatar_path'));
    }

    public function url(?Model $owner): ?string
    {
        $path = $owner?->getAttribute('avatar_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function initials(?Model $owner): string
    {
        if ($owner === null) {
            return '·';
        }

        $initials = Str::of(($owner->firstname ?? '').' '.($owner->lastname ?? ''))
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials ?: Str::upper(Str::substr((string) ($owner->email ?? '·'), 0, 1));
    }

    public function backgroundColor(?Model $owner): string
    {
        $colors = ['#0284c7', '#16a34a', '#9333ea', '#db2777', '#ea580c', '#0d9488', '#4f46e5'];
        $seed = (string) ($owner?->email ?? $owner?->getKey() ?? 'anonymous');

        return $colors[abs(crc32($seed)) % count($colors)];
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Str::startsWith($path, 'avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function extension(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };
    }

    private function resize(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        [$width, $height] = @getimagesize($file->getPathname()) ?: [0, 0];
        if ($width === 0 || $height === 0 || ($width === $height && $width <= self::MAX_SIDE)) {
            return null;
        }

        $source = match ($file->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($file->getPathname()),
            'image/png' => @imagecreatefrompng($file->getPathname()),
            'image/gif' => @imagecreatefromgif($file->getPathname()),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getPathname()) : false,
            default => false,
        };
        if ($source === false) {
            return null;
        }

        $side = min($width, $height);
        $target = imagecreatetruecolor(self::MAX_SIDE, self::MAX_SIDE);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), self::MAX_SIDE, self::MAX_SIDE, $side, $side);

        ob_start();
        imagepng($target, null, 6);
        $contents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $contents ?: null;
    }
}
