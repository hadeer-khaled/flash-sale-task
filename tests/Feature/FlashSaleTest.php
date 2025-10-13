<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ProductSeeder;
use App\Models\Product;
use App\Models\Hold;
use App\Models\Order;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => ProductSeeder::class]);    
    }

    public function test_hold_expiry_returns_availability(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $holdResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $holdId = $holdResponse->json('data')['hold_id'] ?? null;

        $this->assertNotNull($holdId);

        $this->assertEquals(5, $product->fresh()->available_stock);

        Carbon::setTestNow(Carbon::now()->addMinutes(3));
        Artisan::call('app:expire-holds-command'); 

        $hold = Hold::find($holdId);
        $this->assertEquals('expired', $hold->status);
        $this->assertEquals(10, $product->fresh()->available_stock);
    }

    public function test_webhook_idempotency_handles_repeated_calls(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $holdResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $holdId = $holdResponse->json('data')['hold_id'] ?? null;

        $this->assertNotNull($holdId);

        $orderResponse = $this->postJson('/api/orders', ['hold_id' => $holdId]);
        $orderId = $orderResponse->json('order_id') ?? null;

        $this->assertNotNull($orderId);

        $initialStock = $product->fresh()->available_stock;  

        $idempotencyKey = 'test-uuid-123';
        $firstWebhook = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => 'success',
        ]);
        $this->assertEquals(200, $firstWebhook->status());
        $this->assertEquals('paid', Order::find($orderId)->status);

        $secondWebhook = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,  
            'order_id' => $orderId,
            'status' => 'success',
        ]);
        $this->assertEquals(409, $secondWebhook->status());  
        $this->assertEquals('paid', Order::find($orderId)->status);

        $this->assertEquals($initialStock, $product->fresh()->available_stock);
    }


    public function test_webhook_arriving_before_order_creation_is_out_of_order_safe(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $holdResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $holdId = $holdResponse->json('data')['hold_id'] ?? null;

        $this->assertNotNull($holdId);

        $orderResponse = $this->postJson('/api/orders', ['hold_id' => $holdId]);
        $orderId = $orderResponse->json('order_id') ?? null;

        $this->assertNotNull($orderId);

        $initialStock = $product->fresh()->available_stock;

        $idempotencyKey = 'race-test-uuid';
        $earlyWebhook = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => 'success',
        ]);
        $this->assertEquals(200, $earlyWebhook->status());
        $this->assertEquals('paid', Order::find($orderId)->status); 

        $this->assertEquals('paid', Order::find($orderId)->status);  

        $this->assertEquals($initialStock, $product->fresh()->available_stock);
    }
}