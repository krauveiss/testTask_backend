<?php

namespace App\Http\Requests;

use App\Enums\DishCategory;
use App\Rules\PhotoReference;
use App\Services\DishDraftService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpsertDishRequest extends FormRequest
{
    private ?array $draft = null;

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
            'calories' => ['nullable', 'numeric', 'min:0'],
            'proteins' => ['nullable', 'numeric', 'min:0'],
            'fats' => ['nullable', 'numeric', 'min:0'],
            'carbohydrates' => ['nullable', 'numeric', 'min:0'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
            'portion_size' => ['required', 'numeric', 'gt:0'],
            'category' => ['nullable', Rule::enum(DishCategory::class)],
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

            $draft = $this->dishDraft();

            if (mb_strlen($draft['normalized_name']) < 2) {
                $validator->errors()->add('name', 'Название блюда после обработки макросов должно содержать минимум 2 символа.');
            }

            if (! $draft['effective_category']) {
                $validator->errors()->add('category', 'Категория блюда обязательна, если она не указана макросом в названии.');
            }

            $nutrition = $this->finalNutrition();
            $minimumCalories = $this->minimumCalories(
                $nutrition['proteins'],
                $nutrition['fats'],
                $nutrition['carbohydrates']
            );
            if ($nutrition['calories'] < $minimumCalories) {
                $validator->errors()->add('calories', 'Калорийность не может быть меньше расчётной по БЖУ.');
            }
        });
    }

    public function dishDraft(): array
    {
        if ($this->draft !== null) {
            return $this->draft;
        }

        $category = DishCategory::tryFrom((string) $this->input('category'));

        $this->draft = app(DishDraftService::class)->build(
            (string) $this->input('name'),
            $category,
            $this->ingredients()
        );

        return $this->draft;
    }

    public function rawPhotos(): array
    {
        return array_values(Arr::wrap(data_get($this->all(), 'photos', [])));
    }

    public function ingredients(): array
    {
        return collect($this->input('ingredients', []))
            ->map(fn (array $ingredient) => [
                'product_id' => (int) $ingredient['product_id'],
                'quantity' => (float) $ingredient['quantity'],
            ])
            ->values()
            ->all();
    }

    public function finalNutrition(): array
    {
        $calculated = $this->dishDraft()['calculated_nutrition'];

        return [
            'calories' => round((float) $this->input('calories', $calculated['calories']), 2),
            'proteins' => round((float) $this->input('proteins', $calculated['proteins']), 2),
            'fats' => round((float) $this->input('fats', $calculated['fats']), 2),
            'carbohydrates' => round((float) $this->input('carbohydrates', $calculated['carbohydrates']), 2),
        ];
    }

    public function finalFlags(): array
    {
        $availableFlags = $this->dishDraft()['available_flags'];

        return [
            'vegan' => $availableFlags['vegan'] && $this->boolean('flags.vegan'),
            'gluten_free' => $availableFlags['gluten_free'] && $this->boolean('flags.gluten_free'),
            'sugar_free' => $availableFlags['sugar_free'] && $this->boolean('flags.sugar_free'),
        ];
    }

    private function minimumCalories(float $proteins, float $fats, float $carbohydrates): float
    {
        return round(($proteins * 4) + ($fats * 9) + ($carbohydrates * 4), 2);
    }
}
