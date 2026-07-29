<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'name' => fake()->jobTitle() . ' Agent',
            'role' => fake()->randomElement(['ceo', 'director', 'specialist']),
            'model_config' => [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.5,
                'max_tokens' => 2048,
            ],
            'autonomy_level' => fake()->randomElement(['supervised', 'semi_autonomous', 'full']),
            'parent_agent_id' => null,
            'capabilities' => ['monitoring', 'analysis'],
            'instructions' => null,
            'metadata' => null,
            'is_active' => true,
        ];
    }

    public function ceo(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'ceo',
            'autonomy_level' => 'full',
            'model_config' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
        ]);
    }

    public function specialist(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'specialist',
            'autonomy_level' => 'supervised',
        ]);
    }
}
