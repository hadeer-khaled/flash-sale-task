<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function show(int $id): JsonResponse
        {
            $cacheKey = "product:{$id}";

            $product = Cache::remember($cacheKey, config('constants.product_cache_ttl'), function () use ($id ) { 
                return Product::select('id', 'name', 'price', 'available_stock')->find($id);
            });

            if (Cache::has($cacheKey)) info('Cache hit', ['key' => $cacheKey]);

            if (!$product) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product retrieved successfully',
                'data' => $product
            ], 200);
        }
}
