<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

class ProductApiTest extends ApiTestCase
{
    #[TestDox('GET /api/products/{product} возвращает все основные поля продукта')]
    public function test_it_shows_a_product(): void
    {
        $product = $this->createProduct([
            'name' => 'Огурец',
            'composition' => 'Свежий огурец',
            'flags' => [
                'vegan' => true,
                'gluten_free' => true,
                'sugar_free' => false,
            ],
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Огурец')
            ->assertJsonPath('data.composition', 'Свежий огурец')
            ->assertJsonPath('data.category', 'Овощи')
            ->assertJsonPath('data.cooking_requirement', 'Готовый к употреблению')
            ->assertJsonPath('data.flags.vegan', true)
            ->assertJsonPath('data.flags.sugar_free', false);
    }

    #[TestDox('POST /api/products создаёт продукт с валидными значениями')]
    public function test_it_creates_a_product_for_a_valid_boundary_payload(): void
    {
        $payload = $this->validProductPayload([
            'name' => 'Аб',
            'photos' => [],
            'calories' => 550,
            'proteins' => 40,
            'fats' => 30,
            'carbohydrates' => 30,
        ]);

        $response = $this->postJson('/api/products', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Аб')
            ->assertJsonPath('data.photos', [])
            ->assertJsonPath('data.updated_at', null);

        $this->assertDatabaseHas('products', [
            'name' => 'Аб',
            'category' => 'Овощи',
            'cooking_requirement' => 'Готовый к употреблению',
        ]);
    }

    #[TestDox('POST /api/products принимает ровно пять фотографий продукта')]
    public function test_it_accepts_the_maximum_allowed_number_of_product_photos(): void
    {
        $payload = $this->validProductPayload([
            'photos' => [
                'https://example.com/1.jpg',
                'https://example.com/2.jpg',
                'https://example.com/3.jpg',
                'https://example.com/4.jpg',
                'https://example.com/5.jpg',
            ],
            'calories' => 210,
        ]);

        $this->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonCount(5, 'data.photos');
    }

    public static function invalidProductPayloadProvider(): array
    {
        return [
            'boundary: name shorter than minimum length' => [
                ['name' => 'Я'],
                'name',
            ],
            'boundary: more than five photos' => [
                ['photos' => array_fill(0, 6, 'https://example.com/photo.jpg')],
                'photos',
            ],
            'equivalence: unknown category' => [
                ['category' => 'Фрукты'],
                'category',
            ],
            'equivalence: unknown cooking requirement' => [
                ['cooking_requirement' => 'Сырое'],
                'cooking_requirement',
            ],
            'boundary: BJU sum exceeds 100 grams' => [
                [
                    'calories' => 555,
                    'proteins' => 40,
                    'fats' => 30,
                    'carbohydrates' => 30.01,
                ],
                'proteins',
            ],
            'equivalence: calories lower than BJU formula' => [
                [
                    'calories' => 20,
                    'proteins' => 27,
                    'fats' => 0,
                    'carbohydrates' => 0,
                ],
                'calories',
            ],
        ];
    }

    #[DataProvider('invalidProductPayloadProvider')]
    #[TestDox('POST /api/products отклоняет невалидные данные из эквивалентных классов и граничных значений')]
    public function test_it_rejects_invalid_product_payloads(array $overrides, string $expectedErrorField): void
    {
        $response = $this->postJson('/api/products', $this->validProductPayload($overrides));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($expectedErrorField);
    }

    #[TestDox('PUT /api/products/{product} обновляет продукт и заполняет updated_at')]
    public function test_it_updates_an_existing_product(): void
    {
        $product = $this->createProduct([
            'name' => 'Томаты',
            'flags' => [
                'vegan' => false,
                'gluten_free' => false,
                'sugar_free' => false,
            ],
        ]);

        $response = $this->putJson("/api/products/{$product->id}", $this->validProductPayload([
            'name' => 'Спелые томаты',
            'calories' => 120,
            'proteins' => 10,
            'fats' => 4,
            'carbohydrates' => 6,
            'flags' => [
                'vegan' => true,
                'gluten_free' => true,
                'sugar_free' => true,
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.name', 'Спелые томаты')
            ->assertJsonPath('data.flags.vegan', true);

        $this->assertNotNull($response->json('data.updated_at'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Спелые томаты',
            'is_vegan' => 1,
        ]);
    }

    #[TestDox('GET /api/products применяет комбинированные фильтры, поиск по подстроке и сортировку')]
    public function test_it_lists_products_with_filters_search_and_sorting(): void
    {
        $this->createProduct([
            'name' => 'Томат сушёный',
            'calories' => 250,
            'category' => 'Овощи',
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $this->createProduct([
            'name' => 'Томат свежий',
            'calories' => 50,
            'category' => 'Овощи',
            'flags' => ['vegan' => true, 'gluten_free' => true, 'sugar_free' => true],
        ]);
        $this->createProduct([
            'name' => 'Курица',
            'calories' => 190,
            'category' => 'Мясной',
            'flags' => ['vegan' => false, 'gluten_free' => true, 'sugar_free' => true],
        ]);

        $response = $this->getJson('/api/products?search=томат&category=Овощи&vegan=1&sort_by=calories&sort_direction=desc');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Томат сушёный')
            ->assertJsonPath('data.1.name', 'Томат свежий');
    }

    #[TestDox('GET /api/products ищет продукты без учёта регистра')]
    public function test_it_searches_products_case_insensitively(): void
    {
        $this->createProduct(['name' => 'Томат']);
        $this->createProduct(['name' => 'Огурец']);

        $this->getJson('/api/products?search=тОмА')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Томат');
    }

    #[TestDox('DELETE /api/products/{product} запрещает удаление продукта, используемого в блюде')]
    public function test_it_prevents_deleting_a_product_used_in_a_dish(): void
    {
        $product = $this->createProduct(['name' => 'Морковь']);
        $dish = $this->createDish([
            ['product_id' => $product->id, 'quantity' => 120],
        ], [
            'name' => 'Суп-пюре',
        ]);

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(409)
            ->assertJsonPath('dishes.0.id', $dish->id)
            ->assertJsonPath('dishes.0.name', 'Суп-пюре');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    #[TestDox('DELETE /api/products/{product} удаляет неиспользуемый продукт')]
    public function test_it_deletes_an_unused_product(): void
    {
        $product = $this->createProduct(['name' => 'Лук']);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
