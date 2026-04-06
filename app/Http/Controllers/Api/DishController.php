<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertDishRequest;
use App\Http\Resources\DishResource;
use App\Models\Dish;
use App\Services\PhotoStorageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DishController extends Controller
{
    public function index(Request $request)
    {
        $query = Dish::query()->with('ingredients');
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where('search_name', 'like', '%'.mb_strtolower($search).'%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->boolean('vegan')) {
            $query->where('is_vegan', true);
        }

        if ($request->boolean('gluten_free')) {
            $query->where('is_gluten_free', true);
        }

        if ($request->boolean('sugar_free')) {
            $query->where('is_sugar_free', true);
        }

        return DishResource::collection($query->orderBy('search_name')->get());
    }

    public function store(UpsertDishRequest $request, PhotoStorageService $photoStorageService)
    {
        $draft = $request->dishDraft();
        $nutrition = $request->finalNutrition();
        $flags = $request->finalFlags();

        $dish = Dish::query()->create([
            'name' => $draft['normalized_name'],
            'search_name' => mb_strtolower($draft['normalized_name']),
            'photos' => $photoStorageService->store($request->rawPhotos(), 'dishes'),
            'calories' => $nutrition['calories'],
            'proteins' => $nutrition['proteins'],
            'fats' => $nutrition['fats'],
            'carbohydrates' => $nutrition['carbohydrates'],
            'portion_size' => round((float) $request->input('portion_size'), 2),
            'category' => $draft['effective_category']->value,
            'is_vegan' => $flags['vegan'],
            'is_gluten_free' => $flags['gluten_free'],
            'is_sugar_free' => $flags['sugar_free'],
        ]);

        $dish->ingredients()->sync($this->syncIngredients($request->ingredients()));

        return (new DishResource($dish->load('ingredients')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Dish $dish): DishResource
    {
        return new DishResource($dish->load('ingredients'));
    }

    public function update(UpsertDishRequest $request, Dish $dish, PhotoStorageService $photoStorageService): DishResource
    {
        $draft = $request->dishDraft();
        $nutrition = $request->finalNutrition();
        $flags = $request->finalFlags();

        $dish->update([
            'name' => $draft['normalized_name'],
            'search_name' => mb_strtolower($draft['normalized_name']),
            'photos' => $photoStorageService->store($request->rawPhotos(), 'dishes'),
            'calories' => $nutrition['calories'],
            'proteins' => $nutrition['proteins'],
            'fats' => $nutrition['fats'],
            'carbohydrates' => $nutrition['carbohydrates'],
            'portion_size' => round((float) $request->input('portion_size'), 2),
            'category' => $draft['effective_category']->value,
            'is_vegan' => $flags['vegan'],
            'is_gluten_free' => $flags['gluten_free'],
            'is_sugar_free' => $flags['sugar_free'],
        ]);

        $dish->ingredients()->sync($this->syncIngredients($request->ingredients()));

        return new DishResource($dish->load('ingredients'));
    }

    public function destroy(Dish $dish)
    {
        $dish->delete();

        return response()->noContent();
    }

    private function syncIngredients(array $ingredients): array
    {
        return collect($ingredients)
            ->mapWithKeys(fn (array $ingredient) => [
                $ingredient['product_id'] => ['quantity' => round((float) $ingredient['quantity'], 2)],
            ])
            ->all();
    }
}
