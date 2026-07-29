<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'name' => fake()->sentence(3),
            'objective' => fake()->randomElement(['awareness', 'traffic', 'conversions', 'leads', 'engagement']),
            'status' => fake()->randomElement(['draft', 'scheduled', 'active', 'paused', 'completed']),
            'budget_amount' => fake()->randomFloat(2, 100, 100000),
            'budget_currency' => 'USD',
            'target_audience' => [
                'age' => ['18-65'],
                'locations' => ['US'],
            ],
            'platforms' => fake()->randomElements(['meta', 'google', 'linkedin', 'tiktok'], rand(1, 2)),
            'start_date' => fake()->optional()->date(),
            'end_date' => fake()->optional()->date(),
            'metadata' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
