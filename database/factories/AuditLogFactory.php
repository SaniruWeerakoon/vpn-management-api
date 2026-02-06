<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'action' => $this->faker->randomElement(['vpn_client.created', 'vpn_client.updated', 'vpn_client.deleted']),
            'target_type' => $this->faker->word(),
            'target_id' => $this->faker->randomNumber(),
            'metadata' => $this->faker->words(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'actor_id' => User::factory(),
        ];
    }
}
