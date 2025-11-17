<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->withRole('admin');
    }

    public function seller(): static
    {
        return $this->withRole('vendedor');
    }

    public function auditor(): static
    {
        return $this->withRole('auditor');
    }

    public function withRole(string $slug): static
    {
        return $this->afterCreating(function (User $user) use ($slug) {
            $actions = match ($slug) {
                'admin' => ['sw-admin'],
                'auditor' => ['reports.view'],
                default => ['pos.checkout'],
            };

            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucfirst($slug),
                    'description' => null,
                    'actions' => $actions,
                ],
            );

            $user->roles()->syncWithoutDetaching([$role->id]);
        });
    }
}
