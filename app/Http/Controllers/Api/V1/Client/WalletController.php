<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Wallet\ListWalletTransactionsRequest;
use App\Http\Requests\Api\V1\Client\Wallet\ShowWalletRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    /**
     * My business wallet: what Tayari owes me for delivered goods, less shipping and cancel fees.
     */
    public function show(ShowWalletRequest $request): WalletResource
    {
        return WalletResource::make(Wallet::ensureFor($request->user()->id, WalletType::Business));
    }

    /**
     * My business wallet's transactions, newest first, with cursor pages.
     */
    public function transactions(ListWalletTransactionsRequest $request): AnonymousResourceCollection
    {
        $wallet = Wallet::ensureFor($request->user()->id, WalletType::Business);

        return WalletTransactionResource::collection($wallet->transactions()
            ->with(['order:id,code', 'posting.reason'])
            ->when($request->transactionType(), fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage()));
    }
}
