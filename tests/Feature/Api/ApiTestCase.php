<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    private int $nameSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        config(['app.url' => 'http://localhost']);
    }

    protected function validProductPayload(array $overrides = []): array
    {
        $name = $overrides['name'] ?? $this->nextName('Продукт');

        $payload = array_replace_recursive([
            'name' => $name,
            'photos' => ['https://example.com/product.jpg'],
            'calories' => 200,
            'proteins' => 20,
            'fats' => 10,
            'carbohydrates' => 10,
            'composition' => 'Описание состава',
            'category' => 'Овощи',
            'cooking_requirement' => 'Готовый к употреблению',
            'flags' => [
                'vegan' => true,
                'gluten_free' => true,
                'sugar_free' => true,
            ],
        ], $overrides);

        if (array_key_exists('photos', $overrides)) {
            $payload['photos'] = $overrides['photos'];
        }

        return $payload;
    }

    protected function createProduct(array $overrides = []): Product
    {
        $payload = $this->validProductPayload($overrides);

        return Product::query()->create([
            'name' => $payload['name'],
            'search_name' => mb_strtolower($payload['name']),
            'photos' => $payload['photos'] ?? [],
            'calories' => $payload['calories'],
            'proteins' => $payload['proteins'],
            'fats' => $payload['fats'],
            'carbohydrates' => $payload['carbohydrates'],
            'composition' => $payload['composition'] ?? null,
            'category' => $payload['category'],
            'cooking_requirement' => $payload['cooking_requirement'],
            'is_vegan' => (bool) data_get($payload, 'flags.vegan', false),
            'is_gluten_free' => (bool) data_get($payload, 'flags.gluten_free', false),
            'is_sugar_free' => (bool) data_get($payload, 'flags.sugar_free', false),
        ]);
    }

    protected function validDishPayload(array $ingredients, array $overrides = []): array
    {
        $name = $overrides['name'] ?? $this->nextName('Блюдо');

        $payload = array_replace_recursive([
            'name' => $name,
            'photos' => [$this->fakeImageUpload('dish.jpg')],
            'portion_size' => 250,
            'ingredients' => $ingredients,
            'flags' => [
                'vegan' => true,
                'gluten_free' => true,
                'sugar_free' => true,
            ],
        ], $overrides);

        if (array_key_exists('photos', $overrides)) {
            $payload['photos'] = $overrides['photos'];
        }

        if (array_key_exists('ingredients', $overrides)) {
            $payload['ingredients'] = $overrides['ingredients'];
        }

        return $payload;
    }

    protected function createDish(array $ingredients, array $overrides = []): Dish
    {
        $payload = array_replace_recursive([
            'name' => $this->nextName('Блюдо'),
            'photos' => [],
            'calories' => 150,
            'proteins' => 8,
            'fats' => 5,
            'carbohydrates' => 12,
            'portion_size' => 250,
            'category' => 'Суп',
            'flags' => [
                'vegan' => false,
                'gluten_free' => false,
                'sugar_free' => false,
            ],
        ], $overrides);

        $dish = Dish::query()->create([
            'name' => $payload['name'],
            'search_name' => mb_strtolower($payload['name']),
            'photos' => $payload['photos'],
            'calories' => $payload['calories'],
            'proteins' => $payload['proteins'],
            'fats' => $payload['fats'],
            'carbohydrates' => $payload['carbohydrates'],
            'portion_size' => $payload['portion_size'],
            'category' => $payload['category'],
            'is_vegan' => (bool) data_get($payload, 'flags.vegan', false),
            'is_gluten_free' => (bool) data_get($payload, 'flags.gluten_free', false),
            'is_sugar_free' => (bool) data_get($payload, 'flags.sugar_free', false),
        ]);

        $dish->ingredients()->sync(
            collect($ingredients)->mapWithKeys(fn (array $ingredient) => [
                $ingredient['product_id'] => ['quantity' => $ingredient['quantity']],
            ])->all()
        );

        return $dish->load('ingredients');
    }

    protected function nextName(string $prefix): string
    {
        $this->nameSequence++;

        return $prefix.' '.$this->nameSequence;
    }

    protected function fakeImageUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 32, 'image/jpeg');
    }
}
