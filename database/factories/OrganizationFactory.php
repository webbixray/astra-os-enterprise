<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'id' => Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'settings' => [
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'currency' => 'USD',
                'language' => 'en',
            ],
            'is_active' => true,
            'extras' => null,
        ];
    }
}
