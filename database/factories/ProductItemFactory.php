<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductItemFactory extends Factory
{
    protected $model = ProductItem::class;

    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'item_name' => 'Beras', 'qty' => 5, 'unit' => 'kg'];
    }
}
