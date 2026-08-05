<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'isbn' => fake()->unique()->isbn13(),
            'author' => fake()->name(),
            'description' => fake()->paragraph(),
            'purchase_price' => fake()->randomFloat(2, 5, 50),
            'sale_price' => fake()->randomFloat(2, 10, 80),
            'stock' => fake()->numberBetween(0, 100),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock' => fake()->numberBetween(1, 10)]);
    }
}
