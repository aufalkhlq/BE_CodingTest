<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 10;

    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->search($request->validated('search'))
            ->withStatus($request->validated('status'))
            ->orderBy('id')
            ->paginate($request->validated('per_page', self::DEFAULT_PER_PAGE))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        return new ProductResource(Product::create($request->validated()));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }

    public function stats(): JsonResponse
    {
        return response()->json(Product::countByStatus());
    }
}
