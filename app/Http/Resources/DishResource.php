<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Services\PhotoUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DishResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('ingredients');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'photos' => app(PhotoUrlService::class)->resolve($this->photos ?? []),
            'calories' => $this->calories,
            'proteins' => $this->proteins,
            'fats' => $this->fats,
            'carbohydrates' => $this->carbohydrates,
            'calculated_nutrition' => $this->calculatedNutrition(),
            'portion_size' => $this->portion_size,
            'category' => $this->category,
            'flags' => [
                'vegan' => $this->is_vegan,
                'gluten_free' => $this->is_gluten_free,
                'sugar_free' => $this->is_sugar_free,
            ],
            'available_flags' => $this->availableFlags(),
            'ingredients' => $this->ingredients
                ->map(fn (Product $product) => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => (float) $product->pivot->quantity,
                    'product_category' => $product->category,
                    'product_flags' => [
                        'vegan' => $product->is_vegan,
                        'gluten_free' => $product->is_gluten_free,
                        'sugar_free' => $product->is_sugar_free,
                    ],
                ])
                ->values()
                ->all(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
