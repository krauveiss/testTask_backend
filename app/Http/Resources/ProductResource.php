<?php

namespace App\Http\Resources;

use App\Services\PhotoUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photos' => app(PhotoUrlService::class)->resolve($this->photos ?? []),
            'calories' => $this->calories,
            'proteins' => $this->proteins,
            'fats' => $this->fats,
            'carbohydrates' => $this->carbohydrates,
            'composition' => $this->composition,
            'category' => $this->category,
            'cooking_requirement' => $this->cooking_requirement,
            'flags' => [
                'vegan' => $this->is_vegan,
                'gluten_free' => $this->is_gluten_free,
                'sugar_free' => $this->is_sugar_free,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
