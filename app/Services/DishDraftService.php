<?php

namespace App\Services;

use App\Enums\DishCategory;
use App\Models\Product;

class DishDraftService
{
    private const MACROS = [
        '!десерт',
        '!первое',
        '!второе',
        '!напиток',
        '!салат',
        '!суп',
        '!перекус',
    ];

    public function build(string $name, ?DishCategory $category, array $ingredients): array
    {
        $normalizedName = $this->normalizeName($name);
        $macroCategory = $this->resolveMacroCategory($name);
        $products = Product::query()
            ->whereIn('id', collect($ingredients)->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        $totals = [
            'calories' => 0.0,
            'proteins' => 0.0,
            'fats' => 0.0,
            'carbohydrates' => 0.0,
        ];

        $availableFlags = [
            'vegan' => ! empty($ingredients),
            'gluten_free' => ! empty($ingredients),
            'sugar_free' => ! empty($ingredients),
        ];

        foreach ($ingredients as $ingredient) {
            $product = $products->get($ingredient['product_id']);

            if (! $product) {
                continue;
            }

            $quantity = (float) $ingredient['quantity'];

            $totals['calories'] += $product->calories * $quantity / 100;
            $totals['proteins'] += $product->proteins * $quantity / 100;
            $totals['fats'] += $product->fats * $quantity / 100;
            $totals['carbohydrates'] += $product->carbohydrates * $quantity / 100;

            $availableFlags['vegan'] = $availableFlags['vegan'] && $product->is_vegan;
            $availableFlags['gluten_free'] = $availableFlags['gluten_free'] && $product->is_gluten_free;
            $availableFlags['sugar_free'] = $availableFlags['sugar_free'] && $product->is_sugar_free;
        }

        return [
            'normalized_name' => $normalizedName,
            'macro_category' => $macroCategory,
            'effective_category' => $category ?? $macroCategory,
            'calculated_nutrition' => [
                'calories' => round($totals['calories'], 2),
                'proteins' => round($totals['proteins'], 2),
                'fats' => round($totals['fats'], 2),
                'carbohydrates' => round($totals['carbohydrates'], 2),
            ],
            'available_flags' => $availableFlags,
        ];
    }

    public function normalizeName(string $name): string
    {
        $cleanName = preg_replace('/!(десерт|первое|второе|напиток|салат|суп|перекус)/iu', ' ', $name) ?? $name;
        $cleanName = preg_replace('/\s+/u', ' ', $cleanName) ?? $cleanName;

        return trim($cleanName);
    }

    private function resolveMacroCategory(string $name): ?DishCategory
    {
        $firstMacro = null;
        $firstPosition = null;

        foreach (self::MACROS as $macro) {
            $position = mb_stripos($name, $macro);

            if ($position === false) {
                continue;
            }

            if ($firstPosition === null || $position < $firstPosition) {
                $firstPosition = $position;
                $firstMacro = $macro;
            }
        }

        return $firstMacro ? DishCategory::fromMacro($firstMacro) : null;
    }
}
