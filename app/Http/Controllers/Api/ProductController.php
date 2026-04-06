<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\PhotoStorageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        $search = trim((string) $request->query('search', ''));
        $sortColumn = match ($request->query('sort_by', 'name')) {
            'calories' => 'calories',
            'proteins' => 'proteins',
            'fats' => 'fats',
            'carbohydrates' => 'carbohydrates',
            default => 'search_name',
        };
        $sortDirection = $request->query('sort_direction') === 'desc' ? 'desc' : 'asc';

        if ($search !== '') {
            $query->where('search_name', 'like', '%'.mb_strtolower($search).'%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('cooking_requirement')) {
            $query->where('cooking_requirement', $request->query('cooking_requirement'));
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

        return ProductResource::collection($query->orderBy($sortColumn, $sortDirection)->get());
    }

    public function store(UpsertProductRequest $request, PhotoStorageService $photoStorageService)
    {
        $flags = $request->flags();

        $product = Product::query()->create([
            'name' => $request->string('name')->toString(),
            'search_name' => mb_strtolower($request->string('name')->toString()),
            'photos' => $photoStorageService->store($request->rawPhotos(), 'products'),
            'calories' => round((float) $request->input('calories'), 2),
            'proteins' => round((float) $request->input('proteins'), 2),
            'fats' => round((float) $request->input('fats'), 2),
            'carbohydrates' => round((float) $request->input('carbohydrates'), 2),
            'composition' => $request->input('composition'),
            'category' => $request->input('category'),
            'cooking_requirement' => $request->input('cooking_requirement'),
            'is_vegan' => $flags['vegan'],
            'is_gluten_free' => $flags['gluten_free'],
            'is_sugar_free' => $flags['sugar_free'],
        ]);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(UpsertProductRequest $request, Product $product, PhotoStorageService $photoStorageService): ProductResource
    {
        $flags = $request->flags();

        $product->update([
            'name' => $request->string('name')->toString(),
            'search_name' => mb_strtolower($request->string('name')->toString()),
            'photos' => $photoStorageService->store($request->rawPhotos(), 'products'),
            'calories' => round((float) $request->input('calories'), 2),
            'proteins' => round((float) $request->input('proteins'), 2),
            'fats' => round((float) $request->input('fats'), 2),
            'carbohydrates' => round((float) $request->input('carbohydrates'), 2),
            'composition' => $request->input('composition'),
            'category' => $request->input('category'),
            'cooking_requirement' => $request->input('cooking_requirement'),
            'is_vegan' => $flags['vegan'],
            'is_gluten_free' => $flags['gluten_free'],
            'is_sugar_free' => $flags['sugar_free'],
        ]);

        return new ProductResource($product->refresh());
    }

    public function destroy(Product $product)
    {
        $dishes = $product->dishes()
            ->select('dishes.id', 'dishes.name')
            ->orderBy('dishes.name')
            ->get();

        if ($dishes->isNotEmpty()) {
            return response()->json([
                'message' => 'Нельзя удалить продукт, который используется в составе блюда.',
                'dishes' => $dishes,
            ], Response::HTTP_CONFLICT);
        }

        $product->delete();

        return response()->noContent();
    }
}
