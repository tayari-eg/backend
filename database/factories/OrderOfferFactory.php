<?php

namespace Database\Factories;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Amounts match OrderFactory: 80% of a 40.00 fee.
 *
 * @extends Factory<OrderOffer>
 */
class OrderOfferFactory extends Factory
{
    /**
     * Define the model's default state: a live first-round offer to an approved tayar online in a zone session.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'work_session_id' => fn (): int => self::onlineSession()->id,
            'tayar_id' => fn (array $attributes): int => TayarWorkSession::query()->findOrFail($attributes['work_session_id'])->tayarProfile()->value('user_id'),
            'round' => 1,
            'distance_km' => '1.20',
            'tayar_share_percent' => '80.00',
            'tayar_earning' => '32.00',
            'platform_fee' => '8.00',
            'status' => OfferStatus::Offered,
            'expires_at' => now()->addSeconds(config()->integer('tayari.matching.offer_timeout_seconds')),
        ];
    }

    /**
     * Offered to the current session of this online tayar.
     */
    public function forTayar(TayarProfile $profile): static
    {
        return $this->state(fn (array $attributes) => [
            'tayar_id' => $profile->user_id,
            'work_session_id' => $profile->current_work_session_id,
        ]);
    }

    /**
     * Still marked offered, but past expires_at: the sweep has not written expired yet.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subSecond(),
        ]);
    }

    public function withStatus(OfferStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'responded_at' => in_array($status, [OfferStatus::Accepted, OfferStatus::Rejected], true) ? now() : null,
        ]);
    }

    /**
     * An approved tayar online near the OrderFactory pickup, in a zone session with its vehicle.
     */
    private static function onlineSession(): TayarWorkSession
    {
        $profile = TayarProfile::factory()->approved()->onlineAt(30.0450, 31.2360)->create();
        $session = TayarWorkSession::factory()->create(['tayar_profile_id' => $profile->id, 'vehicle_id' => $profile->vehicles()->value('id')]);
        $profile->forceFill(['current_work_session_id' => $session->id])->save();

        return $session;
    }
}
