<?php

use App\Models\IdempotencyLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;


Schedule::command('app:expire-holds')->everyMinute();

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