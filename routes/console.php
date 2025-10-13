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
        $updatedCount = Hold::where('status', 'active')  // update holds in bulk instead of one by one
                ->where('expires_at', '<', now())
                ->update(['status' => 'expired', 'updated_at' => now()]);

        if ($updatedCount > 0) {
            Hold::where('status', 'expired')  
                ->chunkById(100, function ($holds) {  
                    foreach ($holds as $hold) {
                        $hold->product->increment('available_stock', $hold->quantity);
                    }
                });

            Log::info("{$updatedCount} expired holds successfully processed");
        } else {
            Log::debug('No expired holds found');
        }
    });
})->everyMinute();  


Schedule::call(function () {
    $cleanupDays = config('schedule.idempotency_cleanup_days');
    $deletedCount = IdempotencyLog::where('created_at', '<', now()->subDays($cleanupDays))->delete();
    if($deletedCount){
        Log::info("{$deletedCount} expired idempotency keys successfully deleted");
    }
    else{
        Log::info('No expired idempotency keys found');  
    }
})->cron(config('schedule.idempotency_cron'));