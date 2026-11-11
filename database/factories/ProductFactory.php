<?php

namespace Database\Factories;

use App\Enums\ProductAvailability;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'name' => 'Product '.fake()->unique()->words(2, true),
            'brand' => fake()->company(),
            'short_description' => fake()->sentence(),
            'availability_status' => ProductAvailability::Enquire,
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
