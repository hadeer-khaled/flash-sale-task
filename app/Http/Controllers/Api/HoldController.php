<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreHoldRequest;
use App\Models\Hold;
use App\Models\Product;
use Exception;

class HoldController extends Controller
{
    public function store(StoreHoldRequest $request)
    {
        $productId = $request->product_id;
        $quantity = $request->qty;

        try {
            return DB::transaction(function () use ($productId, $quantity) {
                $product = Product::lockForUpdate()->find($productId);

                if (!$product) {
                    return response()->json([
                        'status' => 'failure',
                        'message' => 'Product not found'
                    ], 404);
                }

                if ($product->available_stock < $quantity) {
                    return response()->json([
                        'status' => 'failure',
                        'message' => 'Insufficient stock'
                    ], 400);
                }

                $product->decrement('available_stock', $quantity);

                $hold = Hold::create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'expires_at' => now()->addMinutes(2), // TODO: Make configurable
                ]);

                // TODO: Create to update hold status to 'expired' after expiration time using a scheduled job
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product held successfully',
                    'data' => [
                        'hold_id' => $hold->id,
                        'expires_at' => $hold->expires_at,
                    ]
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failure',
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }
}