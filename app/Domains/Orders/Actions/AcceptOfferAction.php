<?php

namespace App\Domains\Orders\Actions;

use App\Domains\Orders\Enums\OfferStatus;
use App\Domains\Orders\Enums\OrderStatus;
use App\Domains\Orders\Events\OrderAccepted;
use App\Domains\Orders\Models\Order;
use App\Domains\Orders\Models\OrderOffer;
use App\Domains\Tayars\Models\TayarProfile;
use App\Domains\Tayars\Models\TayarWorkSession;
use App\Domains\Wallet\Data\WalletSummary;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptOfferAction
{
    /**
     * Gives the order to the tayar: pending, with the tayar, the work session and the earning from the offer, a history
     * row, and the order's other open offers withdrawn; when this brings the tayar to the mode's limit, the tayar's open
     * offers on other orders are withdrawn too. Locks the order, then the tayar profile (so one tayar can't take two
     * orders at once), then the offers: a cancel locks the order first too, so whoever commits first wins. Accepting an
     * offer already accepted by this tayar returns the order again while it is still pending with this tayar. The tayar's
     * wallet is locked after the offer, to refuse a tayar owing more cash than tayari.wallet.max_cash_owed; the bulk
     * withdrawals of the other open offers run after it, so their offer rows are locked after the wallet.
     *
     * @throws ValidationException (errors.offer) when the offer expired, the order is no longer available, the offer
     *                             belongs to an older work session, the tayar already holds as many orders as the mode
     *                             allows, or the tayar owes too much cash
     */
    public function handle(OrderOffer $offer, User $tayar): Order
    {
        $result = DB::transaction(function () use ($offer, $tayar): Order|string {
            $order = Order::query()->lockForUpdate()->find($offer->order_id);
            $profile = TayarProfile::query()->where('user_id', $tayar->id)->lockForUpdate()->firstOrFail();
            $offer = OrderOffer::query()->lockForUpdate()->findOrFail($offer->id);

            // A second tap: only while the order is still this tayar's accepted order.
            if ($offer->status === OfferStatus::Accepted && $order?->status === OrderStatus::Pending && $order->tayar_id === $tayar->id) {
                return $order;
            }

            if ($order === null || ! $order->status->canTransitionTo(OrderStatus::Pending) || ! $offer->status->canTransitionTo(OfferStatus::Accepted)) {
                return $offer->status === OfferStatus::Expired ? 'offer_expired' : 'offer_unavailable';
            }

            if (! $offer->isLive()) {
                // Committed with the refusal, so the expiry is recorded even before the sweep runs.
                $offer->moveTo(OfferStatus::Expired);

                return 'offer_expired';
            }

            $session = TayarWorkSession::query()->with('mode')->find($offer->work_session_id);

            if (! $profile->isApproved() || ! $profile->is_online || (int) $profile->current_work_session_id !== $offer->work_session_id
                || $session === null || $session->ended_at !== null || $session->mode === null) {
                return 'offer_session_changed';
            }

            $activeOrders = Order::query()->where('tayar_id', $tayar->id)->whereStatusIn(OrderStatus::withTayarCases())->count();

            if ($activeOrders >= $session->mode->code->maxActiveOrders()) {
                return 'tayar_busy';
            }

            // Locked after the order, the tayar profile and the offer; the bulk offer withdrawals below come after it.
            $wallet = Wallet::query()->where('user_id', $tayar->id)->where('type', WalletType::Tayar)->lockForUpdate()->first();

            if ($wallet !== null && WalletSummary::owesTooMuchCash(BigDecimal::of((string) $wallet->balance))) {
                return 'tayar_owes_cash';
            }

            $order->forceFill([
                'status' => OrderStatus::Pending,
                'tayar_id' => $tayar->id,
                'work_session_id' => $offer->work_session_id,
                'accepted_at' => now(),
                'tayar_share_percent' => $offer->tayar_share_percent,
                'tayar_earning' => $offer->tayar_earning,
                'platform_fee' => $offer->platform_fee,
            ])->save();

            $order->statusHistories()->create([
                'from_status' => OrderStatus::LookingForTayar,
                'to_status' => OrderStatus::Pending,
                'changed_by' => $tayar->id,
            ]);

            $offer->moveTo(OfferStatus::Accepted, ['responded_at' => now()]);

            // Bulk updates: Offered is the only non-final offer status, so every matched row may move to Withdrawn.
            OrderOffer::query()
                ->where('order_id', $order->id)
                ->whereKeyNot($offer->id)
                ->where('status', OfferStatus::Offered)
                ->update(['status' => OfferStatus::Withdrawn, 'updated_at' => now()]);

            // At the mode's limit now, so the tayar's other open offers can't be accepted: free those orders for others.
            if ($activeOrders + 1 >= $session->mode->code->maxActiveOrders()) {
                OrderOffer::query()
                    ->where('tayar_id', $tayar->id)
                    ->where('order_id', '!=', $order->id)
                    ->where('status', OfferStatus::Offered)
                    ->update(['status' => OfferStatus::Withdrawn, 'updated_at' => now()]);
            }

            OrderAccepted::dispatch($order);

            return $order;
        });

        if (is_string($result)) {
            throw ValidationException::withMessages(['offer' => __("orders.{$result}")]);
        }

        return $result;
    }
}
