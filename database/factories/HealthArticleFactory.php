<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\HealthArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HealthArticle> */
class HealthArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Article '.fake()->unique()->sentence(3),
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (HealthArticle $a) {
            $a->status ??= ArticleStatus::Published;
            if ($a->status === ArticleStatus::Published) {
                $a->published_at ??= now()->subDay();
            }
        });
    }

    public function draft(): static
    {
        return $this->afterMaking(fn (HealthArticle $a) => $a->status = ArticleStatus::Draft);
    }
}
