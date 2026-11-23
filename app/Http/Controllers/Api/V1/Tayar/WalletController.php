<?php

namespace App\Http\Controllers\Api\V1\Tayar;

use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tayar\ListWalletTransactionsRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    /**
     * My wallet: balance, what can be paid out, the locked signup bonus and the collected cash I owe.
     */
    public function show(Request $request): WalletResource
    {
        return WalletResource::make(Wallet::ensureFor($request->user()->id, WalletType::Tayar));
    }

    /**
     * My wallet's transactions, newest first, with cursor pages.
     */
    public function transactions(ListWalletTransactionsRequest $request): AnonymousResourceCollection
    {
        $wallet = Wallet::ensureFor($request->user()->id, WalletType::Tayar);

        return WalletTransactionResource::collection($wallet->transactions()
            ->with(['order:id,code', 'posting.reason'])
            ->when($request->transactionType(), fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage()));
    }
}
