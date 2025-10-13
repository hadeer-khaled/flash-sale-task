<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookPaymentRequest;
use App\Models\IdempotencyLog;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentController extends Controller
{
    public function webhook(WebhookPaymentRequest $request)
    {
        $idempotencyKey = $request->idempotency_key;
        $orderId = $request->order_id;
        $status = $request->status;

        try {
            $processed = IdempotencyLog::where('key', $idempotencyKey)->exists();

            if ($processed) {
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Already Processed'
                ], 409);
            }

            IdempotencyLog::create([
                'key' => $idempotencyKey,
            ]);

            return DB::transaction(function () use ($orderId, $status) {
                $order = Order::with('hold')->lockForUpdate()->find($orderId);

                if (!$order || $order->status !== 'pending') {
                    return response()->json([
                        'status' => 'failure',
                        'message' => 'Already Processed'
                    ], 409);
                }

                if ($status === 'success') {
                    $order->update(['status' => 'paid']);
                    return response()->json([
                        'status' => $status
                    ],200);
                } 
                else {
                    $order->update(['status' => 'cancelled']);
                    $order->product->increment('available_stock', $order->quantity);
                    return response()->json([
                        'status' => $status
                    ],200);                
                }
            });
        } catch (Exception $e) {
            return response()->json([
                'status' => 'failure',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}