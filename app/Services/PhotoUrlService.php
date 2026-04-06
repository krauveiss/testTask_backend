<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PhotoUrlService
{
    public function resolve(array $photos): array
    {
        return collect($photos)
            ->map(fn (mixed $photo) => $this->resolveOne($photo))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveOne(mixed $photo): ?string
    {
        $value = trim((string) $photo);

        if ($value === '') {
            return null;
        }

        if ($this->isAbsoluteUrl($value)) {
            return $value;
        }

        if (Storage::disk('public')->exists($value)) {
            return URL::route('media.show', ['disk' => 'public', 'path' => $value], false);
        }

        if (Storage::disk('local')->exists($value)) {
            return URL::route('media.show', ['disk' => 'local', 'path' => $value], false);
        }

        return $value;
    }

    private function isAbsoluteUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }
}
