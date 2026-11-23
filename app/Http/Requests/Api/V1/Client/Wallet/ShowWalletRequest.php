<?php

namespace App\Http\Requests\Api\V1\Client\Wallet;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class ShowWalletRequest extends FormRequest
{
    /**
     * Only business accounts have a client wallet; a tayar's wallet is under tayar mode.
     */
    public function authorize(): Response
    {
        return $this->user()->isBusiness() ? Response::allow() : Response::deny(__('wallet.business_only'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
