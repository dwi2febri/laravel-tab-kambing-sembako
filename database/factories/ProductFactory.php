<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return ['category_id' => Category::factory(), 'name' => 'Kambing Etawa', 'slug' => fake()->unique()->slug(), 'description' => 'Kambing sehat berkualitas.', 'price' => 2500000, 'stock' => 20, 'is_active' => true, 'is_featured' => true];
    }
}
