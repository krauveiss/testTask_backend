<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

class DishApiTest extends ApiTestCase
{


    #[TestDox('POST /api/dishes принимает блюдо без фотографий')]
    public function test_it_creates_a_dish_without_photos(): void
    {
        $product = $this->createProduct([
            'name' => 'Рис',
            'category' => 'Крупы',
            'calories' => 130,
            'proteins' => 2.5,
            'fats' => 0.3,
            'carbohydrates' => 28,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);

        $this->postJson('/api/dishes', $this->validDishPayload([
            ['product_id' => $product->id, 'quantity' => 150],
        ], [
            'name' => 'Рисовая каша',
            'category' => 'Первое',
            'photos' => [],
            'calories' => 195,
            'proteins' => 3.75,
            'fats' => 0.45,
            'carbohydrates' => 42,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Рисовая каша')
            ->assertJsonPath('data.photos', []);
    }

    public static function invalidDishPayloadProvider(): array
    {
        return [
            'boundary: empty ingredient list' => [
                ['ingredients' => []],
                'ingredients',
            ],
            'equivalence: duplicate product in ingredients' => [
                [
                    'ingredients' => [
                        ['product_id' => 1, 'quantity' => 70],
                        ['product_id' => 1, 'quantity' => 30],
                    ],
                ],
                'ingredients.1.product_id',
            ],
            'boundary: zero portion size' => [
                ['portion_size' => 0],
                'portion_size',
            ],
            'equivalence: category required when no macro exists' => [
                ['name' => 'Овощной', 'category' => null],
                'category',
            ],
        ];
    }

    #[DataProvider('invalidDishPayloadProvider')]
    #[TestDox('POST /api/dishes отклоняет невалидные данные')]
    public function test_it_rejects_invalid_dish_payloads(array $overrides, string $expectedErrorField): void
    {
        $product = $this->createProduct([
            'name' => 'Гречка',
            'category' => 'Крупы',
            'calories' => 110,
            'proteins' => 4,
            'fats' => 1,
            'carbohydrates' => 21,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);

        $payload = $this->validDishPayload([
            ['product_id' => $product->id, 'quantity' => 100],
        ], array_replace([
            'name' => 'Гречка',
            'category' => 'Второе',
            'photos' => [],
        ], $overrides));

        if (isset($overrides['ingredients'])) {
            $payload['ingredients'] = collect($overrides['ingredients'])
                ->map(function (array $ingredient) use ($product): array {
                    return [
                        'product_id' => $ingredient['product_id'] === 1 ? $product->id : $ingredient['product_id'],
                        'quantity' => $ingredient['quantity'],
                    ];
                })
                ->all();
        }

        $response = is_array($payload['photos']) && isset($payload['photos'][0]) && $payload['photos'][0] instanceof UploadedFile
            ? $this->post('/api/dishes', $payload, ['Accept' => 'application/json'])
            : $this->postJson('/api/dishes', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrorField);
    }

    #[TestDox('POST /api/dishes автоматически снимает недоступный флаг блюда')]
    public function test_it_drops_an_unavailable_dish_flag_on_create(): void
    {
        $product = $this->createProduct([
            'name' => 'Творог',
            'category' => 'Мясной',
            'calories' => 120,
            'proteins' => 17,
            'fats' => 5,
            'carbohydrates' => 3,
            'flags' => ['vegan' => false, 'gluten_free' => true, 'sugar_free' => true],
        ]);

        $this->postJson('/api/dishes', $this->validDishPayload([
            ['product_id' => $product->id, 'quantity' => 100],
        ], [
            'name' => 'Творожный перекус',
            'category' => 'Перекус',
            'photos' => [],
            'calories' => 125,
            'proteins' => 17,
            'fats' => 5,
            'carbohydrates' => 3,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.flags.vegan', false)
            ->assertJsonPath('data.available_flags.vegan', false)
            ->assertJsonPath('data.flags.gluten_free', true);
    }

    #[TestDox('PUT /api/dishes/{dish} пересчитывает доступные флаги при изменении состава')]
    public function test_it_recalculates_dish_flags_on_update(): void
    {
        $veganProduct = $this->createProduct([
            'name' => 'Тофу',
            'category' => 'Консервы',
            'calories' => 150,
            'proteins' => 16,
            'fats' => 8,
            'carbohydrates' => 3,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $nonVeganProduct = $this->createProduct([
            'name' => 'Сыр',
            'category' => 'Мясной',
            'calories' => 300,
            'proteins' => 22,
            'fats' => 24,
            'carbohydrates' => 0,
            'flags' => ['vegan' => false, 'gluten_free' => true, 'sugar_free' => true],
        ]);

        $createResponse = $this->postJson('/api/dishes', $this->validDishPayload([
            ['product_id' => $veganProduct->id, 'quantity' => 100],
        ], [
            'name' => 'Тёплый боул',
            'category' => 'Перекус',
            'photos' => [],
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]));

        $dishId = $createResponse->json('data.id');

        $this->putJson("/api/dishes/{$dishId}", $this->validDishPayload([
            ['product_id' => $veganProduct->id, 'quantity' => 100],
            ['product_id' => $nonVeganProduct->id, 'quantity' => 50],
        ], [
            'name' => 'Тёплый боул',
            'category' => 'Перекус',
            'photos' => [],
            'calories' => 300,
            'proteins' => 27,
            'fats' => 20,
            'carbohydrates' => 3,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]))
            ->assertOk()
            ->assertJsonPath('data.flags.vegan', false)
            ->assertJsonPath('data.flags.gluten_free', true)
            ->assertJsonPath('data.flags.sugar_free', true)
            ->assertJsonPath('data.available_flags.vegan', false)
            ->assertJsonPath('data.available_flags.gluten_free', true)
            ->assertJsonPath('data.available_flags.sugar_free', true);
    }

    #[TestDox('GET /api/dishes применяет комбинированные фильтры и поиск по подстроке')]
    public function test_it_lists_dishes_with_filters_and_search(): void
    {
        $veganProduct = $this->createProduct([
            'name' => 'Авокадо',
            'category' => 'Овощи',
            'calories' => 160,
            'proteins' => 2,
            'fats' => 15,
            'carbohydrates' => 9,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $otherProduct = $this->createProduct([
            'name' => 'Йогурт',
            'category' => 'Жидкость',
            'calories' => 60,
            'proteins' => 3,
            'fats' => 2,
            'carbohydrates' => 7,
            'flags' => ['vegan' => false, 'gluten_free' => true, 'sugar_free' => false],
        ]);

        $this->createDish([
            ['product_id' => $veganProduct->id, 'quantity' => 100],
        ], [
            'name' => 'крутой продукт из АВОКАДО',
            'category' => 'Салат',
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $this->createDish([
            ['product_id' => $otherProduct->id, 'quantity' => 100],
        ], [
            'name' => 'Йогуртовый десерт',
            'category' => 'Десерт',
            'flags' => ['vegan' => false, 'gluten_free' => true, 'sugar_free' => false],
        ]);

        $response = $this->getJson('/api/dishes?search=авокадо&category=Салат&vegan=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'крутой продукт из АВОКАДО')
            ->assertJsonPath('data.0.category', 'Салат');
    }

    #[TestDox('DELETE /api/dishes/{dish} удаляет блюдо')]
    public function test_it_deletes_a_dish(): void
    {
        $product = $this->createProduct([
            'name' => 'Картофель',
            'calories' => 80,
            'proteins' => 2,
            'fats' => 0,
            'carbohydrates' => 17,
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $dish = $this->createDish([
            ['product_id' => $product->id, 'quantity' => 100],
        ], [
            'name' => 'Картофельное пюре',
            'category' => 'Второе',
        ]);

        $this->deleteJson("/api/dishes/{$dish->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('dishes', ['id' => $dish->id]);
    }
}
