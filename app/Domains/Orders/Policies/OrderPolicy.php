<?php

namespace App\Domains\Orders\Policies;

use App\Domains\Accounts\Enums\VerificationStatus;
use App\Domains\Orders\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Only approved business accounts send orders for now; individual customers come later.
     */
    public function create(User $user): Response
    {
        if (! $user->isBusiness()) {
            return Response::deny(__('orders.business_account_only'));
        }

        if ($user->businessProfile()->value('verification_status') !== VerificationStatus::Approved) {
            return Response::deny(__('orders.business_not_approved'));
        }

        return Response::allow();
    }

    public function view(User $user, Order $order): bool
    {
        return $order->sender_id === $user->id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->sender_id === $user->id;
    }

    /**
     * The tayar who accepted the order.
     */
    public function viewAsTayar(User $user, Order $order): bool
    {
        return $order->tayar_id === $user->id;
    }
}
