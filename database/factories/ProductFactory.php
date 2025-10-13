<?php
namespace Database\Factories;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(4),
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'available_stock' => $this->faker->numberBetween(1, 100),
            'total_stock' => $this->faker->numberBetween(1, 200),
        ];
    }
}