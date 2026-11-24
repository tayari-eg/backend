<?php

namespace Database\Factories;

use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Enums\RaterRole;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderRating;
use App\Domains\Tayars\Models\TayarProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderRating>
 */
class OrderRatingFactory extends Factory
{
    /**
     * Define the model's default state: the business rates the tayar of an order delivered more than the rating window
     * ago, so the rating is revealed.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => fn (): int => Order::factory()
                ->onTheWayWith(TayarProfile::factory()->approved()->create())
                ->create(['status' => OrderStatus::Delivered, 'delivered_at' => now()->subHours(config()->integer('tayari.ratings.window_hours') + 1)])
                ->id,
            'rater_role' => RaterRole::Business,
            'rater_id' => fn (array $attributes): int => self::party(Order::query()->findOrFail($attributes['order_id']), $attributes['rater_role'], true),
            'rated_user_id' => fn (array $attributes): int => self::party(Order::query()->findOrFail($attributes['order_id']), $attributes['rater_role'], false),
            'stars' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
            'revealed_at' => fn (array $attributes): mixed => Order::query()->findOrFail($attributes['order_id'])->ratingEndsAt() ?? now(),
        ];
    }

    /**
     * The order's tayar rates its business.
     */
    public function byTayar(): static
    {
        return $this->state(fn (array $attributes) => [
            'rater_role' => RaterRole::Tayar,
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    /**
     * A rating the given user received, revealed at that moment.
     */
    public function revealedAt(mixed $moment): static
    {
        return $this->state(fn (array $attributes) => [
            'revealed_at' => $moment,
        ]);
    }

    public function withHiddenComment(?int $hiddenBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'comment' => 'Rude words',
            'comment_hidden_at' => now(),
            'comment_hidden_by' => $hiddenBy,
            'comment_hidden_reason' => 'Abusive language',
        ]);
    }

    private static function party(Order $order, RaterRole $role, bool $isRater): int
    {
        $raterIsBusiness = $role === RaterRole::Business;

        return $raterIsBusiness === $isRater ? $order->sender_id : (int) $order->tayar_id;
    }
}
