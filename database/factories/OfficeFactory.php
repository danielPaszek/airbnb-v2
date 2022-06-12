<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    protected $model = Office::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),
            'addres_line1' => $this->faker->address(),
            'approval_status' => 2,
            'hidden' => false,
            'price_per_day' => $this->faker->numberBetween(1000, 2000),
            'monthly_discount' => 0
        ];
    }
}
