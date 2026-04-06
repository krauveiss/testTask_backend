<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoStorageService
{
    public function store(array $photos, string $directory): array
    {
        return collect($photos)
            ->map(function (mixed $photo) use ($directory) {
                if ($photo instanceof UploadedFile) {
                    return Storage::disk(config('filesystems.default'))->putFile($directory, $photo);
                }

                return trim((string) $photo);
            })
            ->values()
            ->all();
    }
}
