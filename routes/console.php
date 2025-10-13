<?php

use App\Models\Hold;
use App\Models\IdempotencyLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    DB::transaction(function () {
        $expiredHolds = Hold::where('status', 'active')
            ->where('expires_at', '<', now())
            ->with('product')
            ->get();

        $deletedHoldCount = $expiredHolds->count();
        if ($deletedHoldCount) {
            foreach ($expiredHolds as $hold) {
                $hold->product->increment('available_stock', $hold->quantity);
                $hold->update(['status' => 'expired']);
            }
            Log::info("{$deletedHoldCount} expired holds successfully deleted");
        } else {
            Log::info('No expired holds found');  
        }
    });
})->everyMinute();  


Schedule::call(function () {
    $deletedCount = IdempotencyLog::where('created_at', '<', now()->subDays(1))->delete();
    if($deletedCount){
        Log::info("{$deletedCount} expired idempotency keys successfully deleted");
    }
    else{
        Log::info('No expired idempotency keys found');  
    }
})->everyMinute();  