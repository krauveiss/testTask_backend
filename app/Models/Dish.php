<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dish extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'search_name',
        'photos',
        'calories',
        'proteins',
        'fats',
        'carbohydrates',
        'portion_size',
        'category',
        'is_vegan',
        'is_gluten_free',
        'is_sugar_free',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'calories' => 'float',
            'proteins' => 'float',
            'fats' => 'float',
            'carbohydrates' => 'float',
            'portion_size' => 'float',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'is_sugar_free' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function calculatedNutrition(): array
    {
        $totals = [
            'calories' => 0.0,
            'proteins' => 0.0,
            'fats' => 0.0,
            'carbohydrates' => 0.0,
        ];

        foreach ($this->ingredients as $ingredient) {
            $quantity = (float) $ingredient->pivot->quantity;

            $totals['calories'] += $ingredient->calories * $quantity / 100;
            $totals['proteins'] += $ingredient->proteins * $quantity / 100;
            $totals['fats'] += $ingredient->fats * $quantity / 100;
            $totals['carbohydrates'] += $ingredient->carbohydrates * $quantity / 100;
        }

        return [
            'calories' => round($totals['calories'], 2),
            'proteins' => round($totals['proteins'], 2),
            'fats' => round($totals['fats'], 2),
            'carbohydrates' => round($totals['carbohydrates'], 2),
        ];
    }

    public function availableFlags(): array
    {
        if ($this->ingredients->isEmpty()) {
            return [
                'vegan' => false,
                'gluten_free' => false,
                'sugar_free' => false,
            ];
        }

        return [
            'vegan' => $this->ingredients->every(fn (Product $product) => $product->is_vegan),
            'gluten_free' => $this->ingredients->every(fn (Product $product) => $product->is_gluten_free),
            'sugar_free' => $this->ingredients->every(fn (Product $product) => $product->is_sugar_free),
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $dish): void {
            $dish->created_at = now();
            $dish->updated_at = null;
        });

        static::updating(function (self $dish): void {
            $dish->updated_at = now();
        });
    }
}
