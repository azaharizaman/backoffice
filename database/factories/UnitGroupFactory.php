<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Database\Factories;

use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\UnitGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitGroup>
 */
class UnitGroupFactory extends Factory
{
    protected $model = UnitGroup::class;

    public function definition(): array
    {
        $groupTypes = [
            'Project Teams',
            'Task Forces',
            'Special Units',
            'Committees',
            'Working Groups',
            'Strike Teams',
            'Quality Circles',
            'Innovation Labs',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($groupTypes),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->optional()->sentence(),
            'company_id' => Company::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory for an inactive unit group.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the factory for an active unit group.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
