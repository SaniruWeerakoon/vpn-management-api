<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VpnClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VpnClientFactory extends Factory
{
    protected $model = VpnClient::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['pending', 'active', 'revoked', 'failed']),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
