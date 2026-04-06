<?php

namespace App\Http\Requests;

use App\Enums\CookingRequirement;
use App\Enums\ProductCategory;
use App\Rules\PhotoReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpsertProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => [new PhotoReference],
            'calories' => ['required', 'numeric', 'min:0'],
            'proteins' => ['required', 'numeric', 'min:0', 'max:100'],
            'fats' => ['required', 'numeric', 'min:0', 'max:100'],
            'carbohydrates' => ['required', 'numeric', 'min:0', 'max:100'],
            'composition' => ['nullable', 'string'],
            'category' => ['required', Rule::enum(ProductCategory::class)],
            'cooking_requirement' => ['required', Rule::enum(CookingRequirement::class)],
            'flags' => ['nullable', 'array'],
            'flags.vegan' => ['sometimes', 'boolean'],
            'flags.gluten_free' => ['sometimes', 'boolean'],
            'flags.sugar_free' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $sum = (float) $this->input('proteins') + (float) $this->input('fats') + (float) $this->input('carbohydrates');

            if ($sum > 100) {
                $validator->errors()->add('proteins', 'Сумма белков, жиров и углеводов на 100 грамм не может превышать 100.');
            }
        });
    }

    public function rawPhotos(): array
    {
        return array_values(Arr::wrap(data_get($this->all(), 'photos', [])));
    }

    public function flags(): array
    {
        return [
            'vegan' => $this->boolean('flags.vegan'),
            'gluten_free' => $this->boolean('flags.gluten_free'),
            'sugar_free' => $this->boolean('flags.sugar_free'),
        ];
    }
}
