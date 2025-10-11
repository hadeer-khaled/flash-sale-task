<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::UpdateOrCreate(['code' => 'P1'],[
            'code' => 'P1',
            'name' => 'Sample Product',
            'price' => 19.99,
            'available_stock' => 100,
            'total_stock' => 100,
        ]);
    }
}
