<?php

namespace App\Console\Commands;

use App\Models\Hold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireHoldsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-holds-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire active holds past their expiry time and release stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            DB::transaction(function () {
                $updatedCount = Hold::where('status', 'active') // update holds in bulk instead of one by one
                    ->where('expires_at', '<', now())
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                if ($updatedCount > 0) {
                    Hold::where('status', 'expired')
                        ->select('id', 'product_id', 'quantity')
                        ->chunkById(500, function ($holds) {
                            $grouped = $holds->groupBy('product_id'); // group by product to reduce increment queries
                            foreach ($grouped as $productId => $group) {
                                $totalQty = $group->sum('quantity');
                                DB::table('products')->where('id', $productId)->increment('available_stock', $totalQty);
                            }
                        });
                    Log::info("{$updatedCount} expired holds successfully processed");
                } else {
                    Log::info('No expired holds found');
                }
            });
            $this->info('Hold expiration task completed successfully.');
        } catch (\Exception $e) {
            Log::error('Hold expiration failed', ['error' => $e->getMessage()]);
            $this->error('Hold expiration task failed: ' . $e->getMessage());
        }
    }
}
