<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Hold;
use App\Models\Order;
use Exception;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request)
    {
        $holdId = $request->hold_id;

        try {
            return DB::transaction(function () use ($holdId) {
                $hold = Hold::lockForUpdate()->find($holdId); 

                if (!$hold || $hold->expires_at < now() || $hold->status !== 'active') {
                    return response()->json([
                        'status' => 'failure',
                        'message' => 'Invalid or expired hold'
                    ], 400);
                }

                $order = Order::create([
                    'hold_id' => $holdId,
                    'product_id' => $hold->product_id,
                    'status' => 'pending',
                ]);

                $hold->update(['status' => 'used']);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Order created successfully',
                    'order_id' => $order->id,
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