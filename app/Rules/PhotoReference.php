<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class PhotoReference implements ValidationRule
{
    public function __construct(
        private bool $allowLinks = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile) {
            if (str_starts_with((string) $value->getMimeType(), 'image/')) {
                return;
            }

            $fail('Каждая фотография должна быть изображением или строковой ссылкой.');

            return;
        }

        if ($this->allowLinks && is_string($value) && trim($value) !== '') {
            return;
        }

        $fail($this->allowLinks
            ? 'Каждая фотография должна быть изображением или строковой ссылкой.'
            : 'Каждая фотография должна быть изображением.');
    }
}
