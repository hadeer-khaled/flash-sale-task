<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(int $id)
    {
        $product = Product::select('id', 'name', 'price', 'available_stock')->find($id);
        
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
        ],200);
    }
}
