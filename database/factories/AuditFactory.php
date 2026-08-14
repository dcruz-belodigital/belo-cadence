<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Audit>
 */
final class AuditFactory extends Factory
{
    protected $model = Audit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => AuditAction::ClientUpdated,
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
        ];
    }

    public function action(AuditAction $action): static
    {
        return $this->state(fn (array $attributes): array => ['action' => $action]);
    }

    /**
     * Recorded by the application itself rather than by a person.
     */
    public function bySystem(): static
    {
        return $this->state(fn (array $attributes): array => ['user_id' => null]);
    }

    public function forRecord(Model $record): static
    {
        return $this->state(fn (array $attributes): array => [
            'auditable_type' => $record->getMorphClass(),
            'auditable_id' => $record->getKey(),
        ]);
    }
}
