<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domains\Wallet\Actions\AdjustWalletAction;
use App\Domains\Wallet\Actions\RecordSettlementAction;
use App\Domains\Wallet\Enums\WalletType;
use App\Domains\Wallet\Models\Wallet;
use App\Domains\Wallet\Models\WalletPosting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListWalletsRequest;
use App\Http\Requests\Api\V1\Admin\ListWalletTransactionsRequest;
use App\Http\Requests\Api\V1\Admin\StoreSettlementRequest;
use App\Http\Requests\Api\V1\Admin\StoreWalletAdjustmentRequest;
use App\Http\Resources\Api\V1\Admin\WalletPostingResource;
use App\Http\Resources\Api\V1\Admin\WalletResource;
use App\Http\Resources\Api\V1\Admin\WalletTransactionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class WalletController extends Controller
{
    public const array POSTING_RELATIONS = ['transactions', 'reason', 'creator', 'reversal'];

    /**
     * Every wallet, Tayari's system wallets included, newest first or by balance. The owing filter lists only tayar and
     * business wallets.
     */
    public function index(ListWalletsRequest $request): AnonymousResourceCollection
    {
        $search = $request->search();
        $phone = $request->searchPhone();

        $wallets = Wallet::query()
            ->with('user')
            ->when($request->walletType(), fn ($query, $type) => $query->where('type', $type))
            ->when($request->owing(), fn ($query) => $query->whereIn('type', [WalletType::Tayar, WalletType::Business])->where('balance', '<', 0))
            ->when($search !== null, fn ($query) => $query->whereIn('user_id', User::query()
                ->select('id')
                ->when($phone !== null, fn ($users) => $users->where('phone', $phone->value), fn ($users) => $users->whereLike('name', "%{$search}%"))))
            ->when($request->sort(), fn ($query, $sort) => $query->orderBy('balance', $sort === 'balance' ? 'asc' : 'desc'))
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return WalletResource::collection($wallets);
    }

    /**
     * One wallet with its payout summary.
     */
    public function show(Wallet $wallet): WalletResource
    {
        return WalletResource::detail($wallet->load('user'));
    }

    /**
     * The wallet's transactions, newest first, with cursor pages.
     */
    public function transactions(ListWalletTransactionsRequest $request, Wallet $wallet): AnonymousResourceCollection
    {
        return WalletTransactionResource::collection($wallet->transactions()
            ->with(['order:id,code', 'posting.reason', 'posting.creator', 'posting.reversal'])
            ->when($request->transactionType(), fn ($query, $type) => $query->where('type', $type))
            ->orderByDesc('id')
            ->cursorPaginate($request->perPage()));
    }

    /**
     * Record a payout to a tayar or a business, or cash a tayar handed in. Sending the same idempotency_key again
     * returns the settlement already recorded (200) instead of a second one (201).
     */
    public function settle(StoreSettlementRequest $request, Wallet $wallet, RecordSettlementAction $recordSettlement): JsonResponse
    {
        $posting = $recordSettlement->handle(
            $wallet,
            $request->kind(),
            $request->amount(),
            $request->validated('reference'),
            $request->note(),
            $request->validated('idempotency_key'),
            $request->user(),
        );

        return $this->postingResponse($posting);
    }

    /**
     * Adjust a wallet by hand with a reason: a positive amount gives money, a negative one takes it. Sending the same
     * idempotency_key again returns the adjustment already made (200).
     */
    public function adjust(StoreWalletAdjustmentRequest $request, Wallet $wallet, AdjustWalletAction $adjustWallet): JsonResponse
    {
        $posting = $adjustWallet->handle(
            $wallet,
            $request->amount(),
            $request->adjustmentReason(),
            $request->note(),
            $request->validated('idempotency_key'),
            $request->user(),
        );

        return $this->postingResponse($posting);
    }

    private function postingResponse(WalletPosting $posting): JsonResponse
    {
        return WalletPostingResource::make($posting->load(self::POSTING_RELATIONS))
            ->response()
            ->setStatusCode($posting->wasRecentlyCreated ? HttpStatus::HTTP_CREATED : HttpStatus::HTTP_OK);
    }
}
