<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => 'tenant-test',
            'uri_user' => Str::uuid()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'roles' => [],
        ];
    }

    public function admin(): static { return $this->withRole(Role::ADMIN); }
    public function seller(): static { return $this->withRole(Role::SELLER); }
    public function auditor(): static { return $this->withRole(Role::AUDITOR); }

    public function withRole(string $role): static
    {
        return $this->state(fn () => ['roles' => [['name' => $role, 'slug' => $role]]]);
    }
}
