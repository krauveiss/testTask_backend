<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
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
        'composition',
        'category',
        'cooking_requirement',
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
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'is_sugar_free' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->created_at = now();
            $product->updated_at = null;
        });

        static::updating(function (self $product): void {
            $product->updated_at = now();
        });
    }
}
