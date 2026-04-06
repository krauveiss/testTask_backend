<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(string $disk, string $path)
    {
        if (! in_array($disk, ['public', 'local'], true)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! Storage::disk($disk)->exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->file(Storage::disk($disk)->path($path));
    }
}
