<?php

return [
    'idempotency_cleanup_days' => env('IDEMPOTENCY_CLEANUP_DAYS', 1), 
    'idempotency_cron' => env('IDEMPOTENCY_CLEANUP_CRON', '0 0 * * *'), // Daily at midnight
];
